<?php
namespace core\object;

use classes\table;
use traits\table_trait;

class image_crop extends table {

    use table_trait;

    public static $module_id = 22;
    public $table_key = 'icid';

}
 