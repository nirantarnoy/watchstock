<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "action_log".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $controller
 * @property string|null $action
 * @property string|null $query_string
 * @property string|null $data
 * @property string|null $sql_query
 * @property int|null $created_at
 */
class ActionLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'action_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'created_at'], 'integer'],
            [['query_string', 'data', 'sql_query'], 'string'],
            [['controller', 'action'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'controller' => 'Controller',
            'action' => 'Action',
            'query_string' => 'Query String',
            'data' => 'Data',
            'sql_query' => 'SQL Query',
            'created_at' => 'Created At',
        ];
    }
}
