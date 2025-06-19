<?php

namespace backend\helpers;

class PayForType
{
    private static $data = [
        '1' => 'พนักงานขับรถ',
        '2' => 'ร้านค้า',
        '3' => 'บุคคลธรรมดา',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'พนักงานขับรถ'],
        ['id'=>'2','name' => 'ร้านค้า'],
        ['id'=>'3','name' => 'บุคคลธรรมดา']
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
