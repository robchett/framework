<?php
namespace core\module\cms\view;

use html\node;
use object\image_size;

abstract class image_reprocess extends cms_view {

    /**
     * @return \html\node
     */
    public function get_view() {
        $images = image_size::get_all([]);
        if ($images) {
            $html = node::create('div', [],
                node::create('table.module', [],
                    node::create('thead', [],
                        node::create('th', [], 'Field ID') .
                        node::create('th', [], 'Title') .
                        node::create('th', [], '')
                    ) .
                    $images->iterate_return(
                        function (image_size $image_size) {
                            return node::create('tr', [],
                                node::create('td', [], $image_size->fid) .
                                node::create('td', [], $image_size->title) .
                                node::create('td a.button', ['href' => '?module=cms&act=image_reprocess&fid=' . $image_size->isid], 'Reprocess')
                            );
                        }
                    )
                )
            );
            return $html;
        }
        return '';
    }
}
 