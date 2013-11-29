<?php
namespace core\module\cms;

use classes\ajax;
use classes\collection;
use classes\db;
use classes\get;
use classes\module;
use classes\paginate;
use classes\table_array;
use core\module\cms\object\_cms_module;
use html\node;
use module\cms\form\add_field_form;
use module\cms\form\cms_filter_form;
use module\cms\form\new_module_form;
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
    /**
     * @var
     */
    public $mid;
    /**
     * @var object\_cms_module
     */
    public $module;

    /**
     * @var
     */
    public $object = false;
    public $order;
    /**
     * @var
     */
    public $tot;
    /**
     * @var
     */
    public $where;

    /**
     *
     */
    public static function do_clear_filter() {
        unset($_SESSION['cms'][$_REQUEST['_class_name']]);
        $cms_filter_form = new cms_filter_form();
        $cms_filter_form->do_submit();
        unset($_SESSION['cms'][$_REQUEST['_class_name']]);
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
     * @param array $path
     */
    public function __controller(array $path) {
        error_reporting(-1);
        \core::$page_config->title_tag = 'Admin Login - UKNXCL';
        \core::$css = ['/inc/module/cms/css/'];
        \core::$js = ['/.core/js/jquery.js', '/.core/js/_ajax.js', ' /.core/module/cms/js/cms.js', '/.core/js/colorbox.js', '/.core/plugins/ckeditor/ckeditor.js'];
        if (admin && !isset($path[1])) {
            $path[1] = 'dashboard';
        }
        $this->view = 'login';
        if (isset($path[1]) && !empty($path[1]) && admin) {
            $this->view = $path[1];
            if (isset($path[2])) {
                $this->set_from_mid($path[2]);
                $this->npp = isset($_SESSION['cms'][$this->module->get_class_name()]['npp']) ? $_SESSION['cms'][$this->module->get_class_name()]['npp'] : 25;
                $this->page = isset($path[4]) ? $path[4] : 1;
                $this->current_class = $this->module->get_class();
                $this->where = [];
                foreach ($this->current_class->get_fields() as $field) {
                    if (isset($_SESSION['cms'][$this->module->get_class_name()][$field->field_name]) && $_SESSION['cms'][$this->module->get_class_name()][$field->field_name]) {
                        $this->where[$field->field_name] = $_SESSION['cms'][$this->module->get_class_name()][$field->field_name];
                    }
                }
                $this->tot = db::result(db::get_query($this->module->table_name, ['count(*) AS count'], ['where_equals' => $this->where], $parameters), $parameters)->count;
            }
        }
        if (isset($path[3]) && !empty($path[3]) && admin) {
            $this->current->do_retrieve_from_id([], $path[3]);
        }
        parent::__controller($path);
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
    public function do_paginate() {
        ajax::add_script('window.location = window.location + "/page/" +' . $_REQUEST['value']);
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
            ajax::update($this->current->get_cms_edit_module()->get());
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
    public function get_filters() {
        $filter_form = new cms_filter_form($this->module->get_class_name());
        $filter_form->npp = $this->npp;
        $wrapper = node::create('div#filter_wrapper ul', [], $this->get_pagi('top') . $filter_form->get_html());
        return $wrapper;
    }

    /**
     * @return node
     */
    public function get_inner() {
        /** @var \classes\table $class */
        $class = $this->module->get_class_name();
        $options = ['where_equals' => $this->where, 'order' => $this->order ? : 'position'];
        if ($this->npp) {
            $options['limit'] = ($this->page - 1) * $this->npp . ',' . $this->npp;
        }
        $class::$retrieve_unlive = true;
        $sres = $class::get_all([], $options);
        $html = node::create('div#inner', [], $this->get_list($class, $sres));
        return $html;
    }

    public function get_sub_modules() {
        $html = '';
        $collection = object\_cms_module::get_all([], ['where_equals' => ['parent_mid' => $this->module->mid]]);
        if ($collection->count()) {
            $html .= $collection->iterate_return(function (_cms_module $module) {
                    $class = $module->get_class_name();
                    $object_collection = $class::get_all([], ['where_equals' => [$this->module->primary_key => $this->current->get_primary_key()]]);
                    return node::create('div.sub_module', [],
                        node::create('h3', [], $module->title) .
                        node::create('a', ['href' => '/cms/edit/' . $class::get_module_id() . '?' . $this->module->primary_key . '=' . $this->current->get_primary_key()], 'Add new ' . $module->title) .
                        $this->get_list_inner($object_collection, $class)
                    );
                }
            );
        }
        return $html;
    }

    /**
     * @param $obj
     * @param table_array $elements
     * @return array
     */
    public function get_list($obj, $elements) {
        return [
            $this->get_filters(new $obj()),
            $this->get_list_inner($elements, $obj),
            $this->get_pagi($elements->count())
        ];
    }

    protected function get_list_inner($elements, $class) {
        $object = new $class();
        return
            $object->get_cms_pre_list() .
            node::create('table.module_list', [],
                $this->get_table_head($object) .
                $this->get_table_rows($elements, $class)
            ) .
            $object->get_cms_post_list();
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
            $html->nest(node::create('li.right a', ['href' => '/cms/edit/' . $this->mid, 'title' => 'Add new ' . get::__class_name($this->current)], 'Add new ' . get::__class_name($this->current)));
        } else if ($this->view === 'module_list') {
            $html->nest(node::create('li.right a', ['href' => '/cms/new_module/', 'title' => 'Add new module'], 'Add new module'));
            $html->nest(node::create('li.right a', ['href' => "/cms/edit/" . object\_cms_group::get_module_id(), 'title' => 'Add new module group'], 'Add new module group'));
        }
        $html->nest(node::create('li.right a', ['href' => '/cms/module_list/', 'title' => 'View all modules'], 'All Modules'));
        return $html;
    }

    /**
     * @return node
     */
    public function get_new_field_form() {
        $form = new add_field_form();
        $form->mid = $this->mid;
        return $form->get_html();
    }

    /**
     * @return node
     */
    public function get_pagi() {
        $paginate = new paginate();
        $paginate->total = $this->tot;
        $paginate->npp = $this->npp;
        $paginate->page = $this->page;
        $paginate->base_url = '/cms/module/' . $this->mid;
        $paginate->act = 'cms:do_paginate';
        return $paginate->get();
    }

    /**
     * @param \classes\table $obj
     * @return node
     */
    public function get_table_head($obj) {
        $node = node::create('thead', [],
            node::create('th.edit') .
            node::create('th.live', [], 'Live') .
            node::create('th.position', [], 'Position') .
            $obj->get_fields()->iterate_return(function ($field) use ($obj) {
                    if ($field->list) {
                        return node::create('th.' . get_class($field) . '.' . $field->field_name . ($field->field_name == $obj->table_key ? '.primary' : ''), [], $field->title);
                    }
                    return '';
                }
            ) .
            node::create('th.delete')
        );
        return $node;
    }

    public function do_delete() {
        /** @var \classes\table $object */
        $object = new $_REQUEST['object'];
        db::update(get::__class_name($_REQUEST['object']))->add_value('deleted', 1)->filter($object->table_key . '=' . $_REQUEST['id'])->execute();
        ajax::add_script('document.location = document.location');
    }

    /**
     * @param table_array $objects
     * @return node
     */
    public function get_table_rows($objects) {
        $nodes = node::create('tbody');
        $new_collection = new table_array();
        /** @var \classes\table $table */
        $objects->iterate(function ($table) use ($new_collection) {
                if ($table->{'parent_' . $table->table_key} == 0) {
                    $table->children = new collection();
                    $new_collection[$table->get_primary_key()] = $table;
                } else if (isset($new_collection[$table->{'parent_' . $table->table_key}])) {
                    $new_collection[$table->{'parent_' . $table->table_key}]->children[] = $table;
                }
            }
        );
        /**
         * @var \classes\table $obj
         * @return string
         */
        return $new_collection->iterate_return(function ($obj) use ($nodes) {
                if (isset($obj->children)) {
                    $obj->children->uasort(function ($a, $b) {
                            return $b->position - $a->position;
                        }
                    );
                }
                return node::create('tr#' . get::__class_name($obj) . $obj->get_primary_key(), [], $obj->get_cms_list()) .
                (isset($obj->children) ? $obj->children->iterate_return(function ($child) {
                        return node::create('tr.child', [], $child->get_cms_list());
                    }
                ) : '');
            }
        );
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
