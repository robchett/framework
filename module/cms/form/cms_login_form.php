<?php
namespace core\module\cms\form;

use classes\ajax as _ajax;
use classes\ini;
use classes\session;
use form\form;
use html\node;
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
        $this->pre_fields_text = node::create('h2.form-signin-heading.text-center', [], node::create('small', [], 'Admin Login<br/>') .ini::get('site', 'title_tag'))->get();
        $this->submit = 'Login';
        $this->id = 'cms_login';
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
        return !count($this->validation_errors);
    }

    public function do_form_submit() {
        controller::do_database_repair();
        parent::do_form_submit();
    }

    public function do_submit() {
        session::set(true,'admin');
        _ajax::current()->redirect = '/cms/dashboard';
    }
}
