<?php
/**
 * Class _cms_group
 */
namespace core\module\cms\object;

use classes\table;
use module\cms\object\_cms_group as __cms_group;
use traits\table_trait;

abstract class _cms_group extends table {

    use table_trait;

    public $gid;

    /**
     * @var string
     */
    public $title;

    public static function create($title) {
        $group = new __cms_group();
        $group->do_retrieve(['title'], ['where_equals' => ['title' => $title]]);
        if(!$group->get_primary_key()) {
            $group->title = $title;
            $group->do_save();
        }
        return $group;
    }

}
