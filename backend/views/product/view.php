<?php

use backend\models\StocktransSearch;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var backend\models\Product $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'สินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="product-view">

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php
    $attributes = [
        'name',
        'description',
        [
            'attribute' => 'product_group_id',
            'value' => function ($data) {
                return \backend\models\Productgroup::findName($data->product_group_id);
            }
        ],
        [
            'attribute' => 'brand_id',
            'value' => function ($data) {
                return \backend\models\Productbrand::findName($data->brand_id);
            }
        ],


        [
            'attribute' => 'status',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->status == 1 ? '<div class="badge badge-success" style="padding: 10px;">ใช้งาน</div>' : '<div class="badge badge-secondary">ไม่ใช้งาน</div>';
            }
        ],
        [
            'attribute' => 'created_at',
            'format' => ['date', 'php:d-m-Y H:i:s'],
        ],
        [
            'attribute' => 'created_by',
            'value' => function ($model) {
                return \backend\models\User::findName($model->created_by);
            }
        ],
        [
            'attribute' => 'updated_at',
            'format' => ['date', 'php:d-m-Y H:i:s'],
        ],
        [
            'attribute' => 'updated_by',
            'value' => function ($model) {
                return \backend\models\User::findName($model->updated_by);
            }
        ],
    ];

    $attributes2 = [
        [
            'attribute' => 'product_type_id',
            'value' => function ($data) {
                return \backend\helpers\ProductType::getTypeById($data->product_type_id);
            }
        ],
        [
            'attribute' => 'type_id',
            'value' => function ($data) {
                return \backend\helpers\CatType::getTypeById($data->type_id);
            }
        ],
    ];
    if (\Yii::$app->user->can('ViewCostPrice')) {
        $attributes2[] = [
            'attribute' => 'cost_price',
            'value' => function ($model) {
                return number_format($model->cost_price, 2);
            }
        ];
    }
    if (\Yii::$app->user->can('ViewSalePrice')) {
        $attributes2[] = [
            'attribute' => 'sale_price',
            'value' => function ($model) {
                return number_format($model->sale_price, 2);
            }
        ];
    }

    ?>
    <div class="row">
        <div class="col-lg-6">
            <?= DetailView::widget(['model' => $model,
                'attributes' => $attributes,]) ?>
        </div>
        <div class="col-lg-6">
            <?= DetailView::widget(['model' => $model,
                'attributes' => $attributes2,]) ?>
        </div>
    </div>
    <br/>
    <?php
    $searchModel = new StocktransSearch();
    $dataProvider = $searchModel->search([]);
    $dataProvider->query->where(['product_id' => $model->id]);
    $dataProvider->setSort(['defaultOrder' => ['id' => SORT_DESC]]);
    ?>
    <h5><b>ประวัติการทำรายการ</b></h5>
    <div class="row">
        <div class="col-lg-12">
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
                'emptyText' => '<div style="color: red;text-align: center;"> <b>ไม่พบรายการไดๆ</b></div>',
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
        </div>
    </div>


</div>
