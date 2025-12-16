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
    require __DIR__ . '/backend/config/main.php',
    require __DIR__ . '/backend/config/main-local.php'
);

$app = new yii\web\Application($config);

use backend\controllers\JournaltransController;
use common\models\JournalTransLine;
use common\models\StockTrans;
use backend\models\JournalTrans;
use common\models\StockSum;

// Setup Test Data
$productId = 1; // Assuming product ID 1 exists
$warehouseId = 1; // Assuming warehouse ID 1 exists
$qty = 10;
$transTypeId = 1; // Adjust stock
$stockTypeId = 1; // Stock In

echo "Starting Test...\n";

// 1. Get Initial Balance
$initialBalance = StockSum::find()->where(['product_id' => $productId])->sum('qty');
echo "Initial Balance: " . ($initialBalance ?? 0) . "\n";

// 2. Create JournalTrans
$model = new JournalTrans();
$model->journal_no = 'TEST-' . time();
$model->trans_date = date('Y-m-d H:i:s');
$model->status = 1;
$model->trans_type_id = $transTypeId;
$model->stock_type_id = $stockTypeId;
$model->created_by = 1;
if (!$model->save(false)) {
    echo "Failed to save JournalTrans\n";
    exit;
}
echo "JournalTrans created: " . $model->id . "\n";

// 3. Create JournalTransLine
$modelLine = new JournalTransLine();
$modelLine->journal_trans_id = $model->id;
$modelLine->product_id = $productId;
$modelLine->qty = $qty;
$modelLine->warehouse_id = $warehouseId;
if (!$modelLine->save(false)) {
    echo "Failed to save JournalTransLine\n";
    exit;
}
echo "JournalTransLine created: " . $modelLine->id . "\n";

// 4. Create StockTrans
$modelStockTrans = new StockTrans();
$modelStockTrans->trans_date = date('Y-m-d H:i:s');
$modelStockTrans->journal_trans_id = $model->id;
$modelStockTrans->trans_type_id = $transTypeId;
$modelStockTrans->product_id = $productId;
$modelStockTrans->qty = $qty;
$modelStockTrans->warehouse_id = $warehouseId;
$modelStockTrans->stock_type_id = $stockTypeId;
$modelStockTrans->created_by = 1;

if ($modelStockTrans->save(false)) {
    echo "StockTrans created\n";
    
    // 5. Simulate Controller Logic
    $controller = new JournaltransController('journaltrans', Yii::$app);
    $controller->calStock($productId, $stockTypeId, $warehouseId, $qty, $transTypeId);
    
    // 6. Calculate and save balance
    $balance = StockSum::find()->where(['product_id' => $productId])->sum('qty');
    $modelLine->balance = $balance;
    $modelLine->save(false);
    
    echo "New Balance Calculated: " . $balance . "\n";
} else {
    echo "Failed to save StockTrans\n";
    exit;
}

// 7. Verify
$savedLine = JournalTransLine::findOne($modelLine->id);
echo "Saved Balance in DB: " . $savedLine->balance . "\n";

if ($savedLine->balance == ($initialBalance + $qty)) {
    echo "TEST PASSED: Balance updated correctly.\n";
} else {
    echo "TEST FAILED: Expected " . ($initialBalance + $qty) . ", got " . $savedLine->balance . "\n";
}

// Cleanup (Optional, but good for repeatable tests)
// $model->delete();
// $modelLine->delete();
// $modelStockTrans->delete();
// Revert Stock?
