<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "journal_trans".
 *
 * @property int $id
 * @property string|null $trans_date
 * @property string|null $journal_no
 * @property int|null $trans_type_id
 * @property int|null $stock_type_id
 * @property int|null $customer_id
 * @property string|null $customer_name
 * @property float|null $qty
 * @property string|null $remark
 * @property int|null $status
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 */
class JournalTrans extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journal_trans';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['trans_date'], 'safe'],
            [['trans_type_id', 'stock_type_id', 'customer_id', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['qty'], 'number'],
            [['journal_no', 'customer_name', 'remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'trans_date' => 'Trans Date',
            'journal_no' => 'Journal No',
            'trans_type_id' => 'Trans Type ID',
            'stock_type_id' => 'Stock Type ID',
            'customer_id' => 'Customer ID',
            'customer_name' => 'Customer Name',
            'qty' => 'Qty',
            'remark' => 'Remark',
            'status' => 'Status',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }
}
