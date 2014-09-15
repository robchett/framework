<?php

namespace core\html\bootstrap;

use html\node;

class modal {

    public $title;
    public $body;
    public $footer;
    public $id;
    public $attributes;

    /**
     * @param string                            $id
     * @param array                             $attributes
     * @param null|node|node[] $title
     * @param null|node|node[] $body
     * @param null|node|node[] $footer
     */
    public function __construct($id, $attributes = [], $title = null, $body = null, $footer = null) {
        $this->title = $title;
        $this->footer = $footer;
        $this->body = $body;
        $this->id = $id;
        $this->attributes = $attributes;
    }

    /**
     * @param string                            $id
     * @param array                             $attributes
     * @param null|node|node[] $title
     * @param null|node|node[] $body
     * @param null|node|node[] $footer
     *
     * @return static
     */
    public static function create($id, $attributes = [], $title = null, $body = null, $footer = null) {
        return new static($id, $attributes, $title, $body, $footer);
    }

    public function get() {
        return
            node::create('div#' . $this->id . '.modal.fade', $this->attributes,
                node::create('div.modal-dialog div.modal-content', [],
                    node::create('div.modal-header', [], $this->title) .
                    node::create('div.modal-body', [], $this->body) .
                    node::create('div.modal-footer', [], $this->footer)
                )
            );
    }

    public function __toString() {
        return $this->get()->get();
    }

} 