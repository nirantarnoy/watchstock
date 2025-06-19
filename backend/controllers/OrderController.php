<?php

namespace backend\controllers;

use backend\models\Order;
use backend\models\OrderSearch;
use backend\models\PositionSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends Controller
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
                        'delete' => ['POST','GET'],
                    ],
                ],
                'access' => [
                    'class' => AccessControl::className(),
                    'denyCallback' => function ($rule, $action) {
                        throw new ForbiddenHttpException('คุณไม่ได้รับอนุญาติให้เข้าใช้งาน!');
                    },
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                            'matchCallback' => function ($rule, $action) {
                                $currentRoute = \Yii::$app->controller->getRoute();
                                if (\Yii::$app->user->can($currentRoute)) {
                                    return true;
                                }
                            }
                        ]
                    ]
                ],
            ]
        );
    }

    /**
     * Lists all Order models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $viewstatus = 1;

        if(\Yii::$app->request->get('viewstatus')!=null){
            $viewstatus = \Yii::$app->request->get('viewstatus');
        }

        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
//        if($viewstatus ==1){
//            $dataProvider->query->andFilterWhere(['status'=>$viewstatus]);
//        }
//        if($viewstatus == 2){
//            $dataProvider->query->andFilterWhere(['status'=>0]);
//        }

        $dataProvider->setSort(['defaultOrder' => ['id' => SORT_DESC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
            'viewstatus'=>$viewstatus,
        ]);
    }

    /**
     * Displays a single Order model.
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
     * Creates a new Order model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Order();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
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
     * Updates an existing Order model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model_line = \common\models\OrderLine::find()->where(['order_id' => $id])->all();


        if ($this->request->isPost && $model->load($this->request->post())) {
            $status = 0;
            if($model->order_tracking_no !=''){
                $status = 3;
            }
            $model->status = $status;
            if($model->save(false)){
                return $this->redirect(['order/index']);
            }

        }

        return $this->render('update', [
            'model' => $model,
            'model_line' => $model_line,
        ]);
    }

    /**
     * Deletes an existing Order model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        \common\models\OrderLine::deleteAll(['order_id' => $id]);
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Order model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Order the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Order::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionCreateissue(){
       $order_id = \Yii::$app->request->get('order_id');
       if($order_id){
           $model = new \backend\models\Journalissue();
           $model->journal_no = $model::getLastNo();
           $model->issue_for_id = $order_id;
           $model->trans_date = date('Y-m-d H:i:s');
           $model->status = 0;
           if($model->save(false)){
               $order_data = \common\models\OrderLine::find()->where(['order_id'=>$order_id])->all();
               if($order_data){
                   foreach ($order_data as $key => $value) {
                       $model_line = new \common\models\JouranlIssueLine();
                       $model_line->journal_issue_id = $model->id;
                       $model_line->product_id = $value->product_id;
                       $model_line->qty = $value->qty;
                       $model_line->price = $value->price;
                       $model_line->status = 0;
                       $model_line->save(false);
                   }
               }
           }
       }
       return $this->redirect(['order/update','id'=>$order_id]);
    }
    public function actionCreatedo(){
        $order_id = \Yii::$app->request->get('order_id');
        if($order_id){
            $model = new \backend\models\Deliveryorder();
            $model->order_no = $model::getLastNo();
            $model->issue_ref_id = $order_id;
            $model->trans_date = date('Y-m-d H:i:s');
            $model->status = 0;
            if($model->save(false)){
                $order_data = \common\models\OrderLine::find()->where(['order_id'=>$order_id])->all();
                if($order_data){
                    foreach ($order_data as $key => $value) {
                        $model_line = new \common\models\DeliveryOrderLine();
                        $model_line->delivery_order_id = $model->id;
                        $model_line->product_id = $value->product_id;
                        $model_line->name = \backend\models\Product::findName($value->product_id);
                        $model_line->qty = $value->qty;
                        $model_line->status = 0;
                        $model_line->save(false);
                    }
                }
            }
        }
        return $this->redirect(['order/update','id'=>$order_id]);
    }
    public function actionCreateinvoice(){
        $order_id = \Yii::$app->request->get('order_id');
        if($order_id){
            $model = new \backend\models\Customerinvoice();
            $model->invoice_no = $model::getLastNo();
            $model->order_ref_id = $order_id;
            $model->trans_date = date('Y-m-d H:i:s');
            $model->status = 0;
            if($model->save(false)){
                $order_data = \common\models\OrderLine::find()->where(['order_id'=>$order_id])->all();
                if($order_data){
                    foreach ($order_data as $key => $value) {
                        $model_line = new \common\models\CustomerInvoiceLine();
                        $model_line->customer_invoice_id = $model->id;
                        $model_line->product_id = $value->product_id;
                        $model_line->qty = $value->qty;
                        $model_line->price = $value->price;
                        $model_line->status = 0;
                        $model_line->save(false);
                    }
                }
            }
        }
        return $this->redirect(['order/update','id'=>$order_id]);
    }
    public function actionNotifymessage()
    {
        //$message = "This is test send request from camel paperless";
        $line_api = 'https://notify-api.line.me/api/notify';
        $line_token = '';

        $b_token = '8H8dtjz5QWvWWBFrMAwYrglYhkwu3Pw7rnXeBK9vYFK';
        $line_token = trim($b_token);

        $message = '' . "\n";
        $message .= 'แจ้งเตือนมีคำสั่งซื้อใหม่' . "\n";
        $message .= 'ลูกค้า:' . 'คุณทดสอบ2' . "\n";
        //   $message .= 'User:' . \backend\models\User::findName($user_id) . "\n";
        $message .= "วันที่:" . date('Y-m-d') . "(" . date('H:i:s') . ")" . "\n";

        $message .= 'เลขที่คำสั่งซื้อ: ' .'SO2405-00002'. "\n";
        $message .= "ยอดเงิน: " . number_format(8000, 2) . "\n";

      //  $message .= 'สามารถดูรายละเอียดได้ที่ http:///103.253.73.108/icesystemdindang/backend/web/index.php?r=dailysum/indexnew' . "\n"; // bkt


        $queryData = array('message' => $message);
        $queryData = http_build_query($queryData, '', '&');
        $headerOptions = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . "Authorization: Bearer " . $line_token . "\r\n"
                    . "Content-Length: " . strlen($queryData) . "\r\n",
                'content' => $queryData
            )
        );
        $context = stream_context_create($headerOptions);
        $result = file_get_contents($line_api, FALSE, $context);
        $res = json_decode($result);
        return $res;
    }

    public function actionConvertnumtostring()
    {
        $txt = '';
        $amount = \Yii::$app->request->post('amount');
        if ($amount >= 0) {
            $txt = self::numtothai($amount);
        }
        echo $txt;
    }

    public function numtothai($num)
    {
        $return = "";
        $num = str_replace(",", "", $num);
        $number = explode(".", $num);
        if (sizeof($number) > 2) {
            return 'รูปแบบข้อมุลไม่ถูกต้อง';
            exit;
        } else if (sizeof($number) == 1) {
            $number[1] = 0;
        }
        // return $number[0];
        $return .= self::numtothaistring($number[0]) . "บาท";

        $stang = intval($number[1]);
        // return $stang;
        if ($stang > 0) {
            if (strlen($stang) == 1) {
                $stang = $stang . '0';
            }
            if ($stang == '10') {
                $return .= 'สิบสตางค์';
            } else if ($stang == '11') {
                $return .= 'สิบเอ็ดสตางค์';
            } else if ($stang == '12') {
                $return .= 'สิบสองสตางค์';
            } else if ($stang == '13') {
                $return .= 'สิบสามสตางค์';
            } else if ($stang == '14') {
                $return .= 'สิบสี่สตางค์';
            } else if ($stang == '15') {
                $return .= 'สิบห้าสตางค์';
            } else if ($stang == '16') {
                $return .= 'สิบหกสตางค์';
            } else if ($stang == '17') {
                $return .= 'สิบเจ็ดสตางค์';
            } else if ($stang == '18') {
                $return .= 'สิบแปดสตางค์';
            } else if ($stang == '19') {
                $return .= 'สิบเก้าสตางค์';
            } else {
                $return .= self::numtothaistring($stang) . "สตางค์";
            }

        } else {
            $return .= "ถ้วน";
        }
        return $return;
    }

    public function numtothaistring($num)
    {
        $return_str = "";
        $txtnum1 = array('', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า');
        $txtnum2 = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
        $num_arr = str_split($num);
        $count = count($num_arr);
        foreach ($num_arr as $key => $val) {
            // echo $count." ".$val." ".$key."</br>";
            if ($count > 1 && $val == 1 && $key == ($count - 1)) {
                $return_str .= "เอ็ด";
            } else if ($count > 1 && $val == 1 && $key == 2) {
                $return_str .= $txtnum2[$val];
            } else if ($count > 1 && $val == 2 && $key == ($count - 2)) {
                $return_str .= "ยี่" . $txtnum2[$count - $key - 1];
            } else if ($count > 1 && $val == 0) {
            } else {
                $return_str .= $txtnum1[$val] . $txtnum2[$count - $key - 1];
            }
        }
        return $return_str;
    }
}
