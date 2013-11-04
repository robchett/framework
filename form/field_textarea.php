<?php
namespace core\form;

abstract class field_textarea extends field {

    public function get_html() {
        return '<textarea ' . $this->get_attributes() . '>' . htmlentities($this->parent_form->{$this->field_name}) . '</textarea>' . "\n";
    }

    public function get_database_create_query() {
        return 'TEXT';
    }
}
