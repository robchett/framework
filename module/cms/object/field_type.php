<?php
namespace core\module\cms\object;

use classes\table;
use traits\table_trait;

abstract class field_type extends table {

    use table_trait;


    public $title;

}
