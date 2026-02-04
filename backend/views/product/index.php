<?php

use backend\models\Product;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;

use kartik\grid\GridView;
//use yii\grid\GridView;
use yii\widgets\Pjax;

//use yii\widgets\LinkPager;
use yii\bootstrap4\LinkPager;

/** @var yii\web\View $this */
/** @var backend\models\ProductSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'สินค้า';
$this->params['breadcrumbs'][] = $this->title;
?>
    <div id="loading"
         style="display:none; position: fixed; top: 0; left: 0; z-index: 9999; width: 100%; height: 100%; background-color: rgba(255,255,255,0.7); text-align: center; padding-top: 20%;">
        <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><br>กำลังโหลดข้อมูล...
    </div>
    <div class="product-index">
        <?php Pjax::begin(); ?>


        <?php echo $this->render('_search', ['model' => $searchModel, 'viewstatus' => $viewstatus]); ?>
        <div style="text-align: right; margin-bottom: 10px;">
             <a href="<?=Url::to(['product/export-check-stock'])?>" class="btn btn-info" target="_blank"><i class="fa fa-file-excel"></i> Export Check Stock</a>
        </div>
        <div id="div-delete-btn" style="padding: 10px;display: none">
            <?php echo Html::button('ลบ', [
                'class' => 'btn btn-danger',
                'id' => 'bulk-delete-btn',
                'data-url' => Url::to(['bulk-delete']),
            ]);?>

        </div>


        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            // 'filterModel' => $searchModel,
            'emptyCell' => '-',
            'layout' => "{items}\n{summary}\n<div class='text-center'>{pager}</div>",
            'summary' => "แสดง {begin} - {end} ของทั้งหมด {totalCount} รายการ",
            'showOnEmpty' => false,
            //    'bordered' => true,
            //     'striped' => false,
            //    'hover' => true,
            'responsive' => false,
            'responsiveWrap' => false,
            'id' => 'product-grid',
            'pjax' => true,

            // 'pjaxSettings' => ['neverTimeout' => true],
            //'tableOptions' => ['class' => 'table table-hover'],
            'emptyText' => '<div style="color: red;text-align: center;"> <b>ไม่พบรายการไดๆ</b> <span> เพิ่มรายการโดยการคลิกที่ปุ่ม </span><span class="text-success">"สร้างใหม่"</span></div>',
            'columns' => [
                ['class' => 'kartik\grid\CheckboxColumn'],
                [
                    'class' => 'yii\grid\SerialColumn',
                    'headerOptions' => ['style' => 'text-align:center;'],
                    'contentOptions' => ['style' => 'text-align: center'],
                ],
                [
                    'attribute' => 'photo',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'format' => 'raw',
                    'value' => function ($data) {
                        $url = Yii::$app->request->hostInfo . Yii::$app->request->baseUrl . '/uploads/product_photo/' . $data->photo;
                        return Html::a(
                            Html::img($url, ['style' => 'max-width:100px']),
                            $url,
                            [
                              'target' => '_blank',
                              'data-pjax' => '0',
                            ]
                        );
                    }
                ],
                'name',
                'description',
                [
                    'attribute' => 'sale_price',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'value' => function ($data) {
                        return number_format($data->sale_price, 0);
                    }
                ],
                [
                    'attribute' => 'stock_qty',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'format' => 'raw',
                    'value' => function ($data) {
                        return '<a href="#" data-var="'.$data->id.'" onclick="showMakerProduct($(this))">'. number_format($data->stock_qty, 0).'</a>';
                    }
                ],
                [
                    'attribute' => 'reserv_qty',
                    'label' => 'จอง/ยืม',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'value' => function ($data) {
                        $res_qty = \backend\models\Stocksum::getResQty($data->id);
                        return number_format($res_qty, 0);
                    }
                ],
                // 'product_type_id',
//                [
//                    'attribute' => 'product_group_id',
//                    'value' => function ($data) {
//                        return \backend\models\Productgroup::findName($data->product_group_id);
//                    }
//                ],
                [
                    'attribute' => 'brand_id',
                    'value' => function ($data) {
                        return \backend\models\Productbrand::findName($data->brand_id);
                    }
                ],
                [
                    'attribute' => 'warehouse_id',
                    'label' => 'คลัง',
                    'format' => 'raw',
                    'value' => function ($data) {
                      //  $namex = \backend\models\Product::getWarehouseName($data->id,$data->stock_qty);
                        $namex = \backend\models\Product::getWarehouseNames($data->id);
                        return $namex;
//                        $warehouses = [];
//                        foreach ($data->warehouse as $line) {
//                            if ($line->product) {
//                                $warehouses[] = $line->warehouse->name;
//                            }
//                        }
//                        return implode('<br>', $warehouses);
                    }
                ],

//                [
//                    'attribute' => 'product_type_id',
//                    'headerOptions' => ['style' => 'text-align: center'],
//                    'contentOptions' => ['style' => 'text-align: center'],
//                    'value' => function ($data) {
//                        return \backend\helpers\ProductType::getTypeById($data->product_type_id);
//                    }
//                ],
//                [
//                    'attribute' => 'type_id',
//                    'headerOptions' => ['style' => 'text-align: center'],
//                    'contentOptions' => ['style' => 'text-align: center'],
//                    'value' => function ($data) {
//                        return \backend\helpers\CatType::getTypeById($data->type_id);
//                    }
//                ],
                //'status',
                //'last_price',
                //'std_price',
                //'company_id',

                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'value' => function ($data) {
                        if ($data->status == 1) {
                            return '<div class="badge badge-pill badge-success" style="padding: 10px;">ใช้งาน</div>';
                        } else {
                            return '<div class="badge badge-pill badge-secondary" style="padding: 10px;">ไม่ใช้งาน</div>';
                        }
                    }
                ],
//                [
//                    'attribute' => 'stock_qty',
//                    'label' => 'คงเหลือ',
//                    'headerOptions' => ['style' => 'text-align: right'],
//                    'contentOptions' => ['style' => 'text-align: right'],
//                    'value' => function ($data) {
//                        // $qty = \backend\models\Product::getTotalQty($data->id);
//                        return number_format($data->stock_qty, 0);
//                    }
//                ],

                [

                    'header' => 'ตัวเลือก',
                    'headerOptions' => ['style' => 'text-align:center;', 'class' => 'activity-view-link',],
                    'class' => 'yii\grid\ActionColumn',
                    'contentOptions' => ['style' => 'text-align: center'],
                    'template' => '{view} {update}{delete}',
                    'buttons' => [
                        'view' => function ($url, $data, $index) {
                            $options = [
                                'title' => Yii::t('yii', 'View'),
                                'aria-label' => Yii::t('yii', 'View'),
                                'data-pjax' => '0',
                            ];
                            return Html::a(
                                '<span class="fas fa-eye btn btn-xs btn-default"></span>', $url, $options);
                        },
                        'update' => function ($url, $data, $index) {
                            $options = array_merge([
                                'title' => Yii::t('yii', 'Update'),
                                'aria-label' => Yii::t('yii', 'Update'),
                                'data-pjax' => '0',
                                'id' => 'modaledit',
                            ]);
                            return Html::a(
                                '<span class="fas fa-edit btn btn-xs btn-default"></span>', $url, [
                                'id' => 'activity-view-link',
                                //'data-toggle' => 'modal',
                                // 'data-target' => '#modal',
                                'data-id' => $index,
                                'data-pjax' => '0',
                                // 'style'=>['float'=>'rigth'],
                            ]);
                        },
                        'delete' => function ($url, $data, $index) {
                            $options = array_merge([
                                'title' => Yii::t('yii', 'Delete'),
                                'aria-label' => Yii::t('yii', 'Delete'),
                                //'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                //'data-method' => 'post',
                                //'data-pjax' => '0',
                                'data-url' => $url,
                                'data-var' => $data->id,
                                'onclick' => 'recDelete($(this));'
                            ]);
                            return Html::a('<span class="fas fa-trash-alt btn btn-xs btn-default"></span>', 'javascript:void(0)', $options);
                        }
                    ]
                ],
            ],
            'pager' => ['class' => LinkPager::className()],
        ]); ?>

        <?php Pjax::end(); ?>

    </div>

    <div id="findModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h3>รายการสินค้าอยู่กับช่าง</h3>
                </div>
                <!--            <div class="modal-body" style="white-space:nowrap;overflow-y: auto">-->
                <!--            <div class="modal-body" style="white-space:nowrap;overflow-y: auto;scrollbar-x-position: top">-->

                <div class="modal-body">
                    <input type="hidden" name="line_qc_product" class="line_qc_product" value="">
                    <table class="table table-bordered table-striped table-find-list" width="100%">
                        <thead>
                        <tr>
                            <th>ชื่อช่าง</th>
                            <th>สินค้า</th>
                            <th>จำนวน</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i
                                class="fa fa-close text-danger"></i> ปิดหน้าต่าง
                    </button>
                </div>
            </div>

        </div>
    </div>


<?php
$url_to_find_maker_product = Url::to(['product/getmakerproduct'], true);
$this->registerJs(<<<JS

function toggleDeleteButton() {
    let selected = $('#product-grid').yiiGridView('getSelectedRows');
    if (selected.length > 0) {
        $('#div-delete-btn').show();
        $("#bulk-delete-btn").text("ลบ " + selected.length + " รายการ");
    } else {
        $('#div-delete-btn').hide();
        $("#bulk-delete-btn").text("ลบ");
    }
}

// ฟังก์ชัน bind checkbox ภายใน grid
function bindCheckboxEvent() {
    $('#product-grid').off('change', 'input[name="selection[]"]').on('change', 'input[name="selection[]"]', function () {
        toggleDeleteButton();
    });

    // กรณีคลิก checkbox ทั้งหมด
    $('#product-grid').off('change', 'input[name="selection_all"]').on('change', 'input[name="selection_all"]', function () {
        setTimeout(toggleDeleteButton, 100); // ต้องหน่วงเวลาเล็กน้อยให้ checkbox อัปเดตก่อน
    });
}

// โหลดครั้งแรก
$(document).ready(function () {
    bindCheckboxEvent();
    toggleDeleteButton(); // เช็ค checkbox เดิมด้วย
});

// ตอน PJAX เริ่มโหลด
$(document).on('pjax:start', function() {
    $('#loading').fadeIn();
});

// ตอน PJAX โหลดเสร็จ
$(document).on('pjax:end', function() {
    $('#loading').fadeOut();
    bindCheckboxEvent(); // bind ใหม่หลัง PJAX
    toggleDeleteButton(); // อัปเดตสถานะปุ่มลบ
});

// ปุ่มลบหลายรายการ
// $('#bulk-delete-btn').on('click', function() {
//     var keys = $('#product-grid').yiiGridView('getSelectedRows');
//     if (keys.length === 0) {
//         alert('กรุณาเลือกรายการที่ต้องการลบ');
//         return;
//     }
//     if (confirm('คุณแน่ใจว่าต้องการลบรายการที่เลือก?')) {
//         $.post($(this).data('url'), {ids: keys}, function(response) {
//             $.pjax.reload({container: '#p0'}); // แก้ให้ตรง container ID ของคุณ
//         });
//     }
// });

// ใช้ delegation เพื่อให้รองรับ DOM ใหม่จาก PJAX
$(document).on('click', '#bulk-delete-btn', function() {
    var keys = $('#product-grid').yiiGridView('getSelectedRows');
    if (keys.length === 0) {
        alert('กรุณาเลือกรายการที่ต้องการลบ');
        return;
    }
    if (confirm('คุณแน่ใจว่าต้องการลบรายการที่เลือก?')) {
        $.post($(this).data('url'), {ids: keys}, function(response) {
            $.pjax.reload({container: '#p0'}); // หรือ container id จริงของ PJAX
        });
    }
});

JS);


$jsx = <<<JS
$(document).ready(function () {
    
});
function showMakerProduct(e){
    var ids = e.attr("data-var");
    if(ids >0 ){
     $.ajax({
              'type':'post',
              'dataType': 'html',
              'async': false,
              'url': '$url_to_find_maker_product',
              'data': {'id': ids},
              'success': function(data) {
                  //  alert(data);
                   $(".table-find-list tbody").html(data);
                   $("#findModal").modal("show");
                 }
              });   
    }
}
JS;

$this->registerJs($jsx,static::POS_END);

?>