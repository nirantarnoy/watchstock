<?php
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

$application = new yii\web\Application($config);

$products = \backend\models\Product::find()->all();
$found = 0;
foreach ($products as $p) {
    $res = \backend\models\Stocksum::getResQty($p->id);
    if ($res > 0) {
        echo "ID: " . $p->id . " | Code: " . $p->name . " | ResQty: " . $res . "\n";
        $found++;
    }
}
echo "Total products with reserved qty: $found\n";
