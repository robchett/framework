<?php
namespace core\classes;

class error_handler {

    public static function handle_error($errno, $errstr, $errfile, $errline) {
        if (strpos($errfile, 'xdebug') !== 0) {
            if (function_exists('xdebug_break')) {
                xdebug_break();
            }
            require_once(root . '/.core/core.php');
            $error = '<div class="error_message mysql"><p>Error #' . $errno . ' "' . $errstr . '" in ' . $errfile . ' on line ' . $errline . '</p>' . \core::get_backtrace() . '</div>';
            if (ajax) {
                require_once(root . '/.core/classes/ajax.php');
                \classes\ajax::inject('body', 'append', $error);
            } else {
                echo $error;
            }
        }
    }
}

 