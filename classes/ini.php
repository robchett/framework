<?php
namespace core\classes;


/**
 * Class ini
 *
 * Read/write controller for .ini files,
 *
 * Loads values from /.conf/config.ini
 * Merged with /.conf/$_SERVER["HTTP_HOST"].ini
 *
 * @package core\classes
 */
class ini {

    /** @var [] */
    private static $settings;

    /**
     * Get a  value from the conf files.
     *
     * @param string $block
     * @param string $key
     * @param null   $default
     *
     * @return string|array
     *
     * @throws \Exception
     */
    public static function get($block, $key, $default = null) {
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


    /**
     * Get a block from the config file
     *
     * @param      $block
     * @param null $default
     *
     * @throws \Exception
     * @return []
     */
    public static function get_block($block, $default = null) {
        if (!isset(self::$settings)) {
            self::load();
        }
        if (isset(self::$settings[$block])) {
            return self::$settings[$block];
        } else if (isset($default)) {
            return $default;
        } else {
            throw new \Exception('ini block [' . $block . '] not found and no default provided');
        }
    }

    /**
     * Reload the config files
     * (Lazy: will be loaded on next call)
     *
     * @return void
     */
    public static function reload() {
        self::$settings = null;
    }

    /**
     * Load the ini files
     * Loads values from /.conf/config.ini
     * Merged with /.conf/$_SERVER["HTTP_HOST"].ini
     *
     * @return void
     */
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

    /**
     * Format and save and ini file with given options
     *
     * @param $file
     * @param $options
     *
     * @return void
     */
    public static function save($file, $options) {
        $string = '';
        foreach ($options as $block => $keys) {
            $string .= '[' . $block . ']' . "\n";
            foreach ($keys as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $sub_value) {
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

    /**
     * Modify and save an ini value
     * Only supports the default file
     *
     * @param string $key
     * @param string $value
     * @param string $block
     *
     * @return void
     */
    public static function modify($key, $value, $block = 'site') {
        self::$settings[$block][$value] = $key;
        self::save(root . '/.conf/config.ini', self::$settings);
    }
}
 