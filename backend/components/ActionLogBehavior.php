<?php

namespace backend\components;

use Yii;
use yii\base\ActionFilter;
use backend\models\ActionLog;

class ActionLogBehavior extends ActionFilter
{
    private $productName = null;
    private $capturedContext = [];

    public function beforeAction($action)
    {
        if (!Yii::$app->user->isGuest) {
            $this->productName = $this->captureProductName($action);
            $this->capturedContext = $this->captureContext($action);
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
                $model->description = $this->generateHumanDescription($action);
                
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

    private function captureContext($action)
    {
        $controllerId = $action->controller->id;
        $actionId = $action->id;
        $id = Yii::$app->request->get('id') ?: Yii::$app->request->post('id');
        $context = [
            'id' => $id,
            'code' => null,
            'name' => null,
            'extra' => null,
        ];

        try {
            switch ($controllerId) {
                case 'product':
                    if ($id) {
                        $product = \backend\models\Product::findOne($id);
                        if ($product) {
                            $context['code'] = $product->code;
                            $context['name'] = $product->name;
                        }
                    } elseif ($actionId === 'create') {
                        $post = Yii::$app->request->post('Product');
                        if ($post) {
                            $context['code'] = $post['code'] ?? null;
                            $context['name'] = $post['name'] ?? null;
                        }
                    }
                    break;

                case 'journaltrans':
                    if ($id) {
                        $journal = \backend\models\JournalTrans::findOne($id);
                        if ($journal) {
                            $context['code'] = $journal->journal_no;
                            $context['name'] = $journal->getTransactionTypeName();
                            
                            $lines = \common\models\JournalTransLine::find()->where(['journal_trans_id' => $id])->all();
                            $pnames = [];
                            foreach ($lines as $line) {
                                $p = \backend\models\Product::findOne($line->product_id);
                                if ($p) {
                                    $pnames[] = $p->code ? "{$p->code} ({$p->name})" : $p->name;
                                }
                            }
                            if (!empty($pnames)) {
                                $context['extra'] = implode(', ', array_unique($pnames));
                            }
                        }
                    } elseif ($actionId === 'create') {
                        $type = Yii::$app->request->get('type') ?: (Yii::$app->request->post('JournalTrans')['trans_type_id'] ?? null);
                        $typeName = '';
                        if ($type) {
                            $list = \common\models\JournalTrans::getTransactionTypeList();
                            $typeName = $list[$type] ?? \backend\helpers\TransType::getTypeById($type);
                        }
                        $context['name'] = $typeName;

                        $postLines = Yii::$app->request->post('JournalTransLine');
                        if ($postLines && is_array($postLines)) {
                            $pnames = [];
                            foreach ($postLines as $line) {
                                if (!empty($line['product_id'])) {
                                    $p = \backend\models\Product::findOne($line['product_id']);
                                    if ($p) {
                                        $pnames[] = $p->code ? "{$p->code} ({$p->name})" : $p->name;
                                    }
                                }
                            }
                            if (!empty($pnames)) {
                                $context['extra'] = implode(', ', array_unique($pnames));
                            }
                        }
                    }
                    break;

                case 'warehouse':
                    if ($id) {
                        $wh = \backend\models\Warehouse::findOne($id);
                        if ($wh) $context['name'] = $wh->name;
                    } elseif ($post = Yii::$app->request->post('Warehouse')) {
                        $context['name'] = $post['name'] ?? null;
                    }
                    break;

                case 'watchmaker':
                    if ($id) {
                        $wm = \backend\models\Watchmaker::findOne($id);
                        if ($wm) $context['name'] = $wm->name;
                    } elseif ($post = Yii::$app->request->post('Watchmaker')) {
                        $context['name'] = $post['name'] ?? null;
                    }
                    break;

                case 'productbrand':
                    if ($id) {
                        $b = \backend\models\Productbrand::findOne($id);
                        if ($b) $context['name'] = $b->name;
                    } elseif ($post = Yii::$app->request->post('Productbrand')) {
                        $context['name'] = $post['name'] ?? null;
                    }
                    break;

                case 'productgroup':
                    if ($id) {
                        $g = \backend\models\Productgroup::findOne($id);
                        if ($g) $context['name'] = $g->name;
                    } elseif ($post = Yii::$app->request->post('Productgroup')) {
                        $context['name'] = $post['name'] ?? null;
                    }
                    break;

                case 'unit':
                    if ($id) {
                        $u = \backend\models\Unit::findOne($id);
                        if ($u) $context['name'] = $u->name;
                    } elseif ($post = Yii::$app->request->post('Unit')) {
                        $context['name'] = $post['name'] ?? null;
                    }
                    break;

                case 'user':
                    if ($id) {
                        $u = \backend\models\User::findOne($id);
                        if ($u) $context['name'] = $u->username;
                    } elseif ($post = Yii::$app->request->post('User')) {
                        $context['name'] = $post['username'] ?? null;
                    }
                    break;

                case 'employee':
                    if ($id) {
                        $e = \backend\models\Employee::findOne($id);
                        if ($e) $context['name'] = trim(($e->fname ?? '') . ' ' . ($e->lname ?? ''));
                    } elseif ($post = Yii::$app->request->post('Employee')) {
                        $context['name'] = trim(($post['fname'] ?? '') . ' ' . ($post['lname'] ?? ''));
                    }
                    break;

                case 'position':
                    if ($id) {
                        $pos = \backend\models\Position::findOne($id);
                        if ($pos) $context['name'] = $pos->name;
                    } elseif ($post = Yii::$app->request->post('Position')) {
                        $context['name'] = $post['name'] ?? null;
                    }
                    break;
            }
        } catch (\Exception $e) {
            // ignore
        }

        return $context;
    }

    private function generateHumanDescription($action)
    {
        $controllerId = $action->controller->id;
        $actionId = $action->id;
        $isPost = Yii::$app->request->isPost;

        // User name
        $user = Yii::$app->user->identity;
        $userName = $user ? ($user->username ?: \backend\models\User::findName(Yii::$app->user->id)) : 'ผู้ใช้งาน';

        // Thai Date & Time (e.g. 12/11/2026 เวลา 10:15:30 น.)
        $dateTimeStr = 'เมื่อวันที่ ' . date('d/m/Y') . ' เวลา ' . date('H:i:s') . ' น.';

        $context = $this->capturedContext;
        $code = $context['code'] ?? null;
        $name = $context['name'] ?? $this->productName;
        $extra = $context['extra'] ?? null;

        // If product controller
        if ($controllerId === 'product') {
            $productLabel = '';
            if ($code && $name) {
                $productLabel = 'รหัส "' . $code . '" และชื่อสินค้า "' . $name . '"';
            } elseif ($code) {
                $productLabel = 'รหัส "' . $code . '"';
            } elseif ($name) {
                $productLabel = 'ชื่อสินค้า "' . $name . '"';
            } elseif (!empty($context['id'])) {
                $productLabel = 'ID: ' . $context['id'];
            }

            if ($actionId === 'delete') {
                return "ผู้ใช้ {$userName} ทำการลบข้อมูลสินค้า {$productLabel} {$dateTimeStr}";
            } elseif ($actionId === 'update') {
                if ($isPost) {
                    return "ผู้ใช้ {$userName} ทำการแก้ไขข้อมูลสินค้า {$productLabel} {$dateTimeStr}";
                } else {
                    return "ผู้ใช้ {$userName} เข้าดูหน้าแก้ไขข้อมูลสินค้า {$productLabel} {$dateTimeStr}";
                }
            } elseif ($actionId === 'create') {
                if ($isPost) {
                    return "ผู้ใช้ {$userName} ทำการเพิ่มข้อมูลสินค้า {$productLabel} {$dateTimeStr}";
                } else {
                    return "ผู้ใช้ {$userName} เข้าดูหน้าเพิ่มสินค้าใหม่ {$dateTimeStr}";
                }
            } elseif ($actionId === 'view') {
                return "ผู้ใช้ {$userName} เข้าดูรายละเอียดสินค้า {$productLabel} {$dateTimeStr}";
            } elseif ($actionId === 'index') {
                return "ผู้ใช้ {$userName} เข้าดูหน้ารายการสินค้า {$dateTimeStr}";
            }
        }

        // If journaltrans controller
        if ($controllerId === 'journaltrans') {
            $journalLabel = '';
            if ($code) {
                $journalLabel .= 'เลขที่ "' . $code . '"';
            }
            if ($name) {
                $journalLabel .= ($journalLabel ? ' ' : '') . '(' . $name . ')';
            }
            if ($extra) {
                $journalLabel .= ($journalLabel ? ' ' : '') . 'สินค้า ' . $extra;
            }
            if (!$journalLabel && !empty($context['id'])) {
                $journalLabel = 'ID: ' . $context['id'];
            }

            if ($actionId === 'delete') {
                return "ผู้ใช้ {$userName} ทำการลบรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
            } elseif (in_array($actionId, ['cancel', 'canceldrop', 'cancelnew'])) {
                return "ผู้ใช้ {$userName} ทำการยกเลิกรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
            } elseif ($actionId === 'update') {
                if ($isPost) {
                    return "ผู้ใช้ {$userName} ทำการแก้ไขรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
                } else {
                    return "ผู้ใช้ {$userName} เข้าดูหน้าแก้ไขรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
                }
            } elseif ($actionId === 'create') {
                if ($isPost) {
                    return "ผู้ใช้ {$userName} ทำการบันทึกรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
                } else {
                    return "ผู้ใช้ {$userName} เข้าดูหน้าสร้างรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
                }
            } elseif ($actionId === 'view') {
                return "ผู้ใช้ {$userName} เข้าดูรายละเอียดรายการ Stock Transaction {$journalLabel} {$dateTimeStr}";
            } elseif ($actionId === 'index') {
                return "ผู้ใช้ {$userName} เข้าดูหน้ารายการ Stock Transaction {$dateTimeStr}";
            }
        }

        // Entity name mappings for other controllers
        $entityLabels = [
            'warehouse' => 'คลังจัดเก็บ',
            'watchmaker' => 'ช่าง',
            'productbrand' => 'แบรนด์สินค้า',
            'productgroup' => 'กลุ่มสินค้า',
            'unit' => 'หน่วยนับ',
            'user' => 'ผู้ใช้งาน',
            'employee' => 'พนักงาน',
            'position' => 'ตำแหน่ง',
            'department' => 'แผนก',
        ];

        if (isset($entityLabels[$controllerId])) {
            $entityTitle = $entityLabels[$controllerId];
            $itemLabel = $name ? "\"{$name}\"" : (!empty($context['id']) ? "ID: {$context['id']}" : '');

            if ($actionId === 'delete') {
                return "ผู้ใช้ {$userName} ทำการลบข้อมูล{$entityTitle} {$itemLabel} {$dateTimeStr}";
            } elseif ($actionId === 'update') {
                if ($isPost) {
                    return "ผู้ใช้ {$userName} ทำการแก้ไขข้อมูล{$entityTitle} {$itemLabel} {$dateTimeStr}";
                } else {
                    return "ผู้ใช้ {$userName} เข้าดูหน้าแก้ไขข้อมูล{$entityTitle} {$itemLabel} {$dateTimeStr}";
                }
            } elseif ($actionId === 'create') {
                if ($isPost) {
                    return "ผู้ใช้ {$userName} ทำการเพิ่มข้อมูล{$entityTitle} {$itemLabel} {$dateTimeStr}";
                } else {
                    return "ผู้ใช้ {$userName} เข้าดูหน้าเพิ่มข้อมูล{$entityTitle}ใหม่ {$dateTimeStr}";
                }
            } elseif ($actionId === 'view') {
                return "ผู้ใช้ {$userName} เข้าดูข้อมูล{$entityTitle} {$itemLabel} {$dateTimeStr}";
            } elseif ($actionId === 'index') {
                return "ผู้ใช้ {$userName} เข้าดูหน้ารายการ{$entityTitle} {$dateTimeStr}";
            }
        }

        // General controller & action fallback
        $controllerMap = [
            'report' => 'รายงาน',
            'salereport' => 'รายงานยอดขาย',
            'stocksum' => 'สต็อกคงเหลือ',
            'stocktrans' => 'ประวัติสต็อก',
            'dbbackup' => 'สำรองฐานข้อมูล',
            'dbrestore' => 'กู้คืนฐานข้อมูล',
            'authitem' => 'สิทธิ์การใช้งาน',
            'action-log' => 'ประวัติการใช้งานระบบ',
            'site' => 'หน้าหลัก/แดชบอร์ด',
        ];

        $actionMap = [
            'create' => 'เพิ่มข้อมูล',
            'update' => 'แก้ไขข้อมูล',
            'delete' => 'ลบข้อมูล',
            'cancel' => 'ยกเลิกรายการ',
            'view' => 'ดูรายละเอียด',
            'index' => 'เข้าดูรายการ',
            'export' => 'ส่งออกข้อมูล',
            'print' => 'พิมพ์เอกสาร',
        ];

        $controllerName = $controllerMap[$controllerId] ?? $controllerId;
        $actionName = $actionMap[$actionId] ?? $actionId;

        $targetText = $name ? "\"{$name}\"" : (!empty($context['id']) ? "ID: {$context['id']}" : '');

        if ($actionId === 'delete') {
            return "ผู้ใช้ {$userName} ทำการลบข้อมูลในเมนู {$controllerName} {$targetText} {$dateTimeStr}";
        } elseif ($actionId === 'update' && $isPost) {
            return "ผู้ใช้ {$userName} ทำการแก้ไขข้อมูลในเมนู {$controllerName} {$targetText} {$dateTimeStr}";
        } elseif ($actionId === 'create' && $isPost) {
            return "ผู้ใช้ {$userName} ทำการเพิ่มข้อมูลในเมนู {$controllerName} {$targetText} {$dateTimeStr}";
        } else {
            return "ผู้ใช้ {$userName} ทำการ{$actionName}ในเมนู {$controllerName} {$targetText} {$dateTimeStr}";
        }
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
