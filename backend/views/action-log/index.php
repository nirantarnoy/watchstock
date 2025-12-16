<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\ActionLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Action Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="action-log-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'user_id',
                'value' => function($model) {
                    return $model->user_id; // You might want to link to the user model here
                }
            ],
            'controller',
            'action',
            'query_string:ntext',
            //'data:ntext',
            [
                'attribute' => 'created_at',
                'value' => function($model) {
                    return date('Y-m-d H:i:s', $model->created_at);
                }
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {delete}',
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
