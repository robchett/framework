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
        $options = ['where_equals' => $this->where, 'order' => $this->order ? : 'position'];
        if ($this->npp) {
            $options['limit'] = ($this->page - 1) * $this->npp . ',' . $this->npp;
        }
        $class = $this->class_name;
        $class::$retrieve_unlive = true;
        if($this->deleted) {
            $class::$retrieve_deleted = true;
        }
        $this->elements = $class::get_all([], $options);
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
    public function get_list() {
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
                $this->get_table_rows()
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
     * @return node
     */
    public function get_table_rows() {
        $nodes = node::create('tbody');
        $new_collection = new table_array();
        /** @var \classes\table $table */
        $this->elements->iterate(function ($table) use ($new_collection) {
                if ($table->{'parent_' . $table->get_primary_key_name()} == 0) {
                    $table->children = new collection();
                    $new_collection[$table->get_primary_key()] = $table;
                } else if (isset($new_collection[$table->{'parent_' . $table->get_primary_key_name()}])) {
                    $new_collection[$table->{'parent_' . $table->get_primary_key_name()}]->children[] = $table;
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
                return node::create('tr#' . get::__class_name($obj) . $obj->get_primary_key() . ($obj->deleted ? '.deleted' : ''), [], $obj->get_cms_list()) .
                (isset($obj->children) ? $obj->children->iterate_return(function ($child) {
                        return node::create('tr#' . get::__class_name($child) . $child->get_primary_key() . ($child->deleted ? '.deleted' : '') . '.child', [], $child->get_cms_list());
                    }
                ) : '');
            }
        );
    }

}