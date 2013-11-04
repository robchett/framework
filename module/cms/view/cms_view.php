<?php
namespace core\module\cms\view;

use core\classes\view;
use core\core;

/**
 * Class cms_view
 * @package cms
 */
abstract class cms_view extends view {

    /**
     * @return \html\node
     */
    public function get() {
        core::$page_config->pre_content = $this->module->get_main_nav();
        return $this->get_view()->get();
    }

}
