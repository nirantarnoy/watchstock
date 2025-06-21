<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%stock_trans}}`.
 */
class m250620_082201_add_stock_type_id_column_to_stock_trans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%stock_trans}}', 'stock_type_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%stock_trans}}', 'stock_type_id');
    }
}
