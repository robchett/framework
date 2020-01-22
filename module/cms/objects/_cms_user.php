<?php
namespace core\module\cms\objects;

use classes\table;
use traits\table_trait;

class _cms_user extends table {

    use table_trait;

    public $last_login;
    public $last_login_ip;
    public $title;
    public $password;
    public $ulid;

}
 