<?php
namespace core\module\cms\object;

use classes\table;
use traits\table_trait;

abstract class _cms_field extends table {

    use table_trait;

    public $fid;
    public $field_name;
    public $table_key = 'fid';
    public $title;
    public $type;

    public static $module_id = '';
}
