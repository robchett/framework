<?php
namespace core\classes;

use form\field_collection;
use form\form;

class filter_form extends form {

    public $identifier;
    public $source_data;

    public function __construct(field_collection $fields, collection $source_data) {
        $final_fields = [];
        $this->source_data = $source_data;
        $fields->iterate(function ($field) use (&$final_fields, $source_data) {
                $values = $source_data->filter_unique($field);
                $new_field = form::create('field_checkboxes', $field->field_name, $values);
                $new_field->original_field = $field;
                $final_fields[] = $new_field;
            }
        );
        $final_fields[] = form::create('field_string', 'identifier')->set_attr('hidden', true);
        parent::__construct($final_fields);
    }

    public function do_submit() {
        $this->set_from_request();
    }

    public function set_from_request() {
        parent::set_from_request();
        if (ajax) {
            if (isset($this->identifier)) {
                $_SESSION[get_class($this->source_data)][$this->identifier]['filter'] = [];
                foreach ($this->fields as $field) {
                    if (isset($this->{$field->field_name})) {
                        $_SESSION[get_class($this->source_data)][$this->identifier]['filter'][$field->field_name] = $this->{$field->field_name};
                    }
                }
            }
        }
    }
}
 