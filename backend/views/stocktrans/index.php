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
        //'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'journal_no',
            'trans_date',
            [
                'attribute' => 'product_id',
                'value' => function ($data) {
                    return \backend\models\Product::findSku($data->product_id);
                }
            ],
            [
                'attribute' => 'product_id',
                'label' => 'ชื่อสินค้า',
                'value' => function ($data) {
                    return \backend\models\Product::findName($data->product_id);
                }
            ],
            [
                'attribute' => 'activity_type_id',
                'value' => function ($data) {
                    return \backend\helpers\ActivityType::getTypeById($data->activity_type_id);
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
                    return number_format($data->qty,0);
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
                'label'=>'ผู้ใช้งาน',
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
