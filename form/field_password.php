<?php
namespace core\form;

abstract class field_password extends \form\field {

    public function __construct($title, $options = []) {
        parent::__construct($title, $options);
        $this->attributes['type'] = 'password';
    }

    public function get_save_sql() {
        return md5($this->parent_form->{$this->field_name});
    }
}
