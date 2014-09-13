<?php

namespace core\classes;

abstract class auto_loader {

    protected static $file_paths = [];

    public function __construct() {
        spl_autoload_register(['self', 'load']);
        $this->load_cache();
    }

    public function load($class) {
        static $depth = 0;
        $depth++;
        if (isset(static::$file_paths[$class])) {
            $path = static::$file_paths[$class];
        } else {

            $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            $path = false;
            $local_path = root . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . $class_path . '.php';
            $dependent_path = root . DIRECTORY_SEPARATOR . '.core' . DIRECTORY_SEPARATOR . 'dependent' . DIRECTORY_SEPARATOR . $class_path . '.php';
            $core_path = root . DIRECTORY_SEPARATOR . str_replace('core' . DIRECTORY_SEPARATOR, '.core' . DIRECTORY_SEPARATOR, $class_path) . '.php';
            $library_path = root . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $class_path . '.php';
            if (is_readable($local_path)) {
                $path = $local_path;
            } else if (is_readable($dependent_path)) {
                $path = $dependent_path;
            } else if (is_readable($core_path)) {
                $path = $core_path;
            } else if (is_readable($library_path)) {
                $path = $library_path;
            }
            static::$file_paths[$class] = $path;
        }

        if ($path) {
            require_once($path);
            if($depth == 1) {
                if(method_exists($class, 'set_statics')) {
                    $class::set_statics();
                }
            }
            $depth--;
            return true;
        } else {
            $depth--;
            return false;
        }
    }

    public function load_cache() {
        try {
            static::$file_paths = \classes\cache::get('autoloader.file_paths', ['autoloader']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __destruct() {
        \classes\cache::set(['autoloader.file_paths' => static::$file_paths], ['autoloader']);
    }
}