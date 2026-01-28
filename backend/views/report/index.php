<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$totalSales = array_sum(array_column($salesByProduct, 'total_sales'));
$totalProfit = array_sum(array_column($salesByProduct, 'profit'));
$totalQty = array_sum(array_column($salesByProduct, 'total_qty'));
$profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
?>

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
                                <?= Html::a('Export Excel', ['export', 'from_date' => $fromDate, 'to_date' => $toDate], ['class' => 'btn btn-success']) ?>
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
</style>