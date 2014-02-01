<?php

namespace core\classes;

use classes\push_state_data as _data;
use traits\var_dump_import;

abstract class push_state {
    use var_dump_import;

    const REPLACE = 1;
    const PUSH = 2;
    public $url = '';
    public $title = '';
    public $type;
    public $push = 0;


    public function __construct() {
        $this->data = new _data();
        $this->data->actions = [];
        $this->type = self::PUSH;
    }

    public function get() {
        if (!ie) {
            $data = json_encode($this->data);
            $script = '$.fn.ajax_factory.states["' . $this->url . '"] = ' . $data . ';';
            if ($this->type == self::PUSH) {
                $script .= 'window.history.pushState(' . $data . ', "","' . $this->url . '");';
            } else {
                $script .= 'window.history.replaceState(' . $data . ', "","' . $this->url . '");';
            }
            \core::$inline_script[] = $script;
        }
    }
}
