<?php

use yii\db\Migration;

class m260120_052448_add_product_name_to_action_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('action_log', 'product_name', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('action_log', 'product_name');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260120_052448_add_product_name_to_action_log_table cannot be reverted.\n";

        return false;
    }
    */
}
