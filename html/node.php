<?php
namespace core\html;

use classes\get;
use html\node as _node;

abstract class node {

    /**
     * @param array $attributes
     *
     * @return string
     */
    public static function get_attributes($attributes) {
        $html = '';
        foreach ($attributes as $attr => $value) {
            if(is_array($value)) {
                if($attr == 'class') {
                    $html .= ' ' . $attr . '="' . htmlentities(implode(' ', $value), ENT_QUOTES) . '"';
                } else if ($attr == 'style') {
                    $styles = [];
                    foreach ($value as $_attr => $_value) {
                        $styles[] = $_attr . ':' . $_value;
                    }
                    $html .= ' ' . $attr . '="' . htmlentities(implode(';', $styles), ENT_QUOTES) . '"';
                } else {
                    $html .= ' ' . $attr . '=\'' . json_encode($value) . '\'';
                }
            } else {
                $html .= ' ' . $attr . '="' . htmlentities($value, ENT_QUOTES) . '"';
            }
        }
        return $html;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->get();
    }

    /**
     * @param $attributes
     */
    public function set_standard_attributes(&$attributes) {
    }

    public $parent;
    protected $type = '';
    protected $id = '';
    protected $content = '';
    protected $class = [];
    protected $attributes = [];
    protected $children = [];
    protected $pointer;

    /**
     * @param $type
     * @param string $content
     * @param array $attr
     */
    public function __construct($type, $attr = [], $content = '') {
        $nodes = explode(' ', $type, 2);
        if (strstr($nodes[0], '#')) {
            list($this->type, $id) = explode('#', $nodes[0], 2);
            if (strstr($id, '.')) {
                list($this->id, $classes) = explode('.', $id, 2);
                $this->class = explode('.', $classes);
            } else {
                $this->id = $id;
            }
        } else if (strstr($nodes[0], '.')) {
            list($this->type, $classes) = explode('.', $nodes[0], 2);
            $this->class = explode('.', $classes);
        } else {
            $this->type = $nodes[0];
        }
        if (isset($nodes[1])) {
            $node = _node::create($nodes[1], $attr, $content);
            $this->add_child($node);
            $this->pointer = $node;
            $attr = [];
        } else {
            $this->content = $content;
        }
        $this->attributes = $attr;
    }

    /**
     * @param $type
     * @param string $content
     * @param array $attr
     * @return node
     */
    public static function create($type, $attr = [], $content = '') {
        $node = new _node($type, $attr, $content);
        return $node;
    }

    /**
     * @param $type
     * @param string $content
     * @param array $attr
     * @return string
     */
    public static function inline($type, $attr = [], $content = '') {
        $node = new _node($type, $attr, $content);
        return $node->get();
    }

    protected function combine_nodes($nodes) {
        if (is_array($nodes)) {
            $html = '';
            foreach($nodes as $node) {
                $html .= $this->combine_nodes($node);
            }
        } else {
            $html = $nodes;
        }
        return $html;
    }

    /**
     * @return string
     */
    public function get() {
        $attributes = $this->attributes;
        $this->set_standard_attributes($attributes);
        if($this->id) {
            $attributes['id'] = str_replace(' ', '-', $this->id);
        }
        if($this->class) {
            if(!isset($attributes['class'])) $attributes['class'] = [];
            $attributes['class'] = array_merge($attributes['class'], $this->class);
        }
        if ($this->is_self_closing()) {
            $html = '<' . $this->type . static::get_attributes($attributes) . '/>';
        } else {
            $html = '<' . $this->type . static::get_attributes($attributes) . '>';
            $html .= $this->combine_nodes($this->content);
            /** @var node $child */
            foreach ($this->children as $child) {
                $html .= $child->get();
            }
            $html .= '</' . $this->type . '>';
        }
        return $html;
    }

    public function is_self_closing () {
        return ($this->type == 'input');
    }

    /* @return node */
    public function nest() {
        if (func_num_args() == 1) {
            $children = func_get_arg(0);
        } else {
            $children = func_get_args();
        }
        if ($this->pointer) {
            $this->pointer->nest($children);
        } else {
            if (is_array($children)) {
                foreach ($children as $child) {
                    if (is_array($child)) {
                        $this->nest($child);
                    } else if ($child) {
                        $this->add_child($child);
                    }
                }
            } else if ($children) {
                $this->add_child($children);
            }
        }
        return $this;
    }


    /**
     * @param node $child
     * @return node
     */
    public function add_child(node $child) {
        if ($this->pointer) {
            $this->pointer->add_child($child);
        } else {
            $this->children[] = $child;
            $child->parent = $this;
        }
        return $this;
    }

    /**
     * @param $classes
     * @return $this
     */
    public function add_class($classes) {
        if (is_array($classes)) {
            foreach ($classes as $class) {
                $this->class[] = $class;
            }
        } else {
            $this->class[] = $classes;
        }
        return $this;
    }

    /**
     * @param $name
     * @param $val
     * @return $this
     */
    public function add_attribute($name, $val) {
        $this->attributes[$name] = $val;
        return $this;
    }
}
