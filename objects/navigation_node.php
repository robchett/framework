<?php
namespace core\objects;

use classes\table;
use html\node;
use object\navigation_node as _navigation_node;
use traits\table_trait;

class navigation_node extends table {

    use table_trait;

    public $link;
    public $title;

    public function get_title() {
        return $this->title;
    }

    public function get_url() {
        return $this->link;
    }

    public static function get_list($nnid = 0) {
        $nodes = _navigation_node::get_all([], ['where_equals' => ['parent_nnid' => $nnid]]);
        if ($nodes->count()) {
            return node::create('ul', [],
                $nodes->iterate_return(
                    function (_navigation_node $node) {
                        return node::create('li', [],
                            node::create('a', ['href' => $node->get_url()], $node->get_title()) .
                            static::get_list($node->get_primary_key())
                        );
                    }
                )
            );
        } else {
            return '';
        }
    }
}
 