<?php
namespace core\db;

use classes\cache;
use classes\compiler;
use classes\db as _db;
use db\query as _query;

abstract class update extends _query {

    protected $values = [];
    public $retrieve_unlive = true;
    public $retrieve_deleted = true;


    public function execute() {
        $query = 'UPDATE ' . $this->table . ' SET ' . $this->get_values() . $this->get_filters();
        compiler::break_cache($this->table);
        cache::break_cache($this->table);
        return _db::query($query, $this->parameters);
    }

    protected function get_values() {
        $sql = [];
        foreach ($this->values as $field => $value) {
            $sql[] = '`' . $field . '`=:' . $field;
            $this->parameters[$field] = $value;
        }
        return implode(', ', $sql);
    }

    public function add_value($field, $value) {
        $this->values[$field] = $value;
        return $this;
    }
}