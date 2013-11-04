<?php
namespace core\form;

class field_collection extends \classes\collection {

    public function get_field($field_name) {
        foreach ($this as $field) {
            if ($field->field_name == $field_name) {
                return $field;
            }
        }
        throw new \Exception('field:' . $field_name . ' not found');
    }

}
 