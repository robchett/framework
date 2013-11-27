<?php
namespace core\form;

use classes\collection;
use core\classes\table;

abstract class field_link extends field {

    /** @var int|string */
    public $link_module;
    /** @var int|string */
    public $link_field;

    public $order;

    public $options = [];

    public function __construct($title, $options = []) {
        parent::__construct($title, $options);
        $this->attributes['type'] = 'number';
    }

    public function  get_cms_list_wrapper($value, $object_class, $id) {
        $class = (is_numeric($this->link_module) ? \core::get_class_from_mid($this->link_module) : $this->link_module);
        $field_name = (is_numeric($this->link_field) ? \core::get_field_from_fid($this->link_field)->field_name : $this->link_field);
        $object = new $class();
        /** @var table $object */
        $object->do_retrieve_from_id(array($field_name, $object->table_key), $value);
        return (isset($object->{$object->table_key}) && $object->{$object->table_key} ? $object->$field_name : '-');
    }

    public function get_database_create_query() {
        return 'int(6)';
    }

    public function get_html() {
        if (!$this->hidden) {
            return '<select ' . $this->get_attributes() . '>' . $this->get_options() . '</select>' . "\n";
        } else {
            return parent::get_html();
        }
    }

    public function get_link_fields() {
        if (is_numeric($this->link_field)) {
            $this->link_field = \core::get_field_from_fid($this->link_field)->field_name;
        }
        if (is_array($this->link_field)) {
            $fields = $this->link_field;
        } else {
            $fields = [$this->link_field];
        }
        return $fields;
    }

    public function get_link_module() {
        if (is_numeric($this->link_module)) {
            $this->link_module = \core::get_class_from_mid($this->link_module);
        }
        return $this->link_module;
    }

    /** @return \classes\table */
    public function get_link_object() {
        $class = $this->get_link_module();
        return new $class;
    }

    public function get_link_mid() {
        if (is_numeric($this->link_module)) {
            return $this->link_module;
        }
        $class = $this->link_module;
        return $class::$module_id;
    }

    public function get_options() {
        $html = '';
        /** @var $class \classes\table */
        $class = $this->get_link_module();
        $fields = $this->get_link_fields();

        /** @var $object table */
        $obj = new $class();

        if (!isset($this->options['order'])) {
            $this->options['order'] = $obj->table_key;
        }
        $options = $class::get_all(array_merge($fields, [$obj->table_key, 'parent_' . $obj->table_key]), $this->options);
        if (!$this->required) {
            $html .= '<option value="0">- Please Select -</option>';
        }

        $parents = new collection();
        $options->iterate(
            function (table $object) use (&$parents) {
                if (!$object->get_parent_primary_key()) {
                    $object->_children = new collection();
                    $parents[$object->get_primary_key()] = $object;
                } else if(isset($parents[$object->get_parent_primary_key()])){
                    $parents[$object->get_parent_primary_key()]->_children[] = $object;
                }
            }
        );
        $parents->iterate(function (table $object) use (&$html, $fields) {
                $html .= '<option value="' . $object->{$object->table_key} . '" ' . ($this->is_selected($object->{$object->table_key}) ? 'selected="selected"' : '') . '>' . $this->get_object_title($object, $fields) . '</option>';
                if ($object->_children->count()) {
                    $object->_children->iterate(
                        function (table $sub_object) use (&$html, $fields) {
                            $html .= '<option value="' . $sub_object->{$sub_object->table_key} . '" ' . ($this->is_selected($sub_object->{$sub_object->table_key}) ? 'selected="selected"' : '') . '>' . $this->get_object_title($sub_object, $fields, 1) . '</option>';
                        }
                    );
                }
            }
        );
        return $html;
    }

    protected function get_object_title(table $object, $fields, $depth = 0) {
        if (is_array($fields)) {
            $parts = [];
            foreach ($fields as $part) {
                $parts[] = $object->{str_replace('.', '_', $part)};
            }
            $title = implode(' - ', $parts);
        } else {
            $title = $object->$title_fields;
        }
        for ($i = 0; $i < $depth; $i++) {
            $title = ' - ' . $title;
        }
        return $title;
    }

    protected function is_selected($id) {
        return $selected = (isset($this->parent_form->{$this->field_name}) ? $this->parent_form->{$this->field_name} : 0) == $id;
    }
}
