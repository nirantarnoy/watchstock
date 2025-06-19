<?php

namespace backend\helpers;

class DeliveryrouteType
{
    private static $data = [
        '1' => 'สายส่งทั่วไป',
        '2' => 'บูธ'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'สายส่งทั่วไป'],
        ['id'=>'2','name' => 'บูธ']
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
