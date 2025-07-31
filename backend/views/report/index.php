<?php
?>
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
                                    <td><?= Html::encode($product['name'].' '.$product['description']) ?></td>
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