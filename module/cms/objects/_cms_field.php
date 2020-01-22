<?php
namespace core\module\cms\objects;

use classes\table;
use module\cms\objects\_cms_field as __cms_field;
use module\cms\objects\_cms_module as __cms_module;
use traits\table_trait;

abstract class _cms_field extends table {

    use table_trait;

    protected static $cms_fields;
    public static $default_fields = [
        'fid',
        'parent_fid',
        'field_name',
        'title',
        'type',
        'mid',
        'list',
        'filter',
        'required',
        'link_module',
        'link_field'
    ];
    public $fid;
    public $field_name;
    public $title;
    public $type;
    public $link_field;
    public $link_module;
    public $primary_key = 'fid';

    public static function create($field_name, $structure, $module) {
        $field = new __cms_field();
        $field->do_retrieve([], ['where_equals' => ['mid' => $module, 'field_name' => $field_name]]);
        if (!$field->get_primary_key()) {
            $field->field_name = $field_name;
            $field->mid = $module;
            $field->type = $structure->type;
            $field->title = isset($structure->title) ? $structure->title : ucwords(str_replace('_', ' ', $field_name));
            if (isset($structure->module) && $structure->module) {
                $_module = new __cms_module();
                $_module->do_retrieve(['mid'], ['where_equals' => ['table_name' => $structure->module]]);
                $field->link_module = $_module->mid;
                if (isset($structure->field) && $structure->field) {
                    $_field = new __cms_field();
                    $_field->do_retrieve(['fid'], ['where_equals' => ['field_name' => $structure->field, 'mid' => $_module->mid]]);
                    if ($_field->get_primary_key()) {
                        $field->link_field = $_field->fid;
                    }
                }
            }
            $field->list = (isset($structure->list) ? $structure->list : true);
            $field->filter = (isset($structure->filter) ? $structure->filter : true);
            $field->required = (isset($structure->required) ? $structure->required : true);
            $field->do_save();
        } else {
            throw new \Exception('Field ' . $field_name . ' already exists in module ' . $module);
        }
        return $field;
    }

    public function get_field() {
        $class = '\\form\\field_' . $this->type;
        /** @var \form\field $field */
        $field = new $class($this->field_name, []);
        $field->set_from_row($this);
        return $field;
    }

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
        return isset(self::$cms_fields[$fid]) ? self::$cms_fields[$fid] : false;
    }

    public function get_primary_key_name() {
        return 'fid';
    }
}
