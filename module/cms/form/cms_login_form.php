<?php
namespace core\module\cms\form;

use classes\ajax as _ajax;
use form\form;
use module\cms\controller;

abstract class cms_login_form extends form {

    public $password;
    public $username;

    public function __construct() {
        $fields = [
            form::create('field_string', 'username')->set_attr('label', 'Username'),
            form::create('field_password', 'password')->set_attr('label', 'Password')
        ];
        parent::__construct($fields);
        $this->h2 = 'Admin Login - ' . \classes\get::ini('title_tag', 'site', '');
        $this->submit = 'Login';
    }

    public function do_validate() {
        parent::do_validate();
        if (!($this->username == ***REMOVED*** && $this->password == '***REMOVED***')) {
            $this->validation_errors['username'] = 'Username and password combination does not match.';
        }

    }

    public function do_submit() {
        if (parent::do_submit()) {
            $_SESSION['admin'] = true;
            _ajax::$redirect = '/cms/dashboard';
            controller::do_database_repair();
        }
    }

}
