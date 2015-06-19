<?php

namespace core;

use classes\ajax;
use classes\compiler;
use classes\compiler_page;
use classes\css\css;
use classes\get;
use classes\ini;
use classes\page_config;
use classes\push_state;
use classes\session;
use module\pages\object\page;
use template\html;

abstract class core {

    public static $push_state_ajax_calls = [];

    /** @var core */
    public static $singleton;
    public static $inline_script = [];
    public static $global_script = [];
    public static $js = ['/js/script.js'];
    public static $css = ['/css/styles.css'];
    public static $cms_modules;
    public static $cms_fields;
    /** @var page_config */
    public static $page_config;
    public $body = '';
    /** @var int */
    public $pagination_page;
    public $pid = 0;
    public $pre_content = '';
    public $path = [];
    public $post_content = '';
    public $module_name = '';
    /** @var \classes\module */
    public $module;
    /** @var page */
    public $page;

    /**
     *
     */
    public function __construct() {
        self::$page_config = new page_config();
        self::$singleton = $this;
        $this->set_path(isset($_REQUEST['url']) ? : uri);
        define('cms', $this->path && $this->path[0] == 'cms');
        if (isset($_REQUEST['module'])) {
            $this->do_ajax();
        }
        $this->load_page();
    }

    public function do_ajax() {
        $class = $_REQUEST['module'];
        $function = $_REQUEST['act'];
        if ($class == 'core' || $class == 'this') {
            $module = 'core';
        } else {
            if (class_exists($class)) {
                $module = $class;
            } else {
                $module = '\\module\\' . $class . '\\controller';
            }
        }
        if (class_exists($module)) {
            $class = new \ReflectionClass($module);
            if ($class->hasMethod($function)) {
                $method = new \ReflectionMethod($module, $function);
                if ($method->isStatic()) {
                    $module::$function();
                } else if ($module != 'core') {
                    $object = new $module;
                    $object->$function();
                } else {
                    $this->$function();
                }
            }
        }
        ajax::do_serve();
        exit();
    }

    public function get_page_from_path() {
        $count = count($this->path);
        if ($count >= 2 && $this->path[$count - 2] == 'page' && is_numeric(end($this->path))) {
            $page = end($this->path);
            unset($this->path[$count - 1]);
            unset($this->path[$count - 2]);
            return $page;
        }
        return 1;
    }

    /**
     *
     */
    public function load_page() {
        $compiler = new compiler();
        $options = ['ajax' => ajax, 'admin' => core::is_admin(), "dev" => dev, "debug" => debug];
        try {
            $compiler_page = $compiler->load(uri, $options);
        } catch (\Exception $e) {
            if ($this->path) {
                if (!is_numeric($this->path[0])) {
                    $this->module_name = $this->path[0];
                } else {
                    $this->module_name = 'pages';
                }
            } else {
                $this->module_name = ini::get('site', 'default_module', 'pages');
            }

            $compiler_page = new compiler_page();


            if (class_exists('module\\' . $this->module_name . '\controller')) {
                $class_name = 'module\\' . $this->module_name . '\controller';
                $this->module = new $class_name();
                $this->module->__controller($this->path);
                $this->module->page = $this->pagination_page;
                if (!ajax) {
                    $compiler_page->content = $this->module->view_object->get_page();
                } else {
                    $compiler_page->content = $this->module->view_object->get();
                    $compiler_page->ajax = ajax::current();
                }
                $push_state = $this->module->get_push_state();
                if ($push_state) {
                    $push_state->data->actions = array_merge($push_state->data->actions, self::$push_state_ajax_calls);
                }
                $compiler_page->push_state = $push_state;
            }
            $compiler->save(uri, $compiler_page, $options);
        }

        if (!ajax) {
            if ($compiler_page->push_state) {
                $compiler_page->push_state->type = push_state::REPLACE;
                $compiler_page->push_state->get();
            }
            echo $compiler_page->content;
        } else {
            ajax::set_current($compiler_page->ajax);
            if ($compiler_page->push_state) {
                ajax::push_state($compiler_page->push_state);
            }
            $class = new \ReflectionClass('\classes\ajax');
            $function = $class->getMethod('inject');
            $function->invokeArgs(null, $compiler_page->content);
        }
    }

    /**
     *
     */
    public function set_page_from_path() {
        $this->page = new page();
        if (is_numeric($this->path[0])) {
            $this->page->do_retrieve_from_id([], (int) $this->path[0]);
        } else {
            $this->page->do_retrieve([], ['where_equals' => ['module_name' => $this->path[0]]]);
        }
        $this->pid = (isset($this->page->pid) ? $this->page->pid : 0);

    }

    /**
     * @param $uri
     */
    public function set_path($uri) {
        $uri_no_qs = trim(parse_url($uri, PHP_URL_PATH), '/');
        if ($uri_no_qs) {
            $this->path = explode('/', $uri_no_qs);
        }
        $this->pagination_page = $this->get_page_from_path();
        define('clean_uri', implode('/', $this->path));
    }

    /**
     * @param int $ignore_count The number of steps to ignore
     * @return string
     */
    public static function get_backtrace($ignore_count = 0) {
        $trace = debug_backtrace();
        array_reverse($trace);
        // Remove the get_backtrace entry
        array_shift($trace);
        for ($i = 0; $i < $ignore_count; $i++) {
            array_shift($trace);
        }
        $html = '<table><thead><th>File</th><th>Line</th><th>Function</th></thead>';
        foreach ($trace as $step) {
            $html .= '<tr>
            ' . (isset($step['file']) ? '<td>' . $step['file'] . '</td>' : '') . '
            ' . (isset($step['line']) ? '<td>' . $step['line'] . '</td>' : '') . '
            <td>' . (isset($step['class']) ? $step['class'] . (isset($step['type']) ? $step['type'] : '::') : '') . $step['function'] . '()</td>
            </tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * @return string
     */
    public function get_js() {
        $script = '';
        $inner = '';
        foreach (self::$js as $js) {
            $script .= '<script src="' . $js . '"></script>';
        }
        foreach (self::$inline_script as $js) {
            $inner .= $js;
        }
        if (!empty($inner))
            $script .= '<script>' . implode(';', self::$global_script) . ';$(document).ready(function(){' . $inner . '});</script>';
        return $script;
    }

    /**
     * @return string
     */
    public function get_css() {
        $html = '';
        foreach (self::$css as $css) {
            $html .= '<link type="text/css" href="' . $css . '" rel="stylesheet"/>';
        }
        return $html;
    }

    public static function is_admin() {
        compiler::allow();
        return session::is_set('admin');
    }

   public function get_js_sheet() {
        \classes\js\js::get_js();
    }
}
