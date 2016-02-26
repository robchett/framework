<?php
define('root', $_SERVER['DOCUMENT_ROOT']);
define('core_dir', root . '/.core');
define('ajax', isset($_REQUEST['module']));

define('host', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Unknown_Host');
define('uri', isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : 'Unknown_URI');

define('ip', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown_IP');


if(!class_exists('\\classes\\autoloader', false)) {
    require_once(root . '/.core/classes/auto_loader.php');
    require_once(root . '/.core/dependent/classes/auto_loader.php');
}
$auto_loader = new \classes\auto_loader();

set_error_handler(['\classes\error_handler', 'handle_error']);
register_shutdown_function(['\classes\error_handler', 'fatal_handler']);

define('dev', in_array(host, \classes\ini::get('domain', 'development', [])));
define('local', in_array(host, \classes\ini::get('domain', 'local', ['localhost'])));
define('debug', in_array(ip, \classes\ini::get('developers', 'ip', [])));

date_default_timezone_set(\classes\get::ini('zone', 'time', 'Europe/London'));

if (debug || dev) {
    error_reporting(-1);
    ini_set('display_errors', '1');
}

if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
    define ('ie', true);
    define ('ie_ver', 0);
} else {
    define ('ie', false);
    define ('ie_ver', 0);
}

if (!defined('load_core') || load_core) {
    $core = new core();
}

function pretty_print($var, $return = false) {
    if ($return) {
        return '<pre>' . print_r($var, true) . '</pre>';
    } else {
        echo '<pre>' . print_r($var, true) . '</pre>';
    }
}
