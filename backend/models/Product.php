<?php

namespace backend\models;

use common\models\JournalTransLine;
use Yii;
use yii\db\ActiveRecord;


date_default_timezone_set('Asia/Bangkok');

class Product extends \common\models\Product
{

    public function behaviors()
    {
        return [
            'timestampcdate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                ],
                'value' => time(),
            ],
            'timestampudate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'updated_at',
                ],
                'value' => time(),
            ],
            'timestampcby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_by',
                ],
                'value' => Yii::$app->user->id,
            ],
            'timestampcby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_by',
                ],
                'value' => Yii::$app->user->id,
            ],
//            'timestampcompany' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'company_id',
//                ],
//                'value' => isset($_SESSION['user_company_id']) ? $_SESSION['user_company_id'] : 1,
//            ],
//            'timestampbranch' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'branch_id',
//                ],
//                'value' => isset($_SESSION['user_branch_id']) ? $_SESSION['user_branch_id'] : 1,
//            ],
            'timestampupdate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => time(),
            ],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if ($this->stock_qty === null || $this->stock_qty === '') {
                    $this->stock_qty = 0;
                }
            }
            if ($this->stock_qty < 0) {
                $this->stock_qty = 0;
            }
            return true;
        }
        return false;
    }

    public function getJournaltransLine()
    {
        return $this->hasMany(JournalTransLine::class, ['product_id' => 'id']);
    }

    public function getJournalTrans()
    {
        return $this->hasMany(JournalTrans::class, ['id' => 'journal_trans_id'])
            ->via('journaltransLine');
    }

//    public function getWatchMaker()
//    {
//        return $this->hasMany(Watchmaker::class, ['id' => 'party_id'])
//            ->via('journalTrans');
//    }


    public static function findCode($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->name:'';
    }
    public static function findSku($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->name:'';
    }
    public static function findBarCode($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->barcode:'';
    }
    public static function findName($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->name.' '.$model->description:'';
    }
    public static function findPrice($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->sale_price:0;
    }
    public static function findDesc($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->description:'';
    }
    public static function findPhoto($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->photo:'';
    }

    public static function findUnitId($product_id){
        $model = Product::find()->where(['id'=>$product_id])->one();
        return $model != null ?$model->unit_id:0;
    }

    public static function findSalePrice($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->sale_price:0;
    }
    public static function findCostPrice($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->cost_price:0;
    }
    public static function findCostAvgPrice($id){
        $model = Product::find()->where(['id'=>$id])->one();
        if ($model != null) {
            return $model->cost_avg > 0 ? $model->cost_avg : $model->cost_price;
        }
        return 0;
    }

    public static function getTotalQty($id){
        $model = \backend\models\Stocksum::find()->where(['product_id'=>$id])->sum('qty');
        return $model;
    }

    public static function getWarehouseNamex($product_id,$qty){
        $name = '';
        if($product_id && $qty){
            $model = \backend\models\Stocksum::find()->where(['product_id'=>$product_id])->andFilterWhere(['>=','qty',$qty])->one();
            if($model){
                $model_warehouse = \backend\models\Warehouse::find()->where(['id'=>$model->warehouse_id])->one();
                if($model_warehouse){
                    $name = $model_warehouse->name;
                }
            }
        }
        return $name;
    }
    public static function getWarehouseName($product_id,$qty){
        $name = '';
        if($product_id && $qty){
            $model = \backend\models\Stocksum::find()->where(['product_id'=>$product_id])->andFilterWhere(['>=','qty',0])->one();
            if($model){
                $model_warehouse = \backend\models\Warehouse::find()->where(['id'=>$model->warehouse_id])->one();
                if($model_warehouse){
                    $name = $model_warehouse->name;
                }
            }
        }
        return $name;
    }

//    public static function findName($id){
//        $model = \common\models\RoutePlan::find()->where(['id'=>$id])->one();
//        return $model!= null?$model->name:'';
//    }
//    public function findUnitid($code){
//        $model = Unit::find()->where(['name'=>$code])->one();
//        return count($model)>0?$model->id:0;
//    }

  public static function getWarehouseNames($id){
        $html = '';
        if($id){
            $model = \backend\models\Stocksum::find()->where(['product_id'=>$id])->andFilterWhere(['>=','qty',0])->all();
            if($model){
                foreach ($model as $value) {
                    if($value->qty == 0) {
                        continue;
                    }
                    $model_warehouse = \backend\models\Warehouse::find()->where(['id'=>$value->warehouse_id])->one();
                    if($model_warehouse){
                        $html .= '<div class="badge badge-pill badge-info">'. $model_warehouse->name.'</div>'.'<br />';
                    }
                }
            }
            return $html;
        }
  }

  public function getStocksum(){
        return $this->hasMany(Stocksum::class, ['product_id' => 'id']);
  }

  public function getBrand(){
        return $this->hasOne(Productbrand::class, ['id' => 'brand_id']);
  }

  public static function getPhoto($id){
        $model = Product::find()->where(['id'=>$id])->one();
        return $model != null ?$model->photo:'';
  }

  public static function getPhotoJournal($journal_id){
       // $model = JournalTransLine::find()->where(['journal_trans_id'=>$journal_id])->one();
  }



    public static function recalculateCostAvg($product_id)
    {
        if (!$product_id) return 0;

        $model = Product::findOne($product_id);
        $current_cost = $model && $model->cost_price > 0 ? (float)$model->cost_price : 0;
        $current_qty = 0;

        // Replay history chronologically for Moving Average
        $sql = "SELECT jl.qty, jl.cost_price, jt.stock_type_id, jt.trans_type_id
                FROM journal_trans_line jl 
                INNER JOIN journal_trans jt ON jl.journal_trans_id = jt.id 
                WHERE jl.product_id = :product_id 
                AND jt.status != 300
                AND jt.status != 4 -- Cancelled
                ORDER BY jt.trans_date ASC, jt.id ASC";
        
        $transactions = Yii::$app->db->createCommand($sql, [':product_id' => $product_id])->queryAll();
        
        foreach ($transactions as $trans) {
            $qty = (float)$trans['qty'];
            $cost = (float)$trans['cost_price'];
            $stock_type = (int)$trans['stock_type_id'];
            $trans_type = (int)$trans['trans_type_id'];

            if ($stock_type == 1) { // IN
                // Only Receive (1) and Adjust In (10) change the moving average if cost > 0
                if (in_array($trans_type, [1, 10]) && $cost > 0) {
                    if ($current_qty <= 0) {
                        $current_cost = $cost;
                        $current_qty = $qty;
                    } else {
                        $total_value = ($current_qty * $current_cost) + ($qty * $cost);
                        $current_qty += $qty;
                        $current_cost = $total_value / $current_qty;
                    }
                } else {
                    $current_qty += $qty;
                }
            } else if ($stock_type == 2) { // OUT
                $current_qty -= $qty;
                if ($current_qty < 0) $current_qty = 0;
            }
        }
        
        if ($current_cost > 0) {
            Product::updateAll(['cost_avg' => $current_cost], ['id' => $product_id]);
        }
        
        return $current_cost;
    }

    public function getThumbnailUrl($maxWidth = 100, $maxHeight = 100)
    {
        if (empty($this->photo)) {
            return '';
        }

        $uploadPath = Yii::getAlias('@webroot') . '/uploads/product_photo/';
        $thumbPath = $uploadPath . 'thumbs/';
        $thumbUrl = Yii::$app->request->baseUrl . '/uploads/product_photo/thumbs/';

        if (!file_exists($uploadPath . $this->photo)) {
            return '';
        }

        if (!is_dir($thumbPath)) {
            if (!mkdir($thumbPath, 0777, true)) {
                return Yii::$app->request->baseUrl . '/uploads/product_photo/' . $this->photo;
            }
        }

        $thumbName = $maxWidth . 'x' . $maxHeight . '_' . $this->photo;
        $thumbFile = $thumbPath . $thumbName;

        if (!file_exists($thumbFile)) {
            $info = @getimagesize($uploadPath . $this->photo);
            if ($info) {
                $mime = $info['mime'];
                switch ($mime) {
                    case 'image/jpeg':
                        $image_create_func = 'imagecreatefromjpeg';
                        $image_save_func = 'imagejpeg';
                        break;
                    case 'image/png':
                        $image_create_func = 'imagecreatefrompng';
                        $image_save_func = 'imagepng';
                        break;
                    case 'image/gif':
                        $image_create_func = 'imagecreatefromgif';
                        $image_save_func = 'imagegif';
                        break;
                    case 'image/webp':
                        if (function_exists('imagecreatefromwebp')) {
                            $image_create_func = 'imagecreatefromwebp';
                            $image_save_func = 'imagewebp';
                        } else {
                            $image_create_func = '';
                        }
                        break;
                    default:
                        $image_create_func = '';
                        $image_save_func = '';
                }

                if ($image_create_func && function_exists($image_create_func)) {
                    $original_image = @$image_create_func($uploadPath . $this->photo);
                    if ($original_image) {
                        $original_width = imagesx($original_image);
                        $original_height = imagesy($original_image);

                        if ($original_width <= $maxWidth && $original_height <= $maxHeight) {
                            copy($uploadPath . $this->photo, $thumbFile);
                        } else {
                            $ratio = min($maxWidth / $original_width, $maxHeight / $original_height);
                            $new_width = (int)round($original_width * $ratio);
                            $new_height = (int)round($original_height * $ratio);

                            $new_image = imagecreatetruecolor($new_width, $new_height);
                            
                            if ($mime == 'image/png' || $mime == 'image/gif') {
                                imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
                                imagealphablending($new_image, false);
                                imagesavealpha($new_image, true);
                            }

                            imagecopyresampled($new_image, $original_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
                            
                            if ($mime == 'image/jpeg') {
                                @$image_save_func($new_image, $thumbFile, 80);
                            } else {
                                @$image_save_func($new_image, $thumbFile);
                            }
                            
                            imagedestroy($new_image);
                        }
                        imagedestroy($original_image);
                    } else {
                         return Yii::$app->request->baseUrl . '/uploads/product_photo/' . $this->photo;
                    }
                } else {
                     return Yii::$app->request->baseUrl . '/uploads/product_photo/' . $this->photo;
                }
            } else {
                 return Yii::$app->request->baseUrl . '/uploads/product_photo/' . $this->photo;
            }
        }

        return $thumbUrl . $thumbName;
    }
}
