<?php
namespace core\object;

use classes\table;
use traits\table_trait;

class image_format extends table {

    use table_trait;

    public static $module_id = 23;
    public $table_key = 'ifid';
}
 