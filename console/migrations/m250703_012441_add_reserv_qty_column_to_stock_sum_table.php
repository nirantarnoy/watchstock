<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%stock_sum}}`.
 */
class m250703_012441_add_reserv_qty_column_to_stock_sum_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%stock_sum}}', 'reserv_qty', $this->float());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%stock_sum}}', 'reserv_qty');
    }
}
