<?php

namespace backend\controllers;

use common\models\LoginForm;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error','logindriver','calproduct-stock'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'changepassword','grab','logoutdriver','export'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */


    public function actionIndex()
    {

        // รับค่าวันที่จาก request หรือใช้ค่า default (30 วันย้อนหลัง)
        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));

        // แปลงวันที่เป็น timestamp
        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate . ' 23:59:59');

        // 1. ยอดขายแยกตามสินค้า
        $salesByProduct = $this->getSalesByProduct($fromTimestamp, $toTimestamp);

        // 2. ข้อมูลสำหรับกราฟเปรียบเทียบราคาขายกับต้นทุน
        $priceComparisonData = $this->getPriceComparisonData($fromTimestamp, $toTimestamp);

        // 3. สินค้าขายดี 10 อันดับ
        $topProducts = $this->getTopProducts($fromTimestamp, $toTimestamp);

        // 4. ยอดขายแยกตามกลุ่มสินค้า
        $salesByGroup = $this->getSalesByGroup($fromTimestamp, $toTimestamp);

        // 5. ข้อมูลแนวโน้มยอดขาย (Daily Sales Trend)
        $salesTrend = $this->getSalesTrendData($fromTimestamp, $toTimestamp);

        return $this->render('index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'salesByProduct' => $salesByProduct,
            'priceComparisonData' => $priceComparisonData,
            'topProducts' => $topProducts,
            'salesByGroup' => $salesByGroup,
            'salesTrend' => $salesTrend,
        ]);
    }

    /**
     * ดึงข้อมูลแนวโน้มยอดขายรายวัน
     */
    private function getSalesTrendData($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                "DATE(FROM_UNIXTIME(jt.created_at)) as sale_date",
                "SUM(jtl.qty * jtl.sale_price) as daily_sales",
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * 
                        CASE 
                            WHEN COALESCE(jtl.line_price, 0) > 0 THEN jtl.line_price
                            WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                            ELSE p.cost_price 
                        END
                    ) AS daily_profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->groupBy(['sale_date'])
            ->orderBy(['sale_date' => SORT_ASC]);

        $data = $query->all();

        $categories = [];
        $sales = [];
        $profits = [];

        // เติมวันที่ที่ไม่มีข้อมูล (ถ้าต้องการ)
        $current = $fromTimestamp;
        $dataMapSales = ArrayHelper::map($data, 'sale_date', 'daily_sales');
        $dataMapProfits = ArrayHelper::map($data, 'sale_date', 'daily_profit');

        while ($current <= $toTimestamp) {
            $dateStr = date('Y-m-d', $current);
            $categories[] = $dateStr;
            $sales[] = isset($dataMapSales[$dateStr]) ? floatval($dataMapSales[$dateStr]) : 0;
            $profits[] = isset($dataMapProfits[$dateStr]) ? floatval($dataMapProfits[$dateStr]) : 0;
            $current = strtotime('+1 day', $current);
        }

        return [
            'categories' => $categories,
            'sales' => $sales,
            'profits' => $profits
        ];
    }

    /**
     * ดึงข้อมูลยอดขายแยกตามกลุ่มสินค้า
     */
    private function getSalesByGroup($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'pg.name',
                'SUM(jtl.qty * jtl.sale_price) as total_sales',
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * 
                        CASE 
                            WHEN COALESCE(jtl.line_price, 0) > 0 THEN jtl.line_price
                            WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                            ELSE p.cost_price 
                        END
                    ) AS profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['pg' => 'product_group'], 'p.product_group_id = pg.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->groupBy(['pg.name'])
            ->orderBy(['total_sales' => SORT_DESC]);

        $data = $query->all();

        $categories = [];
        $sales = [];
        $profits = [];
        foreach ($data as $item) {
            $categories[] = $item['name'];
            $sales[] = floatval($item['total_sales']);
            $profits[] = floatval($item['profit']);
        }

        return [
            'categories' => $categories,
            'sales' => $sales,
            'profits' => $profits
        ];
    }


    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            //echo "login ok"; return;
            // return $this->goBack();
            $model_user_info = \backend\models\User::find()->where(['id' => \Yii::$app->user->id])->one();
            if($model_user_info){
                if($model_user_info->user_group_id == 3){
                    \Yii::$app->user->logout();
                }
            }
            return $this->redirect(['site/index']);
        }

        //   $model->password = '';
        $model->password = '';
        $this->layout = 'main_login';
        $model->password = '';
        return $this->render('login_new', [
            'model' => $model,
        ]);


    }




    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        \Yii::$app->user->logout();

//        if(isset($_SESSION['driver_login'])){
//            return $this->redirect(['site/logindriver']);
//        }

        return $this->goHome();
    }
    public function actionLogoutdriver()
    {
        \Yii::$app->user->logout();

        return $this->redirect(['site/logindriver']);
    }


    public function actionChangepassword()
    {
        $model = new \backend\models\Resetform();
        if ($model->load(Yii::$app->request->post())) {

            $model_user = \backend\models\User::find()->where(['id' => Yii::$app->user->id])->one();
            if ($model->oldpw != '' && $model->newpw != '' && $model->confirmpw != '') {
                if ($model->confirmpw != $model->newpw) {
                    $session = Yii::$app->session;
                    $session->setFlash('msg_err', 'รหัสยืนยันไม่ตรงกับรหัสใหม่');
                } else {
                    if ($model_user->validatePassword($model->oldpw)) {
                        $model_user->setPassword($model->confirmpw);
                        if ($model_user->save()) {
                            $session = Yii::$app->session;
                            $session->setFlash('msg_success', 'ทำการเปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
                            return $this->redirect(['site_/logout']);
                        }
                    } else {
                        $session = Yii::$app->session;
                        $session->setFlash('msg_err', 'รหัสผ่านเดิมไม่ถูกต้อง');
                    }
                }

            } else {
                $session = Yii::$app->session;
                $session->setFlash('msg_err', 'กรุณาป้อนข้อมูลให้ครบ');
            }

        }
        return $this->render('_setpassword', [
            'model' => $model
        ]);
    }

    public function actionGrab()
    {

        $aControllers = [];


        // $path = \Yii::$app->getBasePath() . 'icesystem/';
        $path = \Yii::$app->basePath;

        $ctrls = function ($path) use (&$ctrls, &$aControllers) {

            $oIterator = new \DirectoryIterator($path);

            foreach ($oIterator as $oFile) {

                if (!$oFile->isDot()

                    && (false !== strpos($oFile->getPathname(), 'controllers')

                        || false !== strpos($oFile->getPathname(), 'modules')

                    )

                ) {


                    if ($oFile->isDir()) {

                        $ctrls($oFile->getPathname());

                    } else {

                        if (strpos($oFile->getBasename(), 'Controller.php')) {


                            $content = file_get_contents($oFile->getPathname());

                            $controllerName = $oFile->getBasename('.php');


                            $route = explode(\Yii::$app->basePath, $oFile->getPathname());

                            $route = str_ireplace(array('modules', 'controllers', 'Controller.php'), '', $route[1]);

                            $route = preg_replace("/(\/){2,}/", "/", $route);


                            $aControllers[$controllerName] = [

                                'filepath' => $oFile->getPathname(),

                                'route' => mb_strtolower($route),

                                'actions' => [],

                            ];

                            preg_match_all('#function action(.*)\(#ui', $content, $aData);


                            $acts = function ($aData) use (&$aControllers, &$controllerName) {


                                if (!empty($aData) && isset($aData[1]) && !empty($aData[1])) {


                                    $aControllers[$controllerName]['actions'] = array_map(

                                        function ($actionName) {
                                            return mb_strtolower(trim($actionName, '{\\.*()'));
                                        },

                                        $aData[1]

                                    );


                                }

                            };


                            $acts($aData);

                        }

                    }


                }

            }

        };


        $ctrls($path);


        echo '<pre>';

        //   print_r($aControllers);

        foreach ($aControllers as $value) {

            //  $route_name = substr($value['route'],2);
            $route_name = substr($value['route'], 1);
            for ($x = 0; $x <= count($value['actions']) - 1; $x++) {
                $fullname = $route_name . '/' . $value['actions'][$x];
                if ($fullname != '') {
                    $chk = \common\models\AuthItem::find()->where(['name' => $fullname])->one();
                    if ($chk) continue;

                    $model = new \common\models\AuthItem();
                    $model->name = $fullname;
                    $model->type = 2;
                    $model->description = '';
                    $model->created_at = time();
                    $model->save(false);
                }
                echo $fullname . '<br/>';

            }
            //echo $route_name;
            // print_r($value['route']);
        }
        // print_r($aControllers['AdjustmentController']);

    }

    /**
     * ดึงข้อมูลยอดขายแยกตามสินค้า
     */
    private function getSalesByProduct($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'p.id',
                'p.code',
                'p.name',
                'p.description',
                'SUM(jtl.qty) as total_qty',
                'SUM(jtl.qty * jtl.sale_price) as total_sales',
                'AVG(jtl.sale_price) as avg_price',
                new \yii\db\Expression("
                    CASE 
                        WHEN COALESCE(AVG(jtl.line_price), 0) > 0 THEN AVG(jtl.line_price)
                        WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                        ELSE p.cost_price 
                    END AS cost_price
                "),
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * 
                        CASE 
                            WHEN COALESCE(jtl.line_price, 0) > 0 THEN jtl.line_price
                            WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                            ELSE p.cost_price 
                        END
                    ) AS profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->groupBy(['p.id', 'p.code', 'p.name'])
            ->having('SUM(jtl.qty) > 0')
            ->orderBy(['total_sales' => SORT_DESC]);

        return $query->all();
    }

    /**
     * ดึงข้อมูลสำหรับกราฟเปรียบเทียบราคาขายกับต้นทุน
     */
//    private function getPriceComparisonData($fromTimestamp, $toTimestamp)
//    {
//        $query = (new Query())
//            ->select([
//                'pb.name',
//                'SUM(p.cost_price) as cost_price',
//                'SUM(jtl.sale_price * jtl.qty) as avg_sale_price',
//                'SUM(jtl.qty) as total_qty'
//            ])
//            ->from(['jtl' => 'journal_trans_line'])
//            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
//            ->innerJoin(['pb' => 'product_brand'], 'pb.id = p.brand_id')
//            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
//            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
//            ->andWhere(['jt.status' => 3,'jt.trans_type_id' => [3,9]])
//            ->groupBy(['pb.name'])
//            ->having('SUM(jt.qty) > 0')
//            ->orderBy(['total_qty' => SORT_DESC])
//            ->limit(20); // จำกัดแค่ 20 สินค้าสำหรับกราฟ
//
//        $data = $query->all();
//
//        // จัดรูปแบบข้อมูลสำหรับ Highcharts
//        $categories = [];
//        $salePrices = [];
//        $profits = [];
//
//        foreach ($data as $item) {
//            $categories[] = $item['name'];
//            $salePrices[] = floatval($item['avg_sale_price']);
//            $profits[] = floatval($item['avg_sale_price']) - floatval($item['cost_price']);
//        }
//
//        return [
//            'categories' => $categories,
//            'costPrices' => null,
//            'salePrices' => $salePrices,
//            'profits' => $profits
//        ];
//    }

    private function getPriceComparisonData($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'pb.name',
                'SUM(jtl.qty) as total_qty',

                // รวมยอดขายทั้งหมด
                'SUM(jtl.sale_price * jtl.qty) AS total_sale',

                // ต้นทุนถ่วงน้ำหนัก (ใช้ line_price ถ้ามี ไม่งั้นใช้ p.cost_price)
                new \yii\db\Expression("
                (
                    SUM(jtl.qty * 
                        CASE 
                            WHEN COALESCE(jtl.line_price, 0) > 0 THEN jtl.line_price
                            WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                            ELSE p.cost_price 
                        END
                    )
                    / SUM(jtl.qty)
                ) AS cost_price
            "),

                // กำไรรวม
                new \yii\db\Expression("
                SUM(jtl.sale_price * jtl.qty)
                -
                SUM(jtl.qty * 
                    CASE 
                        WHEN COALESCE(jtl.line_price, 0) > 0 THEN jtl.line_price
                        WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                        ELSE p.cost_price 
                    END
                ) AS profit
            ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['pb' => 'product_brand'], 'pb.id = p.brand_id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->groupBy(['pb.name'])
            ->having('SUM(jtl.qty) > 0')
            ->orderBy(['total_sale' => SORT_DESC])
            ->limit(20);

        $data = $query->all();

        // ส่งข้อมูลให้ Highcharts
        $categories = [];
        $salePrices = [];
        $profits = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $salePrices[] = floatval($item['total_sale']); // ยอดขายรวม
            $profits[] = floatval($item['profit']);      // กำไร
        }

        return [
            'categories' => $categories,
            'salePrices' => $salePrices,
            'profits' => $profits
        ];
    }


//    private function getPriceComparisonData($fromTimestamp, $toTimestamp)
//    {
//        $query = (new Query())
//            ->select([
//                'p.name',
//                'p.cost_price',
//                'AVG(jtl.sale_price) as avg_sale_price',
//                'SUM(jtl.qty) as total_qty'
//            ])
//            ->from(['jtl' => 'journal_trans_line'])
//            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
//            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
//            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
//            ->andWhere(['jt.status' => 3,'jt.trans_type_id' => [3,9]])
//            ->groupBy(['p.id', 'p.name', 'p.cost_price'])
//            ->having('SUM(jt.qty) > 0')
//            ->orderBy(['total_qty' => SORT_DESC])
//            ->limit(20); // จำกัดแค่ 20 สินค้าสำหรับกราฟ
//
//        $data = $query->all();
//
//        // จัดรูปแบบข้อมูลสำหรับ Highcharts
//        $categories = [];
//        $costPrices = [];
//        $salePrices = [];
//        $profits = [];
//
//        foreach ($data as $item) {
//            $categories[] = $item['name'];
//            $costPrices[] = floatval($item['cost_price']);
//            $salePrices[] = floatval($item['avg_sale_price']);
//            $profits[] = floatval($item['avg_sale_price']) - floatval($item['cost_price']);
//        }
//
//        return [
//            'categories' => $categories,
//            'costPrices' => $costPrices,
//            'salePrices' => $salePrices,
//            'profits' => $profits
//        ];
//    }

    /**
     * ดึงข้อมูลสินค้าขายดี 10 อันดับ
     */
    private function getTopProducts($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'p.name',
                'p.code',
                'SUM(jtl.qty) as total_qty',
                'SUM(jtl.qty * jtl.sale_price) as total_sales',
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * 
                        CASE 
                            WHEN COALESCE(jtl.line_price, 0) > 0 THEN jtl.line_price
                            WHEN COALESCE(p.cost_avg, 0) > 0 THEN p.cost_avg
                            ELSE p.cost_price 
                        END
                    ) AS profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3,'jt.trans_type_id' => [3,9]])
            ->andFilterWhere(['!=','jtl.status',300])
            ->groupBy(['p.id', 'p.name', 'p.code'])
            ->orderBy(['total_sales' => SORT_DESC])
            ->limit(10);

        $data = $query->all();

        // จัดรูปแบบข้อมูลสำหรับ Highcharts
        $categories = [];
        $quantities = [];
        $sales = [];
        $profits = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $quantities[] = intval($item['total_qty']);
            $sales[] = floatval($item['total_sales']);
            $profits[] = floatval($item['profit']);
        }

        return [
            'categories' => $categories,
            'quantities' => $quantities,
            'sales' => $sales,
            'profits' => $profits,
            'rawData' => $data
        ];
    }

    /**
     * Export ข้อมูลเป็น Excel (optional)
     */
//    public function actionExport()
//    {
//        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
//        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));
//
//        $fromTimestamp = strtotime($fromDate);
//        $toTimestamp = strtotime($toDate . ' 23:59:59');
//
//        $salesData = $this->getSalesByProduct($fromTimestamp, $toTimestamp);
//
//        // สร้าง CSV
//        $filename = 'sales_report_' . date('Y-m-d') . '.csv';
//        header('Content-Type: text/csv');
//        header('Content-Disposition: attachment; filename="' . $filename . '"');
//
//        $output = fopen('php://output', 'w');
//
//        // Header
//        fputcsv($output, ['รหัสสินค้า', 'ชื่อสินค้า', 'จำนวนขาย', 'ยอดขาย', 'ราคาเฉลี่ย', 'ต้นทุน', 'กำไร']);
//
//        // Data
//        foreach ($salesData as $row) {
//            fputcsv($output, [
//                $row['code'],
//                $row['name'],
//                $row['total_qty'],
//                number_format($row['total_sales'], 2),
//                number_format($row['avg_price'], 2),
//                number_format($row['cost_price'], 2),
//                number_format($row['profit'], 2)
//            ]);
//        }
//
//        fclose($output);
//        exit;
//    }
    public function actionExport()
    {
        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));

        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate . ' 23:59:59');

        $salesData = $this->getSalesByProduct($fromTimestamp, $toTimestamp);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ตั้งชื่อหัวตาราง
        $sheet->fromArray([
            ['รหัสสินค้า', 'ชื่อสินค้า', 'จำนวนขาย', 'ยอดขาย', 'ราคาเฉลี่ย', 'ต้นทุน', 'กำไร']
        ]);

        // เพิ่มข้อมูลลงตาราง
        $rowNum = 2;
        foreach ($salesData as $row) {
            $sheet->fromArray([
                $row['code'],
                $row['name'],
                $row['total_qty'],
                number_format($row['total_sales'], 2),
                number_format($row['avg_price'], 2),
                number_format($row['cost_price'], 2),
                number_format($row['profit'], 2)
            ], null, 'A' . $rowNum);
            $rowNum++;
        }

        // สร้างไฟล์ Excel
        $filename = 'sales_report_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function actionCalproductStock(){
        $rows=0;
        $model = \backend\models\Product::find()->where(['status'=>1])->all();
        if($model){
            foreach($model as $value){
                $stock_qty = $this->getProductStock($value->id);
                $model_update = \backend\models\Product::find()->where(['id'=>$value->id])->one();
                if($model_update){
                    $model_update->stock_qty = (int)$stock_qty;
                    if($model_update->save(false)){
                        $rows+=1;
                    }
                }
              //  echo $stock_qty.'<br />';
            }
        }
        echo "Update ".$rows. " Rows";
    }
    public function getProductStock($product_id){
        $qty = 0;
        if($product_id){
            $model = \backend\models\Stocksum::find()->where(['product_id'=>$product_id])->andFilterWhere(['>','warehouse_id',0])->all();
            if($model){
                foreach ($model as $value){
                    $qty += (int)$value->qty + (int)$value->reserv_qty;
                }
            }

        }
        return $qty == null?0:$qty;
    }
}
