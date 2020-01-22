<?php

namespace core\module\pages\objects;

use classes\get;
use classes\table;
use traits\table_trait;

abstract class page extends table {

    use table_trait;


    public $nav_title;
    public $direct_link;
    public $nav;
    public $pid;
    public $body;
    public $module_name;
    public $title;


    /**
     * @return string
     */
    public function get_url() {
        if($this->pid == \module\pages\controller::$homepage_id) {
            return '/';
        }
        if (isset($this->direct_link) && $this->direct_link) {
            return $this->direct_link;
        } else if (!empty($this->module_name)) {
            return '/' . $this->module_name;
        } else {
            return '/' . $this->pid . '/' . get::fn($this->title);
        }
    }
}
