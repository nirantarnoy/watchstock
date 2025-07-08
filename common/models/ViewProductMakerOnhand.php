<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "view_product_maker_onhand".
 *
 * @property int $product_id
 * @property string|null $product_name
 * @property string|null $description
 * @property int|null $warehouse_id
 * @property float|null $qty
 * @property string|null $warehouse_name
 * @property string|null $journal_no
 * @property string|null $trans_date
 * @property int|null $party_id
 * @property string|null $watchmaker_name
 * @property int|null $status
 */
class ViewProductMakerOnhand extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_product_maker_onhand';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_name', 'description', 'warehouse_id', 'qty', 'warehouse_name', 'journal_no', 'trans_date', 'party_id', 'watchmaker_name', 'status'], 'default', 'value' => null],
            [['product_id'], 'default', 'value' => 0],
            [['product_id', 'warehouse_id', 'party_id', 'status'], 'integer'],
            [['qty'], 'number'],
            [['trans_date'], 'safe'],
            [['product_name', 'description', 'warehouse_name', 'journal_no', 'watchmaker_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'product_name' => 'Product Name',
            'description' => 'Description',
            'warehouse_id' => 'Warehouse ID',
            'qty' => 'Qty',
            'warehouse_name' => 'Warehouse Name',
            'journal_no' => 'Journal No',
            'trans_date' => 'Trans Date',
            'party_id' => 'Party ID',
            'watchmaker_name' => 'Watchmaker Name',
            'status' => 'Status',
        ];
    }

}
