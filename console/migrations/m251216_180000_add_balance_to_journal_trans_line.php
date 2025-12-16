<?php

use yii\db\Migration;

/**
 * Class m251216_180000_add_balance_to_journal_trans_line
 */
class m251216_180000_add_balance_to_journal_trans_line extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%journal_trans_line}}', 'balance', $this->decimal(10, 2)->defaultValue(0)->comment('ยอดคงเหลือหลังทำรายการ'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%journal_trans_line}}', 'balance');
    }
}
