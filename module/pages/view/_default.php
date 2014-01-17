<?php

namespace core\module\pages\view;

use classes\view;

abstract class _default extends \template\html {

    /** @var \module\pages\controller */
    public $module;

    public function get_view() {
        return $this->module->current->body;
    }

    public function get_page_selector() {
        return 'pages-' . $this->module->current->pid;
    }
}
