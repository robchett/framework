<?php
namespace core\form;

abstract class field_multi_select extends \form\field {

    public $default = 'Please Choose';
    public $options = [];

    public function get_html() {
        $html = '';
        $html .= '<select id="' . $this->field_name . '" name="' . $this->field_name . '" class="picker' . ($this->required ? ' false' : '') . '" multiple="multiple">' . "\n";
        if (!empty($this->default)) {
            $html .= '<option value="default">' . $this->default . '</option>' . "\n";
        }
        foreach ($this->options as $k => $v) {
            $html .= '<option value="' . $k . '" ' . (in_array($k, $this->value) ? 'selected="selected"' : '') . '>' . $v . '</option>' . "\n";

        }
        $html .= '</select>' . "\n";
        return $html;
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = (isset($_REQUEST[$this->field_name]) ? $_REQUEST[$this->field_name] : []);
    }
}
