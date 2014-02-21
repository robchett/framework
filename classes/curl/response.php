<?php
namespace core\classes\curl;

class response {

    public $data;
    public $request;
    public $info;
    public $status_code;
    public $header_list;

    public function __construct() {
        $this->status_code = 0;
    }
}