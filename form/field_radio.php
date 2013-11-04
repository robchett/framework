<?php
namespace core\form;

abstract class field_radio extends field {

    public $options = array();

    public function get_html() {
        $html = '';
        foreach ($this->options as $name => $val) {
            $html .= $name . '<input type="radio" name="' . $this->field_name . '" value="' . $val . '" ' . ($val == $this->parent_form->{$this->field_name} ? 'checked="checked"' : '') . '/>' . "\n";
        }
        return $html;
    }
}
