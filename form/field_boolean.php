<?php
namespace core\form;

use html\node;

abstract class field_boolean extends \form\field {

    public $class = [];

    public function  __construct($title = '', $options = []) {
        parent::__construct($title, $options);
        $this->value = false;
        $this->attributes['type'] = 'checkbox';
    }

    public function do_validate(&$error_array) {
        if (!is_bool($this->parent_form->{$this->field_name})) {
            $error_array[$this->field_name] = $this->field_name . ' is not a valid boolean';
        }
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = (isset($_REQUEST[$this->field_name]) ? true : false);
    }

    public function get_html_wrapper() {
        $html = '';
        $html .= $this->pre_text;

        $label = '';
        if (!$this->hidden && isset($this->label) && !empty($this->label)) {
            $label = node::create('label.control-label.col-md-' . $this->parent_form->bootstrap[0], [
                'for' => $this->field_name,
                'id' => $this->field_name . '_wrapper'
            ], $this->label);
        }
        $html .= node::create('div.col-md-offset-' . $this->parent_form->bootstrap[0] . '.col-md-' . $this->parent_form->bootstrap[1] . ' div.checkbox label', [], $this->get_html() . $this->label);
        $html .= $this->post_text;
        return $html;
    }

    public function get_cms_list_wrapper($value, $object_class, $id) {
        $this->attributes['data-ajax-click'] = $object_class . ':do_cms_update';
        $this->attributes['data-ajax-post'] = '{"field":"' . $this->field_name . '", "value":' . (int) !$this->parent_form->{$this->field_name} . ',"id":' . $id . '}';
        $this->attributes['id'] = (isset($this->attributes['id']) ? $this->attributes['id'] : $this->field_name) . '_' . $id;
        $this->attributes['data-ajax-shroud'] = '#' . $this->field_name . '_' . $this->parent_form->{$this->parent_form->get_primary_key_name()};
        return $this->get_html();
    }

    public function get_html() {
        $attributes = $this->attributes;
        $this->set_standard_attributes($attributes);
        if ($this->required) {
            $this->class[] = 'required';
            $this->required = 0;
        }
        if ($this->parent_form->{$this->field_name}) {
            $attributes['checked'] = 'checked';
        }
        return '<input ' . static::get_attributes($attributes) . '/>';
    }

}
