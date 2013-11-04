<?php
namespace core\classes\interfaces;

/**
 * Class database_interface
 */
interface database_interface {

    /**
     * @param $host
     * @param $db
     * @param $username
     * @param $password
     * @param string $name
     * @return mixed
     */
    public static function connect($host, $db, $username, $password, $name = 'new');

    /**
     * @param $str
     * @return mixed
     */
    public static function esc($str);

    /**
     * @param $res
     * @param string $class
     * @return mixed
     */
    public static function fetch_all($res, $class = 'stdClass');

    /**
     * @param $object
     * @param array $fields_to_retrieve
     * @param $options
     * @param array $parameters
     * @return mixed
     */
    public static function get_query($object, array $fields_to_retrieve, $options, &$parameters = array());

    /**
     * @return mixed
     */
    public static function insert_id();

    /**
     * @param $res
     * @return mixed
     */
    public static function num($res);

    /**
     * @param $sql
     * @param array $params
     * @param string $class
     * @return mixed
     */
    public static function result($sql, $params = array(), $class = 'stdClass');

    /**
     * @param $sql
     * @param array $params
     * @param bool $throwable
     * @return mixed
     */
    public static function query($sql, $params = array(), $throwable = false);

    /**
     * @param $res
     * @param string $class
     * @return mixed
     */
    public static function fetch($res, $class = 'stdClass');

    /**
     * @param $name
     * @return bool
     */
    public static function swap_connection($name);

    /**
     * @param $table
     * @return bool
     */
    public static function table_exists($table);

    /**
     * @param $table
     * @param $column
     * @return bool
     */
    public static function column_exists($table, $column);
}
