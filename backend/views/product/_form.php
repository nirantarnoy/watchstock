<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\Product $model */
/** @var yii\widgets\ActiveForm $form */
$data_warehouse = \backend\models\Warehouse::find()->all();

$yesno = [['id' => 1, 'YES'], ['id' => 0, 'NO']];

$model_warehouse_product = null;

if (!$model->isNewRecord) {
    $sql = "SELECT w.name as warehouse_name,st.qty,st.reserv_qty 
            FROM product as p 
                left join stock_sum as st on p.id = st.product_id 
                inner join warehouse as w on st.warehouse_id = w.id 
            where st.qty >= 0 and p.id = " . $model->id;

    $model_warehouse_product = Yii::$app->db->createCommand($sql)->queryAll();

}

$this->registerCss('
#preview {
      margin-top: 10px;
      max-width: 200px;
      max-height: 200px;
      border: 1px solid #ddd;
      border-radius: 8px;
      object-fit: cover;
    }
');
?>
    <!-- Flash Messages -->
<?php if (\Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= \Yii::$app->session->getFlash('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                onclick="closeAlert();"></button>
    </div>
<?php endif; ?>

<?php if (\Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= \Yii::$app->session->getFlash('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                onclick="closeAlert();"></button>
    </div>
<?php endif; ?>
    <div class="product-form">

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        <input type="hidden" class="remove-list" name="remove_list" value="">
        <input type="hidden" class="remove-customer-list" name="remove_customer_list" value="">
        <div class="row">
            <div class="col-lg-3">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'product_group_id')->widget(\kartik\select2\Select2::className(), [
                    'data' => \yii\helpers\ArrayHelper::map(\backend\models\Productgroup::find()->all(), 'id', 'name'),
                    'options' => [

                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ]
                ]) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'product_type_id')->widget(\kartik\select2\Select2::className(), [
                    'data' => \yii\helpers\ArrayHelper::map(\backend\helpers\ProductType::asArrayObject(), 'id', 'name'),
                    'options' => [
                        'placeholder' => '-- เลือกประเภทสินค้า --',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ]
                ]) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'brand_id')->widget(\kartik\select2\Select2::className(), [
                    'data' => ArrayHelper::map(\backend\models\Productbrand::find()->all(), 'id', 'name'),
                    'options' => [
                        'placeholder' => '-- เลือกยี่ห้อ --',
                    ]
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'type_id')->widget(\kartik\select2\Select2::className(), [
                    'data' => \yii\helpers\ArrayHelper::map(\backend\helpers\CatType::asArrayObject(), 'id', 'name'),
                    'options' => [
                        'placeholder' => '-- เลือกสภาพสินค้า --',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ]
                ]) ?>
            </div>
            <div class="col-lg-3">
                <?php echo $form->field($model, 'status')->widget(\toxor88\switchery\Switchery::className(), ['options' => ['label' => '', 'class' => 'form-control']])->label() ?>
            </div>
        </div>
        <div class="row">
            <?php //if (\Yii::$app->user->can('ViewCostPrice')): ?>
                <div class="col-lg-3">
                    <?= $form->field($model, 'cost_price')->textInput() ?>

                </div>
            <?php //endif; ?>
            <?php if (\Yii::$app->user->can('ViewSalePrice')): ?>
                <div class="col-lg-3">
                    <?= $form->field($model, 'sale_price')->textInput() ?>
                </div>
            <?php endif; ?>
            <?php //if (\Yii::$app->user->can('ViewCostPrice')): ?>
                <div class="col-lg-3">
                    <?= $form->field($model, 'cost_avg')->textInput(['readonly' => 'readonly','value'=>$model->cost_avg!=null?$model->cost_avg:'0']) ?>
                </div>
            <?php //endif; ?>
            <div class="col-lg-3">
                <?= $form->field($model, 'stock_qty')->textInput(['readonly' => 'readonly']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'remark')->textInput(['maxlength' => true])->label('หมายเหตุ') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <input type="hidden" name="old_photo" value="<?= $model->photo ?>">
            </div>
        </div>
        <br/>
        <div class="row">
            <div class="col-lg-6">
                <label for="">รูปภาพ</label>
                <?php if ($model->isNewRecord): ?>
                    <table style="width: 100%">
                        <tr>
                            <td style="border: 1px dashed grey;height: 250px;text-align: center;">
                                <i class="fa fa-ban fa-lg none-file-icon" style="color: grey"></i>
                                <span style="color: lightgrey" class="none-file-text">ไม่พบไฟล์แนบ</span>
                                <img id="preview" src="#" alt="Preview" style="display:none;">
                            </td>
                        </tr>
                    </table>
                <?php else: ?>
                    <table style="width: 100%">
                        <tr>
                            <?php if ($model->photo != ''): ?>
                                <td style="border: 1px dashed grey;height: 250px;text-align: center;">
                                    <a href="<?= \Yii::$app->getUrlManager()->baseUrl . '/uploads/product_photo/' . $model->photo ?>"
                                       target="_blank"><img
                                                src="<?= \Yii::$app->getUrlManager()->baseUrl . '/uploads/product_photo/' . $model->photo ?>"
                                                style="max-width: 130px;margin-top: 5px;" alt=""></a>
                                </td>
                            <?php else: ?>
                                <td style="border: 1px dashed grey;height: 250px;text-align: center;">
                                    <i class="fa fa-ban fa-lg" style="color: grey"></i>
                                    <span style="color: lightgrey">ไม่พบไฟล์แนบ</span>
                                </td>
                            <?php endif; ?>
                        </tr>
                    </table>
                <?php endif; ?>
                <input type="file" id="file" name="product_photo" class="form-control">
                <br/>
            </div>
            <div class="col-lg-6">
                <label for="">สินค้าคงเหลือ</label>
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <td>คลังจัดเก็บ</td>
                        <td>จำนวน</td>
                        <td>จำนวนยืม/จอง</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($model_warehouse_product != null): ?>
                        <?php for ($i = 0; $i <= count($model_warehouse_product) - 1; $i++): ?>
                            <?php if ((int)$model_warehouse_product[$i]['qty'] <= 0 && (int)$model_warehouse_product[$i]['reserv_qty'] <= 0) continue; ?>
                            <tr>
                                <td><?= $model_warehouse_product[$i]['warehouse_name'] ?></td>
                                <td><?= number_format($model_warehouse_product[$i]['qty'], 0) ?></td>
                                <td><?= number_format($model_warehouse_product[$i]['reserv_qty'], 0) ?></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <br/>

        <?php if (Yii::$app->user->identity->username == 'Mhee' || Yii::$app->user->identity->username == 'Tan' || Yii::$app->user->identity->username == 'mheeadmin'): ?>
            <div class="row">
                <div class="col-lg-12">
                    <?php echo $form->field($model, 'edit_stock_qty')->widget(\toxor88\switchery\Switchery::className(), ['options' => ['label' => '', 'class' => 'form-control']])->label() ?>
                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-12">
                    <h4>จัดการสต๊อกสินค้า</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <table class="table table-bordered table-striped" id="table-list">
                        <thead>
                        <tr>
                            <th style="text-align: center;">ที่จัดเก็บ</th>
                            <th style="text-align: center;">จำนวนคงเหลือ</th>
                            <th>-</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($model_line != null): ?>
                            <?php foreach ($model_line as $value): ?>
                                <tr data-var="<?= $value->id; ?>">
                                    <td>
                                        <input type="hidden" class="form-control line-rec-id" name="line_rec_id[]"
                                               value="<?= $value->id ?>">
                                        <select name="warehouse_id[]" id="" class="form-control line-warehouse-id"
                                                required>
                                            <option value="-1">--เลือก-</option>
                                            <?php foreach ($data_warehouse as $xvalue): ?>
                                                <?php
                                                $selected = '';
                                                if ($value->warehouse_id == $xvalue->id) {
                                                    $selected = 'selected';
                                                }
                                                ?>
                                                <option value="<?= $xvalue->id ?>" <?= $selected ?>><?= $xvalue->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="hidden" name="line_old_qty[]" value="<?=$value->qty?>">
                                        <input type="number" class="form-control line-qty" name="line_qty[]"
                                               value="<?= $value->qty ?>">
                                    </td>
                                    <td>
                                        <div class="btn btn-danger" onclick="removeline($(this))"><i
                                                    class="fa fa-trash"></i></div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr data-var="">
                                <td>
                                    <!--                            <input type="text" class="form-control line-warehouse-id" name="warehouse_id[]" value="">-->
                                    <input type="hidden" class="form-control line-rec-id" name="line_rec_id[]"
                                           value="0">
                                    <select name="warehouse_id[]" id="" class="form-control line-warehouse-id" required>
                                        <option value="-1">--เลือก-</option>
                                        <?php foreach ($data_warehouse as $xvalue): ?>
                                            <option value="<?= $xvalue->id ?>"><?= $xvalue->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="line_old_qty[]" value="0">
                                    <input type="number" class="form-control line-qty" name="line_qty[]" value="">
                                </td>
                                <td>
                                    <div class="btn btn-danger" onclick="removeline($(this))"><i
                                                class="fa fa-trash"></i></div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: left;">
                                <div class="btn btn-sm btn-primary" onclick="addline($(this))">เพิ่ม</div>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <br/>
        <?php else: ?>
            <?php if ($model->isNewRecord): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <h4>จัดการสต๊อกสินค้า</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <table class="table table-bordered table-striped" id="table-list">
                            <thead>
                            <tr>
                                <th style="text-align: center;">ที่จัดเก็บ</th>
                                <th style="text-align: center;">จำนวนคงเหลือ</th>
                                <th>-</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($model_line != null): ?>
                                <?php foreach ($model_line as $value): ?>

                                    <tr data-var="<?= $value->id; ?>">
                                        <td>
                                            <input type="hidden" class="form-control line-rec-id" name="line_rec_id[]"
                                                   value="<?= $value->id ?>">
                                            <select name="warehouse_id[]" id="" class="form-control line-warehouse-id"
                                                    required>
                                                <option value="-1">--เลือก-</option>
                                                <?php foreach ($data_warehouse as $xvalue): ?>
                                                    <?php
                                                    $selected = '';
                                                    if ($value->warehouse_id == $xvalue->id) {
                                                        $selected = 'selected';
                                                    }
                                                    ?>
                                                    <option value="<?= $xvalue->id ?>" <?= $selected ?>><?= $xvalue->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control line-qty" name="line_qty[]"
                                                   value="<?= $value->qty ?>">
                                        </td>
                                        <td>
                                            <div class="btn btn-danger" onclick="removeline($(this))"><i
                                                        class="fa fa-trash"></i></div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr data-var="">
                                    <td>
                                        <!--                            <input type="text" class="form-control line-warehouse-id" name="warehouse_id[]" value="">-->
                                        <input type="hidden" class="form-control line-rec-id" name="line_rec_id[]"
                                               value="0">
                                        <select name="warehouse_id[]" id="" class="form-control line-warehouse-id"
                                                required>
                                            <option value="-1">--เลือก-</option>
                                            <?php foreach ($data_warehouse as $xvalue): ?>
                                                <option value="<?= $xvalue->id ?>"><?= $xvalue->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control line-qty" name="line_qty[]" value="">
                                    </td>
                                    <td>
                                        <div class="btn btn-danger" onclick="removeline($(this))"><i
                                                    class="fa fa-trash"></i></div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: left;">
                                    <div class="btn btn-sm btn-primary" onclick="addline($(this))">เพิ่ม</div>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <br/>
            <?php endif; ?>
        <?php endif; ?>
        <br/>
        <div class="row">
            <div class="col-lg-12">
                <?php echo $form->field($model, 'transfer_warehouse_stock')->widget(\toxor88\switchery\Switchery::className(), ['options' => ['label' => '', 'class' => 'form-control']])->label() ?>
            </div>
        </div>
        <br/>
        <div class="row">
            <div class="col-lg-12">
                <h4>ย้ายที่เก็บสินค้า</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>คลังปัจจุบัน</th>
                        <th>จำนวนคงเหลือ</th>
                        <th>คลังปลายทาง</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($model_line != null): ?>
                        <?php foreach ($model_line as $value): ?>
                            <tr data-var="<?= $value->id; ?>">
                                <td>
                                    <input type="hidden" class="form-control line-rec-id" name="line_rec_id[]"
                                           value="<?= $value->id ?>">
                                    <select name="from_warehouse_id[]" id="" class="form-control line-from-warehouse-id" readonly>
                                        <?php foreach ($data_warehouse as $xvalue): ?>
                                            <?php
                                            $selected = '';
                                            if ($value->warehouse_id == $xvalue->id) {
                                                $selected = 'selected';
                                            }
                                            ?>
                                            <option value="<?= $xvalue->id ?>" <?= $selected ?>><?= $xvalue->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control line-transfer-qty" name="line_transfer_qty[]"
                                           value="<?= $value->qty ?>" readonly>
                                </td>
                                <td>
                                    <select name="to_warehouse_id[]" id="" class="form-control line-to-warehouse-id" required>
                                        <option value="0">--เลือกคลังปลายทาง--</option>
                                        <?php foreach ($data_warehouse as $xvalue): ?>
                                            <option value="<?= $xvalue->id ?>"><?= $xvalue->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <br/>
    <div class="row" style="display: nonex;">
        <form action="<?= \yii\helpers\Url::to(['product/importupdatestock'], true) ?>" method="post"
              enctype="multipart/form-data">
            <input type="file" name="file_product" class="form-control">
            <div style="height: 10px;"></div>
            <button class="btn btn-success">Import</button>
        </form>
    </div>
<?php
$js = <<<JS
var removelist = [];
var removecustomerpricelist = [];
$(function(){
  // setTimeout(function() {
  //           var alertEl = document.getElementsByClassName('alert');
  //           if (alertEl) {
  //               var alert = bootstrap.Alert.getOrCreateInstance(alertEl);
  //               alert.close();
  //           }
  //       }, 5000); // 3000 = 3 วินาที
  // $(".line-exp-date").datepicker(); 
  document.getElementById("file").addEventListener("change", function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById("preview");
            preview.src = e.target.result;
            preview.style.display = "block";
            $(".none-file-text").hide();
            $(".none-file-icon").hide();
        };
        reader.readAsDataURL(file);
    }
});
});
function addline(e){
    var tr = $("#table-list tbody tr:last");
    var clone = tr.clone();
                    //clone.find(":text").val("");
                    // clone.find("td:eq(1)").text("");
    clone.find(".line-warehouse-id").val("-1").change();
    clone.find(".line-qty").val("");
    clone.find(".line-exp-date").val("");
    clone.find(".line-rec-id").val("0");

    tr.after(clone);
     
}
function removeline(e) {
        if (confirm("ต้องการลบรายการนี้ใช่หรือไม่?")) {
            if (e.parent().parent().attr("data-var") != '') {
                removelist.push(e.parent().parent().attr("data-var"));
                $(".remove-list").val(removelist);
            }
            // alert(removelist);
            // alert(e.parent().parent().attr("data-var"));

            if ($("#table-list tbody tr").length == 1) {
                $("#table-list tbody tr").each(function () {
                    $(this).find(":text").val("");
                    $(this).find(".line-warehouse-id").val("-1").change();
                    $(this).find(".line-qty").val("");
                    $(this).find(".line-exp-date").val("");
                    $(this).find(".line-rec-id").val("0");
                });
            } else {
                e.parent().parent().remove();
            }
            // cal_linenum();
            // cal_all();
        }
}
function removecustomerpriceline(e) {
        if (confirm("ต้องการลบรายการนี้ใช่หรือไม่?")) {
            if (e.parent().parent().attr("data-var") != '') {
                removecustomerpricelist.push(e.parent().parent().attr("data-var"));
                $(".remove-customer-list").val(removecustomerpricelist);
            }
            // alert(removelist);
            // alert(e.parent().parent().attr("data-var"));

            if ($("#table-list2 tbody tr").length == 1) {
                $("#table-list2 tbody tr").each(function () {
                    $(this).find(":text").val("");
                    $(this).find(".line-product-customer-id").val("-1").change();
                    $(this).find(".line-customer-price").val("0");
                });
            } else {
                e.parent().parent().remove();
            }
            // cal_linenum();
            // cal_all();
        }
}
function addcustomerpriceline(e){
    var tr = $("#table-list2 tbody tr:last");
    var clone = tr.clone();
                    //clone.find(":text").val("");
                    // clone.find("td:eq(1)").text("");
    clone.find(".line-product-customer-id").val("-1").change();
    clone.find(".line-customer-price").val("0");

    tr.after(clone);
     
}

function closeAlert(){
    $(".alert").fadeOut();
}
JS;
$this->registerJs($js, static::POS_END);
?>