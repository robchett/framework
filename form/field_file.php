<?php
namespace core\form;

use classes\icon;
use html\node;

abstract class field_file extends \form\field {

    public $external = false;

    public function get_html() {
        $html = '';
        $html .= '<a id="' . $this->field_name . '_wrapper" data-click="file_upload_' . $this->field_name . '" class="file_holder"><span class="icon">+</span><p class="text">Click to select a file<br/><small>Or</small><br/>Drag here to upload</p><input name="' . $this->field_name . '" id="' . $this->field_name . '"  type="file"/></a>' . "\n";
        if (isset($this->parent_form->{$this->field_name}) && $this->parent_form->{$this->field_name}) {
            $path = pathinfo($this->parent_form->{$this->field_name});
            $html .= '<p><a href="' . $this->parent_form->{$this->field_name} . '" target="_blank">' . $path['filename'] . '</a></p>';
        }
        return $html;

    }

    public function get_cms_list_wrapper($value, $object_class, $id) {
        if (isset($this->parent_form->{$this->field_name}) && !empty($this->parent_form->{$this->field_name})) {
            $this->attributes['href'] = $this->parent_form->{$this->field_name};
            return node::create('a.btn.btn-success', $this->attributes, icon::get('save'));
        } else {
            return node::create('span.btn.btn-default', ['disabled' => 'disabled'], icon::get('save'));
        }
    }

    public function get_save_sql() {
        throw new \RuntimeException('Can\t save this field type');
    }

    public function set_from_request() {
    }

    public function do_validate(&$error_array) {

    }
}
