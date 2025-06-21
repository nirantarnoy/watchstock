<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%stock_trans}}`.
 */
class m250621_052435_add_line_price_column_to_stock_trans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%stock_trans}}', 'line_price', $this->float());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%stock_trans}}', 'line_price');
    }
}
