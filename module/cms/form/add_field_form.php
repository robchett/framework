<?php
namespace core\module\cms\form;

use classes\ajax;
use core\classes\db;
use form\form;
use module\cms\object\_cms_module;
use module\cms\object\field_type;

/**
 * @property mixed field_name
 */
abstract class add_field_form extends form {

    public $link_module;
    public $mid;
    public $title;
    public $type;

    public function __construct() {
        parent::__construct([
                form::create('field_string', 'title'),
                form::create('field_string', 'field_name'),
                form::create('field_link', 'type')->set_attr('link_module', '\module\cms\object\field_type')->set_attr('link_field', 'title'),
                form::create('field_link', 'link_module')->set_attr('link_module', '\module\cms\object\_cms_module')->set_attr('link_field', 'title'),
                form::create('field_link', 'link_field')->set_attr('link_module', '\module\cms\object\_cms_field')->set_attr('link_field', 'title'),
                form::create('field_int', 'mid')->set_attr('hidden', true)
            ]
        );
        $this->id = 'add_field_form';
    }

    public function get_html() {
        \core::$inline_script[] = '
            var $form = $("#add_field_form");
            $form.find("#add_field_form_field_link_module").hide();
            $form.find("#add_field_form_field_link_field").hide();
            $form.find("[name=type]").change(function() {
                if($(this).val() == 3 || $(this).val() == 4) {
                     $form.find("#add_field_form_field_link_module").show();
                     $form.find("#add_field_form_field_link_field").show();
                } else {
                     $form.find("#add_field_form_field_link_module").hide();
                     $form.find("#add_field_form_field_link_field").hide();
                }
            });
        ';
        return parent::get_html();
    }

    public function do_submit() {
        if ($_REQUEST['type'] == 3) {
            $this->get_field_from_name('link_field')->required = true;
            $this->get_field_from_name('link_module')->required = true;
        }
        if (parent::do_submit()) {
            $module = new _cms_module([], $this->mid);
            $field = new field_type([], $this->type);
            $type = '\form\field_' . $field->title;
            /** @var \form\field $field_type */
            $field_type = new $type('');
            if ($inner = $field_type->get_database_create_query()) {
                db::query('ALTER TABLE ' . $module->table_name . ' ADD ' . $this->field_name . ' ' . $field_type->get_database_create_query(), array(), 1);
            }

            if ($field->title == 'mlink') {
                $source_module = new \module\cms\object\_cms_module(['table_name', 'primary_key'], $this->link_module);
                db::create_table($module->table_name . '_link_' . $source_module->table_name, [
                        '_link_id' => 'SMALLINT NOT NULL AUTO_INCREMENT',
                        $module->primary_key => 'SMALLINT NOT NULL',
                        'link_' . $source_module->primary_key => 'SMALLINT NOT NULL',
                        'fid' => 'SMALLINT NOT NULL',
                    ], ['PRIMARY KEY (`_link_id`)']
                );
            }

            $res = db::select('_cms_field')->retrieve('MAX(position) AS pos')->filter_field('mid', $this->mid)->execute()->fetchObject();
            $insert = db::insert('_cms_field')
                ->add_value('title', $this->title)
                ->add_value('type', $field->title)
                ->add_value('field_name', $this->field_name)
                ->add_value('mid', $this->mid)
                ->add_value('position', $res->pos + 1);
            if($field->title == 'link' || $field->title == 'mlink') {
                $insert->add_value('link_module', $this->link_module)
                    ->add_value('link_field', $this->link_field);
            }
            $insert->execute();

            $obj = $module->get_class();
            $obj->mid = $this->mid;
            ajax::update($obj->get_cms_edit_module()->get());
        }
        ajax::update($this->get_html()->get());
    }
}
