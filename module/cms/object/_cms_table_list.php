<?php
namespace core\module\cms\object;

use classes\ajax;
use classes\collection;
use classes\get;
use classes\paginate;
use classes\session;
use classes\table;
use classes\table_array;
use html\bootstrap\modal;
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
        if (!isset($this->elements)) {
            $this->elements = $this->get_elements(0);
        }
        return node::create('div#inner', [], array_merge($this->get_list(), $this->get_delete_modal()));
    }

    protected function get_delete_modal() {
        \core::$inline_script[] = <<<JS
        $("body").on('click', 'button.delete', function(e) {
            $("#delete, #undelete").data('ajax-post', $(this).data('ajax-post'));
        });
JS;

        return [
            modal::create('delete_modal', [
                    'class'       => ['delete_modal', 'modal', 'fade'],
                    'role'        => 'dialog',
                    'tabindex'    => -1,
                    'aria-hidden' => true
                ],
                [
                    node::create('button.close', ['data-dismiss' => 'modal'], '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>'),
                    node::create('div.modal-title', [], 'Delete')
                ],
                [node::create('p', [], 'Are you sure you want to do this?')],
                [
                    node::create('button.btn.btn-default', ['data-dismiss' => 'modal'], 'Cancel') .
                    node::create('button#delete.btn.btn-primary', [
                        'data-dismiss'    => 'modal',
                        'data-ajax-click' => 'cms:do_delete'
                    ], 'Delete')
                ]
            ),
            modal::create('undelete_modal', [
                    'class'       => ['undelete_modal', 'modal', 'fade'],
                    'role'        => 'dialog',
                    'tabindex'    => -1,
                    'aria-hidden' => true
                ],
                [
                    node::create('button.close', ['data-dismiss' => 'modal'], '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>'),
                    node::create('div.modal-title', [], 'Un Delete')
                ],
                [node::create('p', [], 'Are you sure you want to do this?')],
                [
                    node::create('button.btn.btn-default', ['data-dismiss' => 'modal'], 'Cancel') .
                    node::create('button#undelete.btn.btn-primary', [
                        'data-dismiss'    => 'modal',
                        'data-ajax-click' => 'cms:do_undelete'
                    ], 'Un-delete')
                ]
            ),
            modal::create('true_delete_modal', [
                    'class'       => ['true_delete_modal', 'modal', 'fade'],
                    'role'        => 'dialog',
                    'tabindex'    => -1,
                    'aria-hidden' => true
                ],
                [
                    node::create('button.close', ['data-dismiss' => 'modal'], '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>'),
                    node::create('div.modal-title', [], 'Completely Delete')
                ],
                [
                    node::create('h2', [], 'This cannot be reversed!'),
                    node::create('p', [], 'Are you sure you want to do this?'),
                ],
                [
                    node::create('button.btn.btn-default', ['data-dismiss' => 'modal'], 'Cancel') .
                    node::create('button#undelete.btn.btn-primary', [
                        'data-dismiss'    => 'modal',
                        'data-ajax-click' => 'cms:do_delete'
                    ], 'Delete')
                ]
            )
        ];
    }

    /**
     * @return node
     */
    public function get_filters() {
        $filter_form = new cms_filter_form($this->module->mid);
        $wrapper = node::create('div.container-fluid div.panel.panel-default', [], [
            node::create('div.panel-heading h4.panel-title.clearfix', [], [
                node::create('a.btn.btn-default', ['href' => '#filter_bar', 'data-toggle' => 'collapse'], 'Filters'),
                node::create('div.pull-right', [], $this->get_pagi()),
            ]),
            node::create('div#filter_bar.panel-collapse.collapse div.panel-body', [], $filter_form->get_html())
        ]);
        return $wrapper;
    }

    protected function get_elements($parent_id = 0) {
        $class = $this->class_name;
        /** @var \classes\table $obj */
        $obj = new $class;
        $options = [
            'where_equals' => $this->where + ['parent_' . $obj->get_primary_key_name() => $parent_id],
            'order'        => $this->order ?: 'position',];
        if ($this->npp && $parent_id === 0) {
            $options['limit'] = ($this->page - 1) * $this->npp . ',' . $this->npp;
        }
        $class::$retrieve_unlive = true;
        if ($this->deleted) {
            $class::$retrieve_deleted = true;
        }
        return $class::get_all([
                '*',
                '(SELECT COUNT(' . $obj->get_primary_key_name() . ') FROM ' . get::__class_name($obj) . ' t WHERE t.parent_' . $obj->get_primary_key_name() . ' = ' . get::__class_name($obj) . '.' . $obj->get_primary_key_name() . ' LIMIT 1) AS _has_child'], $options);
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
            node::create('div.container-fluid', [], $this->get_pagi()),
        ];
    }

    protected function get_list_inner() {
        return
            $this->module->get_cms_pre_list() .
            node::create('div.container-fluid table.module_list.table.table-striped', [], [
                $this->get_table_head(),
                $this->get_table_rows($this->elements)
            ]) .
            $this->module->get_cms_post_list();
    }

    /**
     * @return node
     */
    public function get_table_head() {
        $obj = $this->class;

        $nodes = [];
        $nodes[] = node::create('col.btn-col');
        $nodes[] = node::create('col.btn-col');
        if($this->module->nestable) {
            $nodes[] = node::create('col.btn-col');
        }
        $nodes[] = node::create('col.btn-col2');
        $obj->get_fields()->iterate(function ($field) use (&$nodes) {
            $nodes[] = node::create('col.' .  get::__class_name($field));
        });
        $nodes[] = node::create('col.btn-col');
        $nodes = [node::create('colgroup', [], $nodes)];


        $nodes[] = node::create('thead', [],
            node::create('th.edit.btn-col') .
            node::create('th.live.btn-col', [], '') .
            ($this->module->nestable ? node::create('th.expand.btn-col', [], '') : '') .
            node::create('th.position.btn-col2', [], '') .
            $obj->get_fields()->iterate_return(function ($field) use ($obj) {
                    if ($field->list) {
                        return node::create('th.' . get::__class_name($field) . '.header_' . $field->field_name . ($field->field_name == $obj->get_primary_key_name() ? '.primary' : ''), [], $field->title);
                    }
                    return '';
                }
            ) .
            node::create('th.delete.btn-col')
        );
        return $nodes;
    }

    /**
     * @param table_array $elements
     * @param string      $class
     *
     * @return node
     */
    public function get_table_rows($elements, $class = '') {
        $nodes = node::create('tbody');
        $keys = $this->allowed_keys;
        /**
         * @var \classes\table $obj
         * @return string
         */
        return $elements->iterate_return(function (table $obj) use ($nodes, $keys, $class) {
                if ($obj->_has_child && in_array($obj->get_primary_key(), $keys)) {
                    $obj->_is_expanded = true;
                    $children = $this->get_elements($obj->get_primary_key());
                } else {
                    $obj->_is_expanded = false;
                    $children = false;
                }
                return node::create('tr#' . get::__class_name($obj) . $obj->get_primary_key() . ($obj->deleted ? '.danger.deleted' : '') . $class . '.vertical-align', [], $obj->get_cms_list()) . ($children ? $this->get_table_rows($children, '.child') : '');
            }
        );
    }

}