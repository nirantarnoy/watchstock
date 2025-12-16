<?php

namespace backend\components;

use Yii;
use yii\base\ActionFilter;
use backend\models\ActionLog;

class ActionLogBehavior extends ActionFilter
{
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            // Log only if user is logged in, or you might want to log everything
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
                    $model->created_at = time();
                    $model->save(false);
                } catch (\Exception $e) {
                    // Fail silently to not disrupt the user
                    Yii::error('ActionLogBehavior error: ' . $e->getMessage());
                }
            }
            return true;
        }
        return false;
    }
}
