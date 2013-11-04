<?php

namespace core\classes;

use classes\push_state;

abstract class ajax {

    public static $inject = array();
    public static $inject_script = array();
    public static $update = array();
    public static $remove = array();
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
            self::inject('body', 'append', '<script>window.location.href = "' . self::$redirect . '";</script>');
        }
        $o = new \stdClass();
        $o->update = self::$update;
        $o->inject = array_merge(self::$inject, self::$inject_script);
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

    public static function push_state(push_state $push_state) {
        self::$push_state = $push_state;
    }

    public static function add_script($script) {
        $o = new \stdClass();
        $o->id = 'body';
        $o->pos = 'append';
        $o->html = '<script>' . $script . '</script>';
        $o->over = '';
        self::$inject_script[] = $o;
    }
}
