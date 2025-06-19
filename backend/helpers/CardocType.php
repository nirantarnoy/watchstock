<?php

namespace backend\helpers;

class CardocType
{
    private static $data = [
        '1' => 'สำเนาหน้าเล่ม',
        '2' => 'สำเนากรมธรรม์',
        '3' => 'สำเนาพรบ',
        '4' => 'สำเนาประกันสินค้า',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'สำเนาหน้าเล่ม'],
        ['id'=>'2','name' => 'สำเนากรมธรรม์'],
        ['id'=>'3','name' => 'สำเนาพรบ'],
        ['id'=>'4','name' => 'สำเนาประกันสินค้า']
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
