<?php
namespace core\classes;

use classes\filter_field;
use classes\session;
use form\field_collection;
use form\form;
use object\filter;

class filter_form extends form {

    public $identifier;
    public $source_data;

    public function __construct(field_collection $fields, collection $source_data) {
        $final_fields = [];
        $this->source_data = $source_data;
        $fields->iterate(function (filter $field) use (&$final_fields, $source_data) {
                $values = $source_data->filter_unique($field);
                $new_field = form::create('field_checkboxes', $field->inner_field()->field_name, $values);
                $new_field->original_field = $field->inner_field();
                $new_field->label = $field->title;
                $final_fields[] = $new_field;
            }
        );
        $final_fields[] = form::create('field_string', 'identifier')->set_attr('hidden', true);
        parent::__construct($final_fields);
    }

    public function do_submit() {
    }

    public function set_from_request() {
        parent::set_from_request();
        if (!$this->identifier) {
            $this->identifier = clean_uri;
        }
        if (ajax && $_REQUEST['act'] == 'do_filter_submit') {
            if (isset($this->identifier)) {
                session::set([], get_class($this->source_data), $this->identifier, 'filter');
                foreach ($this->fields as $field) {
                    if (isset($this->{$field->field_name})) {
                        session::set($this->{$field->field_name}, get_class($this->source_data), $this->identifier, 'filter', $field->field_name);
                    }
                }
            }
        }
    }
}
 