<?php
namespace core\classes;

class ini {

    private static $settings;

    public static function get($block, $key,  $default = null) {
        if (!isset(self::$settings)) {
            self::load();
        }

        if (isset(self::$settings[$block][$key])) {
            return self::$settings[$block][$key];
        } else if (isset($default)) {
            return $default;
        } else {
            throw new \Exception('Setting [' . $block . ']:' . $key . ' not found and no default provided');
        }
    }

    public static function reload() {
        unset(self::$settings);
    }

    public static function load() {
        if (is_readable(root . '/.conf/config.ini')) {
            self::$settings = parse_ini_file(root . '/.conf/config.ini', true);
        } else {
            //throw new \Exception('Could not find ini file.');
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

    public static function save($file, $options) {
        $string = '';
        foreach($options as $block => $keys) {
            $string .= '[' . $block . ']' . "\n";
            foreach($keys as $key => $value) {
                if(is_array($value)) {
                    foreach($value as $sub_value) {
                        $string .= $key . '[] = "' . $sub_value . '"' . "\n";
                    }
                } else {
                    $string .= $key . ' = "' . $value . '"' . "\n";
                }
            }
            $string .= "\n";
        }
        file_put_contents($file, $string);
    }

    public static function modify($key, $value, $block = 'site') {
        self::$settings[$block][$value] = $key;
        self::save(root . '/.conf/config.ini', self::$settings);
    }
}
 