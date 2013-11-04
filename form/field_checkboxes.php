<?php
namespace core\form;

abstract class field_checkboxes extends field {

    public $options = [];
    public $value = [];

    public function __construct($name, $options) {
        $this->options = $options;
        parent::__construct($name);
    }

    public function get_html() {
        $html = '';
        foreach ($this->options as $k => $v) {
            $html .= '<label class="checkbox"><input type="checkbox" name="' . $this->field_name . '[]" value="' . $k . '" ' . (in_array($k, $this->parent_form->{$this->field_name}) ? 'checked="checked"' : '') . '>' . $v . '</label>' . "\n";

        }
        return $html;
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = (isset($_REQUEST[$this->field_name]) ? $_REQUEST[$this->field_name] : []);
    }
}
