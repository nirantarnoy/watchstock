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
                    'cancel'=>['POST','GET']
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


            // Ajax validation
            if (\Yii::$app->request->isAjax) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }


            //custom model data
            if($type == 10){
                $model->stock_type_id = 1; // 1 เข้า 2 ออก
            }else {
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
            // $valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                //$transaction = \Yii::$app->db->beginTransaction();
                // try {
                $model->journal_no = $model::generateJournalNoNew($type);
                if ($flag = $model->save(false)) {
                    // echo "ok";return;
                    foreach ($modelLines as $modelLine) {
                        $modelLine->journal_trans_id = $model->id;
                        if (!($flag = $modelLine->save(false))) {
                            break;
                        }
                    }
                }

                if ($flag) {

                    $total_qty = 0;
                    foreach ($modelLines as $modelLine) {
                        $total_qty += $modelLine->qty;
                        $model_stock_trans = new \common\models\StockTrans();
                        $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                        $model_stock_trans->journal_trans_id = $model->id;
                        $model_stock_trans->trans_type_id = $model->trans_type_id;
                        $model_stock_trans->product_id = $modelLine->product_id;
                        $model_stock_trans->qty = $modelLine->qty;
                        $model_stock_trans->warehouse_id = $modelLine->warehouse_id;
                        $model_stock_trans->stock_type_id = $model->stock_type_id;
                        $model_stock_trans->remark = $modelLine->remark;
                        $model_stock_trans->created_by = $model->created_by;
                        if ($model_stock_trans->save(false)) {
                            $this->calStock($modelLine->product_id, $model->stock_type_id, $modelLine->warehouse_id, $modelLine->qty, $model->trans_type_id);
                        }
                    }
                    \backend\models\JournalTrans::updateAll(['qty' => $total_qty], ['id' => $model->id]);

                    // $transaction->commit();

                    \Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                    return $this->redirect(['view', 'id' => $model->id]);
                }

//                    if ($flag) {
//                        print_r($model);return;
//
//                    }
//                } catch (Exception $e) {
//                   // $transaction->rollBack();
//                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
//                }
            } else {

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

        if ($model->load(Yii::$app->request->post())) {

            $oldIDs = ArrayHelper::map($modelLines, 'id', 'id');
            $modelLines = $this->createMultiple(JournalTransLine::class, $modelLines);
            JournalTransLine::loadMultiple($modelLines, \Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($modelLines, 'id', 'id')));

            // Ajax validation
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            // Validate all models
            $valid = $model->validate();
            //$valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            JournalTransLine::deleteAll(['id' => $deletedIDs]);
                        }
                        $total_qty = 0;
                        foreach ($modelLines as $modelLine) {
                            $total_qty += $modelLine->qty;
                            $modelLine->journal_trans_id = $model->id;
                            if (!($flag = $modelLine->save(false))) {
                                break;
                            }
                        }
                        \backend\models\JournalTrans::updateAll(['qty' => $total_qty], ['id' => $model->id]);
                    }

                    if ($flag) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines
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

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete all related lines first
            $model_line = \common\models\JournalTransLine::find()->where(['journal_trans_id' => $id])->all();
            if($model_line){
                foreach($model_line as $value){
                    $this->updateCancelTrans($value->product_id,$value->qty,$value->warehouse_id);
                }
            }
            JournalTransLine::deleteAll(['journal_trans_id' => $id]);

            // Delete master
            $model->delete();

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'ลบข้อมูลสำเร็จ');
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    private function updateCancelTrans($product_id,$qty,$warehouse_id){
        if($product_id && $qty){
            $model = new JournalTrans();
            $model->journal_no = $model::generateJournalNoNew(4);
            $model->trans_date = date('Y-m-d H:i:s');
            $model->stock_type_id = 1; // 1 in , 2 out
            //$model->activity_type = 11;
            $model->created_by = \Yii::$app->user->id;
            $model->created_at = time();
            if($model->save(false)){
                $model_line = new JournalTransLine();
                $model_line->journal_trans_id = $model->id;
                $model_line->product_id = $product_id;
                $model_line->qty = $qty;
                $model_line->warehouse_id = $warehouse_id;
                if($model_line->save(false)){
                    $model_stock = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->sum('qty');
                    if($model_stock){
                        $model_stock->qty += (int)$qty;
                        $model_stock->updated_at = time();
                        if($model_stock->save(false)){
                            $this->updateProductStock($product_id);
                        }
                    }
                }
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
        if ($product_id && $stock_type_id && $qty) {
            if ($stock_type_id == 2) { // stock out
                $model = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->andWhere(['>=', 'qty', $qty])->one();
                if ($model) {
                    $model->qty -= $qty;
                    if ($activity_type == 5 || $activity_type == 7) { // ยืม
                        $model->reserv_qty += $qty;
                    }
                    if ($model->save(false)) {
                        $this->updateProductStock($product_id);
                    }
                }
            }
            if ($stock_type_id == 1) { // stock in

                if ($activity_type == 6) { // คืนยืม
                    $model = \common\models\StockSum::find()->where(['product_id' => $product_id])->andWhere(['>=', 'reserv_qty', $qty])->one();
                    if ($model) {
                        if ($model->warehouse_id == $warehouse_id) { // same warehouse
                            $model->qty += $qty;
                            $model->reserv_qty -= $qty;
                            if ($model->save(false)) {
                                $this->updateProductStock($product_id);
                            }
                        } else { // diff warehouse
                            $model->reserv_qty -= $qty; // remove reserve qty
                            if ($model->save(false)) {
                                $modelx = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->one(); // find new warehouse
                                if ($modelx) {
                                    $modelx->qty += $qty;
                                    if ($modelx->save(false)) {
                                        $this->updateProductStock($product_id);
                                    }
                                } else {
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
                } else if($activity_type == 10){ // รับสินค้าอัพเดทคลัง
                    $model = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->one();
                    if ($model) {
                        $model->qty += $qty;
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
                            $this->updateProductStock($product_id);
                        }
                    }
                } else {
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


            }
        }
    }

    public function calStockReturnFixProduct($product_id, $stock_type_id, $warehouse_id, $qty, $activity_type, $original_product_id, $original_warehouse_id)
    {
        if ($product_id && $stock_type_id && $qty) {
            if ($stock_type_id == 1) { // stock in
                if ($activity_type == 8) { // คืนช่าง
                    if ($original_product_id == $product_id) { // same product
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
                    } else {
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
        $product_id = \Yii::$app->request->post('product_id');
        $journal_trans_id = \Yii::$app->request->post('journal_trans_id');
        $qty = \Yii::$app->request->post('return_qty');
        $remark = \Yii::$app->request->post('return_remark');
        $return_to_type = \Yii::$app->request->post('return_to_type');
        $trans_type_id = \Yii::$app->request->post('trans_type_id');
        $return_to_warehouse = \Yii::$app->request->post('return_to_warehouse');
        $original_warehouse = \Yii::$app->request->post('warehouse_id');
        $is_return_new = \Yii::$app->request->post('is_return_new');
        $return_to_product = \Yii::$app->request->post('return_to_product');
//        $journal_trans_line_id = \Yii::$app->request->post('journal_trans_line_id');

       // print_r($return_to_product);return;

        if ($journal_trans_id && $qty != null && $trans_type_id != null) {
            $model = new \backend\models\JournalTrans();
            $model->trans_date = date('Y-m-d H:i:s');
            $model->journal_no = $model::generateJournalNoNew($trans_type_id);
            $model->remark = '';
            $model->trans_type_id = $trans_type_id; // 8 = คืนสินค้าช่าง 6 = คืนยืม
            $model->status = 3;
            $model->stock_type_id = 1; // 1 เข้า 2 ออก
            $model->trans_ref_id = $journal_trans_id;
            if ($model->save(false)) {
                if ($product_id != null) {
                    for ($i = 0; $i < count($product_id); $i++) {
                        if ($qty[$i] == null || $qty[$i] == '' || $qty[$i] == '0' || $qty[$i] == 0) continue;
                        $model_line = new \common\models\JournalTransLine();
                        $model_line->journal_trans_id = $model->id;
                        $model_line->product_id = $product_id[$i];
                        $model_line->qty = $qty[$i];
                        $model_line->remark = $remark[$i];
                        $model_line->warehouse_id = $return_to_warehouse != null ? $return_to_warehouse[$i] : 1; // default
                        $model_line->return_to_type = $return_to_type != null ? $return_to_type[$i] : null;
                        $model_line->journal_trans_ref_id = $journal_trans_id;
                        if ($model_line->save(false)) {
                            $model_stock_trans = new \common\models\StockTrans();
                            $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                            $model_stock_trans->trans_type_id = $trans_type_id;
                            $model_stock_trans->product_id = $product_id[$i];
                            $model_stock_trans->journal_trans_id = $model->id;
                            $model_stock_trans->qty = $qty[$i];
                            $model_stock_trans->remark = $remark[$i];
                            $model_stock_trans->stock_type_id = 1;
                            $model_stock_trans->warehouse_id = $return_to_warehouse != null ? $return_to_warehouse[$i] : 1;
                            if ($model_stock_trans->save(false)) {
                                if ($trans_type_id == 6) { // คือยืม
                                    $this->calStock($product_id[$i], 1, $return_to_warehouse[$i], $qty[$i], $trans_type_id); // stock in from return borrow
                                    $this->calForupdateTransLine($journal_trans_id, $model_line, $product_id[$i]);
                                }

                                if ($trans_type_id == 8) { // คืนส่งช่าง

                                        if ($return_to_product[$i] == null && $remark[$i] != '') { // create new product from watch maker
                                            $this->crateNewProductFromWatchMaker($product_id[$i], $remark[$i], $return_to_warehouse[$i], $qty[$i], $original_warehouse[$i]);
                                            $this->calForupdateTransLine($journal_trans_id, $model_line, $product_id[$i]);
                                        } else { // return but not create new product
                                            if ($return_to_product[$i] != null) { // return to product by specific
                                                $this->calStockReturnFixProduct($return_to_product[$i], 1, $return_to_warehouse[$i], $qty[$i], $trans_type_id, $product_id, $original_warehouse);
                                                $this->calForupdateTransLineFixProduct($journal_trans_id, $model_line, $return_to_product[$i], $product_id[$i]); // update journal trans line complete
                                            } else {
                                                $this->calStock($product_id[$i], 1, $return_to_warehouse[$i], $qty[$i], $trans_type_id);
                                                $this->calForupdateTransLine($journal_trans_id, $model_line, $product_id[$i]);
                                            }
                                        }


//                                    if ($is_return_new != null) {
//                                        //   if(($remark[$i] !== null || $remark[$i] !== '') && $trans_type_id == 8){ // create new product from watch maker
//                                        if ($is_return_new[$i] == 1) { // create new product from watch maker
//                                            $this->crateNewProductFromWatchMaker($product_id[$i], $remark[$i], $return_to_warehouse[$i], $qty[$i], $original_warehouse[$i]);
//                                            $this->calForupdateTransLine($journal_trans_id, $model_line, $product_id[$i]);
//                                        } else if ($is_return_new[$i] == 0) { // return but not create new product
//
//                                            if ($return_to_product[$i] != '0') { // return to product by specific
//                                                $this->calStockReturnFixProduct($return_to_product[$i], 1, $return_to_warehouse[$i], $qty[$i], $trans_type_id, $product_id, $original_warehouse);
//                                                $this->calForupdateTransLineFixProduct($journal_trans_id, $model_line, $return_to_product[$i], $product_id[$i]); // update journal trans line complete
//                                            } else {
//                                                $this->calStock($product_id[$i], 1, $return_to_warehouse[$i], $qty[$i], $trans_type_id);
//                                                $this->calForupdateTransLine($journal_trans_id, $model_line, $product_id[$i]);
//                                            }
//                                        }
//                                    }

                                }
                            }

                        }


                    }
                }
                $this->calForcomplete($journal_trans_id, $model->id);
            }

        }
        return $this->redirect(['journaltrans/view', 'id' => $journal_trans_id]);
    }

    function calForupdateTransLine($journal_trans_id, $trans_line_new_id, $product_id)
    {
        $borrow_qty = \common\models\JournalTransLine::find()->select('qty')->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $product_id])->one();
        $return_qty = \common\models\JournalTransLine::find()->where(['journal_trans_ref_id' => $journal_trans_id, 'product_id' => $product_id])->sum('qty');
        if ($return_qty >= $borrow_qty->qty) {
            $trans_line = \common\models\JournalTransLine::find()->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $product_id])->one();
            if ($trans_line) {
                $trans_line->status = 1; // line trans complete
                $trans_line->save(false);
            }
        }
    }

    function calForupdateTransLineFixProduct($journal_trans_id, $trans_line_new_id, $product_id, $original_product_id)
    {
        $borrow_qty = \common\models\JournalTransLine::find()->select('qty')->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $original_product_id])->one();
        $return_qty = \common\models\JournalTransLine::find()->where(['journal_trans_ref_id' => $journal_trans_id, 'product_id' => $original_product_id])->sum('qty');
        if ($return_qty >= $borrow_qty->qty) {
            $trans_line = \common\models\JournalTransLine::find()->where(['journal_trans_id' => $journal_trans_id, 'product_id' => $original_product_id])->one();
            if ($trans_line) {
                $trans_line->status = 1; // line trans complete
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

//    function actionGetwarehouseproduct(){
//        $html = '';
//        $id = \Yii::$app->request->post('id');
//        if($id){
//            $model_stock_sum = \common\models\StockSum::find()->where(['warehouse_id'=>$id])->all();
//            if($model_stock_sum){
//                foreach($model_stock_sum as $model){
//                    $html .= '<option value="'.$model->product_id.'">'.\backend\models\Product::findName($model->product_id).'</option>';
//                }
//            }
//        }
//        echo $html;
//    }
    function actionGetwarehouseproduct()
    {
        $html = '';
        $id = \Yii::$app->request->post('id');
        if ($id) {
            $model_stock_sum = \common\models\StockSum::find()->where(['product_id' => $id])->all();
            if ($model_stock_sum) {
                $html .= '<option value="-1">--เลือกคลัง--</option>';
                foreach ($model_stock_sum as $model) {
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
            if ($model_new->save(false)) {

                if ($warehouse_id != null) {

                    $model_trans = new \backend\models\Stocktrans();
                    $model_trans->product_id = $model_new->id;
                    $model_trans->trans_date = date('Y-m-d H:i:s');
                    $model_trans->trans_type_id = 1; // 1 ปรับสต๊อก 2 รับเข้า 3 จ่ายออก
                    $model_trans->qty = $qty;
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

//                        $model_stock = \common\models\StockSum::find()->where(['product_id' => $product_id])->andWhere(['>=', 'reserv_qty', $qty])->one();
//                        if($model_stock) {
//                            if ($model_stock->warehouse_id == $warehouse_id) { // same warehouse
//                                $model_stock->qty += $qty;
//                                $model_stock->reserv_qty -= $qty;
//                                if ($model_stock->save(false)) {
//                                    $this->updateProductStock($product_id);
//                                }
//                            } else { // diff warehouse
//                                $model_stock->reserv_qty -= $qty; // remove reserve qty
//                                if ($model_stock->save(false)) {
//                                    $modelx = \common\models\StockSum::find()->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->one(); // find new warehouse
//                                    if ($modelx) {
//                                        $modelx->qty += $qty;
//                                        if ($modelx->save(false)) {
//                                            $this->updateProductStock($product_id);
//                                        }
//                                    } else {
//                                        $modelx = new \common\models\StockSum();
//                                        $modelx->product_id = $product_id;
//                                        $modelx->warehouse_id = $warehouse_id;
//                                        $modelx->qty = $qty;
//                                        $modelx->reserv_qty = 0;
//                                        $modelx->updated_at = date('Y-m-d H:i:s');
//                                        if ($modelx->save(false)) {
//                                            $this->updateProductStock($product_id);
//                                        }
//                                    }
//                                }
//                            }
//                        }

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
                ->andFilterWhere(['>', 'stock_qty',0])
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
            $product = \backend\models\Product::find()->where(['id' => $id])->andFilterWhere(['>', 'stock_qty',0])->one();
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

    public function actionCancel($id){
        $res = 0;
       // $id = \Yii::$app->request->post('id');
      //  echo $id;return;
        if($id){
            $model = $this->findModel($id);
            if($model){
                $model->status = JournalTrans::JOURNAL_TRANS_STATUS_CANCEL;
                if($model->save(false)){
                    $model_line = JournalTransLine::find()->where(['journal_trans_id'=>$id])->all();
                    if($model_line){
                        foreach($model_line as $value){
                            $model_sum = \backend\models\Stocksum::find()->where(['product_id'=>$value->product_id,'warehouse_id'=>$value->warehouse_id])->one();
                            if($model_sum){
                                if($model->stock_type_id == 2){ // stock out
                                    if($model->trans_type_id == 5 || $model->trans_type_id == 7){
                                        $model_sum->qty = (float)$model_sum->qty + (float)$value->qty;
                                        $model_sum->reserv_qty = (float)$model_sum->reserv_qty - (float)$value->qty;
                                    }else{
                                        $model_sum->qty = (float)$model_sum->qty + (float)$value->qty;
                                    }

                                }else if($model->stock_type_id == 1){ // stock in
                                    $model_sum->qty = (float)$model_sum->qty - (float)$value->qty;
                                }

                                if($model_sum->save(false)){
                                    $res+=1;
                                }
                            }
                        }
                    }
                }
            }
        }
        if($res > 0){
            \Yii::$app->session->setFlash('msg-success','บันทึกรายการสำเร็จ');
        }
        return $this->redirect(['view','id'=>$id]);
    }

}