<?php

namespace backend\helpers;

class ContactcatType
{
    private static $data = [
        '1' => 'LINE ID',
        '2' => 'เบอร์โทรศัพท์',
        '3' => 'Facebook',
        '4' => 'Email'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'LINE ID'],
        ['id'=>'2','name' => 'เบอร์โทรศัพท์'],
        ['id'=>'3','name' => 'Facebook'],
        ['id'=>'4','name' => 'Email']
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
