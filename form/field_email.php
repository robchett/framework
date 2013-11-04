<?php
namespace core\form;

abstract class field_email extends field {

    public function __construct($name, $options = array()) {
        parent::__construct($name, $options);
        $this->attributes['type'] = 'email';
    }
}
