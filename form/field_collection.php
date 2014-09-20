<?php
namespace core\form;

use classes\table_array;

class field_collection extends table_array {

    /**
     * @param $field_name
     *
     * @return field
     * @throws \Exception
     */
    public function get_field($field_name) {
        if($this->has_field($field_name)) {
            return $this[$field_name];
        } else {
            throw new \Exception('field:' . $field_name . ' not found');
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function has_field($name) {
        return isset($this[$name]);
    }

}
 