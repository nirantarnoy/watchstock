<?php

namespace backend\helpers;

class QuotationStatus
{
    private static $data = [
        '0' => 'Open',
        '1' => 'Approved',
        '2' => 'Closed',
        '3' => 'Cancel'
    ];

    private static $dataobj = [
        ['id' => '0', 'name' => 'Open'],
        ['id' => '1', 'name' => 'Approved'],
        ['id' => '2', 'name' => 'Closed'],
        ['id' => '3', 'name' => 'Cancel'],
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
