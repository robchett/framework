<?php
namespace core\classes;

class collection_filter extends \FilterIterator {

    private $values;
    private $key;

    public function __construct(\Iterator $iterator, $key, $values) {
        parent::__construct($iterator);
        $this->values = $values;
        $this->key = $key;
    }

    public function accept() {
        $object = $this->getInnerIterator()->current();
        if(is_array($this->key)) {
            $base = $object->{$this->key[0]}->{$this->key[1]};
        } else {
            $base = $object->{$this->key};
        }
        if (is_object($base)) {
            foreach ($base as $int) {
                if (in_array($int, $this->values)) {
                    return true;
                }
            }
        } else {
            if (in_array($base, $this->values)) {
                return true;
            }
        }
        return false;
    }
}