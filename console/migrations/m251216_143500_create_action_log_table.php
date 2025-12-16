<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%action_log}}`.
 */
class m251216_143500_create_action_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%action_log}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'controller' => $this->string(),
            'action' => $this->string(),
            'query_string' => $this->text(),
            'data' => $this->text(),
            'created_at' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%action_log}}');
    }
}
