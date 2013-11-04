<?php
namespace core\form;

abstract class field_float extends field {

    public function __construct($title, $options = array()) {
        parent::__construct($title, $options);
    }

    public function get_database_create_query() {
        return 'decimal (12,4)';
    }

    public function do_validate(&$error_array) {
        if ($this->required && empty($this->parent_form->{$this->field_name})) {
            $error_array[$this->field_name] = $this->field_name . ' is required field';
        } else if (!empty($this->parent_form->{$this->field_name}) && !is_numeric($this->parent_form->{$this->field_name})) {
            $error_array[$this->field_name] = $this->field_name . ' is not_numeric';
        }
    }
}