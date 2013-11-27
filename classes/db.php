<?php

namespace core\classes;

use classes\ajax as _ajax;
use classes\db as _db;
use classes\get as _get;
use db\count as _count;
use db\insert as _insert;
use db\select as _select;
use db\update as _update;
use html\node;

abstract class db implements interfaces\database_interface {

    /** @var \PDO */
    public static $con;
    /**
     * @var
     */
    public static $con_name;
    /**
     * @var array
     */
    public static $con_arr = [];
    /**
     * @var int
     */
    public static $timeout = 30;

    public static $default_table_settings = array(
        'ENGINE' => 'innoDB',
        'CHARACTER SET' => 'utf8',
    );

    public static function select($table_name) {
        return new _select($table_name);
    }

    public static function insert($table_name) {
        return new _insert($table_name);
    }

    public static function update($table_name) {
        return new _update($table_name);
    }

    public static function count($table_name, $primary_key = '*') {
        $count = new _count($table_name);
        $count->add_field_to_retrieve($primary_key);
        return $count;
    }

    public static function connect_root() {
        try {
            $var = new \PDO('mysql:host=localhost', 'root', '');
        } catch (\PDOException $e) {
            die('Could not connect to database, please try again shortly...');
        }
        _db::$con_arr['root'] = array(
            'connection' => $var,
            'created' => time()
        );
        _db::$con_name = 'root';
        _db::$con = _db::$con_arr['root']['connection'];
        _db::$con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param $host
     * @param $db
     * @param $username
     * @param $password
     * @param string $name
     * @return bool
     */
    public static function connect($host, $db, $username, $password, $name = 'default') {
        try {
            $var = new \PDO('mysql:host=' . $host . ';dbname=' . $db, $username, $password);
        } catch (\PDOException $e) {
            die('Could not connect to database, please try again shortly...' . $e->getMessage());
        }
        _db::$con_arr[$name] = array(
            'connection' => $var,
            'settings' => array(
                'host' => $host,
                'database' => $db,
                'username' => $username,
                'password' => $password,
            ),
            'created' => time()
        );
        _db::$con_name = $name;
        _db::$con = _db::$con_arr[$name]['connection'];
        _db::$con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     *
     */
    public static function reconnect() {
        if (isset(_db::$con_name) && isset(_db::$con_arr[_db::$con_name]) && isset(_db::$con_arr[_db::$con_name]['settings'])) {
            $settings = _db::$con_arr[_db::$con_name]['settings'];
            _db::connect($settings['host'], $settings['database'], $settings['username'], $settings['password'], _db::$con_name);
        } else {
            _db::default_connection();
        }
    }

    public static function connected() {
        if (!isset(_db::$con_name) || _db::has_timed_out()) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    public static function default_connection() {
        _db::connect(_get::ini('server', 'mysql'), _get::ini('database', 'mysql'), _get::ini('username', 'mysql'), _get::ini('password', 'mysql'));
    }

    /**
     * @param string $str
     * @return string
     */
    public static function esc($str) {
        if (!_db::connected()) {
            _db::reconnect();
        }
        return _db::$con->quote($str);
    }

    /**
     * @param \PDOStatement $res
     * @param string $class
     * @return mixed
     */
    public static function fetch_all($res, $class = 'stdClass') {
        if ($class != null) {
            return $res->fetchAll(\PDO::FETCH_OBJ);
        } else {
            return $res->fetchAll();
        }
    }

    /**
     * @param $object
     * @param array $fields_to_retrieve
     * @param $options
     * @param array $parameters
     * @return string
     */
    public static function get_query($object, array $fields_to_retrieve, $options, &$parameters = []) {
        $fields = [];
        $where = 'WHERE 1 ';
        $order = '';
        $limit = '';
        $join = '';
        $group = '';
        $base_object = _get::__class_name($object);
        if (!empty($fields_to_retrieve)) {
            foreach ($fields_to_retrieve as $field) {
                if (strstr($field, '.') && !strstr($field, '.*') && !strstr($field, ' AS ')) {
                    $fields[] = $field . ' AS `' . str_replace('.', '@', $field) . '`';
                } else if (strstr($field, '(') === false && strstr($field, '.*') === false && strstr($field, '.') === false) {
                    $fields[] = $base_object . '.' . $field;
                } else {
                    $fields[] = $field;
                }
            }
        } else {
            $fields[] = $base_object . '.*';
        }
        if (isset($options['join'])) {
            foreach ($options['join'] as $key => $val) {
                $join .= ' LEFT JOIN ' . $key . ' ON ' . $val;
            }
        }
        if (isset($options['where'])) {
            $where .= 'AND ' . $options['where'];
        }

        if (isset($options['where_equals']) && !empty($options['where_equals'])) {
            $where_cnt = 0;
            foreach ($options['where_equals'] as $key => $val) {
                $where_cnt++;
                if (strpos($key, '.') !== false) {
                    $where .= ' AND `' . str_replace('.', '`.', $key) . '=:where_' . $where_cnt;
                } else {
                    $where .= ' AND `' . $key . '`=:where_' . $where_cnt;
                }
                $parameters['where_' . $where_cnt] = $val;
            }
        }
        if (isset($options['order'])) {
            $order .= 'ORDER BY ' . $options['order'];
        }
        if (isset($options['limit'])) {
            $limit .= 'LIMIT ' . $options['limit'];
        }
        if (isset($options['group'])) {
            $group .= 'GROUP BY ' . $options['group'];
        }
        return $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $base_object . ' ' . $join . ' ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit . ' ';

    }

    /**
     * @return string
     */
    public static function insert_id() {
        return _db::$con->lastInsertId();
    }

    /**
     * @param \PDOStatement $res
     * @return int
     */
    public static function num($res) {
        return $res->rowCount();
    }

    /**
     * @param $sql
     * @return \PDOStatement
     */
    private static function prepare($sql) {
        return _db::$con->prepare($sql);
    }

    /**
     * @param $sql
     * @param array $params
     * @param string $class
     * @return bool|mixed
     */
    public static function result($sql, $params = [], $class = 'stdClass') {
        $res = _db::query($sql, $params);
        if ($res) {
            return _db::fetch($res, $class);
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function has_timed_out() {
        return time() - _db::$con_arr[_db::$con_name]['created'] > _db::$timeout;
    }

    /**
     * @param $sql
     * @param array $params
     * @param bool $throwable
     * @return \PDOStatement
     */
    static function query($sql, $params = [], $throwable = false) {
        // Attempt to reconnect if connection has gone away.
        if (!_db::connected()) {
            _db::reconnect();
        }
        $prep_sql = _db::$con->prepare($sql);
        if (!empty($params)) {
            foreach ($params as $key => $val) {
                $prep_sql->bindValue($key, $val);
            }
        }
        try {
            $prep_sql->execute();
        } catch (\PDOException $e) {
            $error = node::create('div.error_message.mysql', [],
                node::create('p', [],
                    $e->getMessage() .
                    \core::get_backtrace() .
                    print_r((isset($prep_sql->queryString) ? $prep_sql->queryString : ''), 1) . print_r($params, true)
                )
            );
            if (ajax) {
                _ajax::inject('body', 'append', $error);
                if (!$throwable) {
                    _ajax::do_serve();
                    die();
                }
            } else {
                echo $error;
                if (!$throwable) {
                    die();
                }
            }
        }

        return $prep_sql;
    }

    /**
     * @param \PDOStatement $res
     * @param string $class
     * @return mixed
     */
    public static function fetch($res, $class = 'stdClass') {
        if ($class != null) {
            return $res->fetchObject($class);
        } else {
            return $res->fetch(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public static function swap_connection($name) {
        if (isset(_db::$con_arr[$name])) {
            _db::$con = _db::$con_arr[$name]['connection'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $table
     * @return bool
     */
    public static function table_exists($table) {
        $res = _db::query('show tables like ' . _db::esc($table));
        return _db::num($res);
    }

    /**
     * @param $table string
     * @param $column string
     * @return bool
     */
    public static function column_exists($table, $column) {
        $res = _db::query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . _db::esc($column));
        return _db::num($res);
    }

    public static function column_count($table) {
        $res = _db::query('SHOW COLUMNS FROM `' . $table . '`');
        return _db::num($res);
    }

    public static function add_column($table, $name, $type, $additional_options) {
        _db::query('ALTER TABLE ' . $table . ' ADD `' . $name . '` ' . $type . ' ' . $additional_options, [], 1);
    }

    public static function move_column($table, $name, $type, $additional_options) {
        _db::query('ALTER TABLE ' . $table . ' MODIFY `' . $name . '` ' . $type . ' ' . $additional_options, [], 1);
    }

    public static function create_table($table_name, $fields = [], $keys = [], $settings = []) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $table_name;
        $column_strings = [];
        foreach ($fields as $field => $structure) {
            $column_strings[] = '`' . $field . '` ' . $structure;
        }
        foreach ($keys as $key) {
            $column_strings[] = $key;
        }
        $sql .= ' (' . implode(',', $column_strings) . ') ';
        $setting_strings = [];
        $settings = array_merge(_db::$default_table_settings, $settings);
        foreach ($settings as $setting => $value) {
            if (is_numeric($setting)) {
                $setting_strings[] = $value;
            } else {
                $setting_strings[] = $setting . ' = ' . $value;
            }
        }
        $sql .= implode(',', $setting_strings);
        _db::query($sql);
    }
}
