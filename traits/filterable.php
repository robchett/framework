<?php
namespace core\traits;

use classes\ajax;
use classes\collection_filter;
use classes\filter_form;
use classes\get;
use form\field_link;
use html\node;

trait filterable {


    /** @var  filter_form */
    public $filters;

    abstract public function get_filterable_fields();

    abstract public function get_all($class, array $fields_to_retrieve, $options = []);

    abstract public function get_filterable_list();

    abstract public function getArrayCopy();

    abstract public function exchangeArray($array);

    abstract public function filterable_get_all();

    abstract function getIterator();

    abstract function setIterator(\Iterator $iterator);

    public function set_filters() {
        if (!isset($this->filters)) {
            $this->filters = new filter_form($this->get_filterable_fields(), $this);
            $this->filters->set_from_request();
            $this->filters->attributes['data-ajax-change'] = get_class($this) . ':do_filter_submit';
            if (!$this->filters->identifier) {
                $this->filters->identifier = clean_uri;
            }
            if (isset($_SESSION[get_class($this)][$this->filters->identifier]['filter'])) {
                foreach ($_SESSION[get_class($this)][$this->filters->identifier]['filter'] as $filter => $value) {
                    $this->filters->$filter = $value;
                }
            }
        }
    }

    public function do_filter_submit() {
        $this->filterable_get_all();
        $this->set_filters();
        $this->filter();
        ajax::update($this->get_filterable_list());
    }

    public function get_filters() {
        $this->set_filters();
        $html = node::create('div#filters', [],
            node::create('h3', [], 'Filters') .
            $this->filters->get_html()
        );
        return $html;
    }

    public function get_filters_ajax() {
        $this->filterable_get_all();
        $this->get_filters();
    }

    public function filter() {
        $res = $this->getIterator();
        if (!isset($this->filters)) {
            $this->set_filters();
        }
        foreach ($this->filters->fields as $field) {
            if ($field->field_name != 'identifier' && isset($this->filters->{$field->field_name}) && $this->filters->{$field->field_name}) {
                $key = $field->field_name;
                if ($field->original_field instanceof field_link) {
                    $key = get::__class_name($field->original_field->get_link_module());
                }
                $res = new collection_filter($res, $key, $this->filters->{$field->field_name});
            }
        }
        $result_set = [];
        foreach ($res as $object) {
            $result_set[] = $object;
        }
        $this->exchangeArray($result_set);
    }

}
 