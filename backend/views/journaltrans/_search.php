<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\PositionSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="position-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <div class="col-lg-2">
            <!--         <span class="input-group-addon" id="basic-addon1"><i class="fa fa-search"></i></span>-->
            <?= $form->field($model, 'globalSearch')->textInput(['placeholder' => 'ค้นหา', 'class' => 'form-control', 'aria-describedby' => 'basic-addon1'])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'trans_type_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\helpers\TransType::asArrayObject(),'id','name'),'options'=>['placeholder'=>'-- ประเภทรายการ --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'status')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map([
                ['id'=>'1','name' => 'Open'],
                ['id'=>'2','name' => 'Waiting'],
                ['id'=>'3','name' => 'Complete'],
                ['id'=>'4','name' => 'Cancel'],
            ],'id','name'),'options'=>['placeholder'=>'-- เลือกสถานะ --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'created_by')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\User::find()->all(),'id','username'),'options'=>['placeholder'=>'-- User --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
