<?php
namespace core\objects;

use classes\table;
use form\field;
use form\field_collection;
use traits\table_trait;

class filter extends table {

    use table_trait;


    public $link_fid;
    public $link_mid;
    public $parent_fid;
    protected $field;
    public $title;
    public $order;


    /**
     * @param array $fields
     * @param array $options
     * @return field_collection
     */
    public static function get_all(array $fields, array $options = []) {
        $array = new field_collection();
        $array->get_all('\\objects\\filter', $fields, $options);
        return $array;
    }

    public function inner_field() {
        return $this->field;
    }

    /** @param field $field */
    public function set_field($field) {
        $this->field = $field;
    }

    public function __get($name) {
        return $this->field->$name;
    }

}
 