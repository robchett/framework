<?php

namespace core\classes;

use html\node;

class icon {

    public static function get($icon, $tag = 'span') {
        return node::create($tag . '.glyphicon.-glyphicon-' . $icon, [], '');
    }

}