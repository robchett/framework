<?php
namespace core\module\cms\view;

use html\node;

abstract class module extends cms_view {

    /** @var  \module\cms\controller */
    public $module;

    public function get_view() {
        $html = node::create('div', [],
            node::create('h2', [], 'View all ' . get_class($this->module->current) . 's') .
            $this->module->get_inner()
        );
        return $html;
    }
}
