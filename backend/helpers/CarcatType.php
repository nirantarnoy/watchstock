<?php

namespace backend\helpers;

class CarcatType
{
    private static $data = [
        '1' => 'หัว',
        '2' => 'หาง'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'หัว'],
        ['id'=>'2','name' => 'หาง']
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
