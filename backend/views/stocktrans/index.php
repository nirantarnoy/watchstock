<?php

use backend\models\Stocktrans;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;

//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var backend\models\StocktransSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'ประวัติการทำรายการ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="stocktrans-index">

    <?php Pjax::begin(); ?>
    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

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
        //'tableOptions' => ['class' => 'table table-hover'],
        'emptyText' => '<div style="color: red;text-align: center;"> <b>ไม่พบรายการไดๆ</b> <span> เพิ่มรายการโดยการคลิกที่ปุ่ม </span><span class="text-success">"สร้างใหม่"</span></div>',
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['style' => 'text-align: center'],
                'contentOptions' => ['style' => 'text-align: center'],
            ],

            [
                'attribute' => 'journal_no',
                'label' => 'เลขที่เอกสาร',
                'value' => function ($data) {
                    return \backend\models\JournalTrans::findJournalNoFromStockTransId($data->journal_trans_id);
                }
            ],
            [
                'attribute' => 'trans_date',
                'value' => function ($data) {
                    return date('d/m/Y H:i:s', strtotime($data->trans_date));
                }
            ],
            [
                'attribute' => 'product_id',
                'value' => function ($data) {
                    return \backend\models\Product::findName($data->product_id);
                }
            ],
            [
                'attribute' => 'warehouse_id',
                'value' => function ($data) {
                    return \backend\models\Warehouse::findName($data->warehouse_id);
                }
            ],
            [
                'attribute' => 'qty',
                'headerOptions' => ['style' => 'text-align: right'],
                'contentOptions' => ['style' => 'text-align: right'],
                'value' => function ($data) {
                    return number_format($data->qty, 0);
                }
            ],
            [
                'attribute' => 'stock_type_id',
                'headerOptions' => ['style' => 'text-align: center'],
                'contentOptions' => ['style' => 'text-align: center'],
                'format' => 'html',
                'value' => function ($data) {
                    if ($data->stock_type_id == 1) {
                        return '<div class="btn btn-sm btn-success" style="text-align: center;">IN</div>';
                    } else if ($data->stock_type_id == 2) {
                        return '<div class="btn btn-sm btn-danger" style="text-align: center;">OUT</div>';
                    }
                }
            ],
            [
                'attribute' => 'created_by',
                'headerOptions' => ['style' => 'text-align: center'],
                'contentOptions' => ['style' => 'text-align: center'],
                'label' => 'ผู้ทํารายการ',
                'value' => function ($data) {
                    return \backend\models\User::findName($data->created_by);
                }
            ],
            //'created_at',
            //'stock_type_id',
        ],
        'pager' => ['class' => LinkPager::className()],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
