<?php
namespace core\db;

use core\classes\db;
use db\query as _query;

abstract class insert extends _query {

    protected $values = [];

    public function execute() {
        $query = 'INSERT INTO ' . $this->table . ' SET ' . $this->get_values();
        db::query($query, $this->parameters);
        return db::insert_id();
    }

    protected function get_values() {
        $sql = [];
        foreach ($this->values as $field => $value) {
            $sql[] = '`' . $field . '` = :' . $field;
            $this->parameters[$field] = $value;
        }
        return implode(', ', $sql);
    }

    public function add_value($field, $value) {
        $this->values[$field] = $value;
        return $this;
    }

}
