<?php
namespace core\form;

abstract class field_date extends \form\field {

    public function __construct($title, $options = []) {
        parent::__construct($title, $options);
        //$this->attributes['pattern'] = '[0-9]{2}/[0-9]{2}/[0-9]{4}';
        $this->attributes['type'] = 'date';
    }

    public function set_value($val) {
        $this->parent_form->{$this->field_name} = strtotime($val);
    }

    public static function sanitise_from_db($value) {
        return strtotime($value);
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = isset($_REQUEST[$this->field_name]) ? strtotime($_REQUEST[$this->field_name]) : '';
    }

    public function get_cms_list_wrapper($value, $object_class, $id) {
        return date('d-m-Y', $value);
    }

    public function mysql_value($value) {
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }
        return date('Y-m-d', $value);
    }

    public function get_value() {
        $value = (float) $this->parent_form->{$this->field_name};
        return $value ? date('Y-m-d', (float) $this->parent_form->{$this->field_name}) : '';
    }

}
