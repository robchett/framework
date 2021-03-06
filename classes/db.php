<?php

namespace core\classes;

use classes\ajax as _ajax;
use classes\db as _db;
use classes\get as _get;
use db\count as _count;
use db\delete as _delete;
use db\insert as _insert;
use db\select as _select;
use db\replace as _replace;
use db\update as _update;
use html\node;
use module\cms\objects\_cms_module;

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

    public static $default_table_settings = [
        'ENGINE' => 'innoDB',
        'CHARACTER SET' => 'utf8',
    ];

    public static function select($table_name) {
        return new _select($table_name);
    }

    public static function insert($table_name, $mode = '') {
        return new _insert($table_name, $mode);
    }

    public static function update($table_name) {
        return new _update($table_name);
    }

    public static function delete($table_name) {
        return new _delete($table_name);
    }
    public static function replace($table_name) {
        return new _replace($table_name);
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
        _db::$con_arr['root'] = [
            'connection' => $var,
            'created' => time()
        ];
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
        _db::$con_arr[$name] = [
            'connection' => $var,
            'settings' => [
                'host' => $host,
                'database' => $db,
                'username' => $username,
                'password' => $password,
            ],
            'created' => time()
        ];
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
        _db::connect(ini::get('mysql', 'server'), ini::get('mysql', 'database'), ini::get('mysql', 'username'), ini::get('mysql', 'password'));
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
     * @return \db\select
     */
    public static function get_query($object, array $fields_to_retrieve, $options) {
        $query = db::select(_get::__class_name($object));
        $base_object = _get::__class_name($object);
        if (!empty($fields_to_retrieve)) {
            foreach ($fields_to_retrieve as $field) {
                if (strstr($field, '.') && !strstr($field, '.*') && !strstr($field, ' AS ')) {
                    $query->add_field_to_retrieve($field . ' AS `' . str_replace('.', '@', $field) . '`');
                } else if (strstr($field, '(') === false && strstr($field, '.*') === false && strstr($field, '.') === false) {
                    $query->add_field_to_retrieve($base_object . '.' . $field);
                } else {
                    $query->add_field_to_retrieve($field);
                }
            }
        } else {
            $query->add_field_to_retrieve($base_object . '.*');
        }
        if (isset($options['parameters'])) {
            $query->filter('1', $options['parameters']);
        }
        if (isset($options['join'])) {
            foreach ($options['join'] as $key => $val) {
                $query->add_join($key, $val);
            }
        }
        if (isset($options['where'])) {
            $query->filter($options['where']);
        }
        if (isset($options['where_equals']) && $options['where_equals']) {
            foreach ($options['where_equals'] as $key => $val) {
                $query->filter_field($key, $val);
            }
        }
        if (isset($options['order'])) {
            $query->set_order($options['order']);
        }
        if (isset($options['limit'])) {
            $query->set_limit($options['limit']);
        }
        if (isset($options['group'])) {
            $query->add_grouping($options['group']);
        }
        return $query;
    }

    /**
     * @return int
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

    public static function create_table_join($source, $destination) {
        $source_module = new _cms_module();
        $source_module->do_retrieve(['primary_key'], ['where_equals' => ['table_name' => $source]]);
        $destination_module = new _cms_module();
        $destination_module->do_retrieve(['primary_key'], ['where_equals' => ['table_name' => $destination]]);

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $source . '_link_' . $destination . '
            (
                `' . $source_module->primary_key . '` INT(6) NOT NULL DEFAULT 0,
                `link_' . $destination_module->primary_key . '` INT(6) NOT NULL DEFAULT 0,
                `fid` INT(6) NOT NULL DEFAULT 0,
                INDEX(`' . $source_module->primary_key . '`,`link_' . $destination_module->primary_key . '`,`fid`),
                INDEX(`link_' . $destination_module->primary_key . '`)
            )
        ';
        $setting_strings = [];
        foreach (_db::$default_table_settings as $setting => $value) {
            if (is_numeric($setting)) {
                $setting_strings[] = $value;
            } else {
                $setting_strings[] = $setting . ' = ' . $value;
            }
        }
        $sql .= implode(',', $setting_strings);
        _db::query($sql);
    }

    public static function create_table_json($json) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $json->tablename;
        $column_strings = [];
        foreach ($json->fieldset as $field => $structure) {
            $string = static::get_column_type_json($structure);
            if ($string) {
                $column_strings[] = '`' . $field . '` ' . $string;
            }
        }
        foreach ($json->indexes as $type => $indexes) {
            switch ($type) {
                case 'primary' :
                    $column_strings[] = 'PRIMARY KEY (`' . $indexes . '`)';
                    break;
                case 'standard':
                    foreach ($indexes as $index) {
                        $column_strings[] = 'INDEX (`' . implode('`,`', $index) . '`)';
                    }
                    break;
            }
        }
        $sql .= ' (' . implode(',', $column_strings) . ') ';

        $setting_strings = [];
        foreach (_db::$default_table_settings as $setting => $value) {
            if (is_numeric($setting)) {
                $setting_strings[] = $value;
            } else {
                $setting_strings[] = $setting . ' = ' . $value;
            }
        }
        foreach ($json->settings as $setting => $value) {
            if (is_numeric($setting)) {
                $setting_strings[] = $value;
            } else {
                $setting_strings[] = $setting . ' = ' . $value;
            }
        }
        $sql .= implode(',', $setting_strings);
        _db::query($sql);
    }

    public static function get_column_type_json(\core\db\stub\field $structure) {
        $string = '';
        switch ($structure->type) {
            case 'int':
                $string .= 'INT(' . ($structure->length ? : 6) . ')';
                $default = 0;
                break;
            case 'boolean':
                $default = 0;
                $string .= 'INT(1)';
                break;
            case 'string':
                $default = '';
                $string .= 'VARCHAR(' . ($structure->length ? : 64) . ')';
                break;
            case 'password':
                $default = '';
                $string .= 'VARCHAR(' . ($structure->length ? : 64) . ')';
                break;
            case 'textarea':
                $default = '';
                $string .= 'TEXT';
                break;
            case 'date':
                $default = '0000-00-00';
                $string .= 'TIMESTAMP';
                break;
            case 'link':
                $default = 0;
                $string .= 'INT(' . ($structure->length ? : 6) . ')';
                break;
            default :
                return false;
        }
        $string .= ' NOT NULL ' . ($structure->autoincrement ? 'AUTO_INCREMENT' : 'DEFAULT "' . ($structure->default ? : $default) . '"');
        return $string;
    }

    public static function rename_table($old, $new) {
        echo 'RENAME TABLE ' . $old . ' TO ' . $new;
        static::query('RENAME TABLE ' . $old . ' TO ' . $new);
    }

    public static function rename_column($table, $old, $new) {
        try {
        $field = static::get_column_definition($table, $old);
            static::query('ALTER TABLE ' . $table . ' CHANGE ' . $old . ' ' . $new . ' ' . $field);
        } catch(\Exception $e) {
            return false;
        }
    }

    protected static function get_column_definition($table, $column) {
        $table = static::result('SHOW CREATE TABLE ' . $table, [], false);
        $matches = [];
        if(preg_match('#`' . $column . '` (.*?),#', $table['Create Table'], $matches)) {
            return $matches[1];
        } else {
            throw new \Exception('Could not find column ' . $column . ' in ' . $table);
        }
    }


}