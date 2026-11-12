<?php

use yii\db\Migration;

/**
 * Handles adding description to table `{{%action_log}}`.
 */
class m261112_032000_add_description_to_action_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('action_log');
        if ($table && !isset($table->columns['description'])) {
            $this->addColumn('action_log', 'description', $this->text()->null()->after('product_name'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = $this->db->getTableSchema('action_log');
        if ($table && isset($table->columns['description'])) {
            $this->dropColumn('action_log', 'description');
        }
    }
}

