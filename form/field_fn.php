<?php
namespace core\form;

use classes\get;

abstract class field_fn extends \form\field {

    public function mysql_value($value) {
        return get::fn($value);
    }
}
