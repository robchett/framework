<?php
namespace core\module\cms\form;

use classes\ajax as _ajax;
use classes\ini;
use form\form;
use module\cms\controller;
use module\cms\object\_cms_user;

abstract class cms_login_form extends form {

    public $password;
    public $username;

    public function __construct() {
        $fields = [
            form::create('field_string', 'username')->set_attr('label', 'Username'),
            form::create('field_password', 'password')->set_attr('label', 'Password')
        ];
        parent::__construct($fields);
        $this->h2 = 'Admin Login - ' . ini::get('site', 'title_tag');
        $this->submit = 'Login';
    }

    public function do_validate() {
        parent::do_validate();
        $user = new _cms_user();
        $user->do_retrieve([], ['where_equals' => ['title' => $this->username, 'password' => md5($this->password)]]);
        if ($user->get_primary_key()) {
            $user->last_login = time();
            $user->last_login_ip = ip;
        } else {
            $this->validation_errors['username'] = 'Username and password combination does not match.';
        }

    }

    public function do_submit() {
        controller::do_database_repair();
        if (parent::do_submit()) {
            $_SESSION['admin'] = true;
            _ajax::$redirect = '/cms/dashboard';
        }
    }

}
