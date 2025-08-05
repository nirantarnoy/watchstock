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
    margin-top: 1px;
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

.table-scroll-container {
    width: 100%;
    overflow-x: auto;
}

table.dynamic-width {
    min-width: max-content;
    width: 100%;
    border-collapse: collapse;
}

table.dynamic-width th, table.dynamic-width td {
    white-space: nowrap;
    padding: 8px;
    border: 1px solid #ddd;
    position: relative;
}

/* ปรับปรุงส่วนของ autocomplete container */
.autocomplete-container {
    position: relative;
    width: 100%;
}

.autocomplete-container .form-control {
    width: 100%;
}

/* Global dropdown portal - แสดงนอก table */
.autocomplete-dropdown-portal {
    position: fixed;
    z-index: 9999 !important;
    background: white;
    border: 1px solid #ccc;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    margin-top: 1px;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 200px;
    max-width: 90vw; /* จำกัดความกว้างสำหรับ mobile */
}

/* Fallback dropdown สำหรับในตาราง */
.autocomplete-container .autocomplete-dropdown {
    position: fixed;
    z-index: 9999 !important;
    background: white;
    border: 1px solid #ccc;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    margin-top: 1px;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 200px;
    max-width: 90vw; /* จำกัดความกว้างสำหรับ mobile */
}

/* ป้องกัน table overflow */
.table-scroll-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible !important;
}

table.dynamic-width {
    min-width: max-content;
    width: 100%;
    border-collapse: collapse;
    overflow: visible !important;
}

table.dynamic-width th, table.dynamic-width td {
    white-space: nowrap;
    padding: 8px;
    border: 1px solid #ddd;
    position: relative;
    overflow: visible !important;
}

/* เพิ่ม style สำหรับ table cell ที่มี autocomplete */
.table-autocomplete-cell {
    overflow: visible !important;
    position: relative;
}

/* Mobile responsive styles */
@media (max-width: 768px) {
    .autocomplete-dropdown-portal,
    .autocomplete-container .autocomplete-dropdown {
        max-width: calc(100vw - 20px);
        max-height: 150px;
        font-size: 14px;
    }
    
    .autocomplete-item {
        padding: 10px 8px;
        font-size: 14px;
    }
    
    .product-code {
        font-size: 11px;
    }
    
    .table-scroll-container {
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 480px) {
    .autocomplete-dropdown-portal,
    .autocomplete-container .autocomplete-dropdown {
        max-width: calc(100vw - 10px);
        max-height: 120px;
        left: 5px !important;
        right: 5px !important;
        width: auto !important;
    }
}
CSS;

$this->registerCss($autocompleteCSS);

// JavaScript สำหรับ autocomplete และ stock management
$autocompleteJs = <<<JS
// ตัวแปรเก็บข้อมูลสินค้า
var productsData = [];
var productStockData = {};
var isProductsLoaded = false;
var currentDropdown = null;

// สร้าง global dropdown portal
function createDropdownPortal() {
    if (!document.getElementById('autocomplete-portal')) {
        var portal = document.createElement('div');
        portal.id = 'autocomplete-portal';
        portal.className = 'autocomplete-dropdown-portal';
        document.body.appendChild(portal);
    }
    return document.getElementById('autocomplete-portal');
}

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

// ฟังก์ชันคำนวณตำแหน่ง dropdown - ปรับปรุงสำหรับ mobile
function calculateDropdownPosition(input) {
    var inputRect = input[0].getBoundingClientRect();
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    var viewport = {
        width: window.innerWidth || document.documentElement.clientWidth,
        height: window.innerHeight || document.documentElement.clientHeight
    };
    
    var position = {
        top: inputRect.bottom + scrollTop,
        left: inputRect.left + scrollLeft,
        width: inputRect.width
    };
    
    // ปรับสำหรับ mobile
    if (viewport.width <= 480) {
        position.left = 5;
        position.width = viewport.width - 10;
    } else if (viewport.width <= 768) {
        // ตรวจสอบว่า dropdown จะล้นขอบจอหรือไม่
        var dropdownWidth = Math.max(inputRect.width, 200);
        if (position.left + dropdownWidth > viewport.width - 20) {
            position.left = viewport.width - dropdownWidth - 20;
        }
        position.width = Math.min(dropdownWidth, viewport.width - 20);
    } else {
        // Desktop - ตรวจสอบการล้นขอบ
        var dropdownWidth = Math.max(inputRect.width, 200);
        if (position.left + dropdownWidth > viewport.width - 20) {
            position.left = viewport.width - dropdownWidth - 20;
        }
        position.width = dropdownWidth;
    }
    
    // ตรวจสอบว่า dropdown จะล้นขอบล่างหรือไม่
    var dropdownHeight = 200; // max-height
    if (position.top + dropdownHeight > viewport.height + scrollTop - 20) {
        // แสดงด้านบนของ input แทน
        position.top = inputRect.top + scrollTop - dropdownHeight - 5;
        if (position.top < scrollTop + 10) {
            // ถ้าพื้นที่ด้านบนไม่พอ ให้แสดงในตำแหน่งที่เหมาะสม
            position.top = inputRect.bottom + scrollTop;
            dropdownHeight = Math.min(200, viewport.height - (inputRect.bottom - scrollTop) - 40);
        }
    }
    
    return position;
}

// ฟังก์ชันแสดงผลลัพธ์ - ใช้ portal
function showAutocompleteResults(input, results) {
    var portal = createDropdownPortal();
    var portalElement = $(portal);
    
    // ซ่อน dropdown อื่นๆ
    hideAllDropdowns();
    
    portalElement.empty();
    
    if (results.length === 0) {
        portalElement.hide();
        return;
    }
    
    results.forEach(function(product) {
        var item = $('<div class="autocomplete-item">')
            .html('<div>' + product.code + '</div><div class="product-code">' + product.name + '</div>')
            .data('product', product)
            .data('input', input);
        portalElement.append(item);
    });
    
    // คำนวณตำแหน่ง
    var position = calculateDropdownPosition(input);
    
    portalElement.css({
        'top': position.top + 'px',
        'left': position.left + 'px',
        'width': Math.max(position.width, 200) + 'px'
    });
    
    portalElement.show();
    currentDropdown = input;
}

// ฟังก์ชันซ่อน dropdown ทั้งหมด
function hideAllDropdowns() {
    $('.autocomplete-dropdown-portal').hide();
    $('.autocomplete-dropdown').hide();
    currentDropdown = null;
}

// ฟังก์ชันซ่อน dropdown
function hideAutocomplete(delay = 200) {
    setTimeout(function() {
        hideAllDropdowns();
    }, delay);
}

// ฟังก์ชันเลือกสินค้า - ปรับปรุงใหม่
function selectProduct(input, product) {
    var container = input.closest('.autocomplete-container');
    var index = input.attr('data-index');
    
    // อัพเดตค่า
    input.val(product.display);
    container.find('.product-id-hidden').val(product.id);
    
    // โหลดข้อมูลสต็อกและอัพเดตคลังสินค้า
    loadProductStock(product.id, index);
    
    // ซ่อน dropdown
    hideAllDropdowns();
}

// ฟังก์ชันโหลดข้อมูลสต็อก (ถ้ามี)
function loadProductStock(productId, index) {
    // เพิ่มฟังก์ชันโหลดสต็อกตามต้องการ
    console.log('Loading stock for product:', productId, 'index:', index);
}

$(document).ready(function() {
    
    // โหลดข้อมูลสินค้าตอนเริ่มต้น
    loadProductsData();
    
    // Event สำหรับ autocomplete - ปรับปรุงใหม่
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
        hideAutocomplete();
    });
    
    // Event สำหรับคลิกรายการใน portal
    $(document).on('click', '.autocomplete-item', function() {
        var product = $(this).data('product');
        var input = $(this).data('input');
        selectProduct(input, product);
    });
    
    // Event navigation ด้วย keyboard - ปรับปรุงใหม่
    $(document).on('keydown', '.product-autocomplete', function(e) {
        var portal = $('#autocomplete-portal');
        var items = portal.find('.autocomplete-item');
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
                var input = highlighted.data('input');
                selectProduct(input, product);
            }
        } else if (e.keyCode === 27) { // Escape
            hideAllDropdowns();
        }
    });
    
    // ซ่อน dropdown เมื่อคลิกนอกพื้นที่
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-container').length && 
            !$(e.target).closest('.autocomplete-dropdown-portal').length) {
            hideAllDropdowns();
        }
    });
    
    // ปรับตำแหน่ง dropdown เมื่อมีการ scroll หรือ resize
    $(window).on('scroll resize', function() {
        if (currentDropdown && $('#autocomplete-portal').is(':visible')) {
            var position = calculateDropdownPosition(currentDropdown);
            $('#autocomplete-portal').css({
                'top': position.top + 'px',
                'left': position.left + 'px',
                'width': Math.max(position.width, 200) + 'px'
            });
        }
    });
    
    // ปิด dropdown เมื่อ scroll ใน table
    $('.table-scroll-container').on('scroll', function() {
        hideAllDropdowns();
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
            <?= $model->status != 3 ? Html::a('ลบ', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                    'method' => 'post',
                ],
            ]) : '' ?>
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
                <div class="table-scroll-container">
                    <table class="dynamic-width">
                        <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th>จำนวนเบิก</th>
                            <th>จำนวนคืน</th>
                            <th>กลับเข้าคลัง</th>
                            <th>คืนเป็นสินค้า</th>
                            <th>เป็นสินค้าเดิม</th>
                            <th>หมายเหตุ</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 0; ?>
                        <?php foreach ($lines as $value): ?>
                            <?php
                            if ($value->status == 1) continue; // คืนสินค้าแล้ว
                            ?>
                            <?php
                            $check_return_qty = getReturnProduct($model->id, $value->product_id, $value->qty);
                            if ($check_return_qty == 0) continue;

                            $product_can_return = getCanreturnProduct($value->product_id);
                            ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="product_id[]" value="<?= $value->product_id ?>">
                                    <input type="hidden" name="warehouse_id[]" value="<?= $value->warehouse_id ?>">
                                    <input type="hidden" name="journal_trans_line_id[]" value="<?= $value->id ?>">
                                    <input type="text" class="form-control" readonly
                                           value="<?= \backend\models\Product::findName($value->product_id) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" readonly value="<?= $value->qty ?>">
                                </td>
                                <td>
                                    <input type="number" name="return_qty[]" class="form-control"
                                           value="<?= $check_return_qty ?>" data-var="<?= $check_return_qty ?>"
                                           onchange="checkReturnQty($(this))">
                                </td>
                                <td>
                                    <select name="return_to_warehouse[]" class="form-control line-return-to-warehouse">
                                        <option value="-1">-- เลือกคลัง --</option>
                                        <?php foreach ($warehouse_data as $value_warehouse): ?>
                                            <option value="<?= $value_warehouse->id ?>"><?= $value_warehouse->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="return_to_type[]" class="form-control line-return-to-type">
                                        <?php for ($j = 0; $j <= count($product_type) - 1; $j++): ?>
                                            <option value="<?= $product_type[$j]['id'] ?>"><?= $product_type[$j]['name'] ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="table-autocomplete-cell">
                                    <div class="autocomplete-container">
                                        <input type="text"
                                               name="return_to_product_name[]"
                                               class="form-control product-autocomplete"
                                               placeholder="พิมพ์ชื่อสินค้าหรือรหัสสินค้า..."
                                               data-index="<?= $i ?>"
                                               autocomplete="off"
                                               required>
                                        <input type="hidden" name="return_to_product[]" class="product-id-hidden"
                                               data-index="<?= $i ?>">
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="return_remark[]" class="form-control line-return-remark" value="">
                                </td>
                            </tr>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <br/>
                <div style="margin-top: 15px;">
                    <button class="btn btn-success">บันทึกรายการ</button>
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
                            <select name="return_to_warehouse[]" class="form-control" required>
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
    for (let i = 0; i < select_warehouses.length; i++) {
        if (select_warehouses[i].value === '-1') {
            alert('กรุณาเลือกคลังให้ครบถ้วน');
            select_warehouses[i].focus();
            return false;
        }
    }
    
    let line_remark = document.querySelectorAll('.line-return-remark');
    
    let selects = document.querySelectorAll('.product-id-hidden');
    for (let i = 0; i < selects.length; i++) {
        if (selects[i].value === '' && line_remark[i].value === '') {
            alert('กรุณาตรวจสอบสินค้าให้ครบถ้วน');
            selects[i].closest('.autocomplete-container').find('.product-autocomplete').focus();
            return false;
        }
    }
    
    return true;
}
JS;
$this->registerJs($js, static::POS_END);
?>