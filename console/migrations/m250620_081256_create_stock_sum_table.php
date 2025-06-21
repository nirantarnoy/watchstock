<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%stock_sum}}`.
 */
class m250620_081256_create_stock_sum_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%stock_sum}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'warehouse_id' => $this->integer(),
            'qty' => $this->float(),
            'updated_at' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%stock_sum}}');
    }
}
