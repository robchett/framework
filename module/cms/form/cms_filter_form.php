<?php
namespace core\module\cms\form;

use classes\ajax;
use form\field_boolean;
use form\form;
use html\node;

abstract class cms_filter_form extends form {

    public $npp;

    public function __construct($class_name = 'string') {
        if (ajax) {
            $class_name = $_REQUEST['_class_name'];
        }
        /** @var \classes\table $class */
        $class = new $class_name;
        $super_fields = $class->get_fields();
        $fields = array(
            form::create('field_select', 'npp')
                ->set_attr('label', 'Number per page')
                ->set_attr('options', array(25 => 25, 50 => 50, 75 => 75, 100 => 100, 0 => 'All'))
                ->set_attr('required', false),
            form::create('field_string', '_class_name')
                ->set_attr('hidden', true)
        );
        foreach ($super_fields as $field) {
            if ($field->filter) {
                $field->required = false;
                if (!ajax && isset($_SESSION['cms'][$class_name][$field->field_name])) {
                    $field->value = $_SESSION['cms'][$class_name][$field->field_name];
                }
                $fields[] = $field;
            }
        }
        parent::__construct($fields);
        $this->id = 'filter_form';
        $this->submit = 'Filter';
        $this->_class_name = $class_name;
        $this->wrapper_class = '';
        if (isset($_SESSION['cms'][$class_name])) {
            $this->post_fields_text = node::create('a.button', ['href' => '#', 'data-ajax-click' => 'cms:do_clear_filter', 'data-ajax-post' => '\'{"_class_name":"' . $class_name . '"}\'', 'data-ajax-shroud' => '#filter_form'], 'Clear Filters');
        }
    }

    public function do_submit() {
        if (parent::do_submit()) {
            foreach ($this->fields as $field) {
                if ($field instanceof field_boolean && !$this->{$field->field_name}) {
                    unset($_SESSION['cms'][$this->_class_name][$field->field_name]);
                } else {
                    $_SESSION['cms'][$this->_class_name][$field->field_name] = $this->{$field->field_name};
                }
            }
        }
        ajax::add_script('window.location = window.location');
        // TODO make this a true ajax act.
    }

    public function get_html() {
        if (!empty($this->fields)) {
            return parent::get_html();
        }
        return node::create('span');
    }
}
