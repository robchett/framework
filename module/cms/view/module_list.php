<?php
namespace core\module\cms\view;

use html\node;
use module\cms\object\_cms_module;

abstract class module_list extends cms_view {

    public function get_view() {
        $html = node::create('div', [],
            node::create('h2', [], 'Module List') .
            node::create('p', [], 'Manage your modules from here') .
            $this->get_module_list()
        );
        return $html;
    }

    public function get_module_list() {
        $modules = _cms_module::get_all(array('mid', 'title', 'primary_key', '_cms_group.title', 'table_name'), array('join' => array('_cms_group' => '_cms_group.gid = _cms_module.gid')));
        if ($modules) {
            $html = node::create('div', [],
                node::create('table.module', [],
                    $modules->iterate_return(
                        function (_cms_module $module) {
                            return node::create('tr', [],
                                node::create('td', [], $module->mid) .
                                node::create('td', [], $module->_cms_group_title) .
                                node::create('td', [], $module->title) .
                                node::create('td', [], $module->table_name) .
                                node::create('td', [], $module->primary_key)
                            );
                        }
                    ) .
                    node::create('thead', [],
                        node::create('th', [], 'Module ID') .
                        node::create('th', [], 'Group') .
                        node::create('th', [], 'Title') .
                        node::create('th', [], 'Table Name') .
                        node::create('th', [], 'Primary Key')
                    )
                )
            );
            return $html;
        }
        return '';
    }
}
