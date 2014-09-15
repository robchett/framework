<?php
namespace core\module\cms\view;

use html\node;

abstract class edit extends cms_view {

    /** @var  \module\cms\controller */
    public $module;

    public function get_view() {
        $html = node::create('div', [],
            node::create('h2.container-fluid.page-header', [], 'Edit a ' . get_class($this->module->current)) .
            ($this->module->current->is_deleted() ? node::create('div.container div.bs-callout.bs-callout-warning p', [], 'This element is deleted') : '') .
            (!$this->module->current->is_live() && $this->module->current->get_primary_key() ? node::create('div.container div.bs-callout.bs-callout-warning p', [], 'This element is not live') : '') .
            $this->module->current->get_cms_edit() .
            ($this->module->current->get_primary_key() ? $this->module->get_sub_modules() : '')
        );
        return $html;
    }
}