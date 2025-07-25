<?php

use yii\helpers\Html;
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use common\models\JournalTrans;
use yii\widgets\LinkPager;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel common\models\JournalTransSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Stock Transaction';
$this->params['breadcrumbs'][] = $this->title;

//echo date_default_timezone_get();  // ควรได้ Asia/Bangkok
//echo date('Y-m-d H:i:s');          // ควรเป็นเวลาปัจจุบันประเทศไทย

?>
<div id="loading"
     style="display:none; position: fixed; top: 0; left: 0; z-index: 9999; width: 100%; height: 100%; background-color: rgba(255,255,255,0.7); text-align: center; padding-top: 20%;">
    <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><br>กำลังโหลดข้อมูล...
</div>
<div class="journal-trans-index">
   <div class="row">
       <div class="col-lg-10">
           <p>
               <?= Html::a('<i class="fa fa-plus"></i> บันทึกขาย', ['create', 'type' => 3], ['class' => 'btn btn-success']) ?>
               <?= Html::a('<i class="fa fa-archive"></i> บันทึกยืมสินค้า', ['create', 'type' => 5], ['class' => 'btn btn-info']) ?>
               <?= Html::a('<i class="fa fa-wrench"></i> บันทึกส่งช่าง', ['create', 'type' => 7], ['class' => 'btn btn-primary']) ?>
               <?= Html::a('<i class="fa fa-shopping-cart"></i> บันทึกขาย Drop Ship', ['create', 'type' => 9], ['class' => 'btn btn-secondary']) ?>
               <?= Html::a('<i class="fa fa-file-import"></i> บันทึกรับยอดสินค้าเข้าคลัง', ['create', 'type' => 10], ['class' => 'btn btn-warning']) ?>
           </p>

       </div>
       <div class="col-lg-2" style="text-align: right">
           <form id="form-perpage" class="form-inline" action="<?= Url::to(['journaltrans/index'], true) ?>"
                 method="post">
               <div class="form-group">
                   <label>แสดง </label>
                   <select class="form-control" name="perpage" id="perpage">
                       <option value="20" <?= $perpage == '20' ? 'selected' : '' ?>>20</option>
                       <option value="50" <?= $perpage == '50' ? 'selected' : '' ?> >50</option>
                       <option value="100" <?= $perpage == '100' ? 'selected' : '' ?>>100</option>
                   </select>
                   <label> รายการ</label>
               </div>
           </form>
       </div>
   </div>

    <?php Pjax::begin(); ?>
    <?php  echo $this->render('_search', ['model' => $searchModel]); ?>

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
        'id' => 'product-grid',
        'pjax' => true,
        'pjaxSettings' => ['neverTimeout' => true],
        'responsive' => false,
        'responsiveWrap' => false,
        //'tableOptions' => ['class' => 'table table-hover'],
        'emptyText' => '<div style="color: red;text-align: center;"> <b>ไม่พบรายการไดๆ</b> <span> เพิ่มรายการโดยการคลิกที่ปุ่ม </span><span class="text-success">"สร้างใหม่"</span></div>',
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['style' => 'text-align: center;width: 5%'],
                'contentOptions' => ['style' => 'text-align: center'],
            ],

            [
                'attribute' => 'journal_no',
               // 'headerOptions' => ['style' => 'width:150px'],
            ],
            [
                'attribute' => 'trans_date',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
               // 'format' => ['datetime', 'php:d/m/Y H:i'],
                'value' => function($model) {
                    return date('d/m/Y H:i:s', strtotime($model->trans_date));
                }
              //  'headerOptions' => ['style' => 'width:150px'],
            ],
            [
                'attribute' => 'trans_type_id',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'format' => 'html',
                'value' => function($model) {
                    $trans_type_name = $model->getTransactionTypeName();
                    $htmel_status = getBadgeType($model->trans_type_id,$trans_type_name);
                    return $htmel_status;
                },
               // 'filter' => JournalTrans::getTransactionTypeList(),
               // 'headerOptions' => ['style' => 'width:120px'],
            ],
            'customer_name',
            [
                'attribute' => 'party_id',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'value'=>function($model) {
                    return \backend\models\Watchmaker::findName($model->party_id);
                }
            ],
            [
                'attribute' => 'product_id',
                'format' => 'raw',
                'label' => 'สินค้า',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'value'=>function($model) {
                    $products = [];
                    foreach ($model->journalTransLines as $line) {
                        if ($line->product) {
                            $products[] = $line->product->name;
                        }
                    }
                    return implode('<br>', $products);
                }
            ],
            [
                'attribute' => 'qty',
                'format' => ['decimal', 0],
                'headerOptions' => ['style' => 'text-align:right'],
                'contentOptions' => ['style' => 'text-align:right'],
                'value' => function($model) {
                    return \backend\models\JournalTrans::getLineQty($model->id);
                },
            ],
//            [
//                'attribute' => 'amount',
//                'format' => ['decimal', 2],
//                'headerOptions' => ['style' => 'text-align:right'],
//                'contentOptions' => ['style' => 'text-align:right'],
//                'value' => function($model) {
//                    return number_format($model->amount, 2);
//                },
//            ],
            [
                'attribute' => 'status',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'format' => 'raw',
                'value' => function($model) {
                    $status_name = \backend\helpers\TransStatusType::getTypeById($model->status);
                    $htmel_status = getBadgeStatus($model->status,$status_name);
                    return $htmel_status;
                },
               // 'format' => 'raw',
               // 'filter' => JournalTrans::getStatusList(),
                //'headerOptions' => ['style' => 'width:100px'],
            ],

            [

                'header' => 'ตัวเลือก',
                'headerOptions' => ['style' => 'text-align:center;width:10%', 'class' => 'activity-view-link',],
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
                        return $data->status ==1? Html::a('<span class="fas fa-trash-alt btn btn-xs btn-default"></span>', 'javascript:void(0)', $options):'';
                    }
                ]
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?php
function getBadgeType($status,$status_name) {
    if ($status == 1) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 2) {
        return '<span class="badge badge-pill badge-warning" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 3) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 4) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 5) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 6) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 7) {
        return '<span class="badge badge-pill badge-primary" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 8) {
        return '<span class="badge badge-pill badge-primary" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 9) {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }else {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }
}

function getBadgeStatus($status,$status_name) {
    if ($status == 1) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 2) {
        return '<span class="badge badge-pill badge-warning" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 3) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 4) {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }
}
?>

<?php
$this->registerJs(<<<JS
        $(document).on('pjax:start', function() {
            $('#loading').fadeIn();
        });
        $(document).on('pjax:end', function() {
           $('#loading').fadeOut();
        });
        JS
);
?>
