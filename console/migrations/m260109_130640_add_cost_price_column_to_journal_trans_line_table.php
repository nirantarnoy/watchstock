<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%journal_trans_line}}`.
 */
class m260109_130640_add_cost_price_column_to_journal_trans_line_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans_line}}', 'cost_price', $this->decimal());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans_line}}', 'cost_price');
    }
}
