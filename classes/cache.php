<?php

namespace core\classes;

use classes\get;

abstract class cache implements interfaces\cache_interface {

    /**
     * @const int Connection error has occurred possible Memcached not installed.
     */
    const ERROR_CONNECT = 1;
    /**
     * @const int Could not retrieve from key.
     */
    const ERROR_RETRIEVE = 2;

    /**
     * @var  \Memcached
     * */
    public static $current = null;

    const DEFAULT_CACHE_TIME = 0;

    /**
     * @var null
     */
    private static $dependants = null;

    /**
     * @param string $instance_id
     * @param string $server
     * @param int $port
     * @return bool
     * @throws \Exception
     */
    public static function connect($instance_id = '', $server = 'localhost', $port = 11211) {
        if (class_exists('Memcached', false)) {
            $cache = new \Memcached($instance_id);
            $cache->addServer($server, $port);
            self::$current = $cache;
        } else {
            throw new \Exception('Memcached is not enabled on this server.', self::ERROR_CONNECT);
        }
        return true;
    }

    /**
     * Load the table dependencies for dynamic cache breaking.
     */
    private static function load_dependants() {
        self::$dependants = [];
        if (class_exists('db')) {
            if (!db::table_exists('_cache_dependants')) {
                db::create_table('_cache_dependants',
                    array(
                        'key' => 'INT',
                        'hash' => 'BINARY(16)'
                    )
                );
            }
            $res = db::query('SELECT * FROM _cache_dependants');
            while ($row = db::fetch($res, false)) {
                self::$dependants[$row['key']] = $row['hash'];
            }
        }
    }

    /**
     * @param string $key the key to retrieve.
     * @param array $dependencies table dependencies.
     * @throws \Exception Throws \Exceptions if the cache node could not be connected or the key is not set.
     * @return mixed
     */
    public static function get($key, array $dependencies = array('global')) {
        if (self::$current == null) {
            self::connect(get::ini('instance', 'memcached'), get::ini('server', 'memcached'), get::ini('port', 'memcached'));
        }
        $key = self::get_key($key, $dependencies);
        if (!($res = self::$current->get($key))) {
            if (self::$current->getResultCode() == \Memcached::RES_NOTFOUND) {
                throw new \Exception('Cache key not set, this is common to distinguish from null values', self::ERROR_RETRIEVE);
            }
        }
        return $res;
    }

    /**
     * @param array $data associative array of key => value to be added the the cache table.
     * @param array $dependencies table dependencies.
     * @param int $cache_time Cache time in seconds, 0 for not breaking
     * @return bool returns true on successful add or false on failure.
     */
    public static function set(array $data, array $dependencies = array('global'), $cache_time = null) {
        if (self::$current == null) {
            try {
                self::connect(get::ini('instance', 'memcached'), get::ini('server', 'memcached'), get::ini('port', 'memcached'));
            } catch (\Exception $e) {
                trigger_error('Could not connect to memcached instance;');
            }
        }
        if (is_null($cache_time)) {
            $cache_time = self::DEFAULT_CACHE_TIME;
        }
        foreach ($data as $key => $value) {
            $new_key = self::get_key($key, $dependencies);
            self::$current->set($new_key, $value, $cache_time);
        }
        return true;
    }

    /**
     * @param string $key
     * @param array $dependencies
     * @return string
     */
    protected static function get_key($key, array $dependencies) {
        if (!self::$dependants) {
            self::load_dependants();
        }
        $salt = '';
        foreach ($dependencies as $key) {
            $salt .= isset(self::$dependants[$key]) ? self::$dependants[$key] : 0;
        }
        $key = md5($salt . $key);
        return $key;
    }

    /**
     * Flush the current cache pool
     */
    public static function flush() {
        self::$current->flush();
    }
}
