<?php
namespace core\form;

abstract class field_email extends \form\field {

    public function __construct($name, $options = []) {
        parent::__construct($name, $options);
        $this->attributes['type'] = 'email';
    }
}
