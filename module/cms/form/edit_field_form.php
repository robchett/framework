<?php
namespace core\module\cms\form;

use classes\ajax;
use classes\db;
use classes\table;
use form\field_hidden;
use form\form;
use module\cms\form\add_field_form as _add_field_form;
use module\cms\object\_cms_field;
use module\cms\object\_cms_module;
use module\cms\object\field_type;

/**
 * @property mixed field_name
 */
abstract class edit_field_form extends _add_field_form {

    public $fid = 0;

    public function __construct() {
        parent::__construct();
        $this->add_field(form::create('field_hidden', 'fid'));
    }

    public function set_from_object($object, $change_target = true) {
        parent::set_from_object($object, $change_target);
        if(isset($object->type)) {
            $fields = field_type::get_all(['title']);
            /** @var field_type $field */
            foreach($fields as $field) {
                if($field->title == $object->type) {
                    $this->type = $field->get_primary_key();
                    break;
                }
            }
        }
    }

    public function do_submit() {
        $module = new _cms_module([], $this->mid);
        $field = new field_type([], $this->type);
        $old_field = new _cms_field([], $this->fid);
        $type = '\form\field_' . $field->title;
        /** @var \form\field $field_type */
        $field_type = new $type('');
        if ($inner = $field_type->get_database_create_query()) {
            db::query('ALTER TABLE ' . $module->table_name . ' MODIFY `' . $old_field->field_name . '` `' . $this->field_name . '` ' . $field_type->get_database_create_query(), [], 1);
        }
        if ($field->title == 'mlink' && $old_field->type !== 'mlink') {
            $source_module = new _cms_module(['table_name', 'primary_key'], $this->link_module);
            db::create_table_join(get::__class_name($this), $source_module->table_name);
        }

        $insert = db::update('_cms_field')
            ->add_value('title', $this->title)
            ->add_value('type', $field->title)
            ->add_value('field_name', $this->field_name)
            ->add_value('link_module', $this->link_module)
            ->add_value('link_field', $this->link_field)
            ->filter_field('fid', $this->fid);
        $insert->execute();

        table::reset_module_fields($module->mid);

        ajax::update($module->get_fields_list()->get());
        ajax::update($this->get_html()->get());
    }
}
