<?php
namespace core\traits;

use classes\ajax;
use classes\sort_form;
use html\node;

trait sortable {

    /** @var sort_form */
    public $sort_form;

    abstract public function getArrayCopy();

    abstract public function get_sortable_fields();

    /** will create a data set and perform and actions on it. */
    abstract public function set_list();

    abstract public function exchangeArray($array);

    abstract public function get_list();

    /** @return \core\classes\collection_iterator */
    abstract function getIterator();

    abstract function setIterator(\Iterator $iterator);

    public function set_sort() {
        if (!isset($this->sort_method)) {
            $this->sort_form = new sort_form($this->get_sortable_fields(), get_class($this));
            if (ajax && $_REQUEST['act'] == 'do_sort_submit')
                $this->sort_form->set_from_request();
            $this->sort_form->attributes['data-ajax-change'] = get_class($this) . ':do_sort_submit';
            if (!$this->sort_form->identifier) {
                $this->sort_form->identifier = clean_uri;
            }
            if (isset($_SESSION[get_class($this)][$this->sort_form->identifier]['sort'])) {
                $this->sort_form->sort = $_SESSION[get_class($this)][$this->sort_form->identifier]['sort'];
            }
        }
    }

    public function do_sort_submit() {
        $this->set_list();
        ajax::update($this->get_list());
    }

    public function get_sort() {
        $this->set_sort();
        $html = node::create('div#sort', [],
            node::create('h3', [], 'Sort Filters') .
            $this->sort_form->get_html()
        );
        return $html;
    }

    public function get_sort_ajax() {
        $this->get_sort();
    }

    public function sort() {
        if (!$this->sort_form) {
            $this->set_sort();
        }
        if ($this->sort_form->sort) {
            $this->getIterator()->uasort([$this, $this->sort_form->sort]);
        }
    }

}
 