<?php
namespace core\form;

abstract class field_datetime extends \form\field {

    public function __construct($title, $options = []) {
        parent::__construct($title, $options);
        $this->attributes['pattern'] = '[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}';
    }

    public function set_value($val) {
        $this->parent_form->{$this->field_name} = date('d/m/Y h:i:s', strtotime($val));
    }

    public static function sanitise_from_db($value) {
        return strtotime($value);
    }

    public function get_html() {
        $attributes = $this->attributes;
        $this->set_standard_attributes($attributes);
        return '<input ' . static::get_attributes($attributes) . ' value="' . date('d/m/Y h:i:s', strtotime($this->parent_form->{$this->field_name})) . '"/>' . "\n";
    }

    public function mysql_value($value) {
        return date('Y-m-d h:i:s', strtotime(str_replace('/', '-', $value)));
    }

    public function get_cms_list_wrapper($value, $class, $id) {
        return date('d/m/Y @h:i:s', strtotime($value));
    }
}
