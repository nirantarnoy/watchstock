<?php

use yii\db\Migration;

/**
 * Class m251216_173000_add_sql_query_to_action_log_table
 */
class m251216_173000_add_sql_query_to_action_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%action_log}}', 'sql_query', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%action_log}}', 'sql_query');
    }
}
