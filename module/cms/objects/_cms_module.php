<?php
namespace core\module\cms\objects;

use classes\jquery;
use classes\table;
use form\field_collection;
use html\node;
use module\cms\form\add_field_form;
use module\cms\form\cms_change_group_form;
use module\cms\form\edit_field_form;
use module\cms\form\edit_module_form;
use module\cms\objects\_cms_field;
use traits\table_trait;

abstract class _cms_module extends table {

    use table_trait;

    public static $default_fields = [
        'mid',
        'primary_key',
        'namespace',
        'table_name',
        'nestable'
    ];
    /** @var field_collection */
    public $_field_elements;
    public $_cms_field_elements;
    public $namespace;
    public $primary_key;
    public $table_name;
    public $title;
    public $gid;
    public $mid;

    public function __construct($fields = [], $id = 0) {
        parent::__construct($fields, $id);
        $this->_field_elements = new field_collection();
    }


    public function get_class_name() {
        if ($this->namespace) {
            return '\\module\\' . $this->namespace . '\\objects\\' . $this->table_name;
        } else {
            return '\\objects\\' . $this->table_name;
        }
    }

    public function get_primary_key_name() {
        return 'mid';
    }

    /** @return table */
    public function get_class() {
        $class = $this->get_class_name();
        return new $class();
    }

    /**
     *
     */
    public function get_cms_change_group() {
        $form = new cms_change_group_form();
        $form->mid = $_REQUEST['mid'];

        jquery::colorbox(['html' => $form->get_html()->get()]);
    }

    public static function create($title, $table_name, $primary_key, $group, $namespace = '') {
        $module = new \module\cms\objects\_cms_module();
        $module->do_retrieve(['title'], ['where_equals' => ['table_name' => $table_name]]);
        if (!$module->get_primary_key()) {
            $module->title = $title;
            $module->table_name = $table_name;
            $module->primary_key = $primary_key;
            $module->gid = $group;
            $module->namespace = $namespace;
            $module->do_save();
        } else {
            throw new \Exception('Module ' . $title . ' already exists.');
        }
        return $module;
    }

    /** @return \html\node */
    public function get_cms_edit_module() {
        $form = new \module\cms\form\edit_module_form();
        $form->set_from_object($this, false);
        return node::create('div.panel.panel-body', [], $form->get_html());
    }

    public function get_fields_list() {

        $obj = $this->get_class();

        $list = node::create('table#module_def.table.table-striped.', [],
            node::create('thead', [],
                node::create('th', [], 'Live') .
                node::create('th', [], 'Edit') .
                node::create('th', [], 'Field id') .
                node::create('th', [], 'Pos') .
                node::create('th', [], 'Title') .
                node::create('th', [], 'Database Title') .
                node::create('th', [], 'Type') .
                node::create('th', [], 'List') .
                node::create('th', [], 'Required') .
                node::create('th', [], 'Filter') .
                node::create('th', [], '')
            ) .
            $obj->get_fields()->iterate_return(function (\form\field $field) use ($obj) {
                    $field->parent_form = $obj;
                    return (node::create('tr.vertical-align', [], $field->get_cms_admin_edit()));
                }
            )
        );
        return $list;
    }


    /**
     * @return node
     */
    public function get_edit_field_form() {
        $form = new edit_field_form();
        $form->mid = $this->mid;
        $field = new _cms_field([], $_REQUEST['fid']);
        $form->set_from_object($field, false);
        die(
            node::create('div.modal-header', [], 'Add new field') .
            node::create('div.modal-body', [], $form->get_html()) .
            '<script>' . implode("\n", \core::$inline_script) . '</script>'
        );
    }

    /**
     * @return node
     */
    public function get_new_field_form() {
        $form = new add_field_form();
        $form->mid = $_REQUEST['mid'];
        die(
            node::create('div.modal-header', [], 'Add new field') .
            node::create('div.modal-body', [], $form->get_html()) .
            '<script>' . implode("\n", \core::$inline_script) . '</script>'
        );
    }
}
