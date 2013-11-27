<?php
namespace core\classes\interfaces;

abstract class asset {

    public $content_type = 'text/plain';

    public abstract function compile();

    public abstract function add_files($files);

    public abstract function add_resource_root($root);

}
 