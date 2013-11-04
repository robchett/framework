<?php

namespace core\classes;

abstract class page_config {

    public $title_tag = '';
    public $pre_content = '';
    public $post_content = '';
    public $body_classes = array();

    public function get_body_class() {
        return implode('.', $this->body_classes);
    }

    public function add_body_class() {
        $classes = func_get_args();
        $this->body_classes = array_merge($this->body_classes, $classes);
    }
}
