<?php

namespace core\classes;

use classes\ajax as _ajax;
use classes\ajax;
use classes\collection as _collection;
use classes\collection;
use classes\db as _db;
use classes\get as _get;
use classes\icon;
use classes\image_resizer;
use classes\jquery;
use classes\session as _session;
use classes\table_array as _table_array;
use classes\table_form;
use db\insert;
use db\update;
use form\field;
use form\field_collection as field_collection;
use form\field_file;
use form\field_fn;
use form\field_image;
use form\field_link;
use form\field_mlink;
use form\field_textarea;
use form\form;
use html\a;
use html\node;
use module\cms\object\_cms_field;
use module\cms\object\_cms_module;
use module\cms\object\_cms_table_list;
use object\filter;
use object\image_size;

/**
 * @property string table_key
 */
abstract class table {

    /**
     * @var collection
     */
    protected static $cms_modules;
    public $live;
    public $deleted;
    public $ts;
    /**
     * @var int
     */
    public $mid;
    /**
     * @var bool
     */
    public $raw = false;
    public $position;

    /**
     * @param array $fields
     * @param int $id
     */
    public function  __construct($fields = [], $id = 0) {
        if ($id) {
            $this->do_retrieve_from_id($fields, $id);
        }
    }

    /**
     * @param int $id
     * @param string $size
     * @param int $width
     * @param int $height
     * @return node
     * */
    public function get_padded_image($id, $size, $width, $height) {
        $url = $this->get_file($id, $size);
        $actual_size = getimagesize(root . $url);
        $vertical = ($height - $actual_size[1]) / 2;
        $horizontal = ($width - $actual_size[0]) / 2;
        return node::create('span.padded_image', ['style' => 'padding:' . $vertical . 'px ' . $horizontal . 'px'], node::create('img', ['src' => $url]));
    }

    public function get_table_class() {
        return get::__class_name($this);
    }

    public function get_filters() {
        $filters = filter::get_all(['title', 'link_mid AS link_mid', 'link_fid AS link_fid', 'order'], ['where_equals' => ['link_mid' => static::get_module_id()]]);
        $filters->iterate(
            function (filter $filter) {
                foreach ($this->get_fields() as $field) {
                    if ($field->fid == $filter->link_fid) {
                        $filter->set_field($field);
                        return;
                    }
                }
                throw new \RuntimeException('Filter field ' . $filter->fid . ' is linked to a field that doesn\'t belong to its module');
            }
        );
        return $filters;
    }

    public static function get_all(array $fields, array $options = []) {
        $array = new _table_array();
        $array->get_all(get_called_class(), $fields, $options);
        return $array;
    }

    /**
     * @return string
     */
    public function get_url() {
        return '';
    }

    /**
     * @param array $fields
     * @param $id
     */
    public function do_retrieve_from_id(array $fields, $id) {
        $this->do_retrieve($fields, ['limit' => '1', 'where_equals' => [$this->get_primary_key_name() => $id]]);
    }

    /**
     * @return int|bool
     */
    public function get_primary_key() {
        if (isset($this->{$this->get_primary_key_name()}) && $this->{$this->get_primary_key_name()}) {
            return $this->{$this->get_primary_key_name()};
        }
        return false;
    }


    /**
     * @return int|bool
     */
    public function get_parent_primary_key() {
        if (isset($this->{'parent_' . $this->get_primary_key_name()}) && $this->{'parent_' . $this->get_primary_key_name()}) {
            return $this->{'parent_' . $this->get_primary_key_name()};
        }
        return false;
    }

    /**
     * @return mixed
     */
    public static function get_count() {
        $class = get_called_class();
        $return = new $class();
        return _db::count($class, $return->get_primary_key_name())->execute();
    }

    /**
     * @return int
     */
    public function do_cms_update() {
        if (\core::is_admin()) {
            _db::update(_get::__class_name($this))->add_value($_REQUEST['field'], $_REQUEST['value'])->filter_field($this->get_primary_key_name(), $_REQUEST['id'])->execute();
        }
        return 1;
    }

    public function get_cms_pre_list() {
        return '';
    }

    public function get_cms_post_list() {
        return '';
    }

    /**
     * @param $row
     * @param $links
     */
    public function set_from_row($row, $links) {
        foreach ($row as $key => $val) {
            if ($field = $this->has_field($key)) {
                /** @var field $field */
                $this->$key = $field::sanitise_from_db($val);
            } else if (strstr($key, '@')) {
                list($module, $field) = explode('@', $key);
                if (!isset($this->$module)) {
                    foreach ($links as $link_module => $link) {
                        if ($link_module == $module) {
                            $this->$module = $link['field']->get_link_object();
                            break;
                        }
                    }
                }
                if (isset($this->$module)) {
                    $this->$module->$field = $val;
                } else {
                    $this->$key = $val;
                }
            } else {
                $this->$key = $val;
            }
        }
    }

    public function set_default_retrieve(&$fields, &$options) {
        if ($fields) {
            $fields = array_unique(array_merge($fields, ['live', 'deleted', 'position', 'ts', $this->get_primary_key_name()]));
        }
        if (!static::$retrieve_unlive) {
            $options['where_equals'][_get::__class_name($this) . '.live'] = 1;
        }
        if (!static::$retrieve_deleted) {
            $options['where_equals'][_get::__class_name($this) . '.deleted'] = 0;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function has_field($name) {
        $fields = $this->get_fields(false);
        foreach ($fields as $field) {
            if ($field->field_name == $name) {
                return $field;
            }
        }
        return false;
    }

    public static function organise_links(table $object, array &$fields, &$links = [], &$mlinks = []) {
        $new_fields = [];
        foreach ($fields as $field) {
            if (strstr($field, ' AS ') === false) {
                if (strstr($field, '.') === false) {
                    foreach ($object->get_fields(false) as $object_field) {
                        if ($object_field instanceof field_link && get::__class_name($object_field->get_link_module()) == $field) {
                            $sub_object = $object_field->get_link_object();
                            $sub_fields = [$sub_object->get_primary_key_name()];
                            if ($sub_object->has_field('title')) {
                                $sub_fields[] = 'title';
                            }
                            if ($object_field instanceof field_mlink) {
                                $mlinks[$field] = ['field' => $object_field, 'retrieve' => $sub_fields];
                            } else {
                                $links[$field] = ['field' => $object_field, 'retrieve' => $sub_fields];
                            }
                            continue 2;
                        }
                    }
                    $new_fields[$field] = $field;
                } else {
                    $field = explode('.', $field);
                    if ($field[0] != $object->class_name()) {
                        foreach ($object->get_fields(false) as $object_field) {
                            if ($object_field instanceof field_link && get::__class_name($object_field->get_link_module()) == $field[0]) {
                                if ($object_field instanceof field_mlink) {
                                    if (isset($mlinks[$field[0]])) {
                                        $mlinks[$field[0]]['retrieve'][] = $field[1];
                                    } else {
                                        $sub_object = $object_field->get_link_object();
                                        $sub_fields = [$sub_object->get_primary_key_name(), $field[1]];
                                        if ($sub_object->has_field('title')) {
                                            $sub_fields[] = 'title';
                                        }
                                        $mlinks[$field[0]] = ['field' => $object_field, 'retrieve' => $sub_fields];
                                    }
                                } else {
                                    if (isset($links[$field[0]])) {
                                        $links[$field[0]]['retrieve'][] = $field[1];
                                    } else {
                                        $sub_object = $object_field->get_link_object();
                                        $sub_fields = [$sub_object->get_primary_key_name(), $field[1]];
                                        if ($sub_object->has_field('title')) {
                                            $sub_fields[] = 'title';
                                        }
                                        $links[$field[0]] = ['field' => $object_field, 'retrieve' => $sub_fields];
                                    }
                                }
                                continue 2;
                            }
                        }
                    }
                    $new_fields[] = implode('.', $field);
                }
            } else {
                $new_fields[] = $field;
            }
        }
        $fields = $new_fields;
    }

    /**
     * @param array $fields
     * @param array $options
     */
    public function do_retrieve(array $fields, array $options) {
        $options['limit'] = 1;
        $this->set_default_retrieve($fields, $options);
        $links = $mlinks = [];
        table::organise_links($this, $fields, $links, $mlinks);
        foreach ($links as $module => $link_info) {
            $field = $link_info['field'];
            $retrieves = $link_info['retrieve'];
            $options['join'][$module] = $module . '.' . $field->field_name . '=' . $this->class_name() . '.' . $field->field_name;
            foreach ($retrieves as $retrieve) {
                $fields[] = $module . '.' . $retrieve;
            }
        }
        $query = _db::get_query(get_class($this), $fields, $options);
        $res = $query->execute();
        //$before = memory_get_usage();
        if (_db::num($res)) {
            $this->set_from_row(_db::fetch($res), $links);
        }
        /** @var field_link $field */
        foreach ($mlinks as $module => $fields) {
            $this->retrieve_link($fields['field'], $fields['retrieve']);
        }
        //print_r('memory usage: ' . $this->class_name() . ' ' . (memory_get_usage() - $before) . "\n");
    }

    public function retrieve_link($field, $fields = []) {
        $full_class = $field->get_link_module();
        $class = get::__class_name($full_class);
        $object = new $full_class();
        $retrieve = array_merge($fields, [$object->get_primary_key_name()]);
        if ($object->has_field('title')) {
            $retrieve[] = 'title';
        }
        if ($field instanceof field_mlink) {
            $link_table = $this->class_name() . '_link_' . $class;
            $this->$class = [];
            $this->{$class . '_elements'} = $full_class::get_all($retrieve, [
                    'join' => [$link_table => $object->class_name() . '.' . $object->get_primary_key_name() . '=' . $link_table . '.link_' . $object->get_primary_key_name()],
                    'where_equals' => [$link_table . '.' . $this->get_primary_key_name() => $this->get_primary_key()]
                ]
            );
            $this->{$class . '_elements'}->iterate(function (table $object) use ($class) {
                    $this->{$class}[] = $object->get_primary_key();
                }
            );
        } else {
            if (!isset($this->{$field->field_name})) {
                $this->lazy_load($field->field_name);
            }
            $object->do_retrieve_from_id($retrieve, $this->{$field->field_name});
        }
    }

    public function class_name() {
        return get::__class_name($this);
    }

    /**
     * @return bool
     */
    public function do_form_submit() {
        $this->raw = true;
        $this->set_from_request();
        $form = $this->get_form();
        foreach ($form->fields as $field) {
            $field->raw = true;
        }
        $form->action = get_class($this) . ':do_form_submit';
        $ok = $form->do_form_submit();
        if ($ok) {
            $this->do_save();
            $this->do_submit();
        } else {
            _ajax::update($form->get_html()->get());
        }
        return $ok;
    }

    public function do_submit() {
        $type = (!$this->get_primary_key() ? 'Added' : 'Updated');

        _ajax::add_script('$(".bs-callout-info").remove()', true);
        _ajax::inject('#' . $_REQUEST['ajax_origin'], 'before', node::create('div.bs-callout.bs-callout-info.' . strtolower($type) . ' p', [], $type . ' successfully'));
    }

    /**
     *
     */
    public function set_from_request() {
        /** @var field $field */
        $this->get_fields()->iterate(function ($field) {
                $field->parent_form = $this;
                if ($this->raw) {
                    $field->raw = true;
                }
                $field->set_from_request();
            }
        );
    }

    protected function set_primary_key($i) {
        $this->{$this->get_primary_key_name()} = $i;
    }

    /**
     * @return string
     */
    public function do_save() {
        $class = _get::__class_name($this);
        if ($this->get_primary_key()) {
            $query = new update($class);
        } else {
            $query = new insert($class);
            $top_pos = _db::select($class)->add_field_to_retrieve('max(position) as pos')->execute()->fetchObject()->pos;
            $query->add_value('position', $top_pos ? : 1);
        }
        /** @var field $field */
        $this->get_fields()->iterate(function ($field) use ($query) {
                $field->parent_form = $this;
                if ($field->field_name != $this->get_primary_key_name()) {
                    if (isset($this->{$field->field_name}) && !($field instanceof field_file) && !($field instanceof field_mlink)) {
                        if (!$this->{$field->field_name} && $field instanceof field_fn && isset($this->title)) {
                            $this->{$field->field_name} = _get::unique_fn(_get::__class_name($this), $field->field_name, $this->title);
                        }
                        try {
                            $data = $field->get_save_sql();
                            $query->add_value($field->field_name, $data);
                        } catch (\RuntimeException $e) {

                        }
                    }
                }
            }
        );
        $query->add_value('live', isset($this->live) ? $this->live : true);
        $query->add_value('deleted', isset($this->deleted) ? $this->deleted : false);
        $query->add_value('ts', date('Y-m-d H:i:s'));
        if ($this->get_primary_key()) {
            $query->filter_field($this->get_primary_key_name(), $this->get_primary_key());
        }

        $key = $query->execute();
        if (!$this->get_primary_key()) {
            $this->set_primary_key($key);
        }

        $this->get_fields()->iterate(function ($field) {
                if ($field->field_name != $this->get_primary_key_name()) {
                    if (isset($this->{$field->field_name}) && $field instanceof field_mlink) {
                        $source_module = new _cms_module(['table_name', 'primary_key'], $field->get_link_mid());
                        $module = new _cms_module(['table_name', 'primary_key'], static::get_module_id());
                        _db::delete($module->table_name . '_link_' . $source_module->table_name)->filter_field($module->primary_key, $this->get_primary_key())->execute();
                        if ($this->{$field->field_name}) {
                            foreach ($this->{$field->field_name} as $value) {
                                _db::insert($module->table_name . '_link_' . $source_module->table_name)
                                    ->add_value($module->primary_key, $this->get_primary_key())
                                    ->add_value('link_' . $source_module->primary_key, $value)
                                    ->add_value('fid', $field->fid)
                                    ->execute();
                            }
                        }
                    }
                }
            }
        );
        if ($this->get_primary_key()) {
            $this->get_fields()->iterate(function ($field) {
                    if ($field instanceof field_file) {
                        $this->do_upload_file($field);
                    }
                }
            );
        }
        return $this->get_primary_key();
    }

    public function get_file($fid, $size = '', $extensions = ['png', 'gif', 'jpg', 'jpeg'], $fallback = '/.core/images/no_image.png') {
        $file = root . '/uploads/' . get::__class_name($this) . '/' . $fid . '/' . $this->get_primary_key() . ($size ? '_' . $size : '') . '.';
        foreach ($extensions as $extension) {
            if (file_exists($file . $extension)) {
                return str_replace(root, '', $file) . $extension;
            }
        }
        return $fallback;
    }

    protected function do_process_image($source, image_size $size) {
        $ext = pathinfo($source, PATHINFO_EXTENSION);
        $resize = new image_resizer($source);
        $resize->resizeImage($size->max_width, $size->max_height, $size->icid == 1 ? true : false);
        $resize->saveImage(str_replace('.' . $ext, '', $source) . '_' . $size->reference . '.' . $size->get_format());
    }

    /**
     * @param field_file $field
     * @return string file path
     */
    protected function do_upload_file(field_file $field) {
        if (isset($_FILES[$field->field_name]) && !$_FILES[$field->field_name]['error']) {
            $tmp_name = $_FILES[$field->field_name]['tmp_name'];
            $name = $_FILES[$field->field_name]['name'];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if (!is_dir(root . '/uploads/' . _get::__class_name($this))) {
                mkdir(root . '/uploads/' . _get::__class_name($this));
            }
            if (!is_dir(root . '/uploads/' . _get::__class_name($this) . '/' . $field->fid)) {
                mkdir(root . '/uploads/' . _get::__class_name($this) . '/' . $field->fid);
            }
            $file_name = root . '/uploads/' . _get::__class_name($this) . '/' . $field->fid . '/' . $this->get_primary_key() . '.' . $ext;
            move_uploaded_file($tmp_name, $file_name);

            if ($field instanceof field_image && $ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                $image_sizes = $field->get_image_sizes();
                $image_sizes->iterate(
                    function (image_size $image) use ($file_name) {
                        $this->do_process_image($file_name, $image);
                    }
                );
            }
            return root . '/uploads/' . _get::__class_name($this) . '/' . $field->fid . '/' . $this->get_primary_key() . '.' . $ext;
        }
        return false;
    }

    /**
     * @return node
     */
    public function get_cms_edit() {
        $form = $this->get_form();
        $form->wrapper_class[] = 'container';
        $form->wrapper_class[] = 'panel';
        $form->wrapper_class[] = 'panel-body';
        $form->id = 'cms_edit';
        $form->set_from_request();
        $form->set_from_object($this);
        foreach ($form->fields as $field) {
            if ($field instanceof field_file) {
                $form->action = '/index.php?module=' . get_class($this) . '&act=do_form_submit&no_ajax=on&ajax_origin=' . $form->id;
            } else if ($field instanceof field_textarea) {
                \core::$inline_script[] = 'CKEDITOR.replace("' . $field->field_name . '");';
            } else if ($field instanceof field_mlink) {
                $class = $field->get_link_object();
                $class_name = get::__class_name($class);
                $this->do_retrieve_from_id([$class_name . '.' . $class->get_primary_key_name()], $this->get_primary_key());
            } else if ($field instanceof field_link) {
                $field->order = 'title';
            }
            $field->label .= '<br/><small class="field_name">' . $field->field_name . '</small>';
            $field->raw = true;
        }
        if (!$this->get_primary_key()) {
            $form->get_field_from_name($this->get_primary_key_name())->set_attr('hidden', true);
            $form->{'parent_' . $this->get_primary_key_name()} = 0;
        }
        return $form->get_html();
    }

    /**
     * @return form
     */
    public function get_form() {
        $form = new table_form($this->get_fields()->getArrayCopy());
        $form->id = str_replace('\\', '_', get_class($this) . '_form');
        if (isset($form->attributes['target'])) {
            $form->attributes['target'] = 'form_target_' . $form->id;
        }
        $form->get_field_from_name($this->get_primary_key_name())->hidden = true;
        return $form;
    }

    public function get_form_ajax() {
        $html = utf8_encode($this->get_form()->get_html()->get());
        jquery::colorbox(['html' => $html]);
    }

    /**
     * @param $fields
     */
    public function lazy_load($fields) {
        $this->do_retrieve_from_id($fields, $this->get_primary_key());
    }

    /**
     * @param $mid
     * @return string
     */
    public static function get_class_from_mid($mid) {
        self::set_cms_modules();
        $module = false;
        /** @var _cms_module $_module */
        foreach (self::$cms_modules as $_module) {
            if ($_module->mid == $mid) {
                $module = $_module;
            }
        }
        if ($module) {
            return $module->get_class_name();
        } else {
            return '';
        }
    }

    private static function set_cms_modules() {
        if (!isset(self::$cms_modules)) {
            $object = new _cms_module();
            $object->_cms_field_elements = new _collection();
            self::$cms_modules = new _collection(['module\cms\object\_cms_module' => $object]);
            $modules = _cms_module::get_all([
                    'mid',
                    'primary_key',
                    'namespace',
                    'table_name',
                    'nestable'
                ]
            );
            $modules->iterate(function (_cms_module $module) {
                    $module->_cms_field_elements = new field_collection();
                    self::$cms_modules[trim($module->get_class_name(), '\\')] = $module;
                }
            );
            $fields = _cms_field::get_all([
                    'fid',
                    'parent_fid',
                    'field_name',
                    'title',
                    'type',
                    'mid',
                    'list',
                    'filter',
                    'required',
                    'link_module',
                    'link_field'
                ]
            );
            $fields->iterate(function (_cms_field $row) {
                    foreach (self::$cms_modules as &$module) {
                        if ($module->mid == $row->mid) {
                            $class = 'form\field_' . $row->type;
                            /** @var field $field */
                            $field = new $class($row->field_name, []);
                            $field->label = $row->title;
                            $field->set_from_row($row);
                            $module->_cms_field_elements[] = $field;
                            break;
                        }
                    }
                }
            );
        }
    }

    public static function reset_module_fields ($mid) {
        $fields = _cms_field::get_all([
                'fid',
                'parent_fid',
                'field_name',
                'title',
                'type',
                'mid',
                'list',
                'filter',
                'required',
                'link_module',
                'link_field'
            ], ['where_equals'=>['mid'=>$mid]]
        );
        $module = new _cms_module([], $mid);
        $module = self::$cms_modules[trim($module->get_class_name(), '\\')];
        $module->_cms_field_elements = new field_collection();
        $fields->iterate(function (_cms_field $row) use ($module) {
                $class = 'form\field_' . $row->type;
                /** @var field $field */
                $field = new $class($row->field_name, []);
                $field->label = $row->title;
                $field->set_from_row($row);
                $module->_cms_field_elements[] = $field;
            }
        );
    }

    public static function reload_table_definitions() {
        self::$cms_modules = null;
        self::set_cms_modules();
    }

    public function get_primary_key_name() {
        self::set_cms_modules();
        $class = isset(static::$table_name) ? static::$table_name : get_called_class();
        if (isset(self::$cms_modules[$class])) {
            return self::$cms_modules[$class]->primary_key;
        } else {
            trigger_error('Attempting to get a primary key for a table that doesn\'t exist - ' . $class);
        }
    }

    public static function get_module_id() {
        self::set_cms_modules();
        $class = get_called_class();
        if (isset(self::$cms_modules[$class])) {
            return self::$cms_modules[$class]->mid;
        } else {
            trigger_error('Attempting to get a module ID for a table that doesn\'t exist - ' . $class);
        }
    }

    /**
     * @return array
     */
    public function get_cms_list() {
        $fields = $this->get_fields(true);
        $live_attributes = [
            'href'            => '#',
            'data-ajax-click' => get_class($this) . ':do_toggle_live',
            'data-ajax-post'  => '{"mid":' . static::get_module_id() . ',"id":' . $this->get_primary_key() . '}'
        ];
        $up_attributes = [
            'data-ajax-click' => get_class($this) . ':do_reorder',
            'data-ajax-post'  => '{"mid":' . static::get_module_id() . ',"id":' . $this->get_primary_key() . ',"dir":"up"}'
        ];
        $down_attributes = [
            'data-ajax-click' => get_class($this) . ':do_reorder',
            'data-ajax-post'  => '{"mid":' . static::get_module_id() . ',"id":' . $this->get_primary_key() . ',"dir":"down"}'
        ];
        $delete_attributes = $undelete_attributes = $true_delete_attributes = [
            'data-ajax-post'  => '{"id":"' . $this->get_primary_key() . '","mid":"' . $this->get_module_id() . '"}',
            'data-toggle'     => 'modal',
            'data-target'     => '#delete_modal'
        ];
        $undelete_attributes['data-target'] = '#undelete_modal';
        $true_delete_attributes['data-target'] = '#true_delete_modal';
        $expand_attributes = [
            'href'            => '#',
            'data-ajax-click' => get_class($this) . ':do_toggle_expand',
            'data-ajax-post'  => '{"mid":' . static::get_module_id() . ',"id":' . $this->get_primary_key() . '}'
        ];
        $nestable = static::$cms_modules[get_class($this)]->nestable;
        return
            node::create('td.btn-col a.btn.btn-primary', ['href' => '/cms/edit/' . static::get_module_id() . '/' . $this->get_primary_key()], icon::get('pencil')) .
            node::create('td.bnt-col a.btn.btn-primary', $live_attributes, icon::get($this->live ? 'ok' : 'remove')) .
            ($nestable ? node::create('td.edit' . ($this->_has_child ? '' : '.no_expand'), [], ($this->_has_child ? node::create('a.expand.btn.btn-primary', $expand_attributes, icon::get(!$this->_is_expanded ? 'plus' : 'minus')) : '')) : '') .
            node::create('td.btn-col2', [],
                node::create('a.btn.btn-primary', $up_attributes, icon::get('arrow-up')) .
                node::create('a.btn.btn-primary', $down_attributes, icon::get('arrow-down'))
            ) .
            $fields->iterate_return(function ($field) {
                    $field->parent_form = $this;
                    if ($field->list) {
                        return node::create('td.' . get_class($field), [], $field->get_cms_list_wrapper(isset($this->{$field->field_name}) ? $this->{$field->field_name} : '', get_class($this), $this->get_primary_key()));
                    }
                    return '';
                }
            ) .
            node::create('td.btn-col', [],
                ($this->deleted ?
                        [
                            node::create('button.delete.btn.btn-info', $undelete_attributes, '<s>' . icon::get('trash') . '</s>'),
                            node::create('button.delete.btn.btn-warning', $true_delete_attributes, '<s>' . icon::get('fire') . '</s>'),
                        ] :
                        node::create('button.delete.btn.btn-warning', $delete_attributes, icon::get('trash'))
                )
            );
    }

    /**
     * @param bool $clone whether to return a cloned copy of the fields our the singleton set.
     * @return field_collection
     */
    public function get_fields($clone = false) {
        $fields = static::_get_fields($clone);
        /*$fields->iterate(function ($field) {
                $field->parent_form = $this;
            }
        );*/
        return $fields;
    }

    /**
     * @param bool $clone
     * @return field_collection
     */
    private static function _get_fields($clone) {
        self::set_cms_modules();
        $class = get_called_class();
        if (isset(self::$cms_modules[$class])) {
            $fields = self::$cms_modules[$class]->_cms_field_elements;
        } else {
            trigger_error('Attempting to get a fields for a table that doesn\'t exist - ' . $class);
            $fields = new field_collection();
        }
        if ($clone) {
            $clone = new field_collection();
            foreach ($fields as $key => $field) {
                $clone[$key] = clone $field;
            }
            return $clone;
        } else {
            return $fields;
        }
    }

    /**
     *
     */
    public function do_reorder() {
        if (isset($_REQUEST['id'])) {
            /** @var table $object */
            static::$retrieve_unlive = true;
            static::$retrieve_deleted = true;
            $object = new static(['position'], $_REQUEST['id']);
            if (isset($_REQUEST['dir']) && $_REQUEST['dir'] == 'down') {
                _db::update(_get::__class_name($object))->add_value('position', $object->position)->filter_field('position', $object->position + 1)->execute();
                _db::update(_get::__class_name($object))->add_value('position', $object->position + 1)->filter_field($object->get_primary_key_name(), $object->get_primary_key())->execute();
            } else {
                _db::update(_get::__class_name($object))->add_value('position', $object->position)->filter_field('position', $object->position - 1)->execute();
                _db::update(_get::__class_name($object))->add_value('position', $object->position - 1)->filter_field($object->get_primary_key_name(), $object->get_primary_key())->execute();
            }
            $list = new _cms_table_list(self::$cms_modules[get_called_class()], 1);
            ajax::update($list->get_table());
        }
    }

    public function do_toggle_live() {
        if (isset($_REQUEST['id'])) {
            static::$retrieve_unlive = true;
            $object = new static(['live'], $_REQUEST['id']);
            $object->live = !$object->live;
            $object->do_save();

            $module = new _cms_module();
            $module->do_retrieve([], ['where_equals' => ['mid' => $_REQUEST['mid']]]);
            $list = new _cms_table_list($module, 1);
            ajax::update($list->get_table());
        }
    }

    public function do_toggle_expand() {
        if (isset($_REQUEST['id'])) {
            $module = new _cms_module();
            $module->do_retrieve([], ['where_equals' => ['mid' => $_REQUEST['mid']]]);
            if(_session::is_set('cms', 'expand', $module->mid)) {
                $value = _session::get('cms', 'expand', $module->mid);
                if(($key = array_search($_REQUEST['id'], $value)) !== false) {
                    unset($value[$key]);
                } else {
                    $value[] = $_REQUEST['id'];
                }
                _session::set($value, 'cms', 'expand', $module->mid);
            } else {
                _session::set([$_REQUEST['id']], 'cms', 'expand', $module->mid);
            }

            $list = new _cms_table_list($module, 1);
            ajax::update($list->get_table());
        }
    }

    public function get_title() {
        return (isset($this->title) ? $this->title : false);
    }

    public function is_live() {
        return $this->live;
    }

    public function is_deleted() {
        return $this->deleted;
    }
}
