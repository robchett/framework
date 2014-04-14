<?php

namespace core\classes;

use classes\cache as _cache;
use classes\compiler;
use classes\ini as _ini;

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

    protected static $ignore_tables = ['_cache_dependants', '_compiler_keys'];

    const DEFAULT_CACHE_TIME = 86400;

    /**
     * @var null
     */
    private static $dependants = null;

    public static function default_connection() {
        _cache::connect(ini::get('memcached', 'instance'), ini::get('memcached', 'server'), ini::get('memcached', 'port'));
    }

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
        if (class_exists('\classes\db')) {
            if (!db::table_exists('_cache_dependants')) {
                db::create_table('_cache_dependants',
                    [
                        'key' => 'INT',
                        'hash' => 'BINARY(16)'
                    ]
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
    public static function get($key, array $dependencies = ['global']) {
        if (self::$current == null) {
            self::connect(_ini::get('memcached', 'instance'), _ini::get('memcached', 'server'), _ini::get('memcached', 'port'));
        }
        $key = self::get_key($key, $dependencies);
        if (!($res = self::$current->get($key))) {
            if (self::$current->getResultCode() != \Memcached::RES_SUCCESS) {
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
    public static function set(array $data, array $dependencies = ['global'], $cache_time = null) {
        if (self::$current == null) {
            try {
                self::connect(_ini::get('memcached', 'instance'), _ini::get('memcached', 'server'), _ini::get('memcached', 'port'));
            } catch (\Exception $e) {
                return;
                //trigger_error('Could not connect to memcached instance;' . $e->getMessage());
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
        foreach ($dependencies as $dependant) {
            $salt .= isset(self::$dependants[$dependant]) ? self::$dependants[$dependant] : 0;
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

    public static function remove($key) {
        static::$current->delete($key);
    }

    public static function break_cache($table) {
        if (in_array($table, static::$ignore_tables)) {
            return;
        }
        $time = microtime(true);
        db::replace('_cache_dependants')->add_value('key', $table)->add_value('hash', $time)->execute();
        self::$dependants[$table] = $time;
    }

    public static function grab($key, callable $callback, $dependencies = ['global'], $time = null) {
        compiler::$dependants = array_merge(compiler::$dependants, $dependencies);
        try {
            $data = cache::get($key, $dependencies);
        } catch (\Exception $e) {
            $data = $callback();
            cache::set([$key => $data], $dependencies, $time);
        }
        return $data;
    }
}
