<?php

namespace core\classes;


use Twig_Environment;

class twig {

    protected static $singleton;
    public $loaders;
    public $env;

    const LOADER_FILE = 0;
    const LOADER_STRING = 1;

    protected function __construct() {
        $this->loaders = [
            static::LOADER_FILE => new \Twig_Loader_Filesystem($this->get_default_paths())];
        $this->env = new Twig_Environment($this->loaders[static::LOADER_FILE], [
            'cache' => root . '/.cache/twig',
            'debug' => true,
        ]);
    }

    public function get_default_paths() {
        return [
            root,
            root . '/template/twig'
        ];
    }

    public function set_template_paths($paths = null) {
        $this->loaders[static::LOADER_FILE]->setPaths($paths ?: $this->get_default_paths());
    }

    /**
     * @return static
     */
    public static function singleton() {
        if (!static::$singleton) {
            static::$singleton = new static;
        }
        return static::$singleton;
    }

    public function render_file($file, $env, $options = []) {
        return $this->env->render($file, $env);
    }


}