<?php
namespace core\classes;

class ini {

    private static $settings;

    public static function get($key, $block = 'site', $default = null) {
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

    public static function modify($key, $value, $block = 'site') {
        $file = root . '/.conf/config.ini';
        try {
            self::get($block, $key);
            $lines = file(file);
            $string = '';
            $matching_block = false;
            $set = false;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line == '[' . $block . ']') {
                    $matching_block = true;
                } else if ($line[0] == '[' && end(trim($line)) == ']') {
                    $matching_block = false;
                }
                if (!$set && $matching_block && $key == $line) {
                    $string .= $key . ' = ' . $value . "\n";
                } else {
                    $string .= $line . "\n";
                }
            }
        } catch (\Exception $e) {
            $string = '';
            $lines = file($file);
            $matching_block = false;
            $set = false;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line == '[' . $block . ']') {
                    $matching_block = true;
                } else if (isset($line[0]) && $line[0] == '[' && $line[strlen($line - 1)] == ']') {
                    $matching_block = false;
                }
                if (!$set && $matching_block) {
                    $string .= $line . "\n";
                    $string .= $key . ' = ' . $value . "\n";
                } else {
                    $string .= $line . "\n";
                }

            }
        }
        file_put_contents($file, $string);
    }
}
 