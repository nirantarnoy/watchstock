<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $fromDate string */
/* @var $toDate string */
/* @var $salesByProduct array */
/* @var $priceComparisonData array */
/* @var $topProducts array */

$this->title = 'ภาพรวมระบบ';
$this->params['breadcrumbs'][] = $this->title;

// Register Highcharts
$this->registerJsFile('https://code.highcharts.com/highcharts.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
$this->registerJsFile('https://code.highcharts.com/modules/exporting.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
?>

<div class="dashboard-index">
    <!-- Date Range Filter -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">เลือกช่วงเวลา</h5>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>วันที่เริ่มต้น:</label>
                                <?= Html::input('date', 'from_date', $fromDate, ['class' => 'form-control']) ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>วันที่สิ้นสุด:</label>
                                <?= Html::input('date', 'to_date', $toDate, ['class' => 'form-control']) ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <?= Html::submitButton('ค้นหา', ['class' => 'btn btn-primary']) ?>
                                    <?= Html::a('Export CSV', ['export', 'from_date' => $fromDate, 'to_date' => $toDate], ['class' => 'btn btn-success']) ?>
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
    <br/>

    <!-- Summary Cards -->
    <div class="row">
        <?php
        $totalSales = array_sum(array_column($salesByProduct, 'total_sales'));
        $totalProfit = array_sum(array_column($salesByProduct, 'profit'));
        $totalQty = array_sum(array_column($salesByProduct, 'total_qty'));
        $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
        ?>

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
    <br/>

    <!-- Charts Row -->
    <div class="row">
        <!-- Price Comparison Chart -->
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">เปรียบเทียบราคาขายกับต้นทุน</h5>
                </div>
                <div class="panel-body">
                    <div id="price-comparison-chart" style="height: 400px;"></div>
                </div>
            </div>
        </div>

        <!-- Top 10 Products Chart -->
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">สินค้าขายดี 10 อันดับ</h5>
                </div>
                <div class="panel-body">
                    <div id="top-products-chart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
    <br/>
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

<?php
// JavaScript for Charts
$priceComparisonJson = json_encode($priceComparisonData);
$topProductsJson = json_encode($topProducts);

$js = <<<JS
// Price Comparison Chart
Highcharts.chart('price-comparison-chart', {
    chart: {
        type: 'column'
    },
    title: {
        text: ''
    },
    xAxis: {
        categories: {$priceComparisonJson}.categories,
        crosshair: true,
        labels: {
            rotation: -45,
            style: {
                fontSize: '11px'
            }
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'ราคา (บาท)'
        }
    },
    tooltip: {
        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
            '<td style="padding:0"><b>฿{point.y:.2f}</b></td></tr>',
        footerFormat: '</table>',
        shared: true,
        useHTML: true
    },
    plotOptions: {
        column: {
            pointPadding: 0.2,
            borderWidth: 0,
            dataLabels: {
                enabled: true,
                format: '฿{y:.0f}'
            }
        }
    },
    series: [{
        name: 'ต้นทุน',
        data: {$priceComparisonJson}.costPrices,
        color: '#f56954'
    }, {
        name: 'ราคาขาย',
        data: {$priceComparisonJson}.salePrices,
        color: '#00a65a'
    }, {
        name: 'กำไร',
        data: {$priceComparisonJson}.profits,
        color: '#3c8dbc'
    }]
});

// Top Products Chart
Highcharts.chart('top-products-chart', {
    chart: {
        type: 'bar'
    },
    title: {
        text: ''
    },
    xAxis: {
        categories: {$topProductsJson}.categories,
        title: {
            text: null
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'จำนวนขาย (ชิ้น)',
            align: 'high'
        },
        labels: {
            overflow: 'justify'
        }
    },
    tooltip: {
        valueSuffix: ' ชิ้น'
    },
    plotOptions: {
        bar: {
            dataLabels: {
                enabled: true
            }
        }
    },
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'top',
        x: -40,
        y: 80,
        floating: true,
        borderWidth: 1,
        backgroundColor: ((Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'),
        shadow: true
    },
    credits: {
        enabled: false
    },
    series: [{
        name: 'จำนวนขาย',
        data: {$topProductsJson}.quantities,
        color: '#00c0ef'
    }]
});

// DataTable Enhancement (ถ้าต้องการ)
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('.table').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'language': {
                'url': '//cdn.datatables.net/plug-ins/1.10.19/i18n/Thai.json'
            }
        });
    }
});
JS;

$this->registerJs($js);
?>

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
        font-weight: bold;
        font-size: 14px;
    }

    .info-box-number {
        display: block;
        font-weight: bold;
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
</style>