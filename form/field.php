<?php
namespace core\form;

use classes\get;
use core\classes\db;
use html\node;

abstract class field extends node {

    public $fid;
    public $filter;
    public $label;
    public $field_name;
    public $list;
    public $live;
    public $pre_text;
    public $post_text;
    public $raw = false;
    public $required = true;
    public $attributes = array(
        'type' => 'text',
    );
    public $hidden;
    public $disabled = false;
    public $class = array();
    public $title;
    public $wrapper_class = array();
    public $parent_form;
    public $value = '';

    public function __construct($name, $options = array()) {
        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $this->$key = $val;
            }
        }
        $this->field_name = $name;
        if (!isset($this->label)) {
            $this->label = ucwords(str_replace('', ' ', $name));
        }
    }

    public function get_html_wrapper() {
        $html = '';
        $html .= $this->pre_text;

        if (!$this->hidden && isset($this->label) && !empty($this->label)) $html .= '<label id="' . $this->field_name . '_wrapper"><span>' . $this->label . '</span>' . "\n";
        $html .= $this->get_html() . "\n";
        if (!$this->hidden && isset($this->label) && !empty($this->label)) $html .= '</label>' . "\n";
        $html .= $this->post_text;
        return $html;
    }

    public function get_html() {
        return '<input ' . $this->get_attributes() . ' value="' . htmlentities($this->get_value()) . '"/>' . "\n";
    }

    public static function sanitise_from_db($value) {
        return $value;
    }

    public function get_value() {
        return $this->parent_form->{$this->field_name};
    }

    public function set_standard_attributes() {
        if ($this->hidden)
            $this->attributes['type'] = 'hidden';
        if (!isset($this->attributes['name']))
            $this->attributes['name'] = $this->field_name;
        if (!isset($this->attributes['id']))
            $this->attributes['id'] = $this->field_name;
        if ($this->disabled)
            $this->attributes['disabled'] = 'disabled';
        if ($this->required)
            $this->class[] = 'required';
        $this->attributes['class'] = implode(' ', $this->class);
    }

    public function do_validate(&$error_array) {
        if ($this->required && empty($this->parent_form->{$this->field_name})) {
            $error_array[$this->field_name] = $this->field_name . ' is required field';
        }
    }

    public function set_attr($attr, $val) {
        $this->$attr = $val;
        return $this;
    }

    public function set_value($val) {
        $this->parent_form->{$this->field_name} = $val;
    }

    public function add_class($val) {
        $this->class[] = $val;
        return $this;
    }

    public function add_wrapper_class($val) {
        $this->wrapper_class[] = $val;
        return $this;
    }

    public function get_class() {
        if (!empty($this->class)) {
            return 'class="' . implode(' ', $this->class) . '"';
        }
        return false;
    }

    public function get_wrapper_class() {
        $this->wrapper_class[] = get::__class_name($this) . '_wrapper';
        if (!empty($this->wrapper_class)) {
            return '.' . implode('.', $this->wrapper_class);
        }
        return '';
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = (isset($_REQUEST[$this->field_name]) ? ($this->raw ? $_REQUEST[$this->field_name] : strip_tags($_REQUEST[$this->field_name])) : '');
    }

    public function get_cms_list_wrapper($value, $object_class, $id) {
        return $value;
    }

    public function set_from_row($row) {
        foreach ($row as $title => $val) {
            $this->{$title} = ($val);
        }
    }

    public function get_database_create_query() {
        return 'varchar(32)';
    }

    public function get_database_create_extra() {
        return false;
    }

    public function get_cms_admin_edit() {
        $cols = array();
        $cols[] = node::create('td', [], ($this->live ? 'Live' : 'Not Live'));
        $cols[] = node::create('td', [], $this->fid);
        $cols[] = node::create('td', [],
            node::create('a.up.reorder', ['data-ajax-click' => 'cms:do_reorder_fields', 'data-ajax-post' => '{"mid":' . $this->parent_form->mid . ',"fid":' . $this->fid . ',"dir":"up"}'], 'Up') .
            node::create('a.down.reorder', ['data-ajax-click' => 'cms:do_reorder_fields', 'data-ajax-post' => '{"mid":' . $this->parent_form->mid . ',"fid":' . $this->fid . ',"dir":"down"}'], 'Down')
        );
        $cols[] = node::create('td', [], $this->title);
        $cols[] = node::create('td', [], $this->field_name);
        $cols[] = node::create('td', [], get::__class_name($this));

        $fields = ['list', 'required', 'filter'];
        foreach ($fields as $field) {
            $list_options = [
                'data-ajax-change' => 'form\field_boolean:update_cms_setting',
                'data-ajax-post' => '{"fid":' . $this->fid . ', "field":"' . $field . '"}',
                'value' => 1,
                'type' => 'checkbox'];
            if ($this->$field) {
                $list_options['checked'] = 'checked';
            }
            $cols[] = node::create('td input#' . $this->fid . '_list', $list_options);
        }
        return $cols;
    }

    public function update_cms_setting() {
        if (admin) {
            db::update('_cms_field')->add_value($_REQUEST['field'], $_REQUEST['value'])->filter_field('fid', $_REQUEST['fid'])->execute();
        }
        return 1;
    }

    public function get_save_sql() {
        return $this->mysql_value($this->parent_form->{$this->field_name});
    }

    public function mysql_value($value) {
        return $value;
    }
}
