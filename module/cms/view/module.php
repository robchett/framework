<?php
namespace core\module\cms\view;

use classes\get;
use html\node;

abstract class module extends cms_view {

    /** @var  \module\cms\controller */
    public $module;

    public function get_view() {
        $html = node::create('div', [],
            node::create('h2.page-header.container-fluid', [], 'View all ' . ucwords(get::__class_name($this->module->current)) . 's') .
            $this->module->get_inner()
        );
        return $html;
    }
}
