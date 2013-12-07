<?php
namespace core\classes;

use classes\filter_field;
use form\form;

class sort_form extends form {

    public $identifier;
    public $calling_class;
    public $sort;

    public function __construct(array $sort_options, $calling_class) {
        $this->calling_class = $calling_class;
        $final_fields[] = form::create('field_select', 'sort')->set_attr('label', 'Sort By')->set_attr('options', $sort_options);
        $final_fields[] = form::create('field_string', 'identifier')->set_attr('hidden', true);
        parent::__construct($final_fields);
    }

    public function do_submit() {
    }

    public function set_from_request() {
        parent::set_from_request();
        if (ajax) {
            if (isset($this->identifier)) {
                $_SESSION[$this->calling_class][$this->identifier]['sort'] = $this->sort;
            }
        }
    }
}
 