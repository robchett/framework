<?php
namespace core\module\cms\form;

use classes\ajax;
use classes\db;
use classes\get;
use classes\ini;
use core\module\cms\object\cms_builder;
use form\form;

abstract class cms_builder_form extends form {

    public $password;
    public $username;
    public $site_name;

    public function __construct() {
        $fields = [
            form::create('field_string', 'site_name')->set_attr('label', 'Site Name'),
            form::create('field_string', 'username')->set_attr('label', 'Username'),
            form::create('field_password', 'password')->set_attr('label', 'Password')
        ];
        parent::__construct($fields);
        $this->h2 = 'Site Creation';
        $this->submit = 'Create';
    }

    public function do_submit() {
        if (parent::do_submit()) {
            db::connect_root();
            db::query('CREATE DATABASE IF NOT EXISTS `' . get::fn($this->username) . '`');
            db::query('USE mysql');
            if(db::select('user')->retrieve(['user'])->filter(['`user`=:user AND `host`=:host'], ['user'=>$this->username, 'host'=>'127.0.0.1'])->execute()->rowCount()) {
                db::query('CREATE USER \'' . get::fn($this->username) . '\'@\'127.0.0.1\' IDENTIFIED BY \'' . $this->password . '\'', [], true);
            }
            if(db::select('user')->retrieve(['user'])->filter(['`user`=:user AND `host`=:host'], ['user'=>$this->username, 'host'=>'localhost'])->execute()->rowCount()) {
                db::query('CREATE USER \'' . get::fn($this->username) . '\'@\'localhost\' IDENTIFIED BY \'' . $this->password . '\'', [], true);
            }
            db::query('GRANT ALL PRIVILEGES ON `' . get::fn($this->username) . '`.* TO \'' . get::fn($this->username) . '\'@\'127.0.0.1\'', [], true);
            db::query('GRANT ALL PRIVILEGES ON `' . get::fn($this->username) . '`.* TO \'' . get::fn($this->username) . '\'@\'localhost\'', [], true);
            if (!is_dir(root . '/.conf')) {
                mkdir(root . '/.conf');
            }
            ini::save(root . '/.conf/config.ini', [
                    'mysql' => [
                        'server' => '127.0.0.1',
                        'username' => get::fn($this->username),
                        'password' => $this->password,
                        'database' => get::fn($this->username),
                    ],
                    'site' => [
                        'title_tag' => $this->site_name
                    ]
                ]
            );
            ini::reload();
            db::default_connection();
            $cms_builder = new \module\cms\object\cms_builder();
            $cms_builder->manage();
            ajax::$redirect = '/cms/login';
        }
    }

}
