<?php
namespace core\module\cms\object;

use classes\table;
use module\cms\object\_cms_field as __cms_field;
use module\cms\object\_cms_module as __cms_module;
use traits\table_trait;

abstract class _cms_field extends table {

    use table_trait;

    protected static $cms_fields;
    public $fid;
    public $field_name;
    public $title;
    public $type;
    public $link_field;
    public $link_module;

    /**
     * @param $fid
     * @return __cms_field
     */
    public static function get_field_from_fid($fid) {
        if (!isset(self::$cms_fields)) {
            $cms_fields = __cms_field::get_all([]);
            $cms_fields->iterate(function ($object) {
                    self::$cms_fields[$object->fid] = $object;
                }
            );
        }
        return self::$cms_fields[$fid];
    }
}
