<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SalesReportSearch */
/* @var $crosstabData array */
/* @var $productList array */

$this->title = 'รายงานยอดขาย (Crosstab)';
$this->params['breadcrumbs'][] = $this->title;

// Calculate grand total
$grandTotal = array_sum(array_column($crosstabData['products'], 'total_amount'));
?>

<div class="sales-report-crosstab">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">

                    <div class="box-tools pull-right">
                        <?= Html::a('<i class="fa fa-print"></i> พิมพ์',
                            Url::to(['print'] + Yii::$app->request->queryParams),
                            ['class' => 'btn btn-default btn-sm', 'target' => '_blank']) ?>
                        <?= Html::a('<i class="fa fa-download"></i> Export Excel',
                            Url::to(['export-excel'] + Yii::$app->request->queryParams),
                            ['class' => 'btn btn-success btn-sm']) ?>
                    </div>
                </div>
                <br/>
                <div class="box-body">
                    <!-- Filter Form -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">
                                <a data-toggle="collapse" href="#filter-panel" aria-expanded="true">
                                    <i class="fa fa-filter"></i> ตัวกรองข้อมูล
                                </a>
                            </h5>
                        </div>
                        <div id="filter-panel" class="panel-collapse collapse in">
                            <div class="panel-body">
                                <?php $form = ActiveForm::begin([
                                    'method' => 'get',
                                    'options' => ['class' => 'form-inline']
                                ]); ?>

                                <div class="row">
                                    <div class="col-md-3">
                                        <?= $form->field($searchModel, 'date_from')->widget(DatePicker::class, [
                                            'options' => ['placeholder' => 'เลือกวันที่เริ่มต้น'],
                                            'pluginOptions' => [
                                                'autoclose' => true,
                                                'format' => 'yyyy-mm-dd',
                                                'todayHighlight' => true,
                                            ]
                                        ])->label('วันที่เริ่มต้น') ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?= $form->field($searchModel, 'date_to')->widget(DatePicker::class, [
                                            'options' => ['placeholder' => 'เลือกวันที่สิ้นสุด'],
                                            'pluginOptions' => [
                                                'autoclose' => true,
                                                'format' => 'yyyy-mm-dd',
                                                'todayHighlight' => true,
                                            ]
                                        ])->label('วันที่สิ้นสุด') ?>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="label">ชื่อสินค้า</div>
                                        <?= $form->field($searchModel, 'product_name')->textInput([
                                            'placeholder' => 'ค้นหาชื่อสินค้า'
                                        ])->label(false) ?>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="label">สินค้า</div>
                                        <?= $form->field($searchModel, 'product_id')->widget(\kartik\select2\Select2::className(),[
                                            'data' => \yii\helpers\ArrayHelper::map(\backend\models\Product::find()->where(['status' => 1])->all(), 'id', 'name'),
                                            'options' => [
                                                'placeholder' => '-- เลือกสินค้า --',
                                                'onchange' => '$(this).submit()',
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' => true,
                                            ]
                                        ])->label(false) ?>
                                    </div>
                                </div>

                                <div class="row" style="margin-top: 15px;">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <?= Html::submitButton('<i class="fa fa-search"></i> ค้นหา',
                                                ['class' => 'btn btn-primary']) ?>
                                            <?= Html::a('<i class="fa fa-refresh"></i> รีเซ็ต',
                                                ['crosstab'], ['class' => 'btn btn-default']) ?>
                                        </div>
                                    </div>
                                </div>

                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
                    <br/>
                    <!-- Report Summary -->
                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>สรุปข้อมูล:</strong>
                                ช่วงวันที่ <?= Html::encode($searchModel->date_from) ?>
                                ถึง <?= Html::encode($searchModel->date_to) ?> |
                                จำนวนสินค้า: <?= count($crosstabData['products']) ?> รายการ |
                                ยอดขายรวม: <?= number_format($grandTotal, 2) ?> บาท
                            </div>
                        </div>
                    </div>

                    <!-- Crosstab Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="crosstab-table">
                            <thead class="bg-primary">
                            <tr>
<!--                                <th rowspan="2" class="text-center" style="vertical-align: middle; min-width: 80px;">-->
<!--                                    รหัสสินค้า-->
<!--                                </th>-->
                                <th rowspan="2" class="text-center" style="vertical-align: middle; min-width: 200px;">
                                    ชื่อสินค้า
                                </th>
                                <th colspan="<?= count($crosstabData['dateRange']) ?>" class="text-center">เดือน
                                    (<?= date('m/Y', strtotime($searchModel->date_from)) ?>)
                                </th>
                                <th rowspan="2" class="text-center bg-warning"
                                    style="vertical-align: middle; min-width: 100px;">รวม
                                </th>
                            </tr>
                            <tr>
                                <?php foreach ($crosstabData['dateRange'] as $dateInfo): ?>
                                    <th class="text-center" style="min-width: 80px;">
                                        <?= $dateInfo['day'] ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($crosstabData['products'])): ?>
                                <tr>
                                    <td colspan="<?= count($crosstabData['dateRange']) + 2 ?>"
                                        class="text-center text-muted">
                                        ไม่พบข้อมูลในช่วงวันที่ที่เลือก
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
//                                   $total_qty = 0;
                                   $total_amount = 0;
                                ?>
                                <?php foreach ($crosstabData['products'] as $product): ?>
                                    <?php
                                    $total_qty = 0;
                                    ?>
                                    <tr>
<!--                                        <td class="text-center">--><?php //= Html::encode($product['product_code']) ?><!--</td>-->
                                        <td><?= Html::encode($product['product_name']) ?></td>

                                        <?php foreach ($crosstabData['dateRange'] as $dateInfo): ?>
                                            <?php
                                            $amount = $product['daily_sales'][$dateInfo['date']]['amount'];
                                            $qty = $product['daily_sales'][$dateInfo['date']]['qty'];

                                            $total_qty += $qty;
                                            $total_amount += $amount;
                                            ?>
                                            <td class="text-right <?= $amount > 0 ? 'text-success' : 'text-muted' ?>">
                                                <?php if ($qty > 0): ?>
                                                    <span title="ยอดเงินรวม: <?= number_format($amount, 0) ?> บาท">
                                        <?= number_format($qty, 2) ?>
                                    </span>
                                                <?php else: ?>
                                                    <span title="ยอดเงินรวม: <?= number_format($qty, 0) ?> บาท">
                                                    -
                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="text-right">
                                    <span title="ยอดเงินรวม: <?= number_format($total_amount, 0) ?> บาท">
                                        <?= number_format($total_qty, 2) ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>