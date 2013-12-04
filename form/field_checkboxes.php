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
        foreach ($this->options as $key => $value) {
            if(is_array($value)) {
                $html .= '<div class="checkboxes_wrapper">';
                $html .= '<span class="legend">' . $key . '</span>';
                foreach($value as $_key => $_value) {
                    $html .= $this->get_inner_html($_key, $_value);
                }
                $html .= '</div>';
            } else {
                $html .= $this->get_inner_html($key, $value);
            }
        }
        return $html;
    }

    protected function get_inner_html($key, $value) {
        return '
        <label class="checkbox">
            <input type="checkbox" name="' . $this->field_name . '[]" value="' . $key . '" ' . (in_array($key, $this->parent_form->{$this->field_name}) ? 'checked="checked"' : '') . '>'
            . $value . '
        </label>';
    }

    public function set_from_request() {
        $this->parent_form->{$this->field_name} = (isset($_REQUEST[$this->field_name]) ? $_REQUEST[$this->field_name] : []);
    }
}
