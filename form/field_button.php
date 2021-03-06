<?php
namespace core\form;

use classes\icon;
use html\node;

abstract class field_button extends \form\field {

    /** @var string */
    public $title;

    public function __construct($title = '', $options = []) {
        parent::__construct($title, $options);
    }

    public function get_database_create_query() {
        return false;
    }

    public function get_cms_list_wrapper($value, $object_class, $id) {
        $this->attributes['data-ajax-click'] = $object_class . ':' . $this->field_name;
        $this->attributes['data-ajax-post'] = '{"id":' . $id . '}';
        $this->attributes['data-ajax-shroud'] = '#button' . $this->field_name . $id;
        return node::create('a#button_' . $this->field_name . $id . '.btn.btn-default', $this->attributes, icon::get($this->field_name));
    }

    public function get_save_sql() {
        throw new \RuntimeException('Can\t save this field type');
    }

    public function get_html_wrapper() {
        return false;
    }
}
