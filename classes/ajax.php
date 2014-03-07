<?php

namespace core\classes;

use classes\ajax_element;
use classes\push_state as _push_state;
use traits\var_dump_import;

abstract class ajax {
    use var_dump_import;

    /** @var  ajax */
    protected static $singleton;

    public $inject = [];
    public $inject_script = [];
    public $inject_script_before = [];
    public $update = [];
    public $remove = [];
    public $push_state;
    public $redirect = null;


    public static function set_statics() {
        static::$singleton = new \classes\ajax();
    }

    public static function update($html) {
        if ($html) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('/html/body/*') as $node) {
                $o = new \stdClass();
                $o->id = $node->nodeName;
                $o->html = '';
                if (isset($node->attributes->getNamedItem('id')->nodeValue)) {
                    $o->id .= '#' . $node->attributes->getNamedItem('id')->nodeValue;
                }
                if (isset($node->attributes->getNamedItem('class')->nodeValue)) {
                    $o->id .= '.' . trim(str_replace('.', ' ', $node->attributes->getNamedItem('class')->nodeValue));
                }
                foreach ($node->childNodes as $subnode) {
                    $o->html .= $dom->saveXML($subnode);
                }
                static::$singleton->update[] = $o;
            }
        }
    }

    public static function do_serve() {
        if (!empty(\core::$inline_script)) {
            foreach (\core::$inline_script as $script) {
                self::add_script($script);
            }
        }
        if (isset(static::$singleton->redirect)) {
            self::inject('body', 'append', '<script id="ajax">window.location.href = "' . static::$singleton->redirect . '";</script>', true);
        }
        $o = new \stdClass();
        $o->pre_inject = [];
        if (static::$singleton->inject_script_before) {
            $s = new ajax_element();
            $s->id = 'body';
            $s->pos = 'append';
            $s->html = '<script id="ajax_script_pre">' . implode(';', static::$singleton->inject_script_before) . '</script>';
            $s->over = '#ajax_script_pre';
            $o->pre_inject[] = $s;
        }
        $o->update = static::$singleton->update;
        $o->inject = static::$singleton->inject;
        if (static::$singleton->inject_script) {
            $s = new ajax_element();
            $s->id = 'body';
            $s->pos = 'append';
            $s->html = '<script id="ajax_script">' . implode(';', static::$singleton->inject_script) . '</script>';
            $s->over = '#ajax_script';
            $o->inject[] = $s;
        }
        if (isset(static::$singleton->push_state)) {
            $o->push_state = static::$singleton->push_state;
        }
        if (isset($_REQUEST['no_ajax'])) {
            echo '
    <script>
        Array.prototype.each = function (callback, context) {
            for (var i = 0; i < this.length; i++) {
                callback(this[i], i, context);
            }
        }
        Array.prototype.count = function () {
            return this.length - 2;
        }
            window.top.window.handle_json_response(' . json_encode($o) . ')
    </script>';
        } else {
            echo json_encode($o);
        }
    }

    public static function inject($id, $pos, $html, $overwrite = '') {
        $o = new ajax_element();
        $o->id = $id;
        $o->pos = $pos;
        $o->html = (string)$html;
        $o->over = $overwrite;
        static::$singleton->inject[] = $o;
    }

    public static function push_state(_push_state $push_state) {
        static::$singleton->push_state = $push_state;
    }

    public static function add_script($script, $before = false) {
        $var = 'inject_script' . ($before ? '_before' : '');
        static::$singleton->{$var}[] = $script;
    }

    public static function current() {
        return static::$singleton;
    }

    public static function set_current(ajax $ajax) {
        static::$singleton = $ajax;
    }
}
