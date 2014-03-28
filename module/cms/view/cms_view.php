<?php
namespace core\module\cms\view;

use classes\get;

/**
 * Class cms_view
 *
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
        $parts = [parent::get_title_tag(), 'CMS', ucwords(str_replace('_', ' ', \classes\get::__class_name($this)))];
        if (isset($this->module->current)) {
            $parts[] = ucwords(str_replace('_', ' ', get::__class_name($this->module->current)));
            if ($this->module->current->get_primary_key()) {
                if ($title = $this->module->current->get_title()) {
                    $parts[] = $title;
                } else {
                    $parts[] = $this->module->current->get_primary_key();
                }
            }
        }
        return implode(' | ', $parts);
    }
}
