<?php

namespace backend\controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\db\Query;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\helpers\ArrayHelper;

class ReportController extends Controller
{
    public function behaviors()
    {
        return [
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'delete' => ['POST','GET'],
//                ],
//            ],
            'access'=>[
                'class'=>AccessControl::className(),
                'denyCallback' => function ($rule, $action) {
                    throw new ForbiddenHttpException('คุณไม่ได้รับอนุญาติให้เข้าใช้งาน!');
                },
//                'rules'=>[
//                    [
//                        'allow'=>true,
//                        'roles'=>['@'],
//                        'matchCallback'=>function($rule,$action){
//                            $currentRoute = \Yii::$app->controller->getRoute();
//                            if(\Yii::$app->user->can($currentRoute)){
//                                return true;
//                            }
//                        }
//                    ]
//                ],
                [
                    'allow' => true,
                    'actions' => ['index'],
                    'roles' => ['CanReadSaleReport'], // RBAC permission
                ],
            ],
        ];
    }
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

        return $this->render('index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'salesByProduct' => $salesByProduct,
            'priceComparisonData' => $priceComparisonData,
            'topProducts' => $topProducts,
        ]);
    }

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
                'AVG(p.cost_price) as cost_price',
                'SUM(jtl.qty * jtl.line_price) - SUM(jtl.qty * p.cost_price) as profit'
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => 3]) // สมมติว่า status 1 = ขายสำเร็จ
            ->groupBy(['p.id', 'p.code', 'p.name', 'p.cost_price'])
            ->orderBy(['total_sales' => SORT_DESC]);

        return $query->all();
    }

    /**
     * ดึงข้อมูลสำหรับกราฟเปรียบเทียบราคาขายกับต้นทุน
     */
    private function getPriceComparisonData($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'p.name',
                'p.cost_price',
                'AVG(jtl.sale_price) as avg_sale_price',
                'SUM(jtl.qty) as total_qty'
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => 3])
            ->groupBy(['p.id', 'p.name', 'p.cost_price'])
            ->having('SUM(jt.qty) > 0')
            ->orderBy(['total_qty' => SORT_DESC]); // จำกัดแค่ 20 สินค้าสำหรับกราฟ

        $data = $query->all();

        // จัดรูปแบบข้อมูลสำหรับ Highcharts
        $categories = [];
        $costPrices = [];
        $salePrices = [];
        $profits = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $costPrices[] = floatval($item['cost_price']);
            $salePrices[] = floatval($item['avg_sale_price']);
            $profits[] = floatval($item['avg_sale_price']) - floatval($item['cost_price']);
        }

        return [
            'categories' => $categories,
            'costPrices' => $costPrices,
            'salePrices' => $salePrices,
            'profits' => $profits
        ];
    }

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
                'SUM(jtl.qty * jtl.sale_price) as total_sales'
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => 3])
            ->groupBy(['p.id', 'p.name', 'p.code'])
            ->orderBy(['total_qty' => SORT_DESC]);

        $data = $query->all();

        // จัดรูปแบบข้อมูลสำหรับ Highcharts
        $categories = [];
        $quantities = [];
        $sales = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $quantities[] = intval($item['total_qty']);
            $sales[] = floatval($item['total_sales']);
        }

        return [
            'categories' => $categories,
            'quantities' => $quantities,
            'sales' => $sales,
            'rawData' => $data
        ];
    }

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
            ['รหัสสินค้า', 'ชื่อสินค้า', 'จำนวนขาย', 'ยอดขาย', 'ราคาเฉลี่ย', 'ต้นทุน', 'กำไร', '%กำไร']
        ]);

// เพิ่มข้อมูลลงตาราง
        $rowNum = 2;
        foreach ($salesData as $row) {
            $profitPercent = $row['total_sales'] > 0 ?
                ($row['profit'] / $row['total_sales']) * 100 : 0;

            $sheet->fromArray([
                $row['name'],
                $row['description'],
                $row['total_qty'],
                number_format($row['total_sales'], 2),
                number_format($row['avg_price'], 2),
                number_format($row['cost_price'], 2),
                number_format($row['profit'], 2),
                number_format($profitPercent, 2)
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
}