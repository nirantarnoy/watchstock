<?php

namespace backend\helpers;

class PayType
{
    private static $data = [
        '1' => 'เงินสด',
        '2' => 'อื่นๆ'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'เงินสด'],
        ['id'=>'2','name' => 'อื่นๆ']
    ];
    public static function asArray()
    {
        return self::$data;
    }
    public static function asArrayObject()
    {
        return self::$dataobj;
    }
    public static function getTypeById($idx)
    {
        if (isset(self::$data[$idx])) {
            return self::$data[$idx];
        }

        return 'Unknown Type';
    }
    public static function getTypeByName($idx)
    {
        if (isset(self::$data[$idx])) {
            return self::$data[$idx];
        }

        return 'Unknown Type';
    }
}
