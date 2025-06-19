<?php

namespace backend\helpers;

class FixcostType
{
    private static $data = [
        '1' => 'จ่าย',
        '2' => 'รับ'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'จ่าย'],
        ['id'=>'2','name' => 'รับ']
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
