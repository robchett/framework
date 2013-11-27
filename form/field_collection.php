<?php
namespace core\form;

use classes\table_array;

class field_collection extends table_array {

    public function get_field($field_name) {
        foreach ($this as $field) {
            if ($field->field_name == $field_name) {
                return $field;
            }
        }
        throw new \Exception('field:' . $field_name . ' not found');
    }

}
 