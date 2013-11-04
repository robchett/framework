<?php

namespace core\classes;

use classes\get as _get;

abstract class get {

    public static $cms_settings = [];

    private static $settings;

    static function ent($html) {
        return htmlentities(html_entity_decode($html));
    }

    public static function __class_name($object) {
        if (is_string($object)) {
            $name = trim($object, '\\');
        } else {
            $name = trim(get_class($object), '\\');
        }
        if (($pos = strrpos($name, '\\')) !== false) {
            $pos++;
        }
        return substr($name, $pos);
    }

    public static function setting($setting, $default = '') {
        if (!self::$cms_settings) {
            $res = db::select('_cms_setting')
                ->add_field_to_retrieve('key')
                ->add_field_to_retrieve('value')
                ->execute();
            while ($obj = $res->fetchObject()) {
                self::$cms_settings[$obj->key] = $obj->value;
            }
        }
        return isset(self::$cms_settings[$setting]) ? self::$cms_settings[$setting] : $default;
    }

    public static function __namespace($object, $index = null) {
        $name = trim(get_class($object), '\\');
        if (isset($index)) {
            return array_reverse(explode('\\', substr($name, 0, strrpos($name, '\\'))))[$index];
        } else {
            return substr($name, 0, strrpos($name, '\\'));
        }
    }

    public static function fn($str) {
        return str_replace(array(' ', '.', ',', '-'), '_', strtolower($str));
    }

    public static function unique_fn($table, $field, $str) {
        $base_fn = _get::fn($str);
        if (db::select($table)->add_field_to_retrieve($field)->filter($field . '=:fn', ['fn' => $base_fn])->execute()->rowCount()) {
            $cnt = 0;
            do {
                $fn = $base_fn . '_' . ++$cnt;
            } while (db::select($table)->add_field_to_retrieve($field)->filter($field . '=:fn', ['fn' => $fn])->execute()->rowCount());
            return $fn;
        } else {
            return $base_fn;
        }

    }

    static function trim_root($string) {
        return str_replace(root, '', $string);
    }

    static function ordinal($num) {
        if (!in_array(($num % 100), array(11, 12, 13))) {
            switch ($num % 10) {
                // Handle 1st, 2nd, 3rd
                case 1:
                    return $num . 'st';
                case 2:
                    return $num . 'nd';
                case 3:
                    return $num . 'rd';
            }
        }
        return $num . 'th';
    }

    public static function header_redirect($url = '', $code = 404) {
        header('Location:' . (!strstr('http', $url) ? 'http://' . host . '/' . trim($url, '/') : $url), $code);
        die();
    }

    static function bool($a) {
        if ($a)
            return "Yes";
        else
            return "No";
    }

    public static function ini($key, $block = 'site', $default = null) {
        if (!isset(self::$settings)) {
            if (is_readable(root . '/.conf/config.ini')) {
                self::$settings = parse_ini_file(root . '/.conf/config.ini', true);
            } else {
                throw new \Exception('Could not find ini file.');
            }
            if (defined('host') && is_readable(root . '/.conf/' . host . '.ini')) {
                $sub_settings = parse_ini_file(root . '/.conf/' . host . '.ini', true);
                foreach ($sub_settings as $ini_block => $ini_keys) {
                    if (isset(self::$settings[$ini_block])) {
                        self::$settings[$ini_block] = $ini_keys;
                    } else {
                        self::$settings[$ini_block] = array_merge(self::$settings[$ini_block], $ini_keys);
                    }
                }
            }
        }


        if (isset(self::$settings[$block][$key])) {
            return self::$settings[$block][$key];
        } else if (isset($default)) {
            return $default;
        } else {
            throw new \Exception('Setting not found and no default provided');
        }

    }
}
