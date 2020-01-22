<?php

namespace core\traits;

use classes\ajax;

trait twig_view {

    function get_view() {
        if (!ajax) {
            \core::$inline_script[] = $this->get_js();
        } else {
            ajax::add_script($this->get_js_ajax());
        }

        return \classes\twig::singleton()->render_file($this->get_template_file(), $this->get_template_data());
    }

    function get_template_file() {
        return str_replace('.php', '.twig', __FILE__);
    }

    abstract function get_template_data();

    public function get_js() {
        return false;
    }

    public function get_js_ajax() {
        return $this->get_js();
    }

}