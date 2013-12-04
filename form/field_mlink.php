<?php
namespace core\form;

use form\field_link as _field_link;
use module\cms\object\_cms_module;

abstract class field_mlink extends _field_link {

    public $value = [];

    public function  get_cms_list_wrapper($value, $object_class, $id) {
        $class = $this->get_link_module();
        $field_name = $this->get_link_fields();

        $source_module = new _cms_module(['table_name', 'primary_key'], $this->get_link_mid());
        $parent_class = get_class($this->parent_form);
        $module = new _cms_module(['table_name', 'primary_key'], $parent_class::get_module_id());

        $select_options = [
            'where' => 'link.' . $module->primary_key . ' IS NOT NULL',
            'join' => [
                $module->table_name . '_link_' . $source_module->table_name . ' link' => 'link_' . $source_module->primary_key . '= ' . $source_module->primary_key . ' AND link.' . $module->primary_key . '=' . $this->parent_form->{$module->primary_key}
            ],
            //'order' => isset($this->order) ? $this->order : 'title'
        ];

        /** @var \classes\collection $objects */
        $objects = $class::get_all($field_name, $select_options);
        return $objects->iterate_return(function ($object) use ($field_name) {
                return $object->{$field_name[0]} . '<br/>';
            }
        );
    }

    public function get_database_create_query() {
        return false;
    }

    public function set_standard_attributes() {
        parent::set_standard_attributes();
        $this->attributes['multiple'] = 'multiple';
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = ((isset($_REQUEST[$this->field_name]) && $_REQUEST[$this->field_name]) ? $_REQUEST[$this->field_name] : []);
    }

    protected function is_selected($id) {
        return in_array($id, $this->parent_form->{$this->field_name});
    }


}
