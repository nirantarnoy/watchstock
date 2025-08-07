<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\JournalTrans */
/* @var $modelLines app\models\JournalTransLine[] */

$this->title = 'สร้างรายการ Stock Transaction';
$this->params['breadcrumbs'][] = ['label' => 'รายการ Stock Transaction', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="journal-trans-create">

    <?php if($create_type !=9):?>
    <?= $this->render('_form', [
        'model' => $model,
        'modelLines' => $modelLines,
        'create_type'=>$create_type,
    ]) ?>
    <?php else:?>
    <?= $this->render('_form_drop', [
        'model' => $model,
        'modelLines' => $modelLines,
        'create_type'=>$create_type,
    ]) ?>
    <?php endif; ?>

</div>