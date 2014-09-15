<?php
namespace core\form;

abstract class field_textarea extends \form\field {

    public function get_html() {
        $attributes = $this->attributes;
        $this->set_standard_attributes($attributes);
        return '<textarea ' . static::get_attributes($attributes) . '>' . htmlentities($this->parent_form->{$this->field_name}) . '</textarea>' . "\n";
    }

    public function get_database_create_query() {
        return 'TEXT';
    }
}
