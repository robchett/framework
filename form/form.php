<?php
namespace core\form;

use classes\ajax;
use classes\get;
use form\field_file as _field_file;
use html\node;

/**
 * Class form
 * @package form
 */
abstract class form {

    /**
     * @var string
     */
    public $action = '';
    /**
     * @var string
     */
    public $method = 'post';
    /**
     * @var string
     */
    public $content = '';
    /** @var field[] $fields */
    public $fields = [];
    /**
     * @var string
     */
    public $h2 = '';
    /**
     * @var string
     */
    public $description = '';
    /**
     * @var
     */
    public $target;
    /**
     * @var bool
     */
    public $use_ajax = true;
    /**
     * @var string
     */
    public $pre_text = '';
    /**
     * @var string
     */
    public $post_submit_text = '';
    /**
     * @var string
     */
    public $post_fields_text = '';
    /**
     * @var string
     */
    public $post_text = '';
    /**
     * @var string
     */
    public $submit = 'Submit';
    /**
     * @var string
     */
    public $id = 'form';
    /**
     * @var bool
     */
    public $submittable = true;
    /**
     * @var bool
     */
    public $has_submit = true;
    /**
     * @var array
     */
    public $attributes = [];
    /**
     * @var array
     */
    public $validation_errors = [];
    /**
     * @var string
     */
    public $wrapper_class = '.form_wrapper';
    protected $table_object;

    /**
     * @param array $fields
     */
    public function  __construct($fields) {
        $this->fields = $fields;
        foreach ($this->fields as $field) {
            $field->parent_form = $this;
            $this->{$field->field_name} = $field->value;
        }
        foreach ($this->fields as $field) {
            if ($field instanceof _field_file) {
                $this->use_ajax = false;
                break;
            }
        }
    }

    /**@return field */
    public static function create() {
        $args = func_get_args();
        $class_name = 'form\\' . $args[0];
        unset($args[0]);
        $class = new \ReflectionClass($class_name);
        return $class->newInstanceArgs($args);
    }

    /**
     * @param $object
     */
    public function set_from_object($object) {
        $this->table_object = $object;
        foreach ($this->fields as $field) {
            if (isset($object->{$field->field_name})) {
                $this->{$field->field_name} = $object->{$field->field_name};
            }
        }
        $this->action = get_class($object) . ':do_form_submit';
    }

    public function get_table_object() {
        if (!$this->table_object) {
            trigger_error('Trying to access a forms table object before set_from_object has been called.');
        }
        return $this->table_object;
    }

    public function get_table_class() {
        return get::__class_name($this->get_table_object());
    }

    /**
     * @param $field_name
     * @return bool
     */
    public function remove_field($field_name) {
        if (!cms) {
            foreach ($this->fields as $key => $field) {
                if ($field->field_name == $field_name) {
                    unset($this->fields[$key]);
                    return true;
                }
            }
        }
        return false;
    }

    public function do_form_submit() {
        $this->set_from_request();
        $ok = $this->do_validate();
        if ($ok) {
            $this->do_submit();
            return true;
        } else {
            $this->do_invalidate_form();
            return false;
        }
    }

    /**
     * @return bool
     */
    abstract public function do_submit();

    /**
     *
     */
    public function set_from_request() {
        foreach ($this->fields as $field) {
            if (isset($_REQUEST[$field->field_name])) {
                $field->set_from_request();
            }
        }
    }

    /**
     * @param $name
     * @return field
     * @throws \Exception
     */
    public function get_field_from_name($name) {
        foreach ($this->fields as $field) {
            if ($field->field_name == $name) {
                return $field;
            }
        }
        throw new \Exception('Field ' . $name . ' not found in ' . get_called_class());
    }

    /**
     * @return bool
     */
    public function do_validate() {
        foreach ($this->fields as $field) {
            $field->do_validate($this->validation_errors);
        }
        return count($this->validation_errors) ? false : true;
    }

    /**
     *
     */
    public function do_invalidate_form() {
        //$html = '<ul class="error_list">';
        foreach ($this->validation_errors as $key => $val) {
            $field = $this->get_field_from_name($key);
            $field->add_class('err');
            $field->add_wrapper_class('err');
            //$html .= '<li>' . $key . ' ' . $val . '</li>';
        }

        //$html .= '</ul>';
        ajax::update($this->get_html()->get());
        //  \classes\ajax::inject('#' . $this->id, 'prepend', $html, '#' . $this->id . '_error');
    }

    /**
     * @return node
     */
    public function get_html() {
        if (!$this->use_ajax) {
            if (!$this->action) {
                $this->action = '/index.php?module=' . get_class($this) . '&act=do_form_submit&no_ajax=on&ajax_origin=' . $this->id;
            }
            $this->attributes['target'] = 'form_target_' . $this->id;
            $this->attributes['enctype'] = 'multipart/form-data';
        }
        $html = node::create('div#' . $this->id . '_wrapper' . trim($this->wrapper_class));
        $this->attributes = array_merge([
                'name' => $this->id,
                'method' => $this->method,
                'action' => !empty($this->action) ? $this->action : get_class($this) . ':' . 'do_form_submit',
                'data-ajax-shroud' => '#' . $this->id,
            ], $this->attributes
        );
        if ($this->h2) {
            $html->nest(node::create('h2.form_title', [], $this->h2));
        }
        $html->nest($this->get_html_body());
        if (!$this->use_ajax) {
            $html->add_child(node::create('iframe#form_target_' . $this->id . '.form_frame', ['width' => 1, 'height' => 1, 'frame-border' => 0, 'border' => 0, 'src' => '/inc/module/blank.html', 'name' => 'form_target_' . $this->id]));
        }
        return $html;
    }

    /**
     * @return node
     */
    public function get_html_body() {
        $form = node::create('form#' . $this->id . '.' . ($this->use_ajax ? 'ajax' : 'noajax'), $this->attributes);
        if (!empty($this->pre_fields_text)) {
            $form->nest(node::create('div.pre_fields_text', [], $this->pre_fields_text));
        }
        $form->nest($this->get_fields_html());
        if (!empty($this->post_fields_text)) {
            $form->nest(node::create('div.post_fields_text', [], $this->post_fields_text));
        }
        $form->nest($this->get_hidden_fields());
        if ($this->has_submit) {
            $form->nest($this->get_submit());
        }
        if (!empty($this->post_submit_text)) {
            $form->nest(node::create('div.post_submit_text', [], $this->post_submit_text));
        }
        if (!empty($this->post_text)) {
            $form->nest(node::create('div.post_text', [], $this->post_text));
        }
        return $form;
    }

    /**
     * @return array
     */
    public function get_fields_html() {
        $fieldsets = [];
        $fields = [];
        $fieldset_title = '';
        foreach ($this->fields as $field) {
            if (!$field->hidden) {
                if (isset($field->fieldset) && $fieldset_title != $field->fieldset) {
                    if ($fields) {
                        $fieldsets[] = node::create('fieldset.fieldset_' . count($fieldsets), [],
                            ($fieldset_title ? node::create('legend', [], $fieldset_title) : '') .
                            node::create('ul')->nest($fields)
                        );
                        $fields = [];
                    }
                    $fieldset_title = $field->fieldset;
                }
                if ($inner = $field->get_html_wrapper()) {
                    $fields[] = node::create('li#' . $this->id . '_field_' . $field->field_name . '.' . $field->get_wrapper_class(), ['data-for' => $this->id], $inner);
                }
            }
        }
        $fieldsets[] = node::create('fieldset.fieldset_' . count($fieldsets), [],
            ($fieldset_title ? node::create('legend', [], $fieldset_title) : '') .
            node::create('ul')->nest($fields)
        );
        return $fieldsets;
    }

    /**
     * @return bool|node
     */
    public function get_hidden_fields() {
        $hidden = false;
        $html = node::create('ul.hidden');
        foreach ($this->fields as $field) {
            if ($field->hidden) {
                $hidden = true;
                $html->add_child(node::create('li', ['data-for' => $this->id, 'class' => $field->get_wrapper_class()], $field->get_html_wrapper()));
            }

        }
        return ($hidden ? $html : false);
    }

    /**
     * @return node
     */
    public function get_submit() {
        if ($this->has_submit) {
            $field = node::create('div.form_submit', [], node::create('a.submit', [], $this->submit) . node::create('input.submit', ['type' => 'submit', 'data-for' => $this->id, 'name' => $this->submit]));
            if (!$this->submittable) {
                $field->add_attribute('disabled', 'disabled');
            }
            return $field;
        }
        return node::create('');
    }
}