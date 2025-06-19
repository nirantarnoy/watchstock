<?php

namespace backend\helpers;

class WorkorderStatus
{
    private static $data = [
        '1' => 'Open',
        '2' => 'Approved',
        '3' => 'Closed',
        '4' => 'Cancel'
    ];

    private static $dataobj = [
        ['id' => '1', 'name' => 'Open'],
        ['id' => '2', 'name' => 'Approved'],
        ['id' => '3', 'name' => 'Closed'],
        ['id' => '4', 'name' => 'Cancel'],
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
