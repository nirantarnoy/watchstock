<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%stock_trans}}`.
 */
class m250620_082052_create_stock_trans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%stock_trans}}', [
            'id' => $this->primaryKey(),
            'journal_trans_id' => $this->integer(),
            'trans_date' => $this->datetime(),
            'product_id' => $this->integer(),
            'trans_type_id' => $this->integer(),
            'qty' => $this->float(),
            'created_at' => $this->integer(),
            'created_by' => $this->integer(),
            'status' => $this->integer(),
            'remark' => $this->string(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%stock_trans}}');
    }
}
