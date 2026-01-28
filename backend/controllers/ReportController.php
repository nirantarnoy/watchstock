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
            'access'=>[
                'class'=>AccessControl::className(),
                'denyCallback' => function ($rule, $action) {
                    throw new ForbiddenHttpException('คุณไม่ได้รับอนุญาติให้เข้าใช้งาน!');
                },
                'rules'=>[
                    [
                        'allow' => true,
                        'actions' => ['index','export'],
                        'roles' => ['Super user','System Administrator'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));
        $brandId = Yii::$app->request->get('brand_id');
        $groupId = Yii::$app->request->get('group_id');

        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate . ' 23:59:59');

        // 1. ยอดขายแยกตามสินค้า
        $salesByProduct = $this->getSalesByProduct($fromTimestamp, $toTimestamp, $brandId, $groupId);

        // 2. ข้อมูลสำหรับกราฟเปรียบเทียบยอดขายกำไรตามยี่ห้อ
        $priceComparisonData = $this->getPriceComparisonData($fromTimestamp, $toTimestamp, $brandId, $groupId);

        // 3. สินค้าขายดี 10 อันดับ
        $topProducts = $this->getTopProducts($fromTimestamp, $toTimestamp, $brandId, $groupId);

        // 4. ยอดขายแยกตามกลุ่มสินค้า
        $salesByGroup = $this->getSalesByGroup($fromTimestamp, $toTimestamp, $brandId, $groupId);

        // 5. ข้อมูลแนวโน้มยอดขาย (Daily Sales Trend)
        $salesTrend = $this->getSalesTrendData($fromTimestamp, $toTimestamp, $brandId, $groupId);

        return $this->render('index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'brandId' => $brandId,
            'groupId' => $groupId,
            'salesByProduct' => $salesByProduct,
            'priceComparisonData' => $priceComparisonData,
            'topProducts' => $topProducts,
            'salesByGroup' => $salesByGroup,
            'salesTrend' => $salesTrend,
        ]);
    }

    private function getSalesByProduct($fromTimestamp, $toTimestamp, $brandId = null, $groupId = null)
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
                        WHEN COALESCE(AVG(jtl.line_price), 0) = 0 
                        THEN p.cost_price 
                        ELSE AVG(jtl.line_price) 
                    END AS cost_price
                "),
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * COALESCE(NULLIF(jtl.line_price, 0), p.cost_price)) 
                    AS profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->andFilterWhere(['p.brand_id' => $brandId])
            ->andFilterWhere(['p.product_group_id' => $groupId])
            ->groupBy(['p.id', 'p.code', 'p.name'])
            ->having('SUM(jtl.qty) > 0')
            ->orderBy(['total_sales' => SORT_DESC]);

        return $query->all();
    }

    private function getPriceComparisonData($fromTimestamp, $toTimestamp, $brandId = null, $groupId = null)
    {
        $query = (new Query())
            ->select([
                'pb.name',
                'SUM(jtl.qty) as total_qty',
                'SUM(jtl.sale_price * jtl.qty) AS total_sale',
                new \yii\db\Expression("
                SUM(jtl.sale_price * jtl.qty)
                -
                SUM(jtl.qty * COALESCE(NULLIF(jtl.line_price, 0), p.cost_price))
                AS profit
            ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['pb' => 'product_brand'], 'pb.id = p.brand_id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->andFilterWhere(['p.brand_id' => $brandId])
            ->andFilterWhere(['p.product_group_id' => $groupId])
            ->groupBy(['pb.name'])
            ->having('SUM(jtl.qty) > 0')
            ->orderBy(['total_sale' => SORT_DESC])
            ->limit(20);

        $data = $query->all();

        $categories = [];
        $salePrices = [];
        $profits = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $salePrices[] = floatval($item['total_sale']);
            $profits[] = floatval($item['profit']);
        }

        return [
            'categories' => $categories,
            'salePrices' => $salePrices,
            'profits' => $profits
        ];
    }

    private function getTopProducts($fromTimestamp, $toTimestamp, $brandId = null, $groupId = null)
    {
        $query = (new Query())
            ->select([
                'p.name',
                'p.code',
                'SUM(jtl.qty) as total_qty',
                'SUM(jtl.qty * jtl.sale_price) as total_sales',
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * COALESCE(NULLIF(jtl.line_price, 0), p.cost_price)) 
                    AS profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->andFilterWhere(['p.brand_id' => $brandId])
            ->andFilterWhere(['p.product_group_id' => $groupId])
            ->groupBy(['p.id', 'p.name', 'p.code'])
            ->orderBy(['total_sales' => SORT_DESC])
            ->limit(10);

        $data = $query->all();

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

    private function getSalesByGroup($fromTimestamp, $toTimestamp, $brandId = null, $groupId = null)
    {
        $query = (new Query())
            ->select([
                'pg.name',
                'SUM(jtl.qty * jtl.sale_price) as total_sales',
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * COALESCE(NULLIF(jtl.line_price, 0), p.cost_price)) 
                    AS profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['pg' => 'product_group'], 'p.product_group_id = pg.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->andFilterWhere(['p.brand_id' => $brandId])
            ->andFilterWhere(['p.product_group_id' => $groupId])
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

    private function getSalesTrendData($fromTimestamp, $toTimestamp, $brandId = null, $groupId = null)
    {
        $query = (new Query())
            ->select([
                "DATE(FROM_UNIXTIME(jt.created_at)) as sale_date",
                "SUM(jtl.qty * jtl.sale_price) as daily_sales",
                new \yii\db\Expression("
                    SUM(jtl.qty * jtl.sale_price) - 
                    SUM(jtl.qty * COALESCE(NULLIF(jtl.line_price, 0), p.cost_price)) 
                    AS daily_profit
                ")
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 3, 'jt.trans_type_id' => [3, 9]])
            ->andFilterWhere(['!=', 'jtl.status', 300])
            ->andFilterWhere(['p.brand_id' => $brandId])
            ->andFilterWhere(['p.product_group_id' => $groupId])
            ->groupBy(['sale_date'])
            ->orderBy(['sale_date' => SORT_ASC]);

        $data = $query->all();

        $categories = [];
        $sales = [];
        $profits = [];

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

    public function actionExport()
    {
        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));
        $brandId = Yii::$app->request->get('brand_id');
        $groupId = Yii::$app->request->get('group_id');

        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate . ' 23:59:59');

        $salesData = $this->getSalesByProduct($fromTimestamp, $toTimestamp, $brandId, $groupId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECEF']]
        ];

        $sheet->fromArray([
            ['รหัสสินค้า', 'ชื่อสินค้า', 'จำนวนขาย', 'ยอดขาย', 'ราคาเฉลี่ย', 'ต้นทุน', 'กำไร', '%กำไร']
        ]);
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        $rowNum = 2;
        $totalQty = 0;
        $totalSales = 0;
        $totalProfit = 0;

        foreach ($salesData as $row) {
            $profitPercent = $row['total_sales'] > 0 ?
                ($row['profit'] / $row['total_sales']) : 0;

            $sheet->setCellValue('A' . $rowNum, $row['code']);
            $sheet->setCellValue('B' . $rowNum, $row['name']);
            $sheet->setCellValue('C' . $rowNum, $row['total_qty']);
            $sheet->setCellValue('D' . $rowNum, $row['total_sales']);
            $sheet->setCellValue('E' . $rowNum, $row['avg_price']);
            $sheet->setCellValue('F' . $rowNum, $row['cost_price']);
            $sheet->setCellValue('G' . $rowNum, $row['profit']);
            $sheet->setCellValue('H' . $rowNum, $profitPercent);

            $totalQty += $row['total_qty'];
            $totalSales += $row['total_sales'];
            $totalProfit += $row['profit'];

            $rowNum++;
        }

        // Summary Row
        $sheet->setCellValue('A' . $rowNum, 'รวมทั้งหมด');
        $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
        $sheet->setCellValue('C' . $rowNum, $totalQty);
        $sheet->setCellValue('D' . $rowNum, $totalSales);
        $sheet->setCellValue('G' . $rowNum, $totalProfit);
        
        $totalProfitPercent = $totalSales > 0 ? ($totalProfit / $totalSales) : 0;
        $sheet->setCellValue('H' . $rowNum, $totalProfitPercent);

        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);

        // Formatting
        $sheet->getStyle('C2:C' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D2:G' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H2:H' . $rowNum)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'sales_report_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}