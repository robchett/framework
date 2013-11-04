<?php
namespace core\classes\css;

abstract class compiler {

    public $file_extension;

    abstract public function add_file($file_name);

    abstract public function compile();
}
 