<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/common/config/main.php';

// Mock minimal application to interact with AR
class MockApp extends \yii\console\Application {
    public function __construct() {
        $config = [
            'id' => 'mockapp',
            'basePath' => __DIR__,
            'components' => [
                'db' => require __DIR__ . '/common/config/main-local.php', // Assuming db config is here
            ],
        ];
        parent::__construct($config);
    }
}
$dbConfig = require __DIR__ . '/common/config/main-local.php';
$app = new \yii\console\Application([
    'id' => 'basic-console',
    'basePath' => __DIR__,
    'components' => ['db' => $dbConfig['components']['db']]
]);

$productId = 4;
$lines = \common\models\StockTrans::find()->where(['product_id' => $productId])->orderBy(['id' => SORT_DESC])->limit(5)->all();
foreach($lines as $line) {
    echo "ID: {$line->id}, Date: {$line->trans_date}, Trans_Type: {$line->trans_type_id}, Stock_Type: {$line->stock_type_id}, Qty: {$line->qty}\n";
}
