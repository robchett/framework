<?php

namespace core\classes;

use classes\collection as _collection;
use classes\db as _db;

/**
 * Class table_array
 */
abstract class table_array extends _collection {

    /**
     * @var bool
     */
    protected static $statics_set = false;
    /* @var table_iterator */
    public $iterator;
    /**
     * @var array
     */
    protected $retrieved_fields = [];
    /**
     * @var array
     */
    protected $original_retrieve_options = [];

    /**
     *
     */
    public function __construct($input = [], $flags = 0, $iterator_class = "\\classes\\collection_iterator") {
        parent::__construct($input, $flags, $iterator_class);
        if (!self::$statics_set) {
            $this->set_statics();
        }
    }

    /**
     *
     */
    protected function set_statics() {
        self::$statics_set = true;
    }

    /**
     * @param array $keys
     */
    public function lazy_load(array $keys) {
        $fields_to_retrieve = [];
        foreach ($keys as $key) {
            if (!$this->has_field($key)) {
                $fields_to_retrieve[] = $key;
            }
        }
        if (!empty($fields_to_retrieve)) {
            $this->do_retrieve($fields_to_retrieve, $this->original_retrieve_options);
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function has_field($name) {
        return (isset($this->retrieved_fields[$name]) ? $this->retrieved_fields[$name] : false);
    }

    /**
     * @param $fields
     * @param $options
     */
    public function do_retrieve($fields, $options) {
        self::get_all($fields, $options);
    }

    /**
     * @param string $class
     * @param array $fields_to_retrieve
     * @param array $options
     */
    public function get_all($class, array $fields_to_retrieve, $options = []) {
        /** @var table $obj */
        $obj = new $class();
        $links = [];
        $mlinks = [];
        $obj->set_default_retrieve($fields_to_retrieve, $options);
        table::organise_links($obj, $fields_to_retrieve, $links, $mlinks);
        $dependencies = [$class];
        foreach ($links as $module => $link_info) {
            $field = $link_info['field'];
            $retrieves = $link_info['retrieve'];
            $dependencies[] = $module;
            $options['join'][$module] = $module . '.' . $field->field_name . '=' . $obj->class_name() . '.' . $field->field_name;
            foreach ($retrieves as $retrieve) {
                $fields_to_retrieve[] = $module . '.' . $retrieve;
            }
        }
        $key = 'get_all_' . $class . '_fetch_' . implode(',', $fields_to_retrieve) . '_options_' . serialize($options);
        $elements = \classes\cache::grab($key,
            function () use ($class, $fields_to_retrieve, $options, $links, $mlinks, $obj) {
                $select = _db::get_query($class, $fields_to_retrieve, $options);
                $res = $select->execute();
                if (_db::num($res)) {
                    while ($row = _db::fetch($res, null)) {
                        /** @var table $class */
                        $object = new $class;
                        $object->set_from_row($row, $links);
                        foreach ($mlinks as $module => $blah) {
                            $object->{$module . '_elements'} = new \classes\table_array();
                            $object->$module = new _collection();
                        }
                        $this[] = $object;
                    }
                }
                $this->reset_iterator();
                foreach ($mlinks as $module => $link_info) {
                    /** @var \form\field_link $field */
                    $field = $link_info['field'];
                    $retrieves = $link_info['retrieve'];
                    $retrieves[] = 'l.' . $obj->get_primary_key_name() . ' AS linked_id';
                    $sub_class = $field->get_link_object();
                    $classes = $sub_class::get_all($retrieves, ['join' => [get::__class_name($class) . '_link_' . get::__class_name($sub_class) . ' l' => 'l.link_' . $sub_class->get_primary_key_name() . '=' . get::__class_name($sub_class) . '.' . $sub_class->get_primary_key_name()], 'where' => 'l.' . $obj->get_primary_key_name() . ' IN(' . implode(',', $this->get_table_keys()) . ')']);
                    /** @var table $sub_object */
                    foreach ($classes as $sub_object) {
                        $object = $this->find_table_key($sub_object->linked_id);
                        if ($object) {
                            $object->{$module . '_elements'}->push($sub_object);
                            $object->$module->push($sub_object->get_primary_key());
                        }
                    }
                }
                return $this->exchangeArray([]);
            }, $dependencies
        );
        $this->exchangeArray($elements);
    }

    protected function find_table_key($id) {
        foreach ($this as $object) {
            if ($object->get_primary_key() == $id) {
                return $object;
            }
        }
        return false;
    }

    protected function get_table_keys() {
        $res = [];
        $this->iterate(function ($object) use (&$res) {
                $res[] = $object->get_primary_key();
            }
        );
        return $res;
    }

    public function reverse() {
        $this->exchangeArray(array_reverse($this->getArrayCopy()));
    }

    /**
     * @return mixed
     */
    public function get_class() {
        return str_replace('_array', '', get_class($this));

    }

    /**
     * @param $index
     * @param $object
     */
    public function inject($index, $object) {
        $start = $this->subset(0, $index - 1);
        $end = $this->subset($index);
        $this->exchangeArray(array_merge($start, $object, $end));
    }

    /**
     *
     */
    public function rewind() {
        $this->iterator->rewind();
    }
}