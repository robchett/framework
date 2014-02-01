<?php

namespace core\classes;

use html\node;

abstract class view {

    public $module;

    /**
     * @return \html\node
     */
    public abstract function get_view();

    public function get_view_ajax() {
        $content = $this->get_view();
        ajax::inject('#main', 'append', '<div id="' . $this->get_page_selector() . '" data-url="' . (isset($_POST['url']) ? $_POST['url'] : uri) . '">' . $content . '</div>', '#' . $this->get_page_selector());
    }

    public function get_page_selector() {
        return get::__namespace($this->module, 0) . (isset($this->module->current) && $this->module->current->get_primary_key() ? '-' . $this->module->current->get_primary_key() : '');
    }

    public function get() {
        if (!ajax) {
            \core::$inline_script[] = 'loaded_modules = {"' . uri . '":true};';
            return node::create('div#main div#' . $this->get_page_selector(), ['data-url' => isset($_POST['url']) ? $_POST['url'] : uri], $this->get_view());
        } else {
            $this->get_view_ajax();
        }
        return '';
    }

    public function get_ajax() {

    }
}