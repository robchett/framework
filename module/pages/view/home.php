<?php

namespace core\module\pages\view;


abstract class home extends \module\pages\view\_default {

    /** @var \module\cms\controller */
    public $module;

    public function get_view() {
        return $this->module->current->body;
    }

    public function get_page_selector() {
        return 'pages-' . $this->module->current->pid;
    }
}
