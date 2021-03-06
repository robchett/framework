<?php
namespace core\db;

use classes\cache;
use classes\compiler;
use classes\db as _db;
use db\query as _query;

abstract class insert extends _query {

    protected $values = [];
    protected $mode;

    public function __construct($table, $mode = '') {
        parent::__construct($table);
        $this->mode = $mode;
    }

    public function execute() {
        $query = 'INSERT ' . $this->mode. ' INTO ' . $this->table . ' SET ' . $this->get_values();
        _db::query($query, $this->parameters);
        $id = _db::insert_id();
        compiler::break_cache($this->table);
        cache::break_cache($this->table);
        return $id;
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
