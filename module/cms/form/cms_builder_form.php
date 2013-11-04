<?php
namespace core\module\cms\form;

use classes\ajax;
use classes\db;
use classes\get;
use form\form;

abstract class cms_builder_form extends form {

    public $password;
    public $username;

    public function __construct() {
        $fields = array(
            form::create('field_string', 'username')->set_attr('label', 'Username'),
            form::create('field_password', 'password')->set_attr('label', 'Password')
        );
        parent::__construct($fields);
        $this->h2 = 'Database Creation';
        $this->submit = 'Create';
    }

    public function do_submit() {
        if (parent::do_submit()) {
            db::connect_root();
            db::query('CREATE DATABASE IF NOT EXISTS `' . get::fn($this->username) . '`');
            db::query('CREATE USER \'' . get::fn($this->username) . '\'@\'127.0.0.1\' IDENTIFIED BY \'' . $this->password . '\'', [], true);
            db::query('GRANT ALL PRIVILEGES ON `' . get::fn($this->username) . '`.* TO \'' . get::fn($this->username) . '\'@\'localhost\'', [], true);
            if (!is_dir(root . '/.conf')) {
                mkdir(root . '/.conf');
            }
            file_put_contents(root . '/.conf/config.ini', '[mysql]
server = \'127.0.0.1\'
username = \'' . get::fn($this->username) . '\'
password = \'' . $this->password . '\'
database = \'' . get::fn($this->username) . '\''
            );
            ajax::$redirect = '/cms';
        }
    }

}
