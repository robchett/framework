<?php
namespace core\classes;

use form\form;

class table_form extends form {

    public $source_table;

    public function __construct(table $table) {
        $this->source_table = $table;
        parent::__construct($table->get_fields()->getArrayCopy());
    }


    /**
     * @return bool
     */
    public function do_submit() {
        return true;
    }
}
 