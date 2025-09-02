<?php

use http\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\ProductSearch $model */
/** @var yii\widgets\ActiveForm $form */
$stock_empty_data = [['id' => 0, 'name' => 'ทั้งหมด'], ['id' => 1, 'name' => 'สต๊อก 0'], ['id' => 2, 'name' => 'สต๊อกมากกว่า 0']];
$stockEmptyOptions = ArrayHelper::map($stock_empty_data, 'id', 'name');

// เก็บ current parameters
$currentParams = Yii::$app->request->queryParams;
unset($currentParams['r']); // ลบ route parameter
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
        <div class="col-lg-10">
            <p>
                <?= Html::a(Yii::t('app', '<i class="fa fa-plus"></i> สร้างใหม่'), ['create'], ['class' => 'btn btn-success']) ?>
                <?= Html::a(Yii::t('app', '<i class="fa fa-download"></i> Export'), array_merge(['export-products'], $currentParams), ['class' => 'btn btn-info','data-pjax' => '0', // ปิดการทำงานของ PJAX สำหรับลิงค์นี้
                    'target' => '_blank']) ?>
            </p>
        </div>
        <div class="col-lg-2" style="text-align: right">

            <div class="form-group d-flex align-items-center">
                <label class="mr-2 mb-0">แสดง</label>
                    <select class="form-control" name="perpage" id="perpage" onchange="$(this).submit()">
                        <option value="20" <?= \Yii::$app->request->get('perpage') == '20' ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= \Yii::$app->request->get('perpage') == '50' ? 'selected' : '' ?> >50</option>
                        <option value="100" <?= \Yii::$app->request->get('perpage') == '100' ? 'selected' : '' ?>>100</option>
                    </select>
                <label class="ml-2 mb-0">รายการ</label>
                </div>
        </div>
    </div>
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
<!--        <div class="col-lg-2">-->
<!--            --><?php //= $form->field($model, 'party_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Watchmaker::find()->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกช่าง --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
<!--        </div>-->
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
