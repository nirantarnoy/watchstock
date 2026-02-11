<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%dashboard_brand_setting}}`.
 */
class m260211_021501_create_dashboard_brand_setting_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%dashboard_brand_setting}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'brand_id' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%dashboard_brand_setting}}');
    }
}
