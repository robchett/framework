<?php

namespace core\classes;

abstract class table_iterator extends \ArrayIterator {

    /**
     * @var int
     */
    public $index = -1;

    /**
     *
     */
    public function rewind() {
        $this->index = -1;
        parent::rewind();
    }

    /**
     *
     */
    public function reset() {
        $this->index = -1;
        parent::rewind();
    }
}
