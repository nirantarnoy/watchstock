<?php

namespace backend\controllers;

use Exception;
use Yii;
use backend\models\JournalTrans;
use backend\models\journalTransSearch;
use common\models\JournalTransLine;
use yii\db\Expression;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

date_default_timezone_set('Asia/Bangkok');

/**
 * JournaltransController implements the CRUD actions for JournalTrans model.
 */
class JournaltransController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST', 'GET'],
                    'delete-line' => ['POST', 'GET'],
                    'cancel' => ['POST', 'GET']
                ],
            ],
        ];
    }

    /**
     * Lists all JournalTrans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new \backend\models\JournalTransSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andFilterWhere(['>','journal_trans_line.product_id',0]);
        $dataProvider->setSort(['defaultOrder' => ['id' => SORT_DESC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
        ]);
    }

    /**
     * Displays a single JournalTrans model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $lines = $model->journalTransLines;

        return $this->render('view', [
            'model' => $model,
            'lines' => $lines,
        ]);
    }

    /**
     * Creates a new JournalTrans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type = null)
    {
        $model = new JournalTrans();
        $modelLines = [new JournalTransLine()];

        if ($model->load(\Yii::$app->request->post())) {
            $modelLines = $this->createMultiple(JournalTransLine::class);
            JournalTransLine::loadMultiple($modelLines, \Yii::$app->request->post());

            if($modelLines != null && $type !=9){
                foreach ($modelLines as $i => $wid) {
                    if ($wid->warehouse_id == -1 || empty($wid->warehouse_id)) {
                        Yii::$app->session->setFlash('error', 'กรุณาเลือกที่จัดเก็บในแถวที่ ' . ($i + 1));
                        return $this->refresh();
                    }
                }
            }

            // Ajax validation
            if (\Yii::$app->request->isAjax) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            //custom model data
            if ($type == 10) {
                $model->stock_type_id = 1; // 1 เข้า 2 ออก
            } else {
                $model->stock_type_id = 2; // 1 เข้า 2 ออก
            }
            if ($model->trans_type_id == 7 || $model->trans_type_id == 5) { // ยืม และ ส่งช่าง
                $model->status = 1;
            } else {
                $model->status = 3;
            }

            $model->trans_date = \Yii::$app->formatter->asDatetime(time(), 'php:Y-m-d H:i:s');

            // Validate all models
            $valid = $model->validate();
            $valid = JournalTransLine::validateMultiple($modelLines) && $valid;


            if ($valid) {
                //$transaction = \Yii::$app->db->beginTransaction();
                // try {
                $model->journal_no = $model::generateJournalNoNew($type);
                if ($flag = $model->save(false)) {
                    // echo "ok";return;
                    foreach ($modelLines as $modelLine) {
                        if($type != 9){
                            $modelLine->line_price = \backend\models\Product::findCostAvgPrice($modelLine->product_id);
                        }
                        $modelLine->journal_trans_id = $model->id;
                        if($type == 5 || $type == 7){
                            $modelLine->status = 0; // ยังไม่คืน
                        }
                        if (!($flag = $modelLine->save(false))) {
                            break;
                        }
                    }
                }

                if ($flag) {
                    $total_qty = 0;
                    foreach ($modelLines as $modelLine) {
                        $total_qty += (int)$modelLine->qty;
                        $model_stock_trans = new \common\models\StockTrans();
                        $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                        $model_stock_trans->journal_trans_id = $model->id;
                        $model_stock_trans->trans_type_id = $model->trans_type_id;
                        $model_stock_trans->product_id = $modelLine->product_id;
                        $model_stock_trans->qty = (int)$modelLine->qty;
                        $model_stock_trans->warehouse_id = $modelLine->warehouse_id;
                        $model_stock_trans->stock_type_id = $model->stock_type_id;
                        $model_stock_trans->remark = $modelLine->remark;
                        $model_stock_trans->created_by = \Yii::$app->user->id;
                        if ($model_stock_trans->save(false)) {
                            $this->calStock($modelLine->product_id, $model->stock_type_id, $modelLine->warehouse_id, $modelLine->qty, $model->trans_type_id);
                            if($type == 10){
                                $this->updateProductPrice($modelLine->product_id,$modelLine->sale_price); // ปรับยอดแล้วปรับราคาขายด้วย
                            }

                            // Calculate and save balance to journal_trans_line
                            $balance = $this->getStockBalance($modelLine->product_id);
                            $modelLine->balance = $balance;
                            $modelLine->save(false);
                        }
                    }
                    \backend\models\JournalTrans::updateAll(['qty' => $total_qty], ['id' => $model->id]);

                    \Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                    return $this->redirect(['view', 'id' => $model->id]);
                }else{
                    Yii::$app->session->setFlash(
                        'error',
                        'เกิดข้อผิดพลาด: ' . implode('; ', array_map(function ($e) {
                            return implode(', ', $e);
                        }, $model->getErrors()))
                    );
                    return $this->refresh();
                }
            } else {
                 Yii::$app->session->setFlash(
                    'error',
                    'เกิดข้อผิดพลาด: ' . implode('; ', array_map(function ($e) {
                        return implode(', ', $e);
                    }, $model->getErrors()))
                );
                return $this->refresh();
            }
        }

        return $this->render('create', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines,
            'create_type' => $type,
        ]);
    }

    /**
     * Updates an existing JournalTrans model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $modelLines = $model->journalTransLines;

        if ($model->load(\Yii::$app->request->post())) {
            $oldIDs = ArrayHelper::map($modelLines, 'id', 'id');
            $modelLines = $this->createMultiple(JournalTransLine::class, $modelLines);
            JournalTransLine::loadMultiple($modelLines, \Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($modelLines, 'id', 'id')));

            // Ajax validation
            if (\Yii::$app->request->isAjax) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            // Validate all models
            $valid = $model->validate();
            $valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                if ($flag = $model->save(false)) {
                    if (!empty($deletedIDs)) {
                        foreach ($deletedIDs as $del_id) {
                            $this->updateCancelTrans($del_id);
                            JournalTransLine::findOne($del_id)->delete();
                        }
                    }
                    foreach ($modelLines as $modelLine) {
                        if($model->trans_type_id != 9){
                             $modelLine->line_price = \backend\models\Product::findCostPrice($modelLine->product_id);
                        }
                        $modelLine->journal_trans_id = $model->id;
                        if($model->trans_type_id == 5 || $model->trans_type_id == 7){
                            $modelLine->status = 0; // ยังไม่คืน
                        }
                        if (!($flag = $modelLine->save(false))) {
                            break;
                        }
                        
                        // Calculate and save balance
                        $balance = $this->getStockBalance($modelLine->product_id);
                        $modelLine->balance = $balance;
                        $modelLine->save(false);
                    }
                }
                if ($flag) {
                    $total_qty = 0;
                    foreach ($modelLines as $modelLine) {
                        $total_qty += (int)$modelLine->qty;
                    }
                    \backend\models\JournalTrans::updateAll(['qty' => $total_qty], ['id' => $model->id]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines,
        ]);
    }

    /**
     * Deletes an existing JournalTrans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $lines = $model->journalTransLines;
        if($lines){
            foreach($lines as $line){
                $this->updateCancelTrans($line->id);
            }
        }
        $model->delete();

        return $this->redirect(['index']);
    }
    
    public function updateCancelTrans($id){
        $model = JournalTransLine::findOne($id);
        if($model){
            $journal = JournalTrans::findOne($model->journal_trans_id);
            if($journal){
                $stock_type = 0;
                if($journal->stock_type_id == 1){
                    $stock_type = 2;
                }else if($journal->stock_type_id == 2){
                    $stock_type = 1;
                }
                $this->calStock($model->product_id, $stock_type, $model->warehouse_id, $model->qty, $journal->trans_type_id);
                
                // Remove StockTrans
                \common\models\StockTrans::deleteAll(['journal_trans_id' => $journal->id, 'product_id' => $model->product_id, 'warehouse_id' => $model->warehouse_id]);
            }
        }
    }

    /**
     * Finds the JournalTrans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return JournalTrans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = JournalTrans::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function calStock($product_id, $stock_type_id, $warehouse_id, $qty, $activity_type)
    {
        $qty = (float)$qty;
        if (!$product_id || !$stock_type_id || $qty <= 0) {
            return false;
        }

        // === Stock Out (2) ===
        if ($stock_type_id == 2) {
            $model = \common\models\StockSum::find()
                ->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])
                ->one();

            if ($model && $model->qty >= $qty) {
                $model->qty -= $qty;
                if (in_array($activity_type, [5, 7])) { // ยืม (5) หรือ ส่งช่าง (7)
                    $model->reserv_qty += $qty;
                }
                $model->updated_at = date('Y-m-d H:i:s');
                if ($model->save(false)) {
                    $this->updateProductStock($product_id);
                }
                return true;
            }
            return false;
        }

        // === Stock In (1) ===
        if ($stock_type_id == 1) {
            // 1. เพิ่ม qty ในคลังที่ระบุ
            $model = \common\models\StockSum::find()
                ->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])
                ->one();

            if (!$model) {
                $model = new \common\models\StockSum();
                $model->product_id = $product_id;
                $model->warehouse_id = $warehouse_id;
                $model->qty = 0;
                $model->reserv_qty = 0;
            }
            $model->qty += $qty;
            $model->updated_at = date('Y-m-d H:i:s');
            
            if ($model->save(false)) {
                // 2. จัดการ reserv_qty สำหรับกิจกรรมที่เกี่ยวข้องกับการคืนหรือยกเลิก (5, 6, 7, 8)
                if (in_array($activity_type, [5, 6, 7, 8])) {
                    $remaining_to_reduce = $qty;
                    
                    // ลดจากคลังปัจจุบันก่อน (ถ้ามี)
                    if ($model->reserv_qty > 0) {
                        $take = min($remaining_to_reduce, $model->reserv_qty);
                        $model->reserv_qty -= $take;
                        $remaining_to_reduce -= $take;
                        $model->save(false);
                    }
                    
                    // ถ้ายังเหลือยอดที่ต้องลด ให้หาจากคลังอื่นของสินค้านี้
                    if ($remaining_to_reduce > 0) {
                        $other_models = \common\models\StockSum::find()
                            ->where(['product_id' => $product_id])
                            ->andWhere(['>', 'reserv_qty', 0])
                            ->all();
                        foreach ($other_models as $om) {
                            if ($remaining_to_reduce <= 0) break;
                            $take = min($remaining_to_reduce, $om->reserv_qty);
                            $om->reserv_qty -= $take;
                            $remaining_to_reduce -= $take;
                            $om->updated_at = date('Y-m-d H:i:s');
                            $om->save(false);
                        }
                    }
                }
                
                $this->updateProductStock($product_id);
                return true;
            }
        }

        return false;
    }

    public function updateProductPrice($product_id,$new_price){
        if($product_id && $new_price >0){
            $cost_avg = 0;
             
            $sql = "SELECT AVG(jl.cost_price) as cost_avg FROM journal_trans_line jl INNER JOIN journal_trans jt ON jl.journal_trans_id = jt.id WHERE jl.product_id = $product_id AND jt.trans_type_id = 10";
            $cost_avg = Yii::$app->db->createCommand($sql)->queryScalar();

            $model = \backend\models\Product::find()->where(['id'=>$product_id])->one();
            if($model){
                $model->sale_price = $new_price;
                $model->cost_avg = $cost_avg;
                $model->save(false);
            }
        }
        return true;
    }


    public function calStockReturnFixProduct($product_id, $stock_type_id, $warehouse_id, $qty, $activity_type, $original_product_id, $original_warehouse_id)
    {
        if ($product_id && $stock_type_id && $qty) {
            if ($stock_type_id == 1) { // stock in
                if ($activity_type == 8) { // คืนช่าง
                    if ($original_product_id == $product_id) { // same product
                        $model_trans = new \backend\models\Stocktrans();
                        $model_trans->product_id = $product_id;
                        $model_trans->trans_date = date('Y-m-d H:i:s');
                        $model_trans->trans_type_id = 1; // 1 ปรับสต๊อก 2 รับเข้า 3 จ่ายออก
                        $model_trans->qty = $qty;
                        $model_trans->warehouse_id = $warehouse_id;
                        $model_trans->status = 1;
                        if ($model_trans->save(false)) {
                            $model = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->one();
                            if ($model) {
                                $model->qty += $qty;
                                if ($model->reserv_qty >= ($qty)) { // remove reserve qty
                                    $model->reserv_qty = ($model->reserv_qty - $qty);
                                }
                                if ($model->save(false)) {
                                    $this->updateProductStock($product_id);
                                }
                            } else {
                                $model = new \common\models\StockSum();
                                $model->product_id = $product_id;
                                $model->warehouse_id = $warehouse_id;
                                $model->qty = $qty;
                                $model->reserv_qty = 0;
                                $model->updated_at = date('Y-m-d H:i:s');
                                if ($model->save(false)) {
                                    $model_update_reserve = \common\models\StockSum::find()->where(['product_id' => $product_id])->all();
                                    if ($model_update_reserve) {
                                        foreach ($model_update_reserve as $model_reserve) {
                                            if ($model_reserve->reserv_qty >= ($qty)) { // remove reserve qty
                                                $model_reserve->reserv_qty = ($model_reserve->reserv_qty - $qty);
                                                $model_reserve->save(false);
                                                break;
                                            }
                                        }
                                    }
                                    $this->updateProductStock($product_id);
                                }
                            }
                        }

                    } else {
                        $model_trans = new \backend\models\Stocktrans();
                        $model_trans->product_id = $product_id;
                        $model_trans->trans_date = date('Y-m-d H:i:s');
                        $model_trans->trans_type_id = 1; // 1 ปรับสต๊อก 2 รับเข้า 3 จ่ายออก
                        $model_trans->qty = $qty;
                        $model_trans->warehouse_id = $warehouse_id;
                        $model_trans->status = 1;
                        if ($model_trans->save(false)) {
                            $model = \common\models\StockSum::find()->where(['product_id' => $original_product_id, 'warehouse_id' => $original_warehouse_id])->one(); // หักยอดจองสินค้าต้นฉบับ
                            if ($model) {
                                // $model->qty = ($model->qty - $qty); //
                                $model->reserv_qty = ($model->reserv_qty - $qty); //reduce reserve qty original product

                                if ($model->save(false)) {
                                    $this->updateProductStock($original_product_id); // update stock qty

                                    $modelx = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->one(); // ตรวจสอบสต๊อกสินค้าใหม่
                                    if ($modelx) { // ถ้ามีเพิ่มยอด
                                        $modelx->qty += $qty;
                                        if ($modelx->save(false)) {
                                            $this->updateProductStock($product_id);
                                        }
                                    } else { //ไม่มีก็เพิ่มสต๊อกใน warehouse ใหม่
                                        $modelx = new \common\models\StockSum();
                                        $modelx->product_id = $product_id;
                                        $modelx->warehouse_id = $warehouse_id;
                                        $modelx->qty = $qty;
                                        $modelx->reserv_qty = 0;
                                        $modelx->updated_at = date('Y-m-d H:i:s');
                                        if ($modelx->save(false)) {
                                            $this->updateProductStock($product_id);
                                        }
                                    }
                                }
                            }
                        }

                    }
                }
            }
        }
    }

    function updateProductStock($product_id)
    {
        if ($product_id) {
            //    $model_stock = \backend\models\Stocksum::find()->where(['product_id'=>$product_id])->andFilterWhere(['is not','warehouse_id',new Expression('null')])->all();
            $model_stock = \backend\models\Stocksum::find()->where(['product_id' => $product_id])->all();
            if ($model_stock) {
                $all_stock = 0;
                foreach ($model_stock as $model) {
                    if ($model->warehouse_id == null || $model->warehouse_id == '') continue;
                    $res_qty = $model->reserv_qty != null ? $model->reserv_qty : 0;
                    $all_stock += ($model->qty + $res_qty); // รวมจํานวน + จํานวนจอง
                }

                \backend\models\Product::updateAll(['stock_qty' => $all_stock], ['id' => $product_id]);
            }else{
                \backend\models\Product::updateAll(['stock_qty' => 0], ['id' => $product_id]);
            }
        }
    }

    /**
     * Function to create multiple models
     */
    protected function createMultiple($modelClass, $multipleModels = [])
    {
        $model = new $modelClass;
        $formName = $model->formName();
        $post = Yii::$app->request->post($formName);
        $models = [];

        if (!empty($multipleModels)) {
            $keys = array_keys(ArrayHelper::map($multipleModels, 'id', 'id'));
            $multipleModels = array_combine($keys, $multipleModels);
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (isset($item['id']) && !empty($item['id']) && isset($multipleModels[$item['id']])) {
                    $models[] = $multipleModels[$item['id']];
                } else {
                    $models[] = new $modelClass;
                }
            }
        }

        unset($model, $formName, $post);

        return $models;
    }

    public function actionAddreturnproduct()
    {
        $request = \Yii::$app->request;
        $product_id = $request->post('product_id', []);
        $journal_trans_id = $request->post('journal_trans_id');
        $qty = $request->post('return_qty', []);
        $remark = $request->post('return_remark', []);
        $return_to_type = $request->post('return_to_type', []);
        $trans_type_id = $request->post('trans_type_id');
        $return_to_warehouse = $request->post('return_to_warehouse', []);
        $original_warehouse = $request->post('warehouse_id', []);
        $return_to_product = $request->post('return_to_product', []);

        //print_r($return_to_warehouse);return;

        if ($journal_trans_id && $qty && $trans_type_id && $return_to_warehouse !=null) {
            if($product_id!=null && $qty !=null){
                $check_has_warehouse = 0;
                for($x=0;$x<=count($return_to_warehouse)-1;$x++){
                    if($return_to_warehouse[$x] >0){
                        $check_has_warehouse+=1;
                    }
                }
                if($check_has_warehouse >0){
                    $model = new \backend\models\JournalTrans();
                    $model->trans_date = date('Y-m-d H:i:s');
                    $model->journal_no = $model::generateJournalNoNew($trans_type_id);
                    $model->remark = '';
                    $model->trans_type_id = $trans_type_id; // 8 = คืนสินค้าช่าง, 6 = คืนยืม
                    $model->status = 3;
                    $model->stock_type_id = 1; // 1 เข้า, 2 ออก
                    $model->trans_ref_id = $journal_trans_id;

                    if ($model->save(false)) {
                        foreach ($product_id as $i => $pid) {
                            $qtyVal = isset($qty[$i]) ? (float)$qty[$i] : 0;
                            $whVal = $return_to_warehouse[$i] ?? null;
                            $remarkVal = $remark[$i] ?? '';
                            $returnToTypeVal = $return_to_type[$i] ?? null;
                            $returnToProductVal = $return_to_product[$i] ?? null;
                            $originalWhVal = $original_warehouse[$i] ?? null;

                            // ข้ามถ้าไม่มี qty หรือ warehouse
                            if ($qtyVal <= 0 || empty($whVal)) {
                                continue;
                            }

                            // JournalTransLine
                            $model_line = new \common\models\JournalTransLine();
                            $model_line->journal_trans_id = $model->id;
                            $model_line->product_id = $pid;
                            $model_line->qty = $qtyVal;
                            $model_line->remark = $remarkVal;
                            $model_line->warehouse_id = $whVal;
                            $model_line->return_to_type = $returnToTypeVal;
                            $model_line->journal_trans_ref_id = $journal_trans_id;

                            if ($model_line->save(false)) {
                                // StockTrans
                                $model_stock_trans = new \common\models\StockTrans();
                                $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                                $model_stock_trans->trans_type_id = $trans_type_id;
                                $model_stock_trans->product_id = $pid;
                                $model_stock_trans->journal_trans_id = $model->id;
                                $model_stock_trans->qty = $qtyVal;
                                $model_stock_trans->remark = $remarkVal;
                                $model_stock_trans->stock_type_id = 1;
                                $model_stock_trans->warehouse_id = $whVal;
                                $model_stock_trans->created_by = \Yii::$app->user->id;

                                if ($model_stock_trans->save(false)) {
                                    if ($trans_type_id == 6) {
                                        // คืนจากยืม
                                        $this->calStock($pid, 1, $whVal, $qtyVal, $trans_type_id);
                                        $this->calForupdateTransLine($journal_trans_id, $pid);
                                    }

                                    if ($trans_type_id == 8) {
                                        // คืนส่งช่าง
                                        if (empty($returnToProductVal) && !empty($remarkVal)) {
                                            // คืนแล้วสร้าง product ใหม่
                                            $this->crateNewProductFromWatchMaker($pid, $remarkVal, $whVal, $qtyVal, $originalWhVal);
                                            $this->calForupdateTransLine($journal_trans_id, $pid);
                                        } elseif (!empty($returnToProductVal)) {
                                            // คืนเข้าของที่ระบุ
                                            $this->calStockReturnFixProduct($returnToProductVal, 1, $whVal, $qtyVal, $trans_type_id, $pid, $originalWhVal);
                                            $this->calForupdateTransLineFixProduct($journal_trans_id, $returnToProductVal, $pid);
                                        } else {
                                            // คืนตาม product เดิม
                                            $this->calStock($pid, 1, $whVal, $qtyVal, $trans_type_id);
                                            $this->calForupdateTransLine($journal_trans_id, $pid);
                                        }
                                    }
                                    
                                    // Update balance for this line
                                    $balance = $this->getStockBalance($pid);
                                    $model_line->balance = $balance;
                                    $model_line->save(false);
                                }
                            }
                        }
                        $this->calForcomplete($journal_trans_id, $model->id);
                    }
                }

            }
        }
        return $this->redirect(['journaltrans/view', 'id' => $journal_trans_id]);
    }

    function calForupdateTransLine($journal_trans_id, $product_id)
    {
        // qty ที่ถูกยืม
        $borrow_qty = \common\models\JournalTransLine::find()
            ->select('qty')
            ->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $product_id])
            ->scalar();

        if ($borrow_qty === null) {
            return; // ถ้าไม่มี line ยืม ไม่ต้องทำอะไร
        }

        // qty ที่คืนมาแล้ว
        $return_qty = \common\models\JournalTransLine::find()
            ->where(['journal_trans_ref_id' => $journal_trans_id, 'product_id' => $product_id])
            ->sum('qty');

        if ($return_qty === null) {
            $return_qty = 0;
        }

        // ถ้าคืนครบแล้ว → ปรับสถานะ complete
        if ($return_qty >= $borrow_qty) {
            $trans_line = \common\models\JournalTransLine::find()
                ->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $product_id])
                ->one();

            if ($trans_line) {
                $trans_line->status = 1; // complete
                $trans_line->save(false);
            }
        }
    }

    function calForupdateTransLineFixProduct($journal_trans_id, $product_id, $original_product_id)
    {
        // qty ที่ถูกยืม (จาก original product)
        $borrow_qty = \common\models\JournalTransLine::find()
            ->select('qty')
            ->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $original_product_id])
            ->scalar();

        if ($borrow_qty === null) {
            return;
        }

        // qty ที่คืน (นับตาม original product เช่นกัน)
        $return_qty = \common\models\JournalTransLine::find()
            ->where(['journal_trans_ref_id' => $journal_trans_id, 'product_id' => $original_product_id])
            ->sum('qty');

        if ($return_qty === null) {
            $return_qty = 0;
        }

        if ($return_qty >= $borrow_qty) {
            $trans_line = \common\models\JournalTransLine::find()
                ->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $original_product_id])
                ->one();

            if ($trans_line) {
                $trans_line->status = 1; // complete
                $trans_line->save(false);
            }
        }
    }



    function calForcomplete($journal_trans_origin_id, $journal_return_id)
    {
        if ($journal_trans_origin_id && $journal_return_id) {
            $return_sum = \common\models\JournalTransLine::find()->where(['journal_trans_ref_id' => $journal_trans_origin_id])->sum('qty'); // จำนวนคืนทั้งหมด
            $trans_sum = \common\models\JournalTransLine::find()->where(['journal_trans_id' => $journal_trans_origin_id])->sum('qty'); // จำนวนยืมทั้งหมด
            if ($return_sum == $trans_sum) {
                $model = \backend\models\JournalTrans::findOne($journal_trans_origin_id);
                $model->status = 3; // trans complete
                $model->save(false);
            }
        }
    }

    function actionGetwarehouseproduct()
    {
        $html = '';
        $id = \Yii::$app->request->post('id');
        if ($id) {
            $model_stock_sum = \common\models\StockSum::find()->where(['product_id' => $id])->all();
            if ($model_stock_sum) {
                $html .= '<option value="-1">--เลือกคลัง--</option>';
                foreach ($model_stock_sum as $model) {
                    if($model->qty <=0)continue;
                    $html .= '<option value="' . $model->warehouse_id . '">' . \backend\models\Warehouse::findName($model->warehouse_id) . '</option>';
                }
            }
        }
        echo $html;
    }

    function actionGetproductonhand()
    {
        $onhand = [];
        $product_id = \Yii::$app->request->post('product_id');
        $warehouse_id = \Yii::$app->request->post('warehouse_id');
        if ($product_id && $warehouse_id) {
            $model_stock_sum = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->one();
            if ($model_stock_sum) {
                $product_sale_price = \backend\models\Product::findSalePrice($product_id);
                array_push($onhand, ['stock_qty' => $model_stock_sum->qty, 'sale_price' => $product_sale_price]); // stock ตัดได้แค่ยอดที่มีคงเหลือ ไม่รวมยอดจอง
            } else {
                array_push($onhand, ['stock_qty' => 0, 'sale_price' => 0]);
            }
        }
        return json_encode($onhand);
    }

    public function crateNewProductFromWatchMaker($product_id, $new_description, $warehouse_id, $qty, $original_warehouse)
    {

        $model = \backend\models\Product::findOne($product_id);
        if ($model) {
            $model_new = new \backend\models\Product();
            $model_new->name = $model->name;
            $model_new->description = $new_description; // new description for new product
            $model_new->cost_price = $model->cost_price;
            $model_new->product_group_id = $model->product_group_id;
            $model_new->brand_id = $model->brand_id;
            $model_new->unit_id = $model->unit_id;
            $model_new->status = $model->status;
            $model_new->product_type_id = $model->product_type_id;
            $model_new->type_id = $model->type_id;
            if ($model_new->save()) {

                if ($warehouse_id != null) {

                    $model_trans = new \backend\models\Stocktrans();
                    $model_trans->product_id = $model_new->id;
                    $model_trans->trans_date = date('Y-m-d H:i:s');
                    $model_trans->trans_type_id = 1; // 1 ปรับสต๊อก 2 รับเข้า 3 จ่ายออก
                    $model_trans->qty = $qty;
                    $model_trans->warehouse_id = $warehouse_id;
                    $model_trans->status = 1;
                    if ($model_trans->save(false)) {
                        $model_sum = \backend\models\Stocksum::find()->where(['product_id' => $model_new->id, 'warehouse_id' => $warehouse_id])->one();
                        if ($model_sum) { // กลับเข้าคลังเดิม
                            $model_sum->qty = $qty;
                            $model_sum->reserv_qty = 0;
                            if ($model_sum->save(false)) {
                                $model_delete_reserve = \backend\models\Stocksum::find()->where(['product_id' => $product_id, 'warehouse_id' => $original_warehouse])->andWhere(['>=', 'reserv_qty', '0'])->one();
                                if ($model_delete_reserve) {
                                    $model_delete_reserve->reserv_qty = 0; // remove reserve and crate new warehouse stock
                                    $model_delete_reserve->save(false);
                                }
                            }
                        } else { // กลับเข้าคลังใหม่ และเคลียร์ยอดจองคลังเดิม
                            $model_sum = new \backend\models\Stocksum();
                            $model_sum->product_id = $model_new->id;
                            $model_sum->warehouse_id = $warehouse_id;
                            $model_sum->qty = $qty;
                            $model_sum->reserv_qty = 0;
                            if ($model_sum->save(false)) {
                                $model_delete_reserve = \backend\models\Stocksum::find()->where(['product_id' => $product_id, 'warehouse_id' => $original_warehouse])->andWhere(['>=', 'reserv_qty', '0'])->one();
                                if ($model_delete_reserve) {
                                    $model_delete_reserve->reserv_qty = 0; // remove reserve and crate new warehouse stock
                                    $model_delete_reserve->save(false);
                                }
                            }
                        }
                    }

                    $this->updateProductStock($model_new->id); // update new product stock

                    $this->updateProductStock($product_id); // update old product stock

                }
            }
        }
    }

    public function actionGetProductInfo()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = \Yii::$app->request;

        // ถ้าขอข้อมูลสินค้าทั้งหมดสำหรับ autocomplete
        if ($request->get('action') === 'get-all-products') {
            $products = \backend\models\Product::find()
                ->where(['status' => 1])
         //       ->andFilterWhere(['>', 'stock_qty', 0])
                ->all();

            $result = [];
            foreach ($products as $product) {
                $result[] = [
                    'id' => $product->id,
                    'name' => $product->description,
                    'code' => $product->name ?? '',
                    'price' => $product->sale_price ?? 0,
                    'display' => $product->name . ($product->description ? ' (' . $product->description . ')' : ''),
                    'unit_id' => $product->unit_id,
                    'unit_name' => \backend\models\Unit::findName($product->unit_id),
                ];
            }

            return $result;
        }

        // ถ้าขอข้อมูลสินค้าเฉพาะ ID (สำหรับการเลือกสินค้า)
        $id = $request->get('id');
        if ($id) {
            $product = \backend\models\Product::find()->where(['id' => $id])->andFilterWhere(['>', 'stock_qty', 0])->one();
            if ($product) {
                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'name' => $product->name,
                    'code' => $product->code ?? '',
                    'price' => $product->sale_price ?? 0,
                    'display' => $product->code . ($product->name ? ' (' . $product->name . ')' : '')
                ];
            }
        }

        return ['error' => 'Product not found'];
    }

    public function actionCancel($id)
    {
        $res = 0;
        // $id = \Yii::$app->request->post('id');
        //  echo $id;return;
        if ($id) {
            $model = $this->findModel($id);
            if ($model) {
                $model->status = JournalTrans::JOURNAL_TRANS_STATUS_CANCEL;
                if ($model->save(false)) {
                    $model_line = JournalTransLine::find()->where(['journal_trans_id' => $id])->all();
                    if ($model_line) {
                        foreach ($model_line as $value) {
                            $model_sum = \backend\models\Stocksum::find()->where(['product_id' => $value->product_id, 'warehouse_id' => $value->warehouse_id])->one();
                            if ($model_sum) {
                                if ($model->stock_type_id == 2) { // stock out
                                    if ($model->trans_type_id == 5 || $model->trans_type_id == 7) {
                                        $model_sum->qty = (float)$model_sum->qty + (float)$value->qty;
                                        $model_sum->reserv_qty = (float)$model_sum->reserv_qty - (float)$value->qty;
                                    } else {
                                        $model_sum->qty = (float)$model_sum->qty + (float)$value->qty;
                                    }

                                } else if ($model->stock_type_id == 1) { // stock in
                                    $model_sum->qty = (float)$model_sum->qty - (float)$value->qty;
                                }

                                if ($model_sum->save(false)) {
                                    $res += 1;
                                }
                            }
                            $this->updateProductStock($value->product_id);
                        }
                    }
                }
            }
        }
        if ($res > 0) {
            \Yii::$app->session->setFlash('msg-success', 'บันทึกรายการสำเร็จ');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionCancelbyline()
    {
        $res = 0;
        $id = \Yii::$app->request->post('cancel_id');
        $cancel_qty = \Yii::$app->request->post('cancel_qty');

        $journal_id = 0;

        if ($id) {
            $model_line = JournalTransLine::find()->where(['id' => $id])->one();

            if ($model_line) {

                // ❗ ป้องกัน cancel_qty มากกว่า qty จริง
                if ($cancel_qty > $model_line->qty) {
                    Yii::$app->session->setFlash('msg-error', 'จำนวนยกเลิกมากกว่ายอดขายจริง');
                    return $this->redirect(Yii::$app->request->referrer);
                }

                $model = \backend\models\JournalTrans::find()
                    ->where(['id' => $model_line->journal_trans_id])->one();

                $model_sum = \backend\models\Stocksum::find()
                    ->where(['product_id' => $model_line->product_id, 'warehouse_id' => $model_line->warehouse_id])
                    ->one();

                $journal_id = $model->id;
                $origin_trans_type_id = $model->trans_type_id;

                if ($model_sum) {

                    // --------------------------------
                    //  ปรับยอดสต๊อกตามจำนวนยกเลิกจริง
                    // --------------------------------
                    if ($model->stock_type_id == 2) { // stock out (ขาย)
                        if ($model->trans_type_id == 5 || $model->trans_type_id == 7) {

                            $model_sum->qty += (float)$cancel_qty;
                            $model_sum->reserv_qty -= (float)$cancel_qty;

                        } else {

                            $model_sum->qty += (float)$cancel_qty;

                        }

                    } else if ($model->stock_type_id == 1) { // stock in
                        $model_sum->qty -= (float)$cancel_qty;
                    }

                    if ($model_sum->save(false)) {
                        $res += 1;
                        $new_trans_type_id = 0;
                        if($origin_trans_type_id == 3){
                            $new_trans_type_id = \common\models\JournalTrans::TYPE_SALE_CANCELED;
                        }else if($origin_trans_type_id == 5){
                            $new_trans_type_id = \common\models\JournalTrans::TYPE_LOAN_CANCELED;
                        }
                        else if($origin_trans_type_id == 7){
                            $new_trans_type_id = \common\models\JournalTrans::TYPE_SEND_CANCELED;
                        }else if($origin_trans_type_id == 9){
                            $new_trans_type_id = \common\models\JournalTrans::TYPE_DROP_CANCELED;
                        }

                        $model_trans = new \backend\models\JournalTrans();
                        $model_trans->trans_type_id = $new_trans_type_id;
                        $model_trans->stock_type_id = 1;
                        $model_trans->created_by = Yii::$app->user->id;
                        $model_trans->trans_date = date('Y-m-d H:i:s');
                        $model_trans->status=3; //3 completed
                        if($model_trans->save(false)){
                            $model_trans_line = new \common\models\JournalTransLine();
                            $model_trans_line->journal_trans_id = $model_trans->id;
                            $model_trans_line->product_id = $model_line->product_id;
                            $model_trans_line->warehouse_id = $model_line->warehouse_id;
                            $model_trans_line->qty = (float)$cancel_qty;
                            $model_trans_line->line_price = $model_line->line_price;
                            $model_trans_line->remark = 'ยกเลิกจากการขาย';
                            if($model_trans_line->save(false)){
                                // Update balance for this line
                                $balance = $this->getStockBalance($model_line->product_id);
                                $model_trans_line->balance = $balance;
                                $model_trans_line->save(false);

                                // --------------------------------
                                //  บันทึก StockTrans เฉพาะ cancel_qty
                                // --------------------------------
                                $model_stock_trans = new \common\models\StockTrans();
                                $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                                $model_stock_trans->journal_trans_id = $model_trans->id;
                                $model_stock_trans->trans_type_id = $model->trans_type_id;
                                $model_stock_trans->product_id = $model_line->product_id;
                                $model_stock_trans->qty = (float)$cancel_qty;       // ← ใช้ยอดยกเลิกจริง
                                $model_stock_trans->warehouse_id = $model_line->warehouse_id;
                                $model_stock_trans->stock_type_id = ($model->stock_type_id == 1 ? 2 : 1);
                                $model_stock_trans->remark = $model_line->remark;
                                $model_stock_trans->created_by = Yii::$app->user->id;
                                $model_stock_trans->save(false);
                            }

                        }


                    }
                }else {
                    // case drop ship ไม่ต้องปรับ logic
                    if ($model->trans_type_id == 9) {

                        $model_stock_trans = new \common\models\StockTrans();
                        $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                        $model_stock_trans->journal_trans_id = $model->id;
                        $model_stock_trans->trans_type_id = $model->trans_type_id;
                        $model_stock_trans->product_id = $model_line->product_id;
                        $model_stock_trans->qty = (float)$cancel_qty;      // ← ใช้ยอดยกเลิกจริง
                        $model_stock_trans->warehouse_id = 0;
                        $model_stock_trans->stock_type_id = ($model->stock_type_id == 1 ? 2 : 1);
                        $model_stock_trans->remark = $model_line->remark;
                        $model_stock_trans->created_by = Yii::$app->user->id;

                        if ($model_stock_trans->save(false)) {
                            $res += 1;
                        }
                    }else{
                        $model_trans = new \backend\models\JournalTrans();
                        $model_trans->trans_type_id = \common\models\JournalTrans::TYPE_SALE_CANCELED;
                        $model_trans->stock_type_id = 1;
                        $model_trans->created_by = Yii::$app->user->id;
                        $model_trans->trans_date = date('Y-m-d H:i:s');
                        $model_trans->status=3; //3 completed
                        if($model_trans->save(false)) {
                            $model_trans_line = new \common\models\JournalTransLine();
                            $model_trans_line->journal_trans_id = $model_trans->id;
                            $model_trans_line->product_id = $model_line->product_id;
                            $model_trans_line->warehouse_id = $model_line->warehouse_id;
                            $model_trans_line->qty = (float)$cancel_qty;
                            $model_trans_line->line_price = $model_line->line_price;
                            $model_trans_line->remark = 'ยกเลิกจากการขาย';
                            if ($model_trans_line->save(false)) {
                                // Update balance for this line
                                $balance = $this->getStockBalance($model_line->product_id);
                                $model_trans_line->balance = $balance;
                                $model_trans_line->save(false);

                                $model_stock_trans = new \common\models\StockTrans();
                                $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                                $model_stock_trans->journal_trans_id = $model_trans->id;
                                $model_stock_trans->trans_type_id = $model->trans_type_id;
                                $model_stock_trans->product_id = $model_line->product_id;
                                $model_stock_trans->qty = (float)$cancel_qty;       // ← ใช้ยอดยกเลิกจริง
                                $model_stock_trans->warehouse_id = $model_line->warehouse_id;
                                $model_stock_trans->stock_type_id = ($model->stock_type_id == 1 ? 2 : 1);
                                $model_stock_trans->remark = $model_line->remark;
                                $model_stock_trans->created_by = Yii::$app->user->id;
                                $model_stock_trans->save(false);
                            }
                        }
                    }
                }

                // อัปเดต product stock
                $this->updateProductStock($model_line->product_id);

                // --------------------------------
                //   UPDATE OR REMOVE LINE
                // --------------------------------
                $new_qty = $model_line->qty - $cancel_qty;

                if ($new_qty > 0) {
                    // ลดจำนวนลงตามที่ยกเลิก
                    $model_line->qty = $new_qty;
                    $model_line->save(false);
                } else {
                    // ถ้าหมดแล้วลบทั้งบรรทัด
                   // $model_line->delete();
                    $model_line->status  = 300; // ยกเลิก ไม่ใช้งาน
                    $model_line->save(false);
                }

//                // ❗ โครงสร้างเดิมของคุณคือ *ลบบรรทัดเสมอ* ผมไม่แก้ให้ตามที่ขอ
//                if ($res > 0) {
//                    \common\models\JournalTransLine::deleteAll(['id' => $model_line->id]);
//                }
            }
        }

        if ($res > 0) {
            Yii::$app->session->setFlash('msg-success', 'บันทึกรายการสำเร็จ');
        }

        return $this->redirect(['view', 'id' => $journal_id]);
    }

    public function actionCalallstock()
    {
        $model_product = \backend\models\Product::find()->where(['status' => 1])->all();
        if ($model_product) {
            foreach ($model_product as $value) {
                $model_stock = \backend\models\Stocksum::find()->where(['product_id' => $value->id])->all();
                if ($model_stock) {
                    $all_stock = 0;
                    foreach ($model_stock as $model) {
                        if ($model->warehouse_id == null || $model->warehouse_id == '') continue;
                        $res_qty = $model->reserv_qty != null ? $model->reserv_qty : 0;
                        $all_stock += ($model->qty + $res_qty); // รวมจํานวน + จํานวนจอง
                    }

                    \backend\models\Product::updateAll(['stock_qty' => $all_stock], ['id' => $value->id]);
                }
            }
        }
    }

    private function getStockBalance($product_id)
    {
        $sum = \common\models\StockSum::find()
            ->where(['product_id' => $product_id])
            ->andWhere(['not', ['warehouse_id' => null]])
            ->andWhere(['<>', 'warehouse_id', ''])
            ->sum('qty');
        $res = \common\models\StockSum::find()
            ->where(['product_id' => $product_id])
            ->andWhere(['not', ['warehouse_id' => null]])
            ->andWhere(['<>', 'warehouse_id', ''])
            ->sum('reserv_qty');
        return (float)$sum + (float)$res;
    }

    public function actionInitAvgCost(){
        $cnt = 0;
        $model = \backend\models\Product::find()->where(['status' => 1])->all();
        if($model){
            foreach($model as $value){
                $value->cost_avg = $value->cost_price;
                $value->save(false);
                $cnt++;
            }
        }
       echo "Update {$cnt} records";
    }

}