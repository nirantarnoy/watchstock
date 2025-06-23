<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%journal_trans_line}}`.
 */
class m250623_031303_add_return_to_type_column_to_journal_trans_line_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans_line}}', 'return_to_type', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans_line}}', 'return_to_type');
    }
}
