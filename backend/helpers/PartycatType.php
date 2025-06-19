<?php

namespace backend\helpers;

class PartycatType
{
    private static $data = [
        '1' => 'ที่อยู่หลัก',
        '2' => 'ที่อยู่เพื่อการจัดส่ง'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'ที่อยู่หลัก'],
        ['id'=>'2','name' => 'ที่อยู่เพื่อการจัดส่ง']
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
