<?php

namespace backend\helpers;

class VatperType
{
    private static $data = [
        '1' => '1%',
        '3' => '3%',
        '5' => '5%',
        '7' => '7%',
        '10' => '10%',
    ];

    private static $dataobj = [
        ['id' => '1', 'name' => '1%'],
        ['id' => '3', 'name' => '3%'],
        ['id' => '5', 'name' => '5%'],
        ['id' => '7', 'name' => '7%'],
        ['id' => '10', 'name' => '10%'],
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
