<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%journal_trans_line}}`.
 */
class m250707_141518_add_journal_trans_ref_id_column_to_journal_trans_line_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans_line}}', 'journal_trans_ref_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans_line}}', 'journal_trans_ref_id');
    }
}
