<?php
namespace core\form;

abstract class field_boolean extends field {

    public function  __construct($title = '', $options = array()) {
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

    public function get_cms_list_wrapper($value, $object_class, $id) {
        $this->attributes['data-ajax-click'] = $object_class . ':do_cms_update';
        $this->attributes['data-ajax-post'] = '{"field":"' . $this->field_name . '", "value":' . (int) !$this->parent_form->{$this->field_name} . ',"id":' . $id . '}';
        $this->attributes['id'] = (isset($this->attributes['id']) ? $this->attributes['id'] : $this->field_name) . '_' . $id;
        $this->attributes['data-ajax-shroud'] = '#' . $this->field_name . '_' . $this->parent_form->{$this->parent_form->table_key};
        return $this->get_html();
    }

    public function get_html() {
        if ($this->required) {
            $this->class[] = 'required';
            $this->required = 0;
        }
        if ($this->parent_form->{$this->field_name}) {
            $this->attributes['checked'] = 'checked';
        }
        return '<span class="checkbox_replace"><input ' . $this->get_attributes() . '/></span>' . "\n";
    }

}
