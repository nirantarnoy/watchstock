<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%journal_trans}}`.
 */
class m250620_082618_add_party_id_column_to_journal_trans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans}}', 'party_id', $this->integer());
        $this->addColumn('{{%journal_trans}}', 'party_type_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans}}', 'party_id');
        $this->dropColumn('{{%journal_trans}}', 'party_type_id');
    }
}
