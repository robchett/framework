<?php
namespace core\module\cms\view;

use html\a;
use html\node;
use module\cms\object\_cms_module;

abstract class module_list extends cms_view {

    public function get_view() {
        $html = node::create('div.container-fluid', [],
            node::create('h2.page-header', [], 'Module List') .
            node::create('p', [], 'Manage your modules from here') .
            $this->get_module_list()
        );
        return $html;
    }

    public function get_module_list() {
        $modules = _cms_module::get_all(['mid', 'title', 'primary_key', '_cms_group.title', 'table_name'], ['join' => ['_cms_group' => '_cms_group.gid = _cms_module.gid']]);
        if ($modules) {
            $html = node::create('div', [],
                node::create('table.module.table.table-striped', [],
                    node::create('thead', [],
                        node::create('th', [], 'Module ID') .
                        node::create('th', [], 'Group') .
                        node::create('th', [], 'Title') .
                        node::create('th', [], 'Table Name') .
                        node::create('th', [], 'Primary Key')
                    ) .
                    $modules->iterate_return(
                        function (_cms_module $module) {
                            $attributes = ['href' => '/cms/admin_edit/' . $module->mid];
                            return node::create('tr', [],
                                node::create('td a', $attributes, $module->mid) .
                                node::create('td a', $attributes, $module->_cms_group->title) .
                                node::create('td a', $attributes, $module->title) .
                                node::create('td a', $attributes, $module->table_name) .
                                node::create('td a', $attributes, $module->primary_key)
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
