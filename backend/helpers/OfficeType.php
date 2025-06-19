<?php

namespace backend\helpers;

class OfficeType
{
    private static $data = [
        '1' => 'วังศาลา',
        '2' => 'บ้านโป่ง'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'วังศาลา'],
        ['id'=>'2','name' => 'บ้านโป่ง']
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
