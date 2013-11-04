<?php
namespace core\module\cms\object;

use core\classes\table;
use traits\table_trait;

abstract class field_type extends table {

    use table_trait;

    public $table_key = 'ftid';
    public static $module_id = 16;
    public $title;

}
