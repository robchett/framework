<?php
namespace core\form;

use classes\ajax;
use classes\get;
use form\field as _field;
use form\field_file as _field_file;
use html\node;

/**
 * Class form
 * @package form
 */
abstract class form {

    public $bootstrap = [2, 10, 'form-horizontal'];

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
    public $wrapper_class = ['form_wrapper'];
    public $field_wrapper_class = ['form-group'];
    public $submit_attributes = ['type'=>'submit'];
    protected $table_object;

    /**
     * @param array $fields
     */
    public function  __construct($fields) {
        $this->fields = [];
        foreach ($fields as $field) {
            $this->add_field($field);
        }
    }

    public function add_field(_field $field) {
        $field->parent_form = $this;
        $this->{$field->field_name} = $field->value;
        if ($field instanceof _field_file) {
            $this->use_ajax = false;
        }
        $this->fields[$field->field_name] = $field;
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
     * @param      $object
     * @param bool $change_target
     */
    public function set_from_object($object, $change_target = true) {
        $this->table_object = $object;
        foreach ($this->fields as $field) {
            if (isset($object->{$field->field_name})) {
                $this->{$field->field_name} = $object->{$field->field_name};
            }
        }
        if ($change_target) {
            $this->action = get_class($object) . ':do_form_submit';
        }
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
            unset($this->fields[$field_name]);
            return true;
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

    public function has_field($name) {
        return isset($this->fields[$name]);
    }

    /**
     * @param $name
     * @return field
     * @throws \Exception
     */
    public function get_field_from_name($name) {
        if ($this->has_field($name)) {
            return $this->fields[$name];
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
        foreach ($this->validation_errors as $key => $val) {
            $field = $this->get_field_from_name($key);
            $field->add_class('has-error');
            $field->add_wrapper_class('has-error');
        }
        ajax::update($this->get_html()->get());
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
        $html = node::create('div#' . $this->id . '_wrapper.' . implode('.', $this->wrapper_class));
        $this->attributes = array_merge([
                'name' => $this->id,
                'method' => $this->method,
                'action' => !empty($this->action) ? $this->action : get_class($this) . ':' . 'do_form_submit',
                'data-ajax-shroud' => '#' . $this->id,
            ], $this->attributes
        );
        $this->attributes['class'][] = $this->bootstrap[2];
        if ($this->h2) {
            $html->nest(node::create('h2.form_title', [], $this->h2));
        }
        $html->nest($this->get_html_body());
        if (!$this->use_ajax) {
            $html->add_child(node::create('iframe#form_target_' . $this->id . '.form_frame', ['style' => 'display:none', 'src' => '/inc/module/blank.html', 'name' => 'form_target_' . $this->id]));
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
        if (!empty($this->post_text)) {
            $form->nest(node::create('div.post_text', [], $this->post_text));
        }
        return $form;
    }

    /**
     * @param fields[] $fields
     * @param int      $index
     * @param string   $title
     *
     * @return bool|\core\html\node
     */
    protected function get_field_set($fields, $index = 1, $title = '') {
        if ($fields) {
            $field_set = node::create('fieldset.fieldset_' . $index, []);
            if($title) {
                $field_set->nest(node::create('legend', [], $title));
            }
            $field_set->nest($fields);
            return $field_set;
        }
        return false;
    }

    /**
     * @return array
     */
    public function get_fields_html() {
        $field_sets = [];
        $fields = [];
        $field_set_title = '';
        foreach ($this->fields as $field) {
            if (!$field->hidden) {
                if (isset($field->fieldset) && $field_set_title != $field->fieldset) {
                    $field_set = $this->get_field_set($fields, count($field_sets), $field_set_title);
                    if($field_set) {
                        $field_sets[] = $field_set;
                        $field_set_title = $field->fieldset;
                        $fields = [];
                    }
                }
                if ($inner = $field->get_html_wrapper()) {
                    $fields[] = node::create('div#' . $this->id . '_field_' . $field->field_name . $field->get_wrapper_class(), ['data-for' => $this->id], $inner);
                }
            }
        }
        if ($this->has_submit) {
            $fields[] = $this->get_submit();
        }
        $field_set = $this->get_field_set($fields, count($field_sets), $field_set_title);
        if($field_set) {
            $field_sets[] = $field_set;
        }
        return $field_sets;
    }

    /**
     * @return node[]
     */
    public function get_hidden_fields() {
        $hidden = [];
        foreach ($this->fields as $field) {
            if ($field->hidden) {
                $hidden[] = $field->get_html_wrapper();
            }

        }
        return $hidden ? node::create('div.hidden', [], $hidden) : '';
    }

    /**
     * @return node
     */
    public function get_submit() {
        if ($this->has_submit) {
            $field = node::create('div.form-group div.col-md-offset-' . $this->bootstrap[0] . '.col-md-' . $this->bootstrap[1], [], node::create('button.btn.btn-default', $this->submit_attributes, $this->submit));
            if (!$this->submittable) {
                $field->add_attribute('disabled', 'disabled');
            }
            return $field;
        }
        return node::create('');
    }
}