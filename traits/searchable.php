<?php
namespace core\traits;

use classes\ajax;
use classes\search_form;
use html\node;

trait searchable {

    /** @var search_form */
    public $search_form;

    abstract public function get_searchable_fields();

    abstract public function exchangeArray($array);

    /** @return \core\classes\collection_iterator */
    abstract function getIterator();

    /** will create a data set and perform any actions on it. */
    abstract public function set_list();

    abstract public function get_list();

    public function set_search() {
        if (!isset($this->sort_method)) {
            $this->search_form = new search_form(get_class($this));
            if (!$this->search_form->identifier) {
                $this->search_form->identifier = clean_uri;
            }
            if (ajax && $_REQUEST['act'] == 'do_search_submit')
                $this->search_form->set_from_request();
            $this->search_form->attributes['data-ajax-change'] = get_class($this) . ':do_search_submit';
            if (isset($_SESSION[get_class($this)][$this->search_form->identifier]['search'])) {
                $this->search_form->keywords = $_SESSION[get_class($this)][$this->search_form->identifier]['search'];
            }
        }
    }

    public function do_search_submit() {
        $this->set_list();
        ajax::update($this->get_list());
    }

    public function get_search() {
        $this->set_search();
        $html = node::create('div#search', [],
            $this->search_form->get_html()
        );
        return $html;
    }

    public function get_search_ajax() {
        $this->get_search();
    }

    public function search() {
        if (!$this->search_form) {
            $this->set_search();
        }
        if ($this->search_form->keywords) {
            $this->search_preprocess();
            $result_set = [];
            foreach ($this->getIterator() as $object) {
                if ($object->search_relevance) {
                    $result_set[] = $object;
                }
            }
            $this->exchangeArray($result_set);
        }
    }

    public function search_preprocess() {
        $fields = $this->get_searchable_fields();
        foreach ($this as $entry) {
            $entry->search_relevance = 0;
            foreach ($fields as $field => $options) {
                if ($entry->$field && stripos($entry->$field, $this->search_form->keywords) !== false) {
                    $entry->search_relevance += $options['value'];
                }
            }
        }
    }

    public function do_sort_by_relevance($a, $b) {
        return $a->search_relevance > $b->search_relevance;
    }
}
 