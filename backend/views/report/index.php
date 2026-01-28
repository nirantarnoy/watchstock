<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $fromDate string */
/* @var $toDate string */
/* @var $brandId int */
/* @var $groupId int */
/* @var $salesByProduct array */
/* @var $priceComparisonData array */
/* @var $topProducts array */
/* @var $salesByGroup array */
/* @var $salesTrend array */

$this->title = 'รายงานสรุปยอดขาย';
$this->params['breadcrumbs'][] = $this->title;

// Register Highcharts
$this->registerJsFile('https://code.highcharts.com/highcharts.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
$this->registerJsFile('https://code.highcharts.com/modules/exporting.js', ['depends' => [\yii\web\JqueryAsset::className()]]);

$totalSales = array_sum(array_column($salesByProduct, 'total_sales'));
$totalProfit = array_sum(array_column($salesByProduct, 'profit'));
$totalQty = array_sum(array_column($salesByProduct, 'total_qty'));
$profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
?>

<div class="report-index">
    <!-- Date Range Filter -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">เลือกช่วงเวลาและตัวกรอง</h5>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>วันที่เริ่มต้น:</label>
                                <?= Html::input('date', 'from_date', $fromDate, ['class' => 'form-control']) ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>วันที่สิ้นสุด:</label>
                                <?= Html::input('date', 'to_date', $toDate, ['class' => 'form-control']) ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>ยี่ห้อ:</label>
                                <?= Html::dropDownList('brand_id', $brandId, \yii\helpers\ArrayHelper::map(\backend\models\Productbrand::find()->all(), 'id', 'name'), [
                                    'class' => 'form-control',
                                    'prompt' => '-- ทั้งหมด --'
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>กลุ่มสินค้า:</label>
                                <?= Html::dropDownList('group_id', $groupId, \yii\helpers\ArrayHelper::map(\backend\models\Productgroup::find()->all(), 'id', 'name'), [
                                    'class' => 'form-control',
                                    'prompt' => '-- ทั้งหมด --'
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <?= Html::submitButton('ค้นหา', ['class' => 'btn btn-primary']) ?>
                                    <?= Html::a('Export Excel', ['export', 'from_date' => $fromDate, 'to_date' => $toDate, 'brand_id' => $brandId, 'group_id' => $groupId], ['class' => 'btn btn-success']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <br/>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ยอดขายรวม</span>
                    <span class="info-box-number">฿<?= number_format($totalSales, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">กำไรรวม</span>
                    <span class="info-box-number">฿<?= number_format($totalProfit, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-cubes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">จำนวนสินค้าที่ขาย</span>
                    <span class="info-box-number"><?= number_format($totalQty) ?> ชิ้น</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-percent"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">อัตรากำไร</span>
                    <span class="info-box-number"><?= number_format($profitMargin, 2) ?>%</span>
                </div>
            </div>
        </div>
    </div>
    <br/>

    <!-- Charts Row 1 -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">เปรียบเทียบยอดขายกำไรตามยี่ห้อ</h5>
                </div>
                <div class="panel-body">
                    <div id="price-comparison-chart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">แนวโน้มยอดขายและกำไรรายวัน</h5>
                </div>
                <div class="panel-body">
                    <div id="sales-trend-chart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
    <br/>

    <!-- Charts Row 2 -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">ยอดขายและกำไรแยกตามกลุ่มสินค้า</h5>
                </div>
                <div class="panel-body">
                    <div id="sales-by-group-chart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">เปรียบเทียบกำไรขาดทุน สินค้าขายดี</h5>
                </div>
                <div class="panel-body">
                    <div id="top-products-profit-chart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
    <br/>

    <!-- Sales by Product Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">รายงานยอดขายแยกตามสินค้า</h5>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>ชื่อสินค้า</th>
                                <th class="text-right">จำนวนขาย</th>
                                <th class="text-right">ยอดขาย</th>
                                <th class="text-right">ราคาขายเฉลี่ย</th>
                                <th class="text-right">ต้นทุน</th>
                                <th class="text-right">กำไร</th>
                                <th class="text-right">%กำไร</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($salesByProduct as $index => $product): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= Html::encode($product['name']) ?></td>
                                    <td class="text-right"><?= number_format($product['total_qty']) ?></td>
                                    <td class="text-right">฿<?= number_format($product['total_sales'], 2) ?></td>
                                    <td class="text-right">฿<?= number_format($product['avg_price'], 2) ?></td>
                                    <td class="text-right">฿<?= number_format($product['cost_price'], 2) ?></td>
                                    <td class="text-right">฿<?= number_format($product['profit'], 2) ?></td>
                                    <td class="text-right">
                                        <?php
                                        $profitPercent = $product['total_sales'] > 0 ?
                                            ($product['profit'] / $product['total_sales']) * 100 : 0;
                                        ?>
                                        <span class="label label-<?= $profitPercent > 20 ? 'success' : ($profitPercent > 10 ? 'warning' : 'danger') ?>">
                                            <?= number_format($profitPercent, 2) ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr class="active">
                                <th colspan="2">รวมทั้งหมด</th>
                                <th class="text-right"><?= number_format($totalQty) ?></th>
                                <th class="text-right">฿<?= number_format($totalSales, 2) ?></th>
                                <th colspan="2"></th>
                                <th class="text-right">฿<?= number_format($totalProfit, 2) ?></th>
                                <th class="text-right">
                                    <span class="label label-primary"><?= number_format($profitMargin, 2) ?>%</span>
                                </th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-box-icon {
        border-top-left-radius: 2px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0, 0, 0, 0.2);
    }

    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }

    .info-box-text {
        text-transform: uppercase;
        font-weight: normal;
        font-size: 14px;
    }

    .info-box-number {
        display: block;
        font-weight: normal;
        font-size: 20px;
    }

    .bg-aqua {
        background-color: #00c0ef !important;
        color: #fff;
    }

    .bg-green {
        background-color: #00a65a !important;
        color: #fff;
    }

    .bg-yellow {
        background-color: #f39c12 !important;
        color: #fff;
    }

    .bg-red {
        background-color: #dd4b39 !important;
        color: #fff;
    }
    
    .panel {
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .panel-heading {
        background-color: #f8f9fa !important;
        font-weight: bold;
    }
</style>

<?php
$categoriesJson = json_encode($priceComparisonData['categories']);
$salePricesJson = json_encode($priceComparisonData['salePrices']);
$profitsJson = json_encode($priceComparisonData['profits']);

$topCategoriesJson = json_encode($topProducts['categories']);
$topSalesJson = json_encode($topProducts['sales']);
$topProfitsJson = json_encode($topProducts['profits']);

$groupCategoriesJson = json_encode($salesByGroup['categories']);
$groupSalesJson = json_encode($salesByGroup['sales']);
$groupProfitsJson = json_encode($salesByGroup['profits']);

$trendCategoriesJson = json_encode($salesTrend['categories']);
$trendSalesJson = json_encode($salesTrend['sales']);
$trendProfitsJson = json_encode($salesTrend['profits']);

$js = <<<JS
Highcharts.setOptions({
    lang: {
        thousandsSep: ','
    }
});

Highcharts.chart('price-comparison-chart', {
    chart: { type: 'column' },
    title: { text: 'ยอดขายและกำไรตามยี่ห้อ' },
    xAxis: { categories: $categoriesJson },
    yAxis: { 
        title: { text: 'จำนวนเงิน (฿)' },
        labels: {
            formatter: function() {
                return '฿' + Highcharts.numberFormat(this.value, 0, '.', ',');
            }
        }
    },
    tooltip: {
        shared: true,
        pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>฿{point.y:,.2f}</b> ({point.percentage:.1f}%)<br/>'
    },
    plotOptions: { 
        column: { 
            stacking: 'normal',
            dataLabels: {
                enabled: true,
                format: '฿{point.y:,.0f}'
            }
        } 
    },
    series: [{
        name: 'กำไร',
        data: $profitsJson,
        color: '#28a745'
    }, {
        name: 'ต้นทุน',
        data: $salePricesJson.map((val, i) => val - $profitsJson[i]),
        color: '#007bff'
    }]
});

Highcharts.chart('sales-trend-chart', {
    chart: { type: 'areaspline' },
    title: { text: 'แนวโน้มยอดขายและกำไรรายวัน' },
    xAxis: { 
        categories: $trendCategoriesJson,
        labels: {
            formatter: function() {
                return this.value.split('-').slice(1).join('/'); // Show MM/DD
            }
        }
    },
    yAxis: { 
        title: { text: 'จำนวนเงิน (฿)' },
        labels: {
            formatter: function() {
                return '฿' + Highcharts.numberFormat(this.value, 0, '.', ',');
            }
        }
    },
    tooltip: {
        shared: true,
        valuePrefix: '฿',
        valueDecimals: 2
    },
    plotOptions: {
        areaspline: {
            fillOpacity: 0.1,
            marker: {
                enabled: false,
                states: {
                    hover: {
                        enabled: true
                    }
                }
            }
        }
    },
    series: [{
        name: 'ยอดขาย',
        data: $trendSalesJson,
        color: '#007bff'
    }, {
        name: 'กำไร',
        data: $trendProfitsJson,
        color: '#28a745'
    }]
});

Highcharts.chart('sales-by-group-chart', {
    chart: { type: 'column' },
    title: { text: 'ยอดขายและกำไรแยกตามกลุ่มสินค้า' },
    xAxis: { categories: $groupCategoriesJson },
    yAxis: { 
        title: { text: 'จำนวนเงิน (฿)' },
        labels: {
            formatter: function() {
                return '฿' + Highcharts.numberFormat(this.value, 0, '.', ',');
            }
        }
    },
    tooltip: {
        shared: true,
        pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>฿{point.y:,.2f}</b> ({point.percentage:.1f}%)<br/>'
    },
    plotOptions: { 
        column: { 
            stacking: 'normal',
            dataLabels: {
                enabled: true,
                format: '฿{point.y:,.0f}'
            }
        } 
    },
    series: [{
        name: 'กำไร',
        data: $groupProfitsJson,
        color: '#28a745'
    }, {
        name: 'ต้นทุน',
        data: $groupSalesJson.map((val, i) => val - $groupProfitsJson[i]),
        color: '#007bff'
    }]
});

Highcharts.chart('top-products-profit-chart', {
    chart: { type: 'column' },
    title: { text: 'เปรียบเทียบกำไรขาดทุน สินค้าขายดี' },
    xAxis: { categories: $topCategoriesJson },
    yAxis: { 
        title: { text: 'จำนวนเงิน (฿)' },
        labels: {
            formatter: function() {
                return '฿' + Highcharts.numberFormat(this.value, 0, '.', ',');
            }
        }
    },
    tooltip: {
        shared: true,
        pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>฿{point.y:,.2f}</b> ({point.percentage:.1f}%)<br/>'
    },
    plotOptions: { 
        column: { 
            stacking: 'normal',
            dataLabels: {
                enabled: true,
                format: '฿{point.y:,.0f}'
            }
        } 
    },
    series: [{
        name: 'กำไร',
        data: $topProfitsJson,
        color: '#28a745'
    }, {
        name: 'ต้นทุน',
        data: $topSalesJson.map((val, i) => val - $topProfitsJson[i]),
        color: '#007bff'
    }]
});
JS;
$this->registerJs($js);
?>