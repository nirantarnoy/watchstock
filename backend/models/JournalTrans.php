<?php
namespace backend\models;
use common\models\JournalTransLine;

use Yii;
use yii\db\ActiveRecord;
date_default_timezone_set('Asia/Bangkok');

class JournalTrans extends \common\models\JournalTrans
{
    // Transaction Types

    const TYPE_OPENING = 1;     // ปรับยอดยกมา
    const TYPE_ADJUST = 2;     // ปรับยอด
    const TYPE_SALE = 3;       // ขาย
    const TYPE_RETURN_SALE = 4; // คืนขาย
    const TYPE_LOAN = 5;       // ยืม
    const TYPE_RETURN_LOAN = 6; // คืนยืม
    const TYPE_SEND = 7;       // เบิกส่งช่าง
    const TYPE_RETURN_SEND = 8; // คืนส่งช่าง
    const TYPE_DROP = 9;       // ขาย Dropship

    const TYPE_ADJUST_IN = 10;

    const JOURNAL_TRANS_STATUS_DRAFT = 0;
    const JOURNAL_TRANS_STATUS_ACTIVE = 1;
    const JOURNAL_TRANS_STATUS_CANCEL = 4;
    public function behaviors()
    {
        return [
            'timestampcdate'=>[
                'class'=> \yii\behaviors\AttributeBehavior::className(),
                'attributes'=>[
                    ActiveRecord::EVENT_BEFORE_INSERT=>'created_at',
                ],
                'value'=> time(),
            ],
            'timestampudate'=>[
                'class'=> \yii\behaviors\AttributeBehavior::className(),
                'attributes'=>[
                    ActiveRecord::EVENT_BEFORE_INSERT=>'updated_at',
                ],
                'value'=> time(),
            ],
            'timestampcby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_by',
                ],
                'value' => Yii::$app->user->id,
            ],
            'timestamuby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_by',
                ],
                'value' => Yii::$app->user->id,
            ],
//            'timestampcompany' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'company_id',
//                ],
//                'value' => isset($_SESSION['user_company_id']) ? $_SESSION['user_company_id'] : 1,
//            ],
//            'timestampbranch' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'branch_id',
//                ],
//                'value' => isset($_SESSION['user_branch_id']) ? $_SESSION['user_branch_id'] : 1,
//            ],
            'timestampupdate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => time(),
            ],
        ];
    }



    public static function getLineQty($id){
        $qty = JournalTransLine::find()->where(['journal_trans_id'=>$id])->sum('qty');
        return $qty;
    }

    public static function findJournalNoFromStockTransId($id) {
        $journal_no = JournalTrans::find()->where(['id'=>$id])->one();
        return $journal_no != null ? $journal_no->journal_no : '';
    }

    public static function generateJournalNoNew($trans_type_id)
    {
        $prefixMap = [
            self::TYPE_OPENING => 'OPN',
            self::TYPE_ADJUST => 'ADJ',
            self::TYPE_SALE => 'SAL',
            self::TYPE_RETURN_SALE => 'RSA',
            self::TYPE_LOAN => 'LOA',
            self::TYPE_RETURN_LOAN => 'RLO',
            self::TYPE_SEND => 'SEN',
            self::TYPE_RETURN_SEND => 'RSE',
            self::TYPE_DROP => 'DRO',
            self::TYPE_ADJUST_IN => 'SIN',
        ];

        $prefix = $prefixMap[$trans_type_id] ?? 'UNK'; // fallback กรณีไม่รู้จักประเภท
        $ym = date('Ym');
        $basePrefix = $prefix . $ym;

        $lastRecord = self::find()
            ->select(['journal_no'])
            ->where(['trans_type_id' => $trans_type_id])
            ->andWhere(['like', 'journal_no', $basePrefix])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if ($lastRecord) {
            $lastNum = (int)substr($lastRecord->journal_no, strlen($basePrefix));
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return $basePrefix . $newNum;
//        $prefix = '';
//        switch ($trans_type_id) {
//            case self::TYPE_OPENING:
//                $prefix = 'OPN';
//                break;
//            case self::TYPE_ADJUST:
//                $prefix = 'ADJ';
//                break;
//            case self::TYPE_SALE:
//                $prefix = 'SAL';
//                break;
//            case self::TYPE_RETURN_SALE:
//                $prefix = 'RSA';
//                break;
//            case self::TYPE_LOAN:
//                $prefix = 'LOA';
//                break;
//            case self::TYPE_RETURN_LOAN:
//                $prefix = 'RLO';
//                break;
//            case self::TYPE_SEND:
//                $prefix = 'SEN';
//                break;
//            case self::TYPE_RETURN_SEND:
//                $prefix = 'RSE';
//                break;
//            case self::TYPE_DROP:
//                $prefix = 'DRO';
//                break;
//            case self::TYPE_ADJUST_IN:
//                $prefix = 'SIN';
//                break;
//
//        }
//
//        $lastRecord = self::find()
//            ->select(['journal_no'])
//            ->where(['trans_type_id' => $trans_type_id])
//            ->andWhere(['like', 'journal_no', $prefix . date('Ym')])
//            ->orderBy(['id' => SORT_DESC])
//            ->one();
//
//
//        if ($lastRecord != null) {
//            $prefix = $prefix . date('Ym');
//            $cnum = substr((string)$lastRecord->journal_no, 9, strlen($lastRecord->journal_no));
//            $len = strlen($cnum);
//            $clen = strlen($cnum + 1);
//            $loop = $len - $clen;
//            for ($i = 1; $i <= $loop; $i++) {
//                $prefix .= "0";
//            }
//            $prefix .= $cnum + 1;
//            return $prefix;
//        } else {
//            $prefix = $prefix.date('Ym');
//            return $prefix . '0001';
//        }
    }

    public function getJournalTransLine()
    {
        return $this->hasMany(\common\models\JournalTransLine::class, ['journal_trans_id' => 'id']);
    }



}
