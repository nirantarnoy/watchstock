<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\JournalTrans */
/* @var $lines app\models\JournalTransLine[] */

$this->title = $model->journal_no;
$this->params['breadcrumbs'][] = ['label' => 'รายการ Stock Transaction', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
?>
<div class="journal-trans-view">

    <p>
        <?= Html::a('แก้ไข', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('ลบ', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                'method' => 'post',
            ],
        ]) ?>
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
                        'format' => ['datetime', 'php:d/m/Y H:i:s'],
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
                    'customer_id',
                    'customer_name',
                    [
                        'attribute' => 'qty',
                        'format' => ['decimal', 2],
                    ],
                    'remark',
                    [
                        'attribute' => 'status',
                        'value' => function ($model) {
                            $statusName = $model->getStatusName();
                            $class = '';
                            switch ($model->status) {
                                case $model::STATUS_ACTIVE:
                                    $class = 'label-success';
                                    break;
                                case $model::STATUS_DRAFT:
                                    $class = 'label-warning';
                                    break;
                                case $model::STATUS_CANCELLED:
                                    $class = 'label-danger';
                                    break;
                            }
                            return Html::tag('span', $statusName, ['class' => 'label ' . $class]);
                        },
                        'format' => 'raw',
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
                'contentOptions' => ['style' => 'text-align:right'],
            ],
            'remark',
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return $model->getStatusName();
                },
            ],
        ],
    ]); ?>

    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <p><strong>สร้างโดย:</strong> <?= \backend\models\User::findName($model->created_by) ?>
                        เมื่อ <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>
                    <?php if ($model->updated_at): ?>
                        <p><strong>แก้ไขโดย:</strong> <?= $model->updated_by ?>
                            เมื่อ <?= Yii::$app->formatter->asDatetime($model->updated_at) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>