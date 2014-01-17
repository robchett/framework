<?php

namespace core\classes;

abstract class auto_loader {

    public function __construct() {
        spl_autoload_register(['self', 'load']);
    }

    public function load($class) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $path = false;
        $local_path = root . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . $class . '.php';
        $dependent_path = root . DIRECTORY_SEPARATOR . '.core' . DIRECTORY_SEPARATOR . 'dependent' . DIRECTORY_SEPARATOR . $class . '.php';
        $core_path = root . DIRECTORY_SEPARATOR . str_replace('core', '.core', $class) . '.php';
        $library_path = root . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $class . '.php';
        if (is_readable($local_path)) {
            $path = $local_path;
        } else if (is_readable($dependent_path)) {
            $path = $dependent_path;
        } else if (is_readable($core_path)) {
            $path = $core_path;
        } else if (is_readable($library_path)) {
            $path = $library_path;
        }

        if ($path) {
            require_once($path);
            return true;
        } else {
            return false;
        }
    }
}