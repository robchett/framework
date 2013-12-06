<?php
namespace core\classes;

use classes\collection as _collection;

class glob extends _collection {

    public function __construct($expression, $flags = null) {
        parent::__construct(glob($expression, $flags));
    }
}
 