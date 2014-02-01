<?php
namespace core\template;

use classes\get;
use classes\ini;
use classes\module;
use core;
use html\node;

abstract class html {

    protected $module;
    protected $inner_html;

    public function __construct(module $module) {
        $this->module = $module;
    }

    public function get_page() {
        $this->inner_html = $this->get();
        return '<!DOCTYPE html>' .
        node::create('html', [],
            $this->get_head() .
            $this->get_body() .
            $this->get_footer()
        );
    }

    public function get_head() {
        return node::create('head', [],
            node::create('title', [], $this->get_title_tag()) .
            node::create('meta', ['name' => 'viewport', 'content' => 'initial-scale=1.0, user-scalable=no']) .
            core::$singleton->get_css()
        );
    }

    public function get_title_tag() {
        return ini::get('site', 'title_tag', 'NO Title tag!!!');
    }

    public function get_body() {
        return node::create('body.' . core::$page_config->get_body_class(), [],
            core::$page_config->pre_content .
            node::create('div#content', [], $this->inner_html) .
            core::$page_config->post_content
        );
    }

    public function get_footer() {
        return core::$singleton->get_js();
    }

    /**
     * @return \html\node
     */
    public abstract function get_view();

    public function get_page_selector() {
        return get::__namespace($this->module, 0) . (isset($this->module->current) && $this->module->current->get_primary_key() ? '-' . $this->module->current->get_primary_key() : '');
    }

    public function get() {
        if (!ajax) {
            \core::$inline_script[] = 'loaded_modules = {"' . uri . '":true};';
            return node::create('div#main div#' . $this->get_page_selector(), ['data-url' => isset($_POST['url']) ? $_POST['url'] : '/' . uri], $this->get_view());
        } else {
            return['#main', 'append', '<div id="' . $this->get_page_selector() . '" data-url="' . (isset($_POST['url']) ? $_POST['url'] : '/' . uri) . '">' . $this->get_view() . '</div>', '#' . $this->get_page_selector()];
        }
    }
}
