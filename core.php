<?php

namespace core;

use classes\ajax;
use classes\get;
use classes\ini;
use classes\page_config;
use classes\push_state;
use module\cms\object\_cms_field;
use module\cms\object\_cms_module;
use module\pages\object\page;
use template\html;

abstract class core {

    public static $push_state_ajax_calls = [];

    /** @var core */
    public static $singleton;
    public static $inline_script = [];
    public static $global_script = [];
    public static $js = ['/js/'];
    public static $css = ['/css/'];
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
        define('cms', $this->path[0] == 'cms');
        if (isset($_REQUEST['module'])) {
            $this->do_ajax();
        }
        $this->load_page();
    }

    public function do_ajax() {
        if ($_REQUEST['module'] == 'core' || $_REQUEST['module'] == 'this') {
            $module = 'core';
        } else {
            if (class_exists($_REQUEST['module'])) {
                $module = $_REQUEST['module'];
            } else {
                $module = '\\module\\' . $_REQUEST['module'] . '\\controller';
            }
        }
        if (class_exists($module)) {
            $class = new \ReflectionClass($module);
            if ($class->hasMethod($_REQUEST['act'])) {
                $method = new \ReflectionMethod($module, $_REQUEST['act']);
                if ($method->isStatic()) {
                    $module::{$_REQUEST['act']}();
                } else if ($module != 'core') {
                    $object = new $module;
                    $object->{$_REQUEST['act']}();
                } else {
                    $this->{$_REQUEST['act']}();
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
        if (!is_numeric($this->path[0])) {
            $this->module_name = $this->path[0];
        } else {
            $this->module_name = 'pages';
        }

        if (class_exists('module\\' . $this->module_name . '\controller')) {
            $class_name = 'module\\' . $this->module_name . '\controller';
            $this->module = new $class_name();
            $this->module->__controller($this->path);
            $this->module->page = $this->pagination_page;
            $push_state = $this->module->get_push_state();
            if ($push_state) {
                $push_state->data->actions = array_merge($push_state->data->actions, self::$push_state_ajax_calls);
                if (!ajax) {
                    $push_state->type = push_state::REPLACE;
                    $push_state->get();
                } else {
                    ajax::push_state($push_state);
                }
            }
            if (!ajax) {
                echo $this->module->view_object->get_page();
            } else {
                $this->module->view_object->get();
            }
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
        $uri_no_qs = parse_url($uri, PHP_URL_PATH);
        $this->path = explode('/', trim($uri_no_qs, '/'));
        if (!$this->path[0]) {
            $this->path[0] = ini::get('site', 'default_module', 'pages');
        }
        $this->pagination_page = $this->get_page_from_path();
        define('clean_uri', implode('/', $this->path));
    }

    /**
     * @return string
     */
    public static function get_backtrace() {
        $trace = debug_backtrace();
        array_reverse($trace);
        unset($trace[count($trace) - 1]);
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
     * @param $fid
     * @return _cms_field
     */
    public static function get_field_from_fid($fid) {
        if (!isset(self::$cms_fields)) {
            $cms_fields = _cms_field::get_all([]);
            $cms_fields->iterate(function ($object) {
                    self::$cms_fields[$object->fid] = $object;
                }
            );
        }
        return self::$cms_fields[$fid];
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
}
