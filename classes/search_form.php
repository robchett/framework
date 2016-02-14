<?php
namespace core\classes;

use form\form;

class search_form extends form {

    public $identifier;
    /** @var string */
    public $calling_class;
    public $keywords;

    public function __construct($calling_class) {
        $this->calling_class = $calling_class;
        $final_fields[] = form::create('field_string', 'keywords')->set_attr('label', 'Search');
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
        if (ajax && $_REQUEST['act'] == 'do_search_submit') {
            if (isset($this->identifier)) {
                session::set($this->keywords, $this->calling_class, $this->identifier, 'search');
            }
        }
    }
}
 