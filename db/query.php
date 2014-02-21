<?php
namespace core\db;

abstract class query {

    protected $parameters = [];
    protected $fields = [];
    protected $table;
    protected $joins = [];
    protected $filters = [];
    protected $order = [];
    protected $groupings;
    protected $limit;

    public function __construct($table) {
        \classes\compiler::$dependants[] = $table;
        $this->table = $table;
    }

    public function retrieve($fields) {
        if ($fields === (array) $fields) {
            foreach ($fields as $field) {
                $this->add_field_to_retrieve($field);
            }
        } else {
            $this->add_field_to_retrieve($fields);
        }
        return $this;
    }

    public function add_field_to_retrieve($field) {
        $this->fields[] = $field;
        return $this;
    }

    public function set_base_table($table) {
        $this->table = $table;
        return $this;
    }

    public function add_join($table, $where, $parameters = [], $type = 'LEFT') {
        if (!isset($this->joins[$table]['where'])) {
            $this->joins[$table]['where'] = [];
        }
        if (is_array($where)) {
            foreach ($where as $where_clause) {
                $this->joins[$table]['where'][] = $where_clause;
            }
        } else {
            $this->joins[$table]['where'][] = $where;
        }
        $this->joins[$table]['type'] = $type;
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    public function filter_field($field, $value, $operator = '=') {
        $field_name = 'filter_' . (count($this->parameters) + 1);
        if (strstr($field, '.') === false && strstr($field, ' AS ') === false) {
            $field = '`' . $field . '`';
        }
        $this->filter($field . $operator . ':' . $field_name);
        $this->parameters[$field_name] = $value;
        return $this;
    }

    protected function add_filter($clause, $parameters = []) {
        $this->filters[] = $clause;
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    public function filter($clauses, $parameters = []) {
        if ($clauses === (array) $clauses) {
            foreach ($clauses as $clause) {
                $this->add_filter($clause);
            }
        } else {
            $this->add_filter($clauses);
        }
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    public function set_order($term) {
        $this->order = [$term];
        return $this;
    }

    public function add_order($term) {
        $this->order[] = $term;
        return $this;
    }

    public function add_grouping($field) {
        $this->groupings[] = $field;
        return $this;
    }

    public function set_limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    abstract public function execute();

    protected function get_joins() {
        $sql = '';
        foreach ($this->joins as $table => $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . $table . ' ON ' . implode(' AND ', $join['where']);
        }
        return $sql;
    }

    protected function get_filters() {
        if ($this->filters) {
            return ' WHERE ' . implode(' AND ', $this->filters);
        }
        return '';
    }

    protected function get_groupings() {
        if ($this->groupings) {
            return ' GROUP BY ' . implode(',', $this->groupings);
        }
        return '';
    }

    protected function get_limit() {
        if ($this->limit) {
            return ' LIMIT ' . $this->limit;
        }
        return '';
    }

    protected function get_order() {
        if ($this->order) {
            return ' ORDER BY ' . implode(',', $this->order);
        }
        return '';
    }

}
