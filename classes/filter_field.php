<?php
namespace core\classes;

use core\form\field;

class filter_field {

    /** @var  field $field */
    protected $field;
    public $label;
    public $options;

    public function __construct(field $field, $label, $options = []) {
        $this->field = $field;
        $this->label = $label;
        $this->options = $options;
    }

    public function inner_field() {
        return $this->field;
    }

    public function __get($name) {
        return $this->field->$name;
    }

}
 