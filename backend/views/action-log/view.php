<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\ActionLog */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Action Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="action-log-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'user_id',
            'controller',
            'action',
            'query_string:ntext',
            'data:ntext',
            [
                'attribute' => 'created_at',
                'value' => function($model) {
                    return date('Y-m-d H:i:s', $model->created_at);
                }
            ],
        ],
    ]) ?>

</div>
