<?php
namespace core\traits;

use classes\paginate;
use html\node;

trait paginatable {

    public $paginate_npp = 20;
    public $paginate_base_url;
    public $paginate_act;
    public $paginate_page;

    /**
     * @param string $id_suffix
     * @return \core\classes\paginate
     */
    public function get_paginate($id_suffix) {
        $paginate = new paginate();
        $paginate->npp = $this->paginate_npp;
        $paginate->base_url = $this->get_paginate_base_url();
        $paginate->total = $this->get_paginate_total();
        $paginate->page = $this->get_paginate_page();;
        $paginate->act = $this->get_paginate_act();
        return node::create('div#' . $this->get_id() . '_paginate' . $id_suffix . '.paginate', [], $paginate);
    }

    public function get_paginate_total() {
        return $this->count();
    }

    public function get_paginate_total_pages() {
        return ceil($this->count() / $this->paginate_npp);
    }

    public function get_paginate_base_url() {
        if (!isset($this->paginate_base_url)) {
            return trim(clean_uri, '/');
        } else {
            return $this->paginate_base_url;
        }
    }

    public function get_paginate_offset() {
        return ($this->get_paginate_page() - 1) * $this->paginate_npp;
    }

    public abstract function count();

    public abstract function do_paginate();

    public abstract function get_id();

    public function get_paginate_page() {
        if (!isset($this->paginate_page)) {
            if (strstr(uri, '/page/') !== false) {
                $path = explode('/', uri);
                foreach ($path as $key => $value) {
                    if ($value == 'path') {
                        return $path[$key + 1];
                    }
                }
            }
        } else {
            return $this->paginate_page;
        }
        return 1;
    }

    public function get_paginate_act() {
        if (isset($this->paginate_act)) {
            return $this->paginate_act;
        } else {
            return get_class($this) . ':do_paginate';
        }
    }

}
 