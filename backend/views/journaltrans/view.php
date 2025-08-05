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
/* Basic autocomplete styles */
.autocomplete-dropdown {
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    -webkit-overflow-scrolling: touch;
    touch-action: manipulation;
}

.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.autocomplete-item:hover,
.autocomplete-item:active {
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

/* Container styles */
.autocomplete-container {
    position: relative;
    width: 100%;
}

.autocomplete-container .form-control {
    width: 100%;
}

/* Global dropdown portal - เพิ่ม debugging styles */
.autocomplete-dropdown-portal {
    position: fixed !important;
    z-index: 999999 !important;
    background: white !important;
    border: 2px solid #007bff !important;
    max-height: 200px !important;
    overflow-y: auto !important;
    display: none !important;
    margin-top: 1px !important;
    border-radius: 4px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3) !important;
    min-width: 200px !important;
    max-width: 90vw !important;
    -webkit-overflow-scrolling: touch !important;
    touch-action: manipulation !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Table styles */
.table-scroll-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch;
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

.table-autocomplete-cell {
    overflow: visible !important;
    position: relative;
}

/* Basic page styles */
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

.table-responsive {
    overflow: visible !important;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.table-responsive .table {
    overflow: visible !important;
}

/* Mobile responsive styles */
@media (max-width: 768px) {
    .autocomplete-dropdown-portal {
        max-width: calc(100vw - 20px);
        max-height: 180px;
        font-size: 14px;
        min-width: 250px;
    }
    
    .autocomplete-item {
        padding: 14px 12px;
        font-size: 14px;
        min-height: 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        -webkit-tap-highlight-color: rgba(0, 123, 255, 0.1);
    }
    
    .product-code {
        font-size: 12px;
        margin-top: 2px;
    }
    
    .product-autocomplete {
        font-size: 16px !important;
        padding: 12px 8px !important;
        min-height: 48px;
        -webkit-appearance: none;
        border-radius: 4px;
    }
}

@media (max-width: 480px) {
    .autocomplete-dropdown-portal {
        max-width: calc(100vw - 10px);
        max-height: 200px;
        left: 5px !important;
        right: 5px !important;
        width: auto !important;
        min-width: auto;
    }
    
    .autocomplete-item {
        padding: 16px 12px;
        font-size: 15px;
        min-height: 52px;
        border-bottom: 1px solid #eee;
    }
    
    .autocomplete-item:active,
    .autocomplete-item:hover {
        background-color: #007bff !important;
        color: white !important;
    }
    
    .autocomplete-item .product-code {
        color: inherit;
        opacity: 0.8;
    }
    
    .product-autocomplete {
        font-size: 16px !important;
        padding: 16px 10px !important;
        min-height: 52px;
        -webkit-appearance: none;
        border-radius: 4px;
    }
}

/* Mobile device specific styles */
.mobile-device .autocomplete-dropdown-portal {
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
}

.mobile-device .autocomplete-item {
    -webkit-tap-highlight-color: rgba(0, 123, 255, 0.1);
    tap-highlight-color: rgba(0, 123, 255, 0.1);
}
CSS;

$this->registerCss($autocompleteCSS);

// JavaScript สำหรับ autocomplete และ stock management
$autocompleteJs = <<<JS
(function() {
    'use strict';
    
    // ตัวแปรเก็บข้อมูลสินค้า
    var productsData = [];
    var isProductsLoaded = false;
    var currentDropdown = null;
    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
    var debugMode = true;

    // ฟังก์ชันสร้าง dropdown portal
    function createDropdownPortal() {
        try {
            var existingPortal = document.getElementById('autocomplete-portal');
            if (existingPortal) {
                return existingPortal;
            }
            
            var portal = document.createElement('div');
            portal.id = 'autocomplete-portal';
            portal.className = 'autocomplete-dropdown-portal';
            
            // Set inline styles แบบทีละ property
            portal.style.position = 'fixed';
            portal.style.zIndex = '9999';
            portal.style.background = 'white';
            portal.style.border = '1px solid #ccc';
            portal.style.maxHeight = '200px';
            portal.style.overflowY = 'auto';
            portal.style.display = 'none';
            portal.style.borderRadius = '4px';
            portal.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            
            // Mobile specific styles
            if (isMobile) {
                portal.style.webkitOverflowScrolling = 'touch';
                portal.style.touchAction = 'manipulation';
            }
            
            document.body.appendChild(portal);
            
            if (debugMode) {
                console.log('Portal created successfully');
            }
            
            return portal;
        } catch (error) {
            console.error('Error creating dropdown portal:', error);
            return null;
        }
    }

    // ฟังก์ชันโหลดข้อมูลสินค้า
    function loadProductsData() {
        if (isProductsLoaded) return;
        
        try {
            if (debugMode) {
                console.log('Loading products data...');
                if (isMobile) {
                    console.log('Mobile device detected, optimizing for mobile...');
                }
            }
            
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }
            
            jQuery.ajax({
                url: '$ajax_url',
                type: 'GET',
                data: { action: 'get-all-products' },
                dataType: 'json',
                timeout: 15000,
                cache: false,
                success: function(data) {
                    try {
                        productsData = data || [];
                        isProductsLoaded = true;
                        if (debugMode) {
                            console.log('Products loaded successfully:', productsData.length, 'items');
                        }
                    } catch (error) {
                        console.error('Error processing products data:', error);
                        productsData = [];
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error loading products:', {
                        status: status,
                        error: error,
                        readyState: xhr.readyState
                    });
                    productsData = [];
                    
                    // Retry สำหรับ mobile
                    if (isMobile && status !== 'timeout') {
                        setTimeout(function() {
                            console.log('Retrying to load products data...');
                            isProductsLoaded = false;
                            loadProductsData();
                        }, 3000);
                    }
                }
            });
        } catch (error) {
            console.error('Error in loadProductsData:', error);
        }
    }

    // ฟังก์ชันค้นหาสินค้า
    function searchProducts(query) {
        try {
            if (!query || query.length < 1) return [];
            
            query = query.toLowerCase();
            var results = productsData.filter(function(product) {
                if (!product) return false;
                return (product.name && product.name.toLowerCase().indexOf(query) !== -1) || 
                       (product.code && product.code.toLowerCase().indexOf(query) !== -1) ||
                       (product.display && product.display.toLowerCase().indexOf(query) !== -1);
            }).slice(0, 10);
            
            if (debugMode) {
                console.log('Search results for "' + query + '":', results.length, 'items');
            }
            
            return results;
        } catch (error) {
            console.error('Error in searchProducts:', error);
            return [];
        }
    }

    // ฟังก์ชันคำนวณตำแหน่ง dropdown
    function calculateDropdownPosition(input) {
        try {
            var inputElement = input[0];
            if (!inputElement) {
                console.error('Input element not found');
                return null;
            }
            
            var inputRect = inputElement.getBoundingClientRect();
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            var viewport = {
                width: window.innerWidth || document.documentElement.clientWidth,
                height: window.innerHeight || document.documentElement.clientHeight
            };
            
            var position = {
                top: inputRect.bottom + scrollTop + 2,
                left: inputRect.left + scrollLeft,
                width: Math.max(inputRect.width, 200)
            };
            
            // Debug positioning
            if (debugMode) {
                console.log('Input position debug:', {
                    inputRect: inputRect,
                    scrollTop: scrollTop,
                    scrollLeft: scrollLeft,
                    viewport: viewport,
                    calculated: position
                });
            }
            
            // ปรับสำหรับ mobile
            if (viewport.width <= 480) {
                position.left = 10;
                position.width = viewport.width - 20;
            } else if (viewport.width <= 768) {
                var dropdownWidth = Math.max(inputRect.width, 250);
                if (position.left + dropdownWidth > viewport.width - 20) {
                    position.left = Math.max(10, viewport.width - dropdownWidth - 20);
                }
                position.width = Math.min(dropdownWidth, viewport.width - 40);
            } else {
                var dropdownWidth = Math.max(inputRect.width, 200);
                if (position.left + dropdownWidth > viewport.width - 20) {
                    position.left = Math.max(10, viewport.width - dropdownWidth - 20);
                }
                position.width = Math.min(dropdownWidth, viewport.width - 40);
            }
            
            // ตรวจสอบการล้นขอบล่าง
            var dropdownHeight = 200;
            if (position.top + dropdownHeight > viewport.height + scrollTop - 20) {
                // แสดงด้านบนของ input แทน
                position.top = inputRect.top + scrollTop - dropdownHeight - 5;
                if (position.top < scrollTop + 10) {
                    // ถ้าพื้นที่ด้านบนไม่พอ ให้แสดงในตำแหน่งที่เหมาะสม
                    position.top = scrollTop + 10;
                    dropdownHeight = Math.min(150, viewport.height - 60);
                }
            }
            
            // ตรวจสอบให้แน่ใจว่าไม่ติดขอบซ้าย
            if (position.left < 10) {
                position.left = 10;
            }
            
            return position;
        } catch (error) {
            console.error('Error calculating dropdown position:', error);
            return null;
        }
    }

    // ฟังก์ชันแสดงผลลัพธ์ - Force แสดงแบบเด็ดขาด
    function showAutocompleteResults(input, results) {
        try {
            if (debugMode) {
                console.log('Showing autocomplete results...');
            }
            
            // ลบ dropdown เก่าทั้งหมดก่อน
            var existingPortals = document.querySelectorAll('#autocomplete-portal, .autocomplete-dropdown-portal');
            for (var i = 0; i < existingPortals.length; i++) {
                existingPortals[i].remove();
            }
            
            if (results.length === 0) {
                if (debugMode) {
                    console.log('No results to show');
                }
                return;
            }
            
            // สร้าง dropdown ใหม่เป็น simple div
            var portal = document.createElement('div');
            portal.id = 'autocomplete-portal-' + Date.now(); // unique ID
            
            // ตั้งค่า content
            var contentHtml = '';
            for (var i = 0; i < results.length; i++) {
                var product = results[i];
                contentHtml += '<div class="autocomplete-item" style="padding:12px;border-bottom:1px solid #eee;cursor:pointer;background:white;" data-product-id="' + product.id + '" data-product-display="' + (product.display || product.name) + '">';
                contentHtml += '<div>' + (product.code || '') + '</div>';
                if (product.name) {
                    contentHtml += '<div style="color:#666;font-size:12px;">' + product.name + '</div>';
                }
                contentHtml += '</div>';
            }
            
            portal.innerHTML = contentHtml;
            
            // คำนวณตำแหน่ง
            var position = calculateDropdownPosition(input);
            if (!position) {
                console.error('Failed to calculate position');
                return;
            }
            
            // ปรับตำแหน่งให้เหมาะสม
            var finalTop = Math.max(10, position.top);
            var finalLeft = Math.max(10, position.left);
            var finalWidth = Math.min(position.width, window.innerWidth - 20);
            
            // Force styles แบบ inline
            portal.style.cssText = 
                'position: fixed !important;' +
                'top: ' + finalTop + 'px !important;' +
                'left: ' + finalLeft + 'px !important;' +
                'width: ' + finalWidth + 'px !important;' +
                'z-index: 999999 !important;' +
                'background: white !important;' +
                'border: 3px solid #ff0000 !important;' +
                'border-radius: 6px !important;' +
                'box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;' +
                'max-height: 250px !important;' +
                'overflow-y: auto !important;' +
                'display: block !important;' +
                'opacity: 1 !important;' +
                'visibility: visible !important;' +
                'pointer-events: auto !important;' +
                'transform: none !important;';
            
            // เพิ่ม debug header
            var debugHeader = '<div style="background:#ff0000;color:white;padding:8px;font-weight:bold;text-align:center;margin-bottom:5px;">🚨 DROPDOWN ACTIVE - ' + results.length + ' ITEMS 🚨</div>';
            portal.innerHTML = debugHeader + portal.innerHTML;
            
            // เพิ่มเข้า body
            document.body.appendChild(portal);
            
            // เพิ่ม event listeners
            var items = portal.querySelectorAll('.autocomplete-item');
            for (var i = 0; i < items.length; i++) {
                items[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var productId = this.getAttribute('data-product-id');
                    var productDisplay = this.getAttribute('data-product-display');
                    
                    // อัพเดตค่า
                    input.val(productDisplay);
                    input.closest('.autocomplete-container').find('.product-id-hidden').val(productId);
                    
                    // ลบ dropdown
                    portal.remove();
                    currentDropdown = null;
                    
                    if (debugMode) {
                        console.log('Product selected:', productDisplay);
                    }
                });
                
                // เพิ่ม hover effect
                items[i].addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#007bff';
                    this.style.color = 'white';
                });
                
                items[i].addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'white';
                    this.style.color = 'black';
                });
            }
            
            currentDropdown = input;
            
            if (debugMode) {
                console.log('🚨 FORCED DROPDOWN shown at:', {
                    top: finalTop,
                    left: finalLeft,
                    width: finalWidth,
                    items: results.length
                });
                console.log('🚨 Portal element:', portal);
                console.log('🚨 Portal in DOM:', document.body.contains(portal));
                console.log('🚨 Portal computed display:', window.getComputedStyle(portal).display);
                console.log('🚨 Portal computed visibility:', window.getComputedStyle(portal).visibility);
                console.log('🚨 Portal getBoundingClientRect:', portal.getBoundingClientRect());
            }
            
        } catch (error) {
            console.error('Error showing autocomplete results:', error);
        }
    }

    // ฟังก์ชันซ่อน dropdown ทั้งหมด - แก้ไขให้ครอบคลุม
    function hideAllDropdowns() {
        try {
            // ลบทุก dropdown ที่เป็นไปได้
            var existingPortals = document.querySelectorAll('[id^="autocomplete-portal"], .autocomplete-dropdown-portal, .autocomplete-dropdown');
            for (var i = 0; i < existingPortals.length; i++) {
                existingPortals[i].remove();
            }
            
            currentDropdown = null;
            
            if (debugMode) {
                console.log('All dropdowns removed:', existingPortals.length);
            }
        } catch (error) {
            console.error('Error hiding dropdowns:', error);
        }
    }

    // ฟังก์ชันเลือกสินค้า
    function selectProduct(input, product) {
        try {
            var container = input.closest('.autocomplete-container');
            
            // อัพเดตค่า
            input.val(product.display || product.name || product.code || '');
            container.find('.product-id-hidden').val(product.id || '');
            
            // ซ่อน dropdown
            hideAllDropdowns();
            
            if (debugMode) {
                console.log('Product selected:', product.display || product.name);
            }
        } catch (error) {
            console.error('Error selecting product:', error);
        }
    }

    // Document Ready
    jQuery(document).ready(function() {
        try {
            console.log('Document ready - Initializing autocomplete...');
            
            // ตรวจสอบว่าเป็น mobile หรือไม่
            if (isMobile) {
                console.log('Mobile device detected!');
                jQuery('body').addClass('mobile-device');
            }
            
            // โหลดข้อมูลสินค้าตอนเริ่มต้น
            loadProductsData();
            
            // Event สำหรับ autocomplete
            jQuery(document).on('input keyup paste', '.product-autocomplete', function(e) {
                try {
                    var input = jQuery(this);
                    var query = input.val();
                    
                    if (debugMode && isMobile) {
                        console.log('Input event triggered:', query);
                    }
                    
                    if (!isProductsLoaded) {
                        console.log('Products not loaded yet, loading...');
                        loadProductsData();
                        return;
                    }
                    
                    // เพิ่ม delay สำหรับ mobile เพื่อป้องกัน lag
                    if (isMobile) {
                        clearTimeout(input.data('timeout'));
                        input.data('timeout', setTimeout(function() {
                            if (query && query.length >= 1) {
                                var results = searchProducts(query);
                                showAutocompleteResults(input, results);
                            } else {
                                hideAllDropdowns();
                            }
                        }, 300));
                    } else {
                        if (query && query.length >= 1) {
                            var results = searchProducts(query);
                            showAutocompleteResults(input, results);
                        } else {
                            hideAllDropdowns();
                        }
                    }
                } catch (error) {
                    console.error('Error in input event:', error);
                }
            });
            
            jQuery(document).on('focus', '.product-autocomplete', function() {
                try {
                    var input = jQuery(this);
                    var query = input.val();
                    
                    if (debugMode && isMobile) {
                        console.log('Focus event triggered:', query);
                    }
                    
                    if (!isProductsLoaded) {
                        loadProductsData();
                        return;
                    }
                    
                    if (query && query.length >= 1) {
                        var results = searchProducts(query);
                        showAutocompleteResults(input, results);
                    }
                } catch (error) {
                    console.error('Error in focus event:', error);
                }
            });
            
            // เพิ่ม blur event สำหรับ mobile
            jQuery(document).on('blur', '.product-autocomplete', function() {
                try {
                    var input = jQuery(this);
                    // บน mobile ให้ delay นานขึ้นเพื่อให้มีเวลาแตะ dropdown
                    var delay = isMobile ? 500 : 200;
                    setTimeout(function() {
                        hideAllDropdowns();
                    }, delay);
                } catch (error) {
                    console.error('Error in blur event:', error);
                }
            });
            
            // Event สำหรับคลิกรายการ - รองรับ touch
            jQuery(document).on('click touchend', '.autocomplete-item', function(e) {
                try {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var product = jQuery(this).data('product');
                    var input = jQuery(this).data('input');
                    
                    if (product && input && input.length) {
                        selectProduct(input, product);
                    }
                    
                    if (debugMode) {
                        console.log('Item clicked/touched:', product ? (product.display || product.name) : 'undefined');
                    }
                } catch (error) {
                    console.error('Error in click event:', error);
                }
            });
            
            // ซ่อน dropdown เมื่อคลิกนอกพื้นที่
            jQuery(document).on('click touchstart', function(e) {
                try {
                    if (!jQuery(e.target).closest('.autocomplete-container').length && 
                        !jQuery(e.target).closest('.autocomplete-dropdown-portal').length) {
                        hideAllDropdowns();
                    }
                } catch (error) {
                    console.error('Error in document click:', error);
                }
            });
            
            // ปรับตำแหน่ง dropdown เมื่อมีการ scroll หรือ resize
            jQuery(window).on('scroll resize orientationchange', function() {
                try {
                    if (currentDropdown && jQuery('#autocomplete-portal').is(':visible')) {
                        var position = calculateDropdownPosition(currentDropdown);
                        if (position) {
                            jQuery('#autocomplete-portal').css({
                                'top': position.top + 'px',
                                'left': position.left + 'px',
                                'width': position.width + 'px'
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error in window events:', error);
                }
            });
            
            // ปิด dropdown เมื่อ scroll ใน table
            jQuery('.table-scroll-container').on('scroll', function() {
                hideAllDropdowns();
            });
            
            console.log('Autocomplete initialized successfully!');
            
        } catch (error) {
            console.error('Error initializing autocomplete:', error);
        }
    });
    
})();
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
                                               inputmode="text"
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