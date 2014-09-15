<?php
namespace core\form;

abstract class field_hidden extends \form\field {

    public function __construct($title, $options = []) {
        parent::__construct($title, $options);
        $this->hidden = true;
        $this->attributes['type'] = 'hidden';
    }
}
