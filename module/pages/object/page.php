<?php

namespace core\module\pages\object;

use classes\get;
use core\classes\table;
use traits\table_trait;

abstract class page extends table {

    use table_trait;

    public static $module_id = 1;
    public $nav_title;
    public $pid;
    public $table_key = 'pid';
    public $body;
    public $module_name = '';
    public $title;


    /**
     * @return string
     */
    public function get_url() {
        if (isset($this->direct_link) && $this->direct_link) {
            return $this->direct_link;
        } else if (!empty($this->module_name)) {
            return '/' . $this->module_name;
        } else {
            return '/' . $this->pid . '/' . get::fn($this->title);
        }
    }
}
