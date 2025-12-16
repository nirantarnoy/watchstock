<?php

namespace backend\components;

use Yii;
use yii\base\ActionFilter;
use backend\models\ActionLog;

class ActionLogBehavior extends ActionFilter
{
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        // Log only if user is logged in
        if (!Yii::$app->user->isGuest) {
            try {
                $model = new ActionLog();
                $model->user_id = Yii::$app->user->id;
                $model->controller = $action->controller->id;
                $model->action = $action->id;
                $model->query_string = Yii::$app->request->queryString;
                
                $data = Yii::$app->request->post();
                // Remove CSRF token to save space
                if (isset($data['_csrf-backend'])) {
                    unset($data['_csrf-backend']);
                }
                
                // Mask password fields
                array_walk_recursive($data, function(&$v, $k) {
                    if (is_string($k) && stripos($k, 'password') !== false) {
                        $v = '***';
                    }
                });

                $model->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                
                // Capture SQL queries
                $messages = Yii::getLogger()->messages;
                $sqlQueries = [];
                foreach ($messages as $message) {
                    if (isset($message[2]) && ($message[2] === 'yii\db\Command::query' || $message[2] === 'yii\db\Command::execute')) {
                        $sql = $message[0];
                        $trimmedSql = trim($sql);
                        // Filter INSERT, UPDATE, DELETE, SELECT
                        if (preg_match('/^(INSERT|UPDATE|DELETE|SELECT)\s/i', $trimmedSql)) {
                            // Exclude system tables
                            $isSystem = false;
                            $systemTables = ['migration', 'information_schema', 'performance_schema', 'mysql', 'sys'];
                            foreach ($systemTables as $table) {
                                if (stripos($trimmedSql, $table) !== false) {
                                    $isSystem = true;
                                    break;
                                }
                            }
                            
                            if (!$isSystem) {
                                $sqlQueries[] = $sql;
                            }
                        }
                    }
                }
                if (!empty($sqlQueries)) {
                    $sqlQueries = array_unique($sqlQueries);
                    $model->sql_query = implode("\n\n", $sqlQueries);
                }

                $model->created_at = time();
                $model->save(false);
            } catch (\Exception $e) {
                // Fail silently to not disrupt the user
                Yii::error('ActionLogBehavior error: ' . $e->getMessage());
            }
        }
        
        return $result;
    }
}
