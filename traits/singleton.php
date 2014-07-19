<?php

namespace core\traits;

trait singleton {

    /** @var  static */
    protected static $singleton;

    /** @return static */
    public static function current() {
        if (!isset(static::$singleton)) {
            static::$singleton = new static;
        }
        return static::$singleton;
    }
}