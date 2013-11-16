<?php
namespace core\classes;

class error_handler {

    public static $file_handler;

    public static function handle_error($errno, $errstr, $errfile, $errline) {
        if (!self::$file_handler) {
            self::$file_handler = fopen(root . '/log/error.log', 'w+');
        }
        if (strpos($errfile, 'xdebug') !== 0) {
            if (function_exists('xdebug_break')) {
                xdebug_break();
            }
            require_once(root . '/.core/core.php');
            $error = '<div class="error_message mysql"><p>Error #' . $errno . ' "' . $errstr . '" in ' . $errfile . ' on line ' . $errline . '</p>' . \core::get_backtrace() . '</div>';
            fwrite(self::$file_handler, $error . "\n\n\n---------------\n\n\n");
            if (dev || debug) {
                if (ajax) {
                    require_once(root . '/.core/classes/ajax.php');
                    \classes\ajax::inject('body', 'append', $error);
                } else {
                    echo $error;
                }
            }
        }
    }

    public static function shutdown() {
        if (self::$file_handler) {
            mail('robchett@gmail.com', 'Error on site', fread(self::$file_handler, 30000));
        }
    }
}

 