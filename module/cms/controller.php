<?php
namespace core\module\cms;

use classes\ajax;
use classes\db;
use classes\get;
use classes\module;
use classes\session;
use core\core;
use html\node;
use module\cms\form\new_module_form;
use module\cms\object\_cms_module;
use module\cms\object;
use object\image_size;

/**
 * Class controller
 * @package cms
 */
abstract class controller extends module {

    /**
     * @var string
     */
    public static $url_base = '/cms/';
    /** @var \classes\table */
    public $current;
    /** @var \classes\table */
    public $current_class;
    public $mid;
    /** @var object\_cms_module */
    public $module;
    public $object = false;
    public $order;
    public $tot;
    public $where;

    /**
     * @param array $path
     */
    public function __controller(array $path) {
        \classes\compiler::disable();
        error_reporting(-1);
        \core::$css = ['/css/cms'];
        \core::$js = ['/.core/js/jquery.js', '/.core/js/_ajax.js', ' /.core/module/cms/js/cms.js', '/.core/js/colorbox.js', '/.core/plugins/ckeditor/ckeditor.js'];
        if (\core::is_admin() && !isset($path[1])) {
            $path[1] = 'dashboard';
        }
        $this->view = 'login';
        if (isset($path[1]) && !empty($path[1]) && \core::is_admin()) {
            $this->view = $path[1];
            if (isset($path[2])) {
                $this->set_from_mid($path[2]);
                $this->npp = session::is_set('cms', $this->module->get_class_name(), 'npp') ? session::get('cms', $this->module->get_class_name(), 'npp') : 25;
                $this->page = isset($path[4]) ? $path[4] : 1;
                $this->current_class = $this->module->get_class();
            }
        }
        if (isset($path[3]) && !empty($path[3]) && core::is_admin()) {
            /** @var table $class */
            $class = $this->current_class;
            $class::$retrieve_unlive = true;
            $class::$retrieve_deleted = true;
            $this->current->do_retrieve_from_id([], $path[3]);
        }
        parent::__controller($path);
    }

    public static function do_database_repair() {
        $database_manager = new object\cms_builder();
        $database_manager->manage();
    }

    public static function image_reprocess() {
        if (isset($_REQUEST['fid'])) {
            $image_size = new image_size([], $_REQUEST['fid']);
            $image_size->reprocess();
        }
    }

    /**
     *
     */
    public function set_view() {
        parent::set_view();
    }

    /**
     * @return bool
     */
    public function get_push_state() {
        return false;
    }

    /**
     *
     */
    public function do_reorder_fields() {
        if (isset($_REQUEST['mid']) && isset($_REQUEST['fid'])) {
            $this->set_from_mid($_REQUEST['mid']);
            $fields = object\_cms_field::get_all([], ['where_equals' => ['mid' => $_REQUEST['mid']]]);
            $reverse = false;
            if (isset($_REQUEST['dir']) && $_REQUEST['dir'] == 'down') {
                $reverse = true;
                $fields->reverse();
            }
            $cnt = $reverse ? count($fields) + 1 : 0;
            /** @var object\_cms_field $previous */
            $previous = $fields[0];
            $fields->iterate(function (object\_cms_field $field) use (&$previous, $reverse, &$cnt) {
                    $cnt += $reverse ? -1 : 1;
                    $field->position = $cnt;
                    $field->position = $cnt;
                    if ($field->fid == $_REQUEST['fid']) {
                        $field->position = $previous->position;
                        $previous->position = $cnt;
                    }
                    $previous = $field;
                }
            );
            if ($reverse) {
                $fields->reverse();
            }
            $fields->uasort(function (object\_cms_field $a, object\_cms_field $b) {
                    return $b->position - $a->position;
                }
            );
            $fields->iterate(function (object\_cms_field $field) {
                    db::update('_cms_field')->add_value('position', $field->position)->filter_field('fid', $field->fid)->execute();
                }
            );
            ajax::update($this->module->get_fields_list()->get());
        }
    }

    /**
     *
     */
    public function get() {
    }

    /**
     * @return node
     */
    public function get_admin_new_module_form() {
        $form = new new_module_form();
        return $form->get_html();
    }

    /**
     * @return node
     */
    public function get_inner() {
        $list = new object\_cms_table_list($this->module, $this->page);
        return $list->get_table();
    }

    public function get_sub_modules() {
        $html = '';
        $collection = object\_cms_module::get_all([], ['where_equals' => ['parent_mid' => $this->module->mid]]);
        if ($collection->count()) {
            $html .= $collection->iterate_return(function (_cms_module $module) {
                    $class = $module->get_class_name();
                    $list = new _cms_table_list($module, 1);
                    $list->where = [$this->module->primary_key => $this->current->get_primary_key()];
                    return node::create('div.sub_module', [],
                        node::create('h3', [], $module->title) .
                        node::create('a.button', ['href' => '/cms/edit/' . $class::get_module_id() . '?' . $this->module->primary_key . '=' . $this->current->get_primary_key()], 'Add new ' . $module->title) .
                        $list->get_table()
                    );
                }
            );
        }
        return $html;
    }

    /**
     * @return string
     */
    function get_main_nav() {
        $groups = object\_cms_group::get_all([]);
        $html = node::create('ul#nav', [],
            $groups->iterate_return(
                function (object\_cms_group $row) {
                    $modules = object\_cms_module::get_all([], ['where_equals' => ['gid' => $row->gid, 'parent_mid' => 0]]);
                    return node::create('li', [],
                        node::create('span', [], $row->title) .
                        node::create('ul', [],
                            $modules->iterate_return(
                                function (object\_cms_module $srow) {
                                    return node::create('li span a', ['href' => '/cms/module/' . $srow->mid], $srow->title);
                                }
                            )
                        )
                    );
                }
            )
        );
        if (isset($this->mid)) {
            $html->nest(node::create('li.right a', ['href' => '/cms/admin_edit/' . $this->mid, 'title' => 'Edit ' . get_class($this->current)], 'Edit Module'));
            $html->nest(node::create('li.right a', ['href' => '/cms/edit/' . $this->mid, 'title' => 'Add new ' . ucwords(str_replace('_', ' ', get::__class_name($this->current)))], 'Add new ' . get::__class_name($this->current)));
        } else if ($this->view === 'module_list') {
            $html->nest(node::create('li.right a', ['href' => '/cms/new_module/', 'title' => 'Add new module'], 'Add new module'));
            $html->nest(node::create('li.right a', ['href' => "/cms/edit/" . object\_cms_group::get_module_id(), 'title' => 'Add new module group'], 'Add new module group'));
        }
        $html->nest(node::create('li.right a', ['href' => '/cms/module_list/', 'title' => 'View all modules'], 'All Modules'));
        return $html;
    }

    public function do_delete() {
        /** @var \classes\table $object */
        $object = new $_REQUEST['object'];
        db::update(get::__class_name($_REQUEST['object']))->add_value('deleted', 1)->filter($object->get_primary_key_name() . '=' . $_REQUEST['id'])->execute();
        ajax::add_script('document.location = document.location');
    }

    /**
     * @param $mid
     */
    public function set_from_mid($mid) {
        $this->mid = $mid;
        $this->module = new object\_cms_module([], $this->mid);
        $this->current = $this->module->get_class();
        $this->current->mid = $this->mid;
    }

}
