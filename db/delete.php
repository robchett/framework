<?php
namespace core\db;

use classes\compiler;
use \classes\db as _db;
use db\query as _query;

abstract class delete extends _query {

    /**
     * @return \PDOStatement
     */
    public function execute() {
        $query = 'DELETE FROM ' . $this->table . $this->get_filters();
        compiler::break_cache($this->table);
        return _db::query($query, $this->parameters);
    }
}
 