<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string|null $code
 * @property string|null $name
 * @property string|null $description
 * @property int|null $status
 * @property int|null $product_group_id
 * @property int|null $unit_id
 * @property float|null $cost_price
 * @property float|null $sale_price
 * @property float|null $stock_qty
 * @property string|null $remark
 * @property string|null $photo
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'product_group_id', 'unit_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['cost_price', 'sale_price', 'stock_qty'], 'number'],
            [['code', 'name', 'description', 'remark', 'photo'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'description' => 'Description',
            'status' => 'Status',
            'product_group_id' => 'Product Group ID',
            'unit_id' => 'Unit ID',
            'cost_price' => 'Cost Price',
            'sale_price' => 'Sale Price',
            'stock_qty' => 'Stock Qty',
            'remark' => 'Remark',
            'photo' => 'Photo',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }
}
