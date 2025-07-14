<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%journal_trans_line}}`.
 */
class m250714_061604_add_is_return_new_column_to_journal_trans_line_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans_line}}', 'is_return_new', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans_line}}', 'is_return_new');
    }
}
