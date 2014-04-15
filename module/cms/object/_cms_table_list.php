<?php
namespace core\module\cms\object;

use classes\ajax;
use classes\collection;
use classes\get;
use classes\paginate;
use classes\session;
use classes\table_array;
use html\node;
use module\cms\form\cms_filter_form;
use module\cms\object\_cms_module as __cms_module;

class _cms_table_list {

    protected $module;
    protected $page;
    protected $npp;
    /** @var  table */
    protected $class;
    public $where = [];
    public $order = 'position';
    /** @var  \classes\table_array */
    protected $elements;

    public function __construct(__cms_module $module, $page) {
        $this->module = $module;
        $this->page = $page;
        $this->npp = session::is_set('cms', 'filter', $module->mid, 'npp') ? session::get('cms', 'filter', $module->mid, 'npp') : 25;
        $this->deleted = session::is_set('cms', 'filter', $module->mid, 'deleted') ? session::get('cms', 'filter', $module->mid, 'deleted') : false;
        $this->allowed_keys = ['' => 0] + (session::is_set('cms', 'expand', $module->mid) ? session::get('cms', 'expand', $module->mid) : []);
        $this->where = [];

        $class = $this->module->get_class_name();
        $this->class_name = $class;
        $this->class = new $class();

        foreach ($module->get_class()->get_fields() as $field) {
            if (session::is_set('cms', 'filter', $module->mid, $field->field_name) && session::get('cms', 'filter', $module->mid, $field->field_name)) {
                $this->where[$field->field_name] = session::get('cms', 'filter', $module->mid, $field->field_name);
            }
        }
    }

    public function get_table() {
        if(!isset($this->elements)) {
            $this->elements = $this->get_elements(0);
        }
        return node::create('div#inner', [], $this->get_list());
    }

    /**
     * @return node
     */
    public function get_filters() {
        $filter_form = new cms_filter_form($this->module->mid);
        $wrapper = node::create('div#filter_wrapper ul', [], $this->get_pagi($this->elements->count()) . $filter_form->get_html());
        return $wrapper;
    }

    protected function get_elements($parent_id = 0) {
        $class = $this->class_name;
        /** @var \classes\table $obj */
        $obj = new $class;
        $options = ['where_equals' => $this->where + ['parent_' . $obj->get_primary_key_name() => $parent_id], 'order' => $this->order ? : 'position',];
        if ($this->npp && $parent_id === 0) {
            $options['limit'] = ($this->page - 1) * $this->npp . ',' . $this->npp;
        }
        $class::$retrieve_unlive = true;
        if($this->deleted) {
            $class::$retrieve_deleted = true;
        }
        return $class::get_all(['*', '(SELECT COUNT(' . $obj->get_primary_key_name() . ') FROM ' . get::__class_name($obj) . ' t WHERE t.parent_' . $obj->get_primary_key_name() . ' = ' . get::__class_name($obj) . '.' . $obj->get_primary_key_name() . ' LIMIT 1) AS _has_child'], $options);
    }

    /**
     * @return node
     */
    public function get_pagi() {
        $paginate = new paginate();
        $paginate->total = $this->elements->get_total_count();
        $paginate->npp = $this->npp;
        $paginate->page = $this->page;
        $paginate->base_url = '/cms/module/' . $this->module->mid;
        $paginate->act = '\module\cms\object\_cms_table_list:do_paginate';
        $paginate->post_data = ['_mid' => $this->module->mid];
        return $paginate->get();
    }

    public static function do_paginate() {
        $module = new __cms_module();
        $module->do_retrieve_from_id([], $_REQUEST['_mid']);
        $object = new static($module, $_REQUEST['value']);
        ajax::update($object->get_table());
    }

    /**
     * @return array
     */
    protected function get_list() {
        return [
            $this->get_filters($this->class),
            $this->get_list_inner(),
            $this->get_pagi()
        ];
    }

    protected function get_list_inner() {
        return
            $this->module->get_cms_pre_list() .
            node::create('table.module_list', [],
                $this->get_table_head() .
                $this->get_table_rows($this->elements)
            ) .
            $this->module->get_cms_post_list();
    }

    /**
     * @return node
     */
    public function get_table_head() {
        $obj = $this->class;
        $node = node::create('thead', [],
            node::create('th.edit') .
            node::create('th.live', [], 'Live') .
            node::create('th.expand', [], 'Expand') .
            node::create('th.position', [], 'Position') .
            $obj->get_fields()->iterate_return(function ($field) use ($obj) {
                    if ($field->list) {
                        return node::create('th.' . get::__class_name($field) . '.' . $field->field_name . ($field->field_name == $obj->get_primary_key_name() ? '.primary' : ''), [], $field->title);
                    }
                    return '';
                }
            ) .
            node::create('th.delete')
        );
        return $node;
    }

    /**
     * @param table_array $elements
     * @param string  $class
     * @return node
     */
    public function get_table_rows($elements, $class = '') {
        $nodes = node::create('tbody');
        $keys = $this->allowed_keys;
        /**
         * @var \classes\table $obj
         * @return string
         */
        return $elements->iterate_return(function ($obj) use ($nodes, $keys, $class) {
                if ($obj->_has_child && in_array($obj->get_primary_key(), $keys)) {
                    $obj->_is_expanded = true;
                    $children = $this->get_elements($obj->get_primary_key());
                } else {
                    $obj->_is_expanded = false;
                    $children = false;
                }
                return node::create('tr#' . get::__class_name($obj) . $obj->get_primary_key() . ($obj->deleted ? '.deleted' : '') . $class, [], $obj->get_cms_list()) . ($children ? $this->get_table_rows($children, '.child') : '' );
            }
        );
    }

}