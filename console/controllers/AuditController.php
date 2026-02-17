<?php
// Put this in your console/controllers or run via a standalone script
// This script audits the stock by comparing StockSum with Transaction History

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\StockSum;
use common\models\StockTrans;

class AuditController extends Controller
{
    public function actionStock()
    {
        $products = Yii::$app->db->createCommand("SELECT DISTINCT product_id FROM stock_sum")->queryColumn();
        
        echo "Starting Stock Audit...\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-10s | %-15s | %-15s | %-15s\n", "Product ID", "StockSum Qty", "Trans Sum Qty", "Difference");
        echo str_repeat("-", 80) . "\n";

        foreach ($products as $productId) {
            $stockSum = Yii::$app->db->createCommand("
                SELECT SUM(qty) FROM stock_sum WHERE product_id = :pid
            ", [':pid' => $productId])->queryScalar() ?: 0;

            $transSum = Yii::$app->db->createCommand("
                SELECT SUM(CASE WHEN stock_type_id = 1 THEN qty ELSE -qty END) 
                FROM stock_trans 
                WHERE product_id = :pid
            ", [':pid' => $productId])->queryScalar() ?: 0;

            $diff = (float)$stockSum - (float)$transSum;

            if (abs($diff) > 0.001) {
                echo sprintf("%-10d | %-15.2f | %-15.2f | %-15.2f !!!\n", $productId, $stockSum, $transSum, $diff);
            }
        }
        echo str_repeat("-", 80) . "\n";
        echo "Audit Complete.\n";
    }
}
