<?php

namespace core\classes;

use classes\ajax as _ajax;

abstract class jquery {

    public static $colourbox_defaults = array('opacity' => 0.7, 'static' => 1, 'maxWidth' => '85%', 'maxHeight' => '85%');

    public static function colorbox($options = array()) {
        $options = array_merge($options, self::$colourbox_defaults);
        _ajax::inject('body', 'append', '<script>$.colorbox(' . json_encode($options) . ')</script>');
    }
}
