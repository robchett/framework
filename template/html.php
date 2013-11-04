<?php
namespace core\template;

use classes\module;
use core;
use html\node;

class html {

    protected $module;

    public function __construct(module $module) {
        $this->module = $module;
    }

    public function get() {
        $html = '<!DOCTYPE html>' . node::create('html', [],
                $this->get_head() .
                $this->get_body() .
                $this->get_footer()
            );
        return $html;
    }

    public function get_head() {
        return node::create('head', [],
            node::create('title', [], core::$page_config->title_tag) .
            node::create('meta', ['name' => 'viewport', 'content' => 'initial-scale=1.0, user-scalable=no']) .
            core::$singleton->get_css()
        );
    }

    public function get_body() {
        return node::create('body.' . core::$page_config->get_body_class(), [],
            core::$page_config->pre_content .
            node::create('div#content', [], core::$singleton->body) .
            core::$page_config->post_content
        );
    }

    public function get_footer() {
        return core::$singleton->get_js();
    }
}
 