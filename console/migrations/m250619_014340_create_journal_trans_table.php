<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%journal_trans}}`.
 */
class m250619_014340_create_journal_trans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%journal_trans}}', [
            'id' => $this->primaryKey(),
            'trans_date' => $this->datetime(),
            'journal_no' => $this->string(),
            'trans_type_id' => $this->integer(),
            'stock_type_id' => $this->integer(),
            'customer_id' => $this->integer(),
            'customer_name' => $this->string(),
            'qty' => $this->float(),
            'remark' => $this->string(),
            'status' => $this->integer(),
            'created_at' => $this->integer(),
            'created_by' => $this->integer(),
            'updated_at' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%journal_trans}}');
    }
}
