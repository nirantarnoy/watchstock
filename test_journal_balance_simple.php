<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/console/config/main.php',
    require __DIR__ . '/console/config/main-local.php'
);

$app = new yii\console\Application($config);

use common\models\JournalTransLine;
use common\models\StockSum;

// Setup Test Data
$productId = 1; 
$warehouseId = 1;
$qty = 10;

echo "Starting Test (Console Mode)...\n";

// 1. Get Initial Balance
$initialBalance = StockSum::find()->where(['product_id' => $productId])->sum('qty');
echo "Initial Balance: " . ($initialBalance ?? 0) . "\n";

// 2. Simulate Stock Update (what calStock does)
$stockSum = StockSum::find()->where(['product_id' => $productId, 'warehouse_id' => $warehouseId])->one();
if (!$stockSum) {
    $stockSum = new StockSum();
    $stockSum->product_id = $productId;
    $stockSum->warehouse_id = $warehouseId;
    $stockSum->qty = 0;
}
$stockSum->qty += $qty;
$stockSum->save(false);
echo "StockSum Updated. New Qty: " . $stockSum->qty . "\n";

// 3. Create JournalTransLine and save balance
$modelLine = new JournalTransLine();
$modelLine->journal_trans_id = 99999; // Fake ID
$modelLine->product_id = $productId;
$modelLine->qty = $qty;
$modelLine->warehouse_id = $warehouseId;

// Calculate balance
$balance = StockSum::find()->where(['product_id' => $productId])->sum('qty');
$modelLine->balance = $balance;

if ($modelLine->save(false)) {
    echo "JournalTransLine Saved with Balance: " . $modelLine->balance . "\n";
} else {
    echo "Failed to save JournalTransLine\n";
    print_r($modelLine->errors);
}

// 4. Verify
$savedLine = JournalTransLine::findOne($modelLine->id);
if ($savedLine->balance == ($initialBalance + $qty)) {
    echo "TEST PASSED: Balance updated correctly.\n";
} else {
    echo "TEST FAILED: Expected " . ($initialBalance + $qty) . ", got " . $savedLine->balance . "\n";
}

// Cleanup
$stockSum->qty -= $qty;
$stockSum->save(false);
$modelLine->delete();
