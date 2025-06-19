<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "journal_trans_line".
 *
 * @property int $id
 * @property int|null $journal_trans_id
 * @property int|null $product_id
 * @property int|null $warehouse_id
 * @property float|null $qty
 * @property string|null $remark
 * @property int|null $status
 */
class JournalTransLine extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journal_trans_line';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['journal_trans_id', 'product_id', 'warehouse_id', 'status'], 'integer'],
            [['qty'], 'number'],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'journal_trans_id' => 'Journal Trans ID',
            'product_id' => 'Product ID',
            'warehouse_id' => 'Warehouse ID',
            'qty' => 'Qty',
            'remark' => 'Remark',
            'status' => 'Status',
        ];
    }
}
