<?php
namespace core\module\cms\view;

/**
 * Class cms_view
 * @package cms
 * @property \module\cms\controller $module
 */
abstract class cms_view extends \template\cms\html {

    /**
     * @return \html\node
     */
    public function get() {
        return $this->get_view()->get();
    }

    public function get_title_tag() {
        return parent::get_title_tag() . ' | CMS | ' . ucwords(str_replace('_', ' ', \classes\get::__class_name($this))) . (isset($this->module->current) ? ' | ' . $this->module->current->get_title() : '');
    }
}
