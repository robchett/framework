<?php
namespace core\classes;

use classes\ajax;

class error_handler {

    public static $file_handler;
    public static $file_name;

    public static function handle_error($errno, $errstr, $errfile, $errline) {
        if (!self::$file_handler) {
            self::$file_name = root . '/log/error_' . time() . '.log';
            //self::$file_handler = fopen(self::$file_name, 'w+');
        }
        if (strpos($errfile, 'xdebug') !== 0) {
            if (function_exists('xdebug_break')) {
                xdebug_break();
            }
            if(!class_exists('\\core')) {
                require_once(root . '/inc/core.php');
                require_once(root . '/.core/core.php');
            }
            $error = '<div class="error_message mysql"><p>Error #' . $errno . ' "' . $errstr . '" in ' . $errfile . ' on line ' . $errline . '</p>' . \core::get_backtrace(1) . '</div>';
            //fwrite(self::$file_handler, $error . "\n\n\n---------------\n\n\n");
            if (dev || debug) {
                if (ajax) {
                    if(!class_exists('\\classes\\ajax')) {
                        require_once(root . '/.core/dependents/classes/ajax.php');
                        require_once(root . '/.core/classes/ajax.php');
                    }
                    ajax::inject('body', 'append', $error);
                } else {
                    echo $error;
                }
            }
        }
        return true;
    }

    public static function shutdown() {
        if (self::$file_handler) {
            if (!dev && !debug) {
                mail('robchett@gmail.com', 'Error on site ' . $_SERVER['HTTP_HOST'], file_get_contents(self::$file_name));
            }
            //unlink(self::$file_name);
        }
    }
}

 