<?php
namespace core\traits;

trait var_dump_import {

    public static function __set_state($params) {
        $object = new static;
        foreach ($params as $key => $value) {
            $object->$key = $value;
        }
        return $object;
    }
}
 