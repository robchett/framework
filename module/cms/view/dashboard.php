<?php
namespace core\module\cms\view;

use html\node;

abstract class dashboard extends cms_view {

    public function get_view() {
        $html = node::create('div', [],
            node::create('h2', [], 'Welcome to the dashboard') .
            node::create('div#summaries.cf', [])
        );
        return $html;
    }


}
