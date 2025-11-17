<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\JournalTrans */
/* @var $lines app\models\JournalTransLine[] */

$this->title = $model->journal_no;
$this->params['breadcrumbs'][] = ['label' => 'รายการ Stock Transaction', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// URL สำหรับ AJAX
$ajax_url = Url::to(['get-product-info']);

// CSS สำหรับ autocomplete และ alerts
$autocompleteCSS = <<<CSS
.autocomplete-dropdown {
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

.autocomplete-item:hover {
    background-color: #f5f5f5;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item.highlighted {
    background-color: #007bff;
    color: white;
}

.product-code {
    color: #666;
    font-size: 12px;
}

.product-field-container {
    position: relative;
}

.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: white;
    border: 1px solid #ccc;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    display: none;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.form-group {
    margin-bottom: 1rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.item-number {
    font-weight: bold;
    color: #6c757d;
}

.dynamicform_wrapper .btn-success {
    margin-right: 5px;
}

.table-responsive {
    overflow: visible !important;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.table-responsive .table {
    overflow: visible !important;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.stock-alert {
    position: relative;
}

.stock-warning {
    background-color: #fff3cd !important;
    border-color: #ffeaa7 !important;
    color: #856404;
}

.stock-error {
    background-color: #f8d7da !important;
    border-color: #f5c6cb !important;
    color: #721c24;
}

.warehouse-option-with-stock {
    display: flex;
    justify-content: space-between;
}

.warehouse-stock-info {
    color: #666;
    font-size: 0.9em;
}

.alert-message {
    position: absolute;
    top: -25px;
    left: 0;
    right: 0;
    z-index: 1000;
    font-size: 11px;
    padding: 2px 5px;
    border-radius: 3px;
    display: none;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
CSS;

$this->registerCss($autocompleteCSS);

// JavaScript สำหรับ autocomplete และ stock management
$autocompleteJs = <<<JS
// ตัวแปรเก็บข้อมูลสินค้า
var productsData = [];
var productStockData = {};
var isProductsLoaded = false;

// ฟังก์ชันโหลดข้อมูลสินค้า
function loadProductsData() {
    if (isProductsLoaded) return;
    
    $.ajax({
        url: '$ajax_url',
        type: 'GET',
        data: { action: 'get-all-products' },
        dataType: 'json',
        success: function(data) {
            productsData = data;
            isProductsLoaded = true;
        },
        error: function() {
            console.log('Error loading products data');
            productsData = [];
        }
    });
}

// ฟังก์ชันค้นหาสินค้า
function searchProducts(query) {
    if (!query || query.length < 1) return [];
    
    query = query.toLowerCase();
    return productsData.filter(function(product) {
        return product.name.toLowerCase().includes(query) || 
               product.code.toLowerCase().includes(query) ||
               product.display.toLowerCase().includes(query);
    }).slice(0, 10);
}

// ฟังก์ชันแสดงผลลัพธ์
function showAutocompleteResults(input, results) {
    var index = input.attr('data-index');
    var dropdown = $('.autocomplete-dropdown[data-index="' + index + '"]');
    
    dropdown.empty();
    
    if (results.length === 0) {
        dropdown.hide();
        return;
    }
    
    results.forEach(function(product) {
        var item = $('<div class="autocomplete-item">')
            .html('<div>' + product.code + '</div><div class="product-code">' + product.name + '</div>')
            .data('product', product);
        dropdown.append(item);
    });
    
    dropdown.show();
}

// ฟังก์ชันซ่อน dropdown
function hideAutocomplete(index) {
    setTimeout(function() {
        $('.autocomplete-dropdown[data-index="' + index + '"]').hide();
    }, 200);
}

// ฟังก์ชันเลือกสินค้า
function selectProduct(input, product) {
    var index = input.attr('data-index');
    
    // อัพเดตค่า
    input.val(product.display);
    $('.product-id-hidden[data-index="' + index + '"]').val(product.id);
    
    // โหลดข้อมูลสต็อกและอัพเดตคลังสินค้า
    loadProductStock(product.id, index);
    
    // ซ่อน dropdown
    $('.autocomplete-dropdown[data-index="' + index + '"]').hide();
}

// ฟังก์ชันโหลดข้อมูลสต็อก
function loadProductStock(productId, index) {
    // สำหรับการโหลดข้อมูลสต็อก (ถ้าต้องการ)
    // ปรับตามความต้องการของระบบ
}

$(document).ready(function() {
    
    // โหลดข้อมูลสินค้าตอนเริ่มต้น
    loadProductsData();
    
    // Event สำหรับ autocomplete
    $(document).on('input', '.product-autocomplete', function() {
        var input = $(this);
        var query = input.val();
        
        if (!isProductsLoaded) {
            loadProductsData();
            return;
        }
        
        var results = searchProducts(query);
        showAutocompleteResults(input, results);
    });
    
    $(document).on('focus', '.product-autocomplete', function() {
        var input = $(this);
        var query = input.val();
        
        if (!isProductsLoaded) {
            loadProductsData();
            return;
        }
        
        if (query) {
            var results = searchProducts(query);
            showAutocompleteResults(input, results);
        }
    });
    
    $(document).on('blur', '.product-autocomplete', function() {
        var index = $(this).attr('data-index');
        hideAutocomplete(index);
    });
    
    $(document).on('click', '.autocomplete-item', function() {
        var product = $(this).data('product');
        var dropdown = $(this).closest('.autocomplete-dropdown');
        var index = dropdown.attr('data-index');
        var input = $('.product-autocomplete[data-index="' + index + '"]');
        selectProduct(input, product);
    });
    
    // Event navigation ด้วย keyboard
    $(document).on('keydown', '.product-autocomplete', function(e) {
        var index = $(this).attr('data-index');
        var dropdown = $('.autocomplete-dropdown[data-index="' + index + '"]');
        var items = dropdown.find('.autocomplete-item');
        var highlighted = items.filter('.highlighted');
        
        if (e.keyCode === 40) { // Arrow Down
            e.preventDefault();
            if (highlighted.length === 0) {
                items.first().addClass('highlighted');
            } else {
                highlighted.removeClass('highlighted');
                var next = highlighted.next('.autocomplete-item');
                if (next.length) {
                    next.addClass('highlighted');
                } else {
                    items.first().addClass('highlighted');
                }
            }
        } else if (e.keyCode === 38) { // Arrow Up
            e.preventDefault();
            if (highlighted.length === 0) {
                items.last().addClass('highlighted');
            } else {
                highlighted.removeClass('highlighted');
                var prev = highlighted.prev('.autocomplete-item');
                if (prev.length) {
                    prev.addClass('highlighted');
                } else {
                    items.last().addClass('highlighted');
                }
            }
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if (highlighted.length) {
                var product = highlighted.data('product');
                selectProduct($(this), product);
            }
        } else if (e.keyCode === 27) { // Escape
            dropdown.hide();
        }
    });
});
JS;

$this->registerJs($autocompleteJs, \yii\web\View::POS_READY);

$product_can_return = null;

\yii\web\YiiAsset::register($this);

$product_type = \backend\helpers\ProductType::asArrayObject();
$warehouse_data = \backend\models\Warehouse::find()->where(['status' => 1])->all();

$yes_no = [['id' => 0, 'name' => 'NO'],['id' => 1, 'name' => 'YES']];
?>
    <div class="journal-trans-view">

        <p>
            <?= Html::a('แก้ไข', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= $model->status != 3 && $model->status !=4 ? Html::a('ลบ', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                    'method' => 'post',
                ],
            ]) : '' ?>
            <?php if($model->status != \backend\models\JournalTrans::JOURNAL_TRANS_STATUS_CANCEL):?>
            <?php endif;?>
            <?= Html::a('สร้างรายการใหม่', ['create'], ['class' => 'btn btn-success']) ?>
        </p>

        <div class="row">
            <div class="col-md-6">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'journal_no',
                        [
                            'attribute' => 'trans_date',
                            'format' => ['datetime', 'php:d/m/Y'],
                        ],
                        [
                            'attribute' => 'trans_type_id',
                            'value' => $model->getTransactionTypeName(),
                        ],
                        [
                            'attribute' => 'stock_type_id',
                            'format' => 'raw',
                            'value' => function ($model) {
                                $type_name = \backend\helpers\StockType::getTypeById($model->stock_type_id);
                                $stock_type = '';
                                if ($model->stock_type_id == 1) {
                                    $stock_type = '<div class="badge badge-pill badge-success">' . $type_name . '</div>';
                                } else if ($model->stock_type_id == 2) {
                                    $stock_type = '<div class="badge badge-pill badge-danger">' . $type_name . '</div>';
                                }
                                return $stock_type;
                            },
                        ],
                        [
                            'attribute' => 'warehouse_id',
                            'value' => function ($model) {
                                return \backend\models\Warehouse::findName($model->warehouse_id);
                            },
                        ],
                    ],
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'customer_name',
                        [
                            'attribute' => 'qty',
                            'format' => ['decimal', 2],
                        ],
                        'remark',
                        [
                            'attribute' => 'status',
                            'headerOptions' => ['style' => 'text-align:center'],
                            'contentOptions' => ['style' => 'text-align:left'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                $status_name = \backend\helpers\TransStatusType::getTypeById($model->status);
                                $htmel_status = getBadgeStatus($model->status, $status_name);
                                return $htmel_status;
                            },
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <h3>รายการสินค้า</h3>

        <?php
        $dataProvider = new ArrayDataProvider([
            'allModels' => $lines,
            'pagination' => false,
        ]);
        ?>

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'product_id',
                    'value' => function ($model) {
                        return \backend\models\Product::findName($model->product_id);
                    },
                ],
                [
                    'attribute' => 'warehouse_id',
                    'value' => function ($model) {
                        return \backend\models\Warehouse::findName($model->warehouse_id);
                    },
                ],
                [
                    'attribute' => 'qty',
                    'format' => ['decimal', 2],
                    'headerOptions' => ['style' => 'text-align:right'],
                    'contentOptions' => ['style' => 'text-align:right'],
                ],
                'remark',
                [
                    'label' => 'ยกเลิกการทำรายการ',
                    'format' => 'raw',
                    'value' => function ($data) {
                        if($data->status ==1 and ($data->journalTrans->trans_type_id == 5 || $data->journalTrans->trans_type_id == 6 || $data->journalTrans->trans_type_id == 7 || $data->journalTrans->trans_type_id == 8)){
                            return '<div class="text-success">รับคืนสินค้าแล้ว</div>';
                        }else{
                            return '<div class="btn btn-danger" data-var="'.$data->id.'" data-value="'.$data->qty.'" onclick="canelline($(this))">ยกเลิก</div>';
                        }

                    }
                ]
            ],
        ]); ?>

        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <p><strong>สร้างโดย:</strong> <?= \backend\models\User::findName($model->created_by) ?>
                            เมื่อ <?= date('d-m-Y H:i:s', $model->created_at) ?></p>
                        <?php if ($model->updated_at): ?>
                            <p><strong>แก้ไขโดย:</strong> <?= $model->updated_by ?>
                                เมื่อ <?= date('d-m-Y H:i:s', $model->updated_at) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <br/>
        <?php if ($model->trans_type_id == 7 && $model->status != 3): ?>
            <div class="row">
                <div class="col-lg-12">
                    <h4>รับสินค้าคืนช่าง</h4>
                </div>
            </div>
            <form onsubmit="return validateForm();" action="<?= \yii\helpers\Url::to(['journaltrans/addreturnproduct'], true) ?>" method="post">
                <input type="hidden" name="journal_trans_id" value="<?= $model->id ?>">
                <input type="hidden" name="trans_type_id" value="8">
                <div class="row" style="margin-top: 10px">
                    <div class="col-lg-2">
                        <label for="">สินค้า</label>
                    </div>
                    <div class="col-lg-1">
                        <label for="">จำนวนเบิก</label>
                    </div>
                    <div class="col-lg-1">
                        <label for="">จำนวนคืน</label>
                    </div>
                    <div class="col-lg-1">
                        <label for="">กลับเข้าคลัง</label>
                    </div>
                    <div class="col-lg-1">
                        <label for="">คืนเป็นสินค้า</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">เป็นสินค้าเดิม</label>
                    </div>
                    <div class="col-lg-3">
                        <label for="">หมายเหตุ</label>
                    </div>
                </div>
                <?php
                $row_index = 0; // เพิ่มตัวแปรนับแถว
                foreach ($lines as $value): ?>
                    <?php
                    if ($value->status == 1) continue; // คืนสินค้าแล้ว
                    ?>
                    <?php
                    $check_return_qty = getReturnProduct($model->id, $value->product_id, $value->qty);
                    if ($check_return_qty == 0) continue;

                    $product_can_return = getCanreturnProduct($value->product_id);
                    ?>
                    <div class="row" style="margin-top: 10px; position: relative;">
                        <div class="col-lg-2">
                            <input type="hidden" name="product_id[]" value="<?= $value->product_id ?>">
                            <input type="hidden" name="warehouse_id[]" value="<?= $value->warehouse_id ?>">
                            <input type="hidden" name="journal_trans_line_id[]" value="<?= $value->id ?>">
                            <input type="text" class="form-control" readonly
                                   value="<?= \backend\models\Product::findName($value->product_id) ?>">
                        </div>
                        <div class="col-lg-1">
                            <input type="text" class="form-control" readonly value="<?= $value->qty ?>">
                        </div>
                        <div class="col-lg-1">
                            <input type="number" name="return_qty[]" class="form-control line-return-qty"
                                   value="<?= $check_return_qty ?>" data-var="<?= $check_return_qty ?>"
                                   onchange="checkReturnQty($(this))">
                        </div>
                        <div class="col-lg-1">
                            <select name="return_to_warehouse[]" class="form-control line-return-to-warehouse">
                                <option value="-1">-- เลือกคลัง --</option>
                                <?php foreach ($warehouse_data as $value_warehouse): ?>
                                    <option value="<?= $value_warehouse->id ?>"><?= $value_warehouse->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-1">
                            <select name="return_to_type[]" class="form-control line-return-to-type">
                                <?php for ($i = 0; $i <= count($product_type) - 1; $i++): ?>
                                    <option value="<?= $product_type[$i]['id'] ?>"><?= $product_type[$i]['name'] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 product-field-container">
                            <input type="text"
                                   name="return_to_product_name[]"
                                   class="form-control product-autocomplete"
                                   placeholder="พิมพ์ชื่อสินค้าหรือรหัสสินค้า..."
                                   data-index="<?= $row_index ?>"
                                   autocomplete="off">
                            <input type="hidden" name="return_to_product[]" class="product-id-hidden"
                                   data-index="<?= $row_index ?>" value="">
                            <div class="autocomplete-dropdown" data-index="<?= $row_index ?>"></div>
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="return_remark[]" class="form-control line-return-remark" value="">
                        </div>
                    </div>
                    <?php
                    $row_index++; // เพิ่มค่า index สำหรับแถวถัดไป
                    ?>
                <?php endforeach; ?>
                <br/>
                <div class="row">
                    <div class="col-lg-3">
                        <button class="btn btn-success">บันทึกรายการ</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($model->trans_type_id == 5 && $model->status != 3): ?> <!-- คืนยืม -->
            <div class="row">
                <div class="col-lg-12">
                    <h4>รับคืนยืมสินค้า</h4>
                </div>
            </div>
            <form action="<?= \yii\helpers\Url::to(['journaltrans/addreturnproduct'], true) ?>" method="post">
                <input type="hidden" name="journal_trans_id" value="<?= $model->id ?>">
                <input type="hidden" name="trans_type_id" value="6">
                <div class="row" style="margin-top: 10px">
                    <div class="col-lg-3">
                        <label for="">สินค้า</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">จำนวนเบิก</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">จำนวนคืน</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">กลับเข้าคลัง</label>
                    </div>
                    <div class="col-lg-3">
                        <label for="">หมายเหตุ</label>
                    </div>
                </div>
                <?php $has_line = 0; ?>
                <?php foreach ($lines as $value): ?>
                    <?php
                    if ($value->status == 1) {
                        continue;
                    } else {
                        $has_line += 1;
                    }
                    ?>
                    <?php
                    $check_return_qty = getReturnProduct($model->id, $value->product_id, $value->qty);
                    if ($check_return_qty == 0) continue;
                    ?>
                    <div class="row" style="margin-top: 10px">
                        <div class="col-lg-3">
                            <input type="hidden" name="product_id[]" value="<?= $value->product_id ?>">
                            <input type="text" class="form-control" readonly
                                   value="<?= \backend\models\Product::findName($value->product_id) ?>">
                        </div>
                        <div class="col-lg-2">
                            <input type="text" class="form-control" readonly value="<?= $value->qty ?>">
                        </div>
                        <div class="col-lg-2">
                            <input type="number" name="return_qty[]" class="form-control"
                                   value="<?= $check_return_qty ?>" data-var="<?= $check_return_qty ?>"
                                   onchange="checkReturnQty($(this))">
                        </div>
                        <div class="col-lg-2">
                            <select name="return_to_warehouse[]" class="form-control">
                                <option value="">--เลือกคลัง--</option>
                                <?php foreach ($warehouse_data as $value_warehouse): ?>
                                    <option value="<?= $value_warehouse->id ?>"><?= $value_warehouse->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="return_remark[]" class="form-control">
                        </div>
                    </div>
                <?php endforeach; ?>
                <br/>
                <div class="row">
                    <?php if ($has_line > 0): ?>
                        <div class="col-lg-3">
                            <button class="btn btn-success">บันทึกรายการ</button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
        <br/>

    </div>
    <form id="form-cancel-line" action="<?=Url::to(['journaltrans/cancelbyline'],true)?>" method="post">
        <input type="hidden" class="cancel-id" name="cancel_id" value="">
        <input type="hidden" name="cancel_qty" id="real-cancel-qty">
    </form>

    <!-- Modal -->
    <div class="modal fade" id="modal-cancel" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">ยกเลิกรายการ</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="cancel-id" name="cancel_id">
                    <input type="hidden" id="max-sale-qty">

                    <div class="form-group">
                        <label>จำนวนที่ต้องการยกเลิก</label>
                        <input type="number" id="cancel-qty" class="form-control" min="1">
                        <small class="text-danger d-none" id="qty-error">กรอกจำนวนเกินยอดขาย!</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-submit-cancel" class="btn btn-danger">ยืนยัน</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
                </div>

            </div>
        </div>
    </div>


<?php
function getReturnProduct($journal_trans_id, $product_id, $original_qty)
{
    $qty = 0;
    if ($product_id && $original_qty) {
        $return_qty = \common\models\JournalTransLine::find()->where(['journal_trans_ref_id' => $journal_trans_id, 'product_id' => $product_id])->sum('qty');
        if ($return_qty <= $original_qty) {
            $qty = $original_qty - $return_qty;
        }
    }
    return $qty;
}

function getCanreturnProduct($product_id)
{
    $product_data = [];
    $model = \common\models\Product::find()->where(['status' => 1])->all();
    if ($model) {
        foreach ($model as $value) {
            array_push($product_data, ['id' => $value->id, 'name' => $value->name . ' ' . $value->description]);
        }
    }
    return $product_data;
}

function getBadgeType($status, $status_name)
{
    if ($status == 1) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 2) {
        return '<span class="badge badge-pill badge-warning" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 3) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 4) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 5) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 6) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 7) {
        return '<span class="badge badge-pill badge-primary" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 8) {
        return '<span class="badge badge-pill badge-primary" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 9) {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    } else {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }
}

function getBadgeStatus($status, $status_name)
{
    if ($status == 1) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 2) {
        return '<span class="badge badge-pill badge-warning" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 3) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 4) {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }
}

?>
<?php
$js = <<<JS
function checkReturnQty(e){
    var remain_qty = e.attr('data-var');
    var return_qty = e.val();
    if(return_qty > remain_qty){
        e.val(remain_qty);
    }
}

function checkReturnNew(e){
    var type = e.val();
    
    if(type == 1){
        e.closest("div.row").find(".line-return-to-product").prop("disabled", true);
    }else{
        e.closest("div.row").find(".line-return-to-product").prop("disabled", false);
    }
}

function validateForm() {
    let select_warehouses = document.querySelectorAll('.line-return-to-warehouse');
    let select_qty = document.querySelectorAll('.line-return-qty');
    
    for (let i = 0; i < select_warehouses.length; i++) {
        // ลบ required ก่อนทุกครั้ง
        select_warehouses[i].removeAttribute("required");
    
        if (parseFloat(select_qty[i].value) > 0) {
            // ถ้าจำนวน > 0 แต่ยังไม่ได้เลือกคลัง
            if (select_warehouses[i].value === '-1' || select_warehouses[i].value === '') {
                select_warehouses[i].focus();
                alert("กรุณาเลือกคลัง");
                return false;
            }
        }
    }
    
    let line_remark = document.querySelectorAll('.line-return-remark');
    let selects = document.querySelectorAll('.product-id-hidden');
    for (let i = 0; i < selects.length; i++) {
         if (parseFloat(select_qty[i].value) > 0) {
            if ((selects[i].value === '' || selects[i].value === undefined) && line_remark[i].value === '') {
                console.log(selects[i].value);
                alert('กรุณาตรวจสอบสินค้าให้ครบถ้วน');
                selects[i].focus();
                return false;
            }
        }
    }
    return true;
}




// function canelline(e){
//     var id = e.attr("data-var");
//     var sale_qty = e.attr("data-value");
//     if(id){
//         if(confirm("ต้องการยกเลิกรายการใช่หรือไม่")){
//             $(".cancel-id").val(id);
//             $("#form-cancel-line").submit();
//         }
//     }
// }

function canelline(e) {
    var id = e.attr("data-var");
    var sale_qty = parseFloat(e.attr("data-value"));

    if (id) {
        // ใส่ค่าใน modal
        $("#cancel-id").val(id);
        $("#max-sale-qty").val(sale_qty);
        $("#cancel-qty").val(sale_qty);
        $("#qty-error").addClass("d-none");

        // เปิด modal
        $("#modal-cancel").modal("show");
    }
}


// เมื่อกดปุ่มยืนยันใน modal
$("#btn-submit-cancel").on("click", function() {
    var maxQty = parseFloat($("#max-sale-qty").val());
    var inputQty = parseFloat($("#cancel-qty").val());

    if (inputQty > maxQty) {
        $("#qty-error").removeClass("d-none");
        return;
    }

    // ส่งค่าเข้าฟอร์มจริง
    $(".cancel-id").val($("#cancel-id").val());
    $("#real-cancel-qty").val(inputQty);

    $("#form-cancel-line").submit();
});
JS;
$this->registerJs($js, static::POS_END);
?>