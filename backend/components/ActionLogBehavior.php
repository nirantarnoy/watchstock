<?php

namespace backend\components;

use Yii;
use yii\base\ActionFilter;
use backend\models\ActionLog;

class ActionLogBehavior extends ActionFilter
{
    private $productName = null;

    public function beforeAction($action)
    {
        if (!Yii::$app->user->isGuest) {
            $this->productName = $this->captureProductName($action);
        }
        return parent::beforeAction($action);
    }

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
                $model->product_name = $this->productName;
                
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
                            $systemTables = ['migration', 'information_schema', 'performance_schema', 'mysql', 'sys', 'action_log'];
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

    private function captureProductName($action)
    {
        $controllerId = $action->controller->id;
        $actionId = $action->id;
        $productName = null;

        if ($controllerId === 'product') {
            if ($actionId === 'create') {
                $post = Yii::$app->request->post('Product');
                if (isset($post['name'])) {
                    $productName = $post['name'];
                }
            } elseif (in_array($actionId, ['update', 'delete', 'view'])) {
                $id = Yii::$app->request->get('id');
                if ($id) {
                    $product = \backend\models\Product::findOne($id);
                    if ($product) {
                        $productName = $product->name;
                    }
                }
            }
        } elseif ($controllerId === 'journaltrans') {
            // For journaltrans, we might have multiple products in lines
            $post = Yii::$app->request->post('JournalTransLine');
            if ($post && is_array($post)) {
                $names = [];
                foreach ($post as $line) {
                    if (isset($line['product_id']) && $line['product_id'] > 0) {
                        $product = \backend\models\Product::findOne($line['product_id']);
                        if ($product) {
                            $names[] = $product->name;
                        }
                    }
                }
                if (!empty($names)) {
                    $productName = implode(', ', array_unique($names));
                }
            } else {
                // If it's update/delete/view, get from the journal lines
                $id = Yii::$app->request->get('id');
                if ($id) {
                    $lines = \common\models\JournalTransLine::find()->where(['journal_trans_id' => $id])->all();
                    $names = [];
                    foreach ($lines as $line) {
                        $product = \backend\models\Product::findOne($line->product_id);
                        if ($product) {
                            $names[] = $product->name;
                        }
                    }
                    if (!empty($names)) {
                        $productName = implode(', ', array_unique($names));
                    }
                }
            }
        }

        return $productName;
    }
}
