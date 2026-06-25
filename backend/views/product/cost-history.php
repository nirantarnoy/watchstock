<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $search string */
/* @var $product backend\models\Product */
/* @var $history array */

$this->title = 'แสดงที่มาการคำนวณราคาเฉลี่ยของสินค้า';
$this->params['breadcrumbs'][] = ['label' => 'สินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="product-cost-history">

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title m-0"><i class="fa fa-calculator"></i> <?= Html::encode($this->title) ?></h4>
                </div>
                <div class="card-body">
                    <?= Html::beginForm(['product/cost-history'], 'get') ?>
                        <div class="row">
                            <div class="col-lg-5">
                                <label for="search">รหัสสินค้า (Product Code)</label>
                                <?php
                                    echo \kartik\select2\Select2::widget([
                                        'name' => 'search',
                                        'value' => $search,
                                        'data' => \yii\helpers\ArrayHelper::map(\backend\models\Product::find()->where(['status' => 1])->all(), 'name', function($model) {
                                            return $model->name . ' (' . $model->description . ')';
                                        }),
                                        'options' => ['placeholder' => 'พิมพ์ค้นหาชื่อ/รหัสสินค้า...', 'id' => 'search', 'required' => true],
                                        'pluginOptions' => [
                                            'allowClear' => true
                                        ],
                                    ]);
                                ?>
                            </div>
                            <div class="col-lg-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i> ค้นหา</button>
                            </div>
                        </div>
                    <?= Html::endForm() ?>

                    <hr>

                    <?php if ($search): ?>
                        <?php if ($product): ?>
                            <div class="alert alert-info d-flex justify-content-between align-items-center">
                                <div>
                                    <h5>ข้อมูลสินค้า: <?= Html::encode($product->name) ?> - <?= Html::encode($product->description) ?></h5>
                                    <p class="mb-0">
                                        <strong>ต้นทุนปัจจุบัน (ล่าสุด):</strong> <?= number_format($product->cost_price, 2) ?> บาท <br>
                                        <strong>จำนวนคงเหลือ (Stock Qty):</strong> <?= number_format($product->stock_qty, 0) ?> 
                                    </p>
                                </div>
                                <div>
                                    <button type="button" id="toggle-out-btn" class="btn btn-outline-secondary" onclick="toggleOutRows()">
                                        <i class="fa fa-eye-slash"></i> ซ่อนรายการออก
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead class="bg-dark text-white">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>วันที่ทำรายการ</th>
                                            <th>เลขที่เอกสาร</th>
                                            <th>ประเภทรายการ</th>
                                            <th class="text-right">จำนวนที่เข้า/ออก</th>
                                            <th class="text-right">ราคาทุน (รายการ)</th>
                                            <th class="text-right">จำนวนเดิม</th>
                                            <th class="text-right">ต้นทุนเฉลี่ยเดิม</th>
                                            <th class="text-right text-success">จำนวนใหม่</th>
                                            <th class="text-right text-success">ต้นทุนเฉลี่ยใหม่</th>
                                            <th>คำอธิบายเพิ่มเติม</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($history)): ?>
                                            <?php foreach ($history as $index => $row): ?>
                                                <tr class="<?= $row['stock_type_id'] == 2 ? 'row-out' : 'row-in' ?>">
                                                    <td class="text-center"><?= $index + 1 ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($row['trans_date'])) ?></td>
                                                    <td><?= Html::encode($row['journal_no']) ?></td>
                                                    <td>
                                                        <?php 
                                                            $list = \common\models\JournalTrans::getTransactionTypeList();
                                                            $transTypeName = isset($list[$row['trans_type_id']]) ? $list[$row['trans_type_id']] : 'ไม่ทราบประเภท';
                                                            echo Html::encode($transTypeName);
                                                        ?>
                                                    </td>
                                                    <td class="text-right font-weight-bold <?= $row['stock_type_id'] == 1 ? 'text-primary' : 'text-danger' ?>">
                                                        <?= $row['stock_type_id'] == 1 ? '+' : '-' ?><?= number_format($row['qty'], 0) ?>
                                                    </td>
                                                    <td class="text-right"><?= number_format($row['cost_price'], 2) ?></td>
                                                    
                                                    <td class="text-right text-muted"><?= number_format($row['prev_qty'], 0) ?></td>
                                                    <td class="text-right text-muted"><?= number_format($row['prev_cost'], 2) ?></td>
                                                    
                                                    <td class="text-right text-success font-weight-bold"><?= number_format($row['new_qty'], 0) ?></td>
                                                    <td class="text-right text-success font-weight-bold"><?= number_format($row['new_cost'], 2) ?></td>
                                                    
                                                    <td><small><?= Html::encode($row['action']) ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted">ไม่พบประวัติการทำรายการสำหรับสินค้านี้</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <!-- Error already shown via flash message -->
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fa fa-search fa-3x mb-3"></i>
                            <p>กรุณากรอกรหัสสินค้าเพื่อดูรายละเอียดการคำนวณต้นทุนเฉลี่ย</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
    var outVisible = true;
    function toggleOutRows() {
        outVisible = !outVisible;
        var rows = document.querySelectorAll('.row-out');
        var btn = document.getElementById('toggle-out-btn');
        rows.forEach(function(row) {
            row.style.display = outVisible ? '' : 'none';
        });
        
        if (outVisible) {
            btn.innerHTML = '<i class="fa fa-eye-slash"></i> ซ่อนรายการออก';
            btn.className = 'btn btn-outline-secondary';
        } else {
            btn.innerHTML = '<i class="fa fa-eye"></i> แสดงรายการออก';
            btn.className = 'btn btn-secondary';
        }
    }
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
