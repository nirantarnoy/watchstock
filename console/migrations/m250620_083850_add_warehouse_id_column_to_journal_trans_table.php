<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%journal_trans}}`.
 */
class m250620_083850_add_warehouse_id_column_to_journal_trans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans}}', 'warehouse_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans}}', 'warehouse_id');
    }
}
