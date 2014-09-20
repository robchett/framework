<?php

namespace core\classes;

use html\node;

class icon {

    public static function get($icon, $tag = 'span', $attributes = []) {
        return node::create($tag . '.glyphicon.glyphicon-' . $icon, $attributes, '');
    }

}