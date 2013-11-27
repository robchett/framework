<?php
namespace core\classes;

class collection_iterator extends \ArrayIterator {

    public function iterate($function, &$count = 0) {
        $this->rewind();
        foreach ($this as $object) {
            $count++;
            call_user_func($function, $object, $count);
        }
    }

    public function iterate_return($function, &$count = 0) {
        $this->rewind();
        $res = '';
        while ($this->valid()) {
            $count++;
            $res .= call_user_func($function, $this->current(), $count);
            $this->next();
        }
        return $res;
    }
}
 