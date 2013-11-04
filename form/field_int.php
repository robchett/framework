<?php
namespace core\form;

abstract class field_int extends field {

    public function __construct($title = '', $options = array()) {
        parent::__construct($title, $options);
        $this->attributes['type'] = 'number';
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = (isset($_REQUEST[$this->field_name]) && !empty($_REQUEST[$this->field_name]) ? $_REQUEST[$this->field_name] : null);
    }

    public function do_validate(&$error_array) {
        if (($this->required && (!isset($this->parent_form->{$this->field_name}))) || (isset($this->parent_form->{$this->field_name}) && !is_numeric($this->parent_form->{$this->field_name}))) {
            $error_array[$this->field_name] = $this->field_name . ' is required field';
        }
    }

    public function get_database_create_query() {
        return 'int(32)';
    }
}
