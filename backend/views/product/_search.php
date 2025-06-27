<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\ProductSearch $model */
/** @var yii\widgets\ActiveForm $form */
$stock_empty_data = [['id' => 0, 'name' => 'ทั้งหมด'], ['id' => 1, 'name' => 'สต๊อก 0'], ['id' => 2, 'name' => 'สต๊อกมากกว่า 0']];
$stockEmptyOptions = ArrayHelper::map($stock_empty_data, 'id', 'name');
?>

<div class="product-search">

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
<!--        <div class="col-lg-2">-->
<!--            --><?php //= $form->field($model, 'product_group_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Productgroup::find()->where(['status'=>1])->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกกลุ่มสินค้า --','onchange'=>'$(this).submit()'],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
<!--        </div>-->
        <div class="col-lg-2">
            <?= $form->field($model, 'brand_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Productbrand::find()->where(['status'=>1])->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกยี่ห้อ --','onchange'=>'$(this).submit()'],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'product_type_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\helpers\ProductType::asArrayObject(),'id','name'),'options'=>['placeholder'=>'-- เลือกประเภทสินค้า --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'party_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Watchmaker::find()->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกช่าง --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'warehouse_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Warehouse::find()->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกคลังสินค้า --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
           <?= $form->field($model, 'stock_empty')->dropDownList($stockEmptyOptions,['onchange'=>'$(this).submit()'])->label(false) ?>
        </div>
        <!--        <div class="col-lg-2">-->
<!--            --><?php //= $form->field($model, 'type_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\helpers\CatType::asArrayObject(),'id','name'),'options'=>['placeholder'=>'-- เลือกสภาพสินค้า --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
<!--        </div>-->
    </div>


    <?php ActiveForm::end(); ?>

</div>
