<?php
/**
 * Class _cms_group
 */
namespace core\module\cms\object;

use core\classes\table;
use traits\table_trait;

abstract class _cms_group extends table {

    use table_trait;

    public $gid;

    /**
     * @var string
     */
    public $table_key = 'gid';
    /**
     * @var int
     */
    public static $module_id = 19;
    public $title;


}
