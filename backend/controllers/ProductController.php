<?php

namespace backend\controllers;

use backend\models\Product;
use backend\models\ProductSearch;
use backend\models\WarehouseSearch;
use common\models\JournalTrans;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST', 'GET'],
                    ],
                ],
//                'access' => [
//                    'class' => AccessControl::className(),
//                    'denyCallback' => function ($rule, $action) {
//                        throw new ForbiddenHttpException('คุณไม่ได้รับอนุญาติให้เข้าใช้งาน!');
//                    },
//                    'rules' => [
//                        [
//                            'allow' => true,
//                            'roles' => ['@'],
//                            'matchCallback' => function ($rule, $action) {
//                                $currentRoute = \Yii::$app->controller->getRoute();
//                                if (\Yii::$app->user->can($currentRoute)) {
//                                    return true;
//                                }
//                            }
//                        ]
//                    ]
//                ],
            ]
        );
    }

    /**
     * Lists all Product models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $viewstatus = 1;

        if (\Yii::$app->request->get('viewstatus') != null) {
            $viewstatus = \Yii::$app->request->get('viewstatus');
        }

        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
//        if($viewstatus ==1){
//            $dataProvider->query->andFilterWhere(['status'=>$viewstatus]);
//        }
//        if($viewstatus == 2){
//            $dataProvider->query->andFilterWhere(['status'=>0]);
//        }

        $dataProvider->setSort(['defaultOrder' => ['name' => SORT_ASC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
            'viewstatus' => $viewstatus,
        ]);
    }

    /**
     * Displays a single Product model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Product model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Product();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $line_warehouse = \Yii::$app->request->post('warehouse_id');
                $line_qty = \Yii::$app->request->post('line_qty');
                $line_exp_date = \Yii::$app->request->post('line_exp_date');


                 $model->code = $model->name;
                if ($model->save(false)) {
                    $uploaded = UploadedFile::getInstanceByName('product_photo');
                    $uploaded2 = UploadedFile::getInstanceByName('product_photo_2');

                    if (!empty($uploaded)) {
                        $upfiles = "photo_" . time() . "." . $uploaded->getExtension();
                        if ($uploaded->saveAs('uploads/product_photo/' . $upfiles)) {
                            \backend\models\Product::updateAll(['photo' => $upfiles], ['id' => $model->id]);
                        }

                    }
                    if (!empty($uploaded2)) {
                        $upfiles2 = "photo_" . time() . "." . $uploaded2->getExtension();
                        if ($uploaded2->saveAs('uploads/product_photo/' . $upfiles2)) {
                            \backend\models\Product::updateAll(['photo_2' => $upfiles2], ['id' => $model->id]);
                        }

                    }

                    if($line_warehouse != null){
                        for($i=0;$i<count($line_warehouse);$i++){
                            if($line_qty[$i] == 0){
                                continue;
                            }

                            $model_trans = new \backend\models\Stocktrans();
                            $model_trans->product_id = $model->id;
                            $model_trans->trans_date = date('Y-m-d H:i:s');
                            $model_trans->activity_type_id = 1; // 1 ปรับสต๊อก 2 รับเข้า 3 จ่ายออก
                            $model_trans->qty = $line_qty[$i];
                            $model_trans->status = 1;
                            if($model_trans->save(false)){
                                $model_sum = \backend\models\Stocksum::find()->where(['product_id'=>$model->id,'warehouse_id'=>$line_warehouse[$i]])->one();
                                if($model_sum){
                                    $model_sum->qty = $line_qty[$i];
                                    $model_sum->save(false);
                                }else{
                                    $model_sum = new \backend\models\Stocksum();
                                    $model_sum->product_id = $model->id;
                                    $model_sum->warehouse_id = $line_warehouse[$i];
                                    $model_sum->qty = $line_qty[$i];
                                    $model_sum->save(false);
                                }
                            }
                        }
                    }
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Product model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model_line = \common\models\StockSum::find()->where(['product_id'=>$id])->andFilterWhere(['>','stock_qty',0])->orderBy(['id'])->all();
        $work_photo = '';
        if ($this->request->isPost && $model->load($this->request->post())) {
            $uploaded = UploadedFile::getInstanceByName('product_photo');


            $line_rec_id = \Yii::$app->request->post('line_rec_id');
            $removelist = \Yii::$app->request->post('remove_list');
            $old_photo = \Yii::$app->request->post('old_photo');

            $line_warehouse = \Yii::$app->request->post('warehouse_id');
            $line_qty = \Yii::$app->request->post('line_qty');

            //  print_r($line_customer_rec_id);return;

            if ($model->save(false)) {
                if (!empty($uploaded)) {
                    $upfiles = "photo_" . time() . "." . $uploaded->getExtension();
                    if ($uploaded->saveAs('uploads/product_photo/' . $upfiles)) {
                        \backend\models\Product::updateAll(['photo' => $upfiles], ['id' => $model->id]);

                        if($old_photo != null){
                            if(file_exists('uploads/product_photo/'.$old_photo)){
                                unlink('uploads/product_photo/'.$old_photo);
                            }
                        }
                    }

                }

                if($line_warehouse != null){
                    $model_journal_trans = new \common\models\JournalTrans();
                    $model_journal_trans->trans_date = date('Y-m-d H:i:s');
                    $model_journal_trans->journal_no = '';
                    $model_journal_trans->remark = '';
                    $model_journal_trans->trans_type_id = JournalTrans::TYPE_ADJUST;
                    $model_journal_trans->status = 3; // 3 complete
                    $model_journal_trans->stock_type_id = 0;
                    $model_journal_trans->warehouse_id = 0;

                    if($model_journal_trans->save(false)){
                        for($i=0;$i<=count($line_warehouse)-1;$i++){
                            if($line_warehouse[$i] == null || $line_qty[$i] == null){
                                continue;
                            }

                            $model_trans = new \common\models\JournalTransLine();
                            $model_trans->product_id = $model->id;
                            $model_trans->journal_trans_id = $model_journal_trans->id;
                            $model_trans->warehouse_id = $line_warehouse[$i];
                            $model_trans->qty = $line_qty[$i];
                            $model_trans->status = 1;
                            if($model_trans->save(false)){
                                $model_sum = \backend\models\Stocksum::find()->where(['product_id'=>$model->id,'warehouse_id'=>$line_warehouse[$i]])->one();
                                if($model_sum){
                                    $model_sum->qty = $line_qty[$i];
                                    if($model_sum->save(false)){
                                        $model->stock_qty = $line_qty[$i];
                                        $model->save(false);
                                    }
                                }else{
                                    $model_sum = new \backend\models\Stocksum();
                                    $model_sum->product_id = $model->id;
                                    $model_sum->warehouse_id = $line_warehouse[$i];
                                    $model_sum->qty = $line_qty[$i];
                                    if($model_sum->save(false)){
                                        $model->stock_qty = $line_qty[$i];
                                        $model->save(false);
                                    }
                                }
                            }
                        }
                    }

                }


//                if($removelist!=null){
//                    $xdel = explode(',', $removelist);
//                    for($i=0;$i<count($xdel);$i++){
//                        \backend\models\Stocksum::deleteAll(['id'=>$xdel[$i]]);
//                    }
//                }

            }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'work_photo' => $work_photo,
            'model_line' => $model_line,
            'model_customer_line'=>null,
        ]);
    }

    /**
     * Deletes an existing Product model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    public function actionImportpage()
    {
        return $this->render('_import');
    }
//    public function actionImportproduct()
//    {
//        $uploaded = UploadedFile::getInstanceByName('file_import');
//        if (!empty($uploaded)) {
//            //echo "ok";return;
//            $upfiles = time() . "." . $uploaded->getExtension();
//            // if ($uploaded->saveAs(Yii::$app->request->baseUrl . '/uploads/files/' . $upfiles)) {
//            if ($uploaded->saveAs('../web/uploads/files/products/' . $upfiles)) {
//                //  echo "okk";return;
//                // $myfile = Yii::$app->request->baseUrl . '/uploads/files/' . $upfiles;
//                $myfile = '../web/uploads/files/products/' . $upfiles;
//                $file = fopen($myfile, "r+");
//                fwrite($file, "\xEF\xBB\xBF");
//
//                setlocale(LC_ALL, 'th_TH.TIS-620');
//                $i = -1;
//                $res = 0;
//                $data = [];
//                while (($rowData = fgetcsv($file, 10000, ",")) !== FALSE) {
//                    $i += 1;
//                    $catid = 0;
//                    $qty = 0;
//                    $price = 0;
//                    $cost = 0;
//                    if ($rowData[1] == '' || $i == 0) {
//                        continue;
//                    }
//
//                    $model_dup = \backend\models\Product::find()->where(['sku' => trim($rowData[1])])->one();
//                    if ($model_dup != null) {
//                        continue;
//                    }
//
//
//                    $modelx = new \backend\models\Product();
//                    // $modelx->code = $rowData[0];
//                    $modelx->code = $rowData[2];
//                    $modelx->sku = $rowData[2];
//                    $modelx->name = $rowData[1];
//                    $modelx->barcode = $rowData[3];
//                    $modelx->total_qty = $rowData[4];
//                    $modelx->sale_price = $rowData[5];
//                    $modelx->status = 1;
//                    if ($modelx->save(false)) {
//                        $res += 1;
//                    }
//                }
//                //    print_r($qty_text);return;
//
//                if ($res > 0) {
//                    $session = \Yii::$app->session;
//                    $session->setFlash('msg', 'นำเข้าข้อมูลเรียบร้อย');
//                    return $this->redirect(['index']);
//                } else {
//                    $session = \Yii::$app->session;
//                    $session->setFlash('msg-error', 'พบข้อมผิดพลาดนะ');
//                    return $this->redirect(['index']);
//                }
//                // }
//                fclose($file);
////            }
////        }
//            }
//            echo "ok";
//        }
//    }

    public function actionFinditem()
    {
        $html = '';
        $has_data = 0;
        //$model = \backend\models\Workqueue::find()->where(['is_invoice' => 0])->all();
        // $model = \backend\models\Stocksum::find()->where(['warehouse_id' => 7])->all();
        $model = \backend\models\Product::find()->where(['status'=>1])->all();
        if ($model) {
            $has_data = 1;
            foreach ($model as $value) {
                $onhand_qty = $this->getProductOnhand($value->id);
                $code = $value->name;
                $name = $value->name;
                $price = 0;
                $unit_id = $value->unit_id;
                $unit_name = \backend\models\Unit::findName($unit_id);
                $is_drummy  = '' ;// $value->is_special;
                $html .= '<tr>';
                $html .= '<td style="text-align: center">
                            <div class="btn btn-outline-success btn-sm" onclick="addselecteditem($(this))" data-var="' . $value->id . '">เลือก</div>
                            <input type="hidden" class="line-find-item-id" value="' . $value->id . '">
                            <input type="hidden" class="line-find-item-code" value="' . $code . '">
                            <input type="hidden" class="line-find-item-name" value="' . $name . '">
                            <input type="hidden" class="line-find-price" value="' . $price . '">
                            <input type="hidden" class="line-find-unit-id" value="' . $unit_id . '">
                            <input type="hidden" class="line-find-unit-name" value="' . $unit_name . '">
                            <input type="hidden" class="line-find-is-drummy" value="' . $is_drummy . '">
                           </td>';
                $html .= '<td style="text-align: left">' . $code . '</td>';
                $html .= '<td style="text-align: left">' . $name . '</td>';
                $html .= '<td style="text-align: left">' . $unit_name . '</td>';
                $html .= '<td style="text-align: left">' . $onhand_qty . '</td>';
                $html .= '</tr>';
            }
        }

        if ($has_data == 0) {
            $html .= '<tr>';
            $html .= '<td colspan="5" style="text-align: center;color: red;">ไม่พบข้อมูล</td>';
            $html .= '</tr>';
        }
        echo $html;
    }

    function getProductOnhand($product_id){
        return \common\models\StockSum::find()->where(['product_id' => $product_id])->sum('qty');
    }

    public function actionImportproduct()
    {
        $uploaded = UploadedFile::getInstanceByName('file_product');
        if (!empty($uploaded)) {
            //echo "ok";return;
            $upfiles = time() . "." . $uploaded->getExtension();
            // if ($uploaded->saveAs(Yii::$app->request->baseUrl . '/uploads/files/' . $upfiles)) {
            if ($uploaded->saveAs('../web/uploads/files/products/' . $upfiles)) {
                //  echo "okk";return;
                // $myfile = Yii::$app->request->baseUrl . '/uploads/files/' . $upfiles;
                $myfile = '../web/uploads/files/products/' . $upfiles;
                $file = fopen($myfile, "r+");
                fwrite($file, "\xEF\xBB\xBF");

                setlocale(LC_ALL, 'th_TH.TIS-620');
                $i = -1;
                $res = 0;
                $data = [];
                while (($rowData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $i += 1;
                    $catid = 0;
                    $qty = 0;
                    $price = 0;
                    $cost = 0;
                    if ($rowData[2] == '' || $i == 0) {
                        continue;
                    }

                    $model_dup = \backend\models\Product::find()->where(['name' => trim($rowData[0])])->one();
                    if ($model_dup != null) {
                        $new_stock_qty = 0;
                        if($rowData[5] != null || $rowData[5] != ''){
                            $new_stock_qty = $rowData[5];
                        }
                        $model_dup->description = $rowData[1];
                        $model_dup->remark = $rowData[6];
                        $model_dup->stock_qty = $new_stock_qty;
                        if($model_dup->save(false)){
                            $this->calStock($model_dup->id,1,$rowData[7],$rowData[5]);
                            $res+=1;
                        }
                        continue;
                    }else{
                    //    echo "must new";
                        $modelx = new \backend\models\Product();
                        // $modelx->code = $rowData[0];
                        $modelx->name = trim($rowData[0]);
                        $modelx->description = trim($rowData[1]);
                        $modelx->product_group_id = $rowData[2]; // watch or phone or etc
                        $modelx->brand_id = $rowData[4];
                        $modelx->product_type_id = 1; // normal or custom
                        $modelx->type_id = 1; // 1 = new 2 = second used
                        $modelx->unit_id = 1;
                        $modelx->status = 1;
                        $modelx->cost_price = 0;
                        $modelx->sale_price = 0;
                        $modelx->stock_qty = 0;//$rowData[5];
                        $modelx->remark = $rowData[6];
                        //
                        if ($modelx->save(false)) {
                            $this->calStock($modelx->id,1,$rowData[7],$rowData[5]);
                            $res += 1;
                        }
                    }


                }
                //    print_r($qty_text);return;

                if ($res > 0) {
                    $session = \Yii::$app->session;
                    $session->setFlash('msg', 'นำเข้าข้อมูลเรียบร้อย');
                    return $this->redirect(['index']);
                } else {
                    $session = \Yii::$app->session;
                    $session->setFlash('msg-error', 'พบข้อมผิดพลาดนะ');
                    return $this->redirect(['index']);
                }
                // }
                fclose($file);
//            }
//        }
            }
        }
    }

    public function calStock($product_id,$stock_type_id,$warehouse_name,$qty){

        $warehouse_id = 0;
        if($warehouse_name!='' || $warehouse_name!=null){
            $warehouse = \common\models\Warehouse::find()->where(['name'=>trim($warehouse_name)])->one();
            if($warehouse){
                $warehouse_id = $warehouse->id;
            }else{
                    $warehouse = new \common\models\Warehouse();
                    $warehouse->name = trim($warehouse_name);
                    $warehouse->description = trim($warehouse_name);
                    $warehouse->status = 1;
                    if($warehouse->save(false)){
                        $warehouse_id = $warehouse->id;
                    }
            }
        }

        if($product_id && $stock_type_id && $qty){
            if($stock_type_id == 1){ // stock in
                $model = \common\models\StockSum::find()->where(['product_id'=>$product_id,'warehouse_id'=>$warehouse_id])->one();
                if($model){
                    $model->qty = $qty; // initial stock
                    $model->save(false);
                }else{
                    $model = new \common\models\StockSum();
                    $model->product_id = $product_id;
                    $model->warehouse_id = $warehouse_id;
                    $model->qty = $qty;
                    $model->updated_at = date('Y-m-d H:i:s');
                    if($model->save(false)){
                        $model_product = \backend\models\Product::findOne($product_id);
                        if($model_product){
                            $model_product->stock_qty = $qty; // update stock product
                            $model_product->save(false);
                        }
                    }
                }
            }
        }
    }

    /**
     * AJAX action for Select2 widget
     * Returns product list in JSON format with stock information
     */
    public function actionProductList($q = null, $page = 1, $warehouse_id = null)
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $limit = 20; // Number of items per page
        $offset = ($page - 1) * $limit;

        $query = Product::find()
            ->alias('p')
            ->select([
                'p.id',
                'p.code',
                'p.name',
                'p.sale_price',
                'p.unit_id',
                'p.photo',
                // ถ้ามี stock table แยกตาม warehouse
                // 'COALESCE(s.qty, 0) as stock_qty'
            ])
            ->where(['p.status' => 1]); // Only active products

        // Join กับ stock table ถ้ามี
        /*
        if ($warehouse_id) {
            $query->leftJoin(['s' => 'product_stock'],
                'p.id = s.product_id AND s.warehouse_id = :warehouse_id',
                [':warehouse_id' => $warehouse_id]
            );
        }
        */

        if (!empty($q)) {
            $query->andWhere(['or',
                ['like', 'p.code', $q],
                ['like', 'p.name', $q],
                ['like', 'p.description', $q],
            ]);
        }

        $countQuery = clone $query;
        $count = $countQuery->count();

        $products = $query
            ->orderBy(['p.name' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->asArray()
            ->all();

        $results = [];
        foreach ($products as $product) {
            // Get stock quantity for specific warehouse
            $stockQty = $this->getProductStock($product['id'], $warehouse_id);

            // Get unit name
            $unit = \backend\models\Unit::findOne($product['unit_id']);
            $unitName = $unit ? $unit->name : 'ชิ้น';

            $results[] = [
                'id' => $product['id'],
                'text' => '[' . $product['code'] . '] ' . $product['name'],
                'code' => $product['code'],
                'name' => $product['name'],
                'price' => $product['sale_price'],
                'stock_qty' => $stockQty,
                'unit' => $unitName,
                'photo' => $product['photo'],
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($offset + $limit) < $count,
            ],
        ];
    }

    /**
     * Get product details with stock information
     */
    public function actionGetProductDetail($id, $warehouse_id = null)
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $product = Product::find()
            ->where(['id' => $id])
            ->one();

        if ($product) {
            $stockQty = $this->getProductStock($id, $warehouse_id);
            $unit = \backend\models\Unit::findOne($product->unit_id);

            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit_price' => $product->cost_price,
                'cost_price' => $product->cost_price,
                'sale_price' => $product->sale_price,
                'stock_qty' => $stockQty,
                'unit' => $unit ? $unit->name : 'ชิ้น',
                'description' => $product->description,
                'photo' => $product->photo,
            ];
        }

        return null;
    }

    /**
     * Get product stock for specific warehouse
     */
    private function getProductStock($product_id, $warehouse_id = null)
    {
        // ถ้ามี table product_stock แยกตาม warehouse
        /*
        if ($warehouse_id) {
            $stock = ProductStock::find()
                ->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])
                ->one();
            return $stock ? $stock->qty : 0;
        }
        */

        // ถ้าเก็บ stock ใน product table
        $product = Product::findOne($product_id);
        return $product ? $product->stock_qty : 0;

        // หรือคำนวณจาก journal_trans
        /*
        $inQty = (new Query())
            ->from('journal_trans_line jtl')
            ->innerJoin('journal_trans jt', 'jtl.journal_trans_id = jt.id')
            ->where(['jtl.product_id' => $product_id])
            ->andWhere(['jt.trans_type_id' => 1]) // สมมติ 1 = รับเข้า
            ->andWhere(['jt.status' => 1]);

        if ($warehouse_id) {
            $inQty->andWhere(['jt.warehouse_id' => $warehouse_id]);
        }

        $totalIn = $inQty->sum('jtl.qty') ?: 0;

        // คำนวณจำนวนที่ออกไป
        $outQty = (new Query())
            ->from('journal_trans_line jtl')
            ->innerJoin('journal_trans jt', 'jtl.journal_trans_id = jt.id')
            ->where(['jtl.product_id' => $product_id])
            ->andWhere(['jt.trans_type_id' => 2]) // สมมติ 2 = จ่ายออก
            ->andWhere(['jt.status' => 1]);

        if ($warehouse_id) {
            $outQty->andWhere(['jt.warehouse_id' => $warehouse_id]);
        }

        $totalOut = $outQty->sum('jtl.qty') ?: 0;

        return $totalIn - $totalOut;
        */
    }

    /**
     * Check product availability before save
     */
    public function actionCheckStock()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $product_id = \Yii::$app->request->post('product_id');
        $warehouse_id = \Yii::$app->request->post('warehouse_id');
        $qty = \Yii::$app->request->post('qty', 0);

        $stockQty = $this->getProductStock($product_id, $warehouse_id);

        return [
            'available' => $stockQty >= $qty,
            'stock_qty' => $stockQty,
            'requested_qty' => $qty,
            'message' => $stockQty >= $qty ?
                'สินค้ามีเพียงพอ' :
                'สินค้าไม่เพียงพอ (คงเหลือ: ' . $stockQty . ')'
        ];
    }

    /**
     * Batch check stock for multiple products
     */
    public function actionBatchCheckStock()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $items = \Yii::$app->request->post('items', []);
        $warehouse_id = \Yii::$app->request->post('warehouse_id');

        $results = [];
        $hasError = false;

        foreach ($items as $item) {
            $product_id = $item['product_id'] ?? null;
            $qty = $item['qty'] ?? 0;

            if ($product_id) {
                $stockQty = $this->getProductStock($product_id, $warehouse_id);
                $product = Product::findOne($product_id);

                $available = $stockQty >= $qty;
                if (!$available) {
                    $hasError = true;
                }

                $results[] = [
                    'product_id' => $product_id,
                    'product_name' => $product ? $product->name : '',
                    'available' => $available,
                    'stock_qty' => $stockQty,
                    'requested_qty' => $qty,
                ];
            }
        }

        return [
            'success' => !$hasError,
            'items' => $results,
            'message' => $hasError ? 'มีสินค้าบางรายการไม่เพียงพอ' : 'สินค้าทุกรายการมีเพียงพอ'
        ];
    }

    public function actionBulkDelete()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $ids = \Yii::$app->request->post('ids', []);
        if (!empty($ids)) {
            Product::deleteAll(['id' => $ids]);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'No IDs received'];
    }
}
