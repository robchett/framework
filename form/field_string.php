<?php
namespace core\form;

abstract class field_string extends \form\field {

    public function get_database_create_query() {
        return 'varchar(255)';
    }
}
