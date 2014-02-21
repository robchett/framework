<?php
namespace core\module\cms\view;

use html\node;

abstract class
admin_edit extends cms_view {

    /** @var \module\cms\controller $module */
    public $module;

    public function get_view() {
        $html = node::create('div', [],
            $this->module->module->get_cms_edit_module() .
            $this->module->module->get_fields_list() .
            $this->module->module->get_new_field_form()
        );
        return $html;
    }
}
