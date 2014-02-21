<?php
namespace core\form;

abstract class field_select extends field {

    public $default = 'Please Choose';
    public $options = [];

    public function  __construct($title, $options = []) {
        parent::__construct($title, $options);
        $this->class[] = 'picker';
    }

    public function get_html() {
        $html = '';
        $html .= '<select ' . $this->get_attributes() . '>' . "\n";
        if (!empty($this->default) && !$this->required) $html .= '<option value="default">' . $this->default . '</option>' . "\n";
        foreach ($this->options as $k => $v) {
            $html .= '<option value="' . $k . '" ' . ($this->parent_form->{$this->field_name} == $k ? 'selected="selected"' : '') . '>' . $v . '</option>' . "\n";

        }
        $html .= '</select>' . "\n";
        return $html;
    }

    public function do_validate(&$error_array) {
        /*if ($this->required && (empty($this->parent_form->{$this->field_name})))
            $error_array[$this->field_name] = $this->field_name . ' is required field';*/
        if ($this->parent_form->{$this->field_name} == 'default' && $this->required)
            $error_array[$this->field_name] = $this->field_name . ' please choose an option';
    }
}
