<?php
namespace core\classes\interfaces;

/**
 * Class cache_interface
 */
interface cache_interface {

    /**
     * @param string $key the key to retrieve.
     * @param array $dependencies table dependencies.
     * @throws \Exception Throws \Exceptions if the cache node could not be connected or the key is not set.
     * @return mixed
     */
    public static function get($key, array $dependencies = array('global'));

    /**
     * @param array $data associative array of key => value to be added the the cache table.
     * @param array $dependencies table dependencies.
     * @return bool
     */
    public static function set(array $data, array $dependencies = array('global'));

    /**
     * @param string $instance_id
     * @return bool
     * */
    public static function connect($instance_id = '');

    /**
     * @return bool
     */
    public static function flush();
}
