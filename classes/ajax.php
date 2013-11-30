<?php

namespace core\classes;

use classes\push_state as _push_state;

abstract class ajax {

    public static $inject = [];
    public static $inject_script = [];
    public static $inject_script_before = [];
    public static $update = [];
    public static $remove = [];
    public static $push_state;
    public static $redirect = null;

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
                self::$update[] = $o;
            }
        }
    }

    public static function do_serve() {
        if (!empty(\core::$inline_script)) {
            foreach (\core::$inline_script as $script) {
                self::add_script($script);
            }
        }
        if (isset(self::$redirect)) {
            self::inject('body', 'append', '<script id="ajax">window.location.href = "' . self::$redirect . '";</script>', true);
        }
        $o = new \stdClass();
        $o->pre_inject = [];
        if(self::$inject_script_before) {
            $s = new \stdClass();
            $s->id = 'body';
            $s->pos = 'append';
            $s->html = '<script id="ajax_script_pre">' . implode(';',self::$inject_script_before) . '</script>';
            $s->over = '#ajax_script_pre';
            $o->pre_inject[] = $s;
        }
        $o->update = self::$update;
        $o->inject = self::$inject;
        if(self::$inject_script) {
            $s = new \stdClass();
            $s->id = 'body';
            $s->pos = 'append';
            $s->html = '<script id="ajax_script">' . implode(';',self::$inject_script) . '</script>';
            $s->over = '#ajax_script';
            $o->inject[] = $s;
        }
        if (isset(self::$push_state)) {
            $o->push_state = self::$push_state;
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
        $o = new \stdClass();
        $o->id = $id;
        $o->pos = $pos;
        $o->html = (string) $html;
        $o->over = $overwrite;
        self::$inject[] = $o;
    }

    public static function push_state(_push_state $push_state) {
        self::$push_state = $push_state;
    }

    public static function add_script($script, $before = false) {
        self::${'inject_script' . ($before ? '_before' : '')}[] = $script;
    }
}
