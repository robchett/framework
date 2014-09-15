<?php
namespace core\module\cms\view;

use html\node;

abstract class new_module extends cms_view {

    /** @var  \module\cms\controller */
    public $module;

    public function get_view() {
        $html = node::create('div.container-fluid', [],
            node::create('h2.page-header', [], 'New Module') .
            node::create('p', [], 'Create a new module and nest it under a group.') .
            $this->module->get_admin_new_module_form()
        );
        return $html;
    }
}
