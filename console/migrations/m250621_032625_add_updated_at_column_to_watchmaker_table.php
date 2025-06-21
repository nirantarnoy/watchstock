<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%watchmaker}}`.
 */
class m250621_032625_add_updated_at_column_to_watchmaker_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%watchmaker}}', 'updated_at', $this->integer());
        $this->addColumn('{{%watchmaker}}', 'updated_by', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%watchmaker}}', 'updated_at');
        $this->dropColumn('{{%watchmaker}}', 'updated_by');
    }
}
