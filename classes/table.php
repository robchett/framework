<?php

namespace core\classes;

use classes\ajax as _ajax;
use classes\get as _get;
use classes\image_resizer;
use form\field_image;
use object\image_size;
use db\insert;
use db\update;
use form\field;
use form\field_collection as field_collection;
use form\field_file;
use form\field_fn;
use form\field_link;
use form\field_mlink;
use form\field_textarea;
use form\form;
use html\node;
use module\cms\object\_cms_field;
use module\cms\object\_cms_module;

/** @property string table_key */
abstract class table {

    /**
     * @var array
     */
    public static $define_table = array();
    public $live;
    public $deleted;
    public $ts;
    //public $table_key;
    /** @var  int $module_id */
    //public static $module_id = 0;
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
        $class = get_class($this);
        if (!isset($class::$fields)) {
            /** @var table $class */
            $class::_set_fields();
        }
    }

    public function get_table_class() {
        return get::__class_name($this);
    }

    public static function get_all(array $fields, array $options = []) {
        $array = new \classes\table_array();
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
        $this->do_retrieve($fields, array('limit' => '1', 'where_equals' => [$this->table_key => $id]));
    }

    /**
     * @return bool
     */
    public function get_primary_key() {
        if (isset($this->{$this->table_key}) && $this->{$this->table_key}) {
            return $this->{$this->table_key};
        }
        return false;
    }

    /**
     * @return mixed
     */
    public static function get_count() {
        $class = get_called_class();
        $return = new $class();
        return db::count($class, $return->table_key)->execute();
    }

    /**
     * @return int
     */
    public function do_cms_update() {
        if (admin) {
            db::update(_get::__class_name($this))->add_value($_REQUEST['field'], $_REQUEST['value'])->filter_field($this->table_key, $_REQUEST['id'])->execute();
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
            if (isset(static::$fields[$key])) {
                $class = get_class(static::$fields[$key]);
                /** @var field $class */
                $this->$key = $class::sanitise_from_db($val);
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
            $fields = array_unique(array_merge($fields, ['live', 'deleted', 'position', 'ts', $this->table_key]));
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
                return true;
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
                            $sub_fields = [$sub_object->table_key];
                            if ($sub_object->has_field('title')) {
                                $sub_fields[] = 'title';
                            }
                            if ($object_field instanceof \form\field_mlink) {
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
                                        $sub_fields = [$sub_object->table_key, $field[1]];
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
                                        $sub_fields = [$sub_object->table_key, $field[1]];
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
        $parameters = (isset($options['parameters']) ? $options['parameters'] : array());
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
        $sql = db::get_query(get_class($this), $fields, $options, $parameters);
        $res = db::query($sql, $parameters);
        //$before = memory_get_usage();
        if (db::num($res)) {
            $this->set_from_row(db::fetch($res), $links);
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
        $retrieve = array_merge($fields, [$object->table_key]);
        if ($object->has_field('title')) {
            $retrieve[] = 'title';
        }
        if ($field instanceof field_mlink) {
            $link_table = $this->class_name() . '_link_' . $class;
            $this->$class = [];
            $this->{$class . '_elements'} = $full_class::get_all($retrieve, [
                    'join' => [$link_table => $object->class_name() . '.' . $object->table_key . '=' . $link_table . '.link_' . $object->table_key],
                    'where_equals' => [$link_table . '.' . $this->table_key => $this->get_primary_key()]
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
    public function do_submit() {
        $this->raw = true;
        $this->set_from_request();
        $form = $this->get_form();
        foreach ($form->fields as $field) {
            $field->raw = true;
        }
        $form->action = get_class($this) . ':do_submit';
        $ok = $form->do_submit();
        if ($ok) {
            $type = (!isset($this->{$this->table_key}) || !$this->{$this->table_key} ? 'Added' : 'Updated');
            $this->do_save();
            _ajax::inject('#' . $_REQUEST['ajax_origin'], 'before', node::create('p.success.boxed.' . strtolower($type), [], $type . ' successfully'));
        } else {
            _ajax::update($form->get_html()->get());
        }
        return $ok;
    }

    /**
     *
     */
    public function set_from_request() {
        /** @var field $field */
        $this->get_fields()->iterate(function ($field) {
                if ($this->raw) {
                    $field->raw = true;
                }
                $field->set_from_request();
            }
        );
    }

    /**
     * @return string
     */
    public function do_save() {
        $class = _get::__class_name($this);
        if (isset($this->{$this->table_key}) && $this->{$this->table_key}) {
            $query = new update($class);
        } else {
            $query = new insert($class);
        }
        /** @var field $field */
        $this->get_fields()->iterate(function ($field) use ($query) {
                if ($field->field_name != $this->table_key) {
                    if (isset($this->{$field->field_name}) && !($field instanceof field_file)) {
                        if (!$this->{$field->field_name} && $field instanceof field_fn && isset($this->title)) {
                            $this->{$field->field_name} = _get::unique_fn(_get::__class_name($this), $field->field_name, $this->title);
                        }
                        if (!($field instanceof field_mlink)) {
                            try {
                                $data = $field->get_save_sql();
                                $query->add_value($field->field_name, $data);
                            } catch (\RuntimeException $e) {

                            }
                        }
                    }
                }
            }
        );
        $query->add_value('live', isset($this->live) ? $this->live : true);
        $query->add_value('deleted', isset($this->live) ? $this->deleted : false);
        $query->add_value('ts', date('Y-m-d H:i:s'));
        if (isset($this->{$this->table_key}) && $this->{$this->table_key}) {
            $query->filter_field($this->table_key, $this->{$this->table_key});
        }
        $res = $query->execute();

        if (!$this->get_primary_key()) {
            $this->{$this->table_key} = $res;
        }

        $this->get_fields()->iterate(function ($field) {
                if ($field->field_name != $this->table_key) {
                    if (isset($this->{$field->field_name}) && $field instanceof field_mlink) {
                        $source_module = new _cms_module(['table_name', 'primary_key'], $field->get_link_mid());
                        $module = new _cms_module(['table_name', 'primary_key'], static::$module_id);
                        db::query('DELETE FROM ' . $module->table_name . '_link_' . $source_module->table_name . ' WHERE ' . $module->primary_key . '=:key', ['key' => $this->{$this->table_key}]);
                        if ($this->{$field->field_name}) {
                            foreach ($this->{$field->field_name} as $value) {
                                db::insert($module->table_name . '_link_' . $source_module->table_name)
                                    ->add_value($module->primary_key, $this->{$this->table_key})
                                    ->add_value('link_' . $source_module->primary_key, $value)
                                    ->add_value('fid', $field->fid)
                                    ->execute();
                            }
                        }
                    }
                }
            }
        );
        if (!(isset($this->{$this->table_key}) && $this->{$this->table_key})) {
            $this->{$this->table_key} = db::insert_id();
        }
        if ($this->{$this->table_key}) {
            $this->get_fields()->iterate(function ($field) {
                    if ($field instanceof field_file) {
                        $this->do_upload_file($field);
                    }
                }
            );
        }
        return $this->{$this->table_key};
    }

    public function get_file($fid, $size = '', $extensions = ['png', 'gif', 'jpg', 'jpeg'], $fallback = '/.core/images/no_image.png') {
        $file = root . '/uploads/' . get::__class_name($this) . '/' . $fid . '/' . $this->get_primary_key() . ($size ? '_' . $size : '') . '.';
        foreach ($extensions as $extension) {
            if (file_exists($file . $extension)) {
                return str_replace(root, '', $file) . $extension;
            }
        }
        return $fallback;;
    }

    protected function do_process_image($source, image_size $size) {
        $ext = pathinfo($source, PATHINFO_EXTENSION);
        $resize = new image_resizer($source);
        $resize->resizeImage($size->max_width, $size->max_height, 'crop');
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

            if($field instanceof field_image && $ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                $image_sizes = $field->get_image_sizes();
                $image_sizes->iterate(
                    function (\object\image_size $image) use ($file_name) {
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
        $form->set_from_request();
        $form->set_from_object($this);
        foreach ($form->fields as $field) {
            if ($field instanceof field_file) {
                $form->action = '/index.php?module=' . get_class($this) . '&act=do_submit&no_ajax=on&ajax_origin=' . $form->id;
            } else if ($field instanceof field_textarea) {
                \core::$inline_script[] = 'CKEDITOR.replace("' . $field->field_name . '");';
            } else if ($field instanceof field_mlink) {
                $class = $field->get_link_object();
                $class_name = get::__class_name($class);
                $this->do_retrieve_from_id([$class_name . '.' . $class->table_key], $this->get_primary_key());
            } else if ($field instanceof field_link) {
                $field->order = 'title';
            }
            $field->label .= '<span class="field_name">' . $field->field_name . '</span>';
            $field->raw = true;
        }
        if (!isset($this->{$this->table_key}) || !$this->{$this->table_key}) {
            $form->get_field_from_name($this->table_key)->set_attr('hidden', true);
            $form->{'parent_' . $this->table_key} = 0;
        }
        return $form->get_html();
    }

    /**
     * @return form
     */
    public function get_form() {
        $form = new form($this->get_fields()->getArrayCopy());
        $form->id = str_replace('\\', '_', get_class($this) . '_form');
        if (isset($form->attributes['target'])) {
            $form->attributes['target'] = 'form_target_' . $form->id;
        }
        return $form;
    }

    /**
     * @param $fields
     */
    public function lazy_load($fields) {
        $this->do_retrieve_from_id($fields, $this->{$this->table_key});
    }

    /** @return \html\node */
    public function get_cms_edit_module() {
        $list = node::create('table#module_def', [],
            node::create('thead', [],
                node::create('th', [], 'Live') .
                node::create('th', [], 'Field id') .
                node::create('th', [], 'Pos') .
                node::create('th', [], 'Title') .
                node::create('th', [], 'Database Title') .
                node::create('th', [], 'Type') .
                node::create('th', [], 'List') .
                node::create('th', [], 'Required') .
                node::create('th', [], 'Filter') .
                node::create('th', [], '')
            ) .
            $this->get_fields()->iterate_return(function ($field) {
                    return (node::create('tr', [], $field->get_cms_admin_edit()));
                }
            )
        );
        return $list;
    }

    /**
     * @return array
     */
    public function get_cms_list() {
        $fields = $this->get_fields();
        return
            node::create('td.edit a.edit', ['href' => '/cms/edit/' . static::$module_id . '/' . $this->get_primary_key()]) .
            node::create('td.edit a.live' . ($this->live ? '' : 'not_live'), ['href' => '#', 'data-ajax-click' => get_class($this) . ':do_toggle_live', 'data-ajax-post' => '{"mid":' . $this::$module_id . ',"id":' . $this->get_primary_key() . '}'], ($this->live ? 'Live' : 'Not Live')) .
            node::create('td.position', [],
                node::create('a.up.reorder', ['data-ajax-click' => get_class($this) . ':do_reorder', 'data-ajax-post' => '{"mid":' . $this::$module_id . ',"id":' . $this->get_primary_key() . ',"dir":"up"}'], 'Up') .
                node::create('a.down.reorder', ['data-ajax-click' => get_class($this) . ':do_reorder', 'data-ajax-post' => '{"mid":' . $this::$module_id . ',"id":' . $this->get_primary_key() . ',"dir":"down"}'], 'Down')
            ) .
            $fields->iterate_return(function ($field) {
                    if ($field->list) {
                        return node::create('td.' . get_class($field), [], $field->get_cms_list_wrapper(isset($this->{$field->field_name}) ? $this->{$field->field_name} : '', get_class($this), $this->get_primary_key()));
                    }
                    return '';
                }
            ) .
            node::create('td.delete a.delete', ['href' => '#', 'data-ajax-click' => 'cms:do_delete', 'data-ajax-post' => '{"id":"' . $this->get_primary_key() . '","object":"' . str_replace('\\', '\\\\', get_class($this)) . '"}'], 'delete');
    }

    /**
     * @param bool $clone whether to return a cloned copy of the fields our the singleton set.
     * @return field_collection
     */
    public function get_fields($clone = false) {
        $fields = static::_get_fields($clone);
        $fields->iterate(function ($field) {
                $field->parent_form = $this;
            }
        );
        return $fields;
    }

    /**
     *
     */
    public static function _set_fields() {
        $final_fields = static::$fields = new field_collection();
        $fields = _cms_field::get_all([], ['where_equals' => ['mid' => static::$module_id], 'order' => '`position` ASC']);
        $fields->iterate(function (_cms_field $row) use (&$final_fields) {
                $class = 'form\field_' . $row->type;
                /** @var field $field */
                $field = new $class($row->field_name, array());
                $field->label = $row->title;
                $field->set_from_row($row);
                $final_fields[$row->field_name] = $field;
            }
        );
        static::$fields = $final_fields;
    }

    /**
     * @param bool $clone
     * @return field_collection
     */
    private static function _get_fields($clone) {
        if (!isset(static::$fields)) {
            static::_set_fields();
        }
        if ($clone) {
            $clone = new field_collection();
            foreach (static::$fields as $key => $field) {
                $clone[$key] = clone $field;
            }
            return $clone;
        } else {
            return static::$fields;
        }
    }

    /**
     *
     */
    public function do_reorder() {
        if (isset($_REQUEST['id'])) {
            /** @var table $object */
            $object = new static(['position'], $_REQUEST['id']);
            if (isset($_REQUEST['dir']) && $_REQUEST['dir'] == 'down') {
                db::query('UPDATE ' . _get::__class_name($object) . ' SET position =' . $object->position . ' WHERE position=' . ($object->position + 1));
                db::query('UPDATE ' . _get::__class_name($object) . ' SET position =' . ($object->position + 1) . ' WHERE ' . $object->table_key . '=' . $object->get_primary_key());
            } else {
                db::query('UPDATE ' . _get::__class_name($object) . ' SET position =' . $object->position . ' WHERE position=' . ($object->position - 1));
                db::query('UPDATE ' . _get::__class_name($object) . ' SET position =' . ($object->position - 1) . ' WHERE ' . $object->table_key . '=' . $object->get_primary_key());
            }
            ajax::add_script('document.location = document.location#' . _get::__class_name($object) . ($_REQUEST['id'] - 1));
        }
    }

    public function do_toggle_live() {
        if (isset($_REQUEST['id'])) {
            static::$retrieve_unlive = true;
            $object = new static(['live'], $_REQUEST['id']);
            $object->live = !$object->live;
            $object->do_save();
            ajax::add_script('document.location = document.location#' . _get::__class_name($object) . ($_REQUEST['id'] - 1));
        }
    }

    public function get_title() {
        return $this->title;
    }
}
