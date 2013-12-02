<?php
namespace core\module\cms\view;

use core\core;

/**
 * Class cms_view
 * @package cms
 * @property \module\cms\controller $module
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
        return parent::get_title_tag() . ' | CMS | ' . ucwords(str_replace('_', ' ', \classes\get::__class_name($this))) . (isset($this->module->current) ? ' | ' . $this->module->current->get_title() : '');
    }
}
