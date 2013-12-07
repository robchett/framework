<?php

namespace core\classes;

use html\node;

class paginate {

    public $total;
    public $base_url;
    public $npp;
    public $page;
    public $act;
    public $title;
    public $post_title;

    public function get() {
        $node = node::create('div');
        if ($this->npp && $this->total > $this->npp) {
            $pages = ceil($this->total / $this->npp);
            if ($this->title) {
                $node->nest(node::create('span.title', [], $this->do_replace($this->title)));
            }
            if ($pages > 40) {
                $node = node::create('select#pagi.cf', ['data-ajax-change' => $this->act]);
                for ($i = 1; $i <= $pages; $i++) {
                    $attributes = ['value' => $i];
                    if ($this->page = $i) {
                        $attributes['selected'] = 'selected';
                    }
                    $node->add_child(node::create('option', ['value' => $i], $i));
                }
            } else {
                $node = node::create('ul#pagi.cf');
                for ($i = 1; $i <= $pages; $i++) {
                    $node->add_child(node::create('li' . ($this->page == $i ? '.sel' : '') . ' a', ['href' => '/' . trim($this->base_url, '/') . '/page/' . $i], $i));
                }
            }
            if ($this->post_title) {
                $node->nest(node::create('span.title', [], $this->do_replace($this->post_title)));
            }
        }
        return $node;
    }

    public function __toString() {
        return $this->get()->get();
    }

    public function do_replace($source) {
        foreach ($this as $key => $value) {
            $source = str_replace('{' . $key . '}', $value, $source);
        }
        return $source;
    }

}
 