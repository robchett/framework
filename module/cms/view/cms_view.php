<?php
namespace core\module\cms\view;

use core\core;

/**
 * Class cms_view
 * @package cms
 */
abstract class cms_view extends \template\html {

    /**
     * @return \html\node
     */
    public function get() {
        core::$page_config->pre_content = $this->module->get_main_nav();
        return $this->get_view()->get();
    }

    public function get_title_tag() {
        return parent::get_title_tag() . ' - CMS - ' . \classes\get::__class_name($this) ;
    }
}
