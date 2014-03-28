<?php
namespace core\module\cms\form;

use classes\ajax;
use classes\db;
use classes\get;
use classes\ini;
use form\form;
use module\cms\object\_cms_user;

abstract class cms_builder_form extends form {

    public $password;
    public $username;
    public $site_name;

    public function __construct() {
        $fields = [
            form::create('field_string', 'site_name')
                ->set_attr('label', 'Site Name')
                ->set_attr('fieldset', 'Database Details'),
            form::create('field_string', 'username')
                ->set_attr('label', 'Database'),
            form::create('field_password', 'password')
                ->set_attr('label', 'Password'),
            form::create('field_checkboxes', 'modules', $this->get_modules())
                ->set_attr('label', 'Modules')
                ->set_attr('required', false)
                ->set_attr('fieldset', 'Modules'),
        ];
        $i = 0;
        do {
            $fields['user_' . $i] = form::create('field_string', 'user_' . $i)
                ->set_attr('label', 'User ' . ($i + 1))
                ->set_attr('fieldset', 'User ' . ($i + 1) . ' Details')
                ->set_attr('required', false);
            $fields['password_' . $i] = form::create('field_password', 'password_' . $i)
                ->set_attr('label', 'Password ' . ($i + 1))
                ->set_attr('required', false);
            $fields['user_level_' . $i] = form::create('field_select', 'user_level' . ($i + 1))
                ->set_attr('options', [1 => 'User', 2 => 'Webmaster', 3 => 'Admin'])
                ->set_attr('label', 'User Level ' . $i)
                ->set_attr('required', false);
            $i++;
        } while (isset($_REQUEST['user' . $i]));
        parent::__construct($fields);

        foreach ($this->fields as $field) {
            $field->attributes['placeholder'] = $field->label;
        }
        $this->set_required_modules();
        $this->h2 = 'Site Creation';
        $this->submit = 'Create';
        $this->id = 'cms_builder';
    }


    private function get_modules() {
        $options = [];
        $stubs = glob(core_dir . '/db/structures/*.json');
        foreach ($stubs as $stub) {
            $base = pathinfo($stub, PATHINFO_FILENAME);
            try {
                $json = \core\db\stub\module::create($base);
                $options[$json->group][$base] = $json->title . ($json->required ? '(*)' : '');
            } catch (\Exception $e) {
            }
        }
        return $options;
    }

    private function set_required_modules() {
        $options = [];
        $stubs = glob(core_dir . '/db/structures/*.json');
        foreach ($stubs as $stub) {
            $base = pathinfo($stub, PATHINFO_FILENAME);
            try {
                $json = \core\db\stub\module::create($base);
                if ($json->required) {
                    $options[] = $base;
                }
            } catch (\Exception $e) {
            }
        }
        \core::$inline_script[] = '
        var required = ' . json_encode($options) . ';
        $("#cms_builder_field_modules input").each(function(cnt, input) {
            if($.inArray($(input).val(), required) != -1) {
                $(input).addClass("readonly");
                $(input).prop("checked", true);
            }
        });';
    }

    public function do_submit() {
        db::connect_root();
        db::query('CREATE DATABASE IF NOT EXISTS `' . get::fn($this->username) . '`');
        db::query('USE mysql');
        if (db::select('user')->retrieve(['user'])->filter(['`user`=:user AND `host`=:host'], ['user' => $this->username, 'host' => '127.0.0.1'])->execute()->rowCount()) {
            db::query('CREATE USER \'' . get::fn($this->username) . '\'@\'127.0.0.1\' IDENTIFIED BY \'' . $this->password . '\'', [], true);
        }
        if (db::select('user')->retrieve(['user'])->filter(['`user`=:user AND `host`=:host'], ['user' => $this->username, 'host' => 'localhost'])->execute()->rowCount()) {
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

        $i = 0;
        do {
            if ($this->{'user_' . $i} && $this->{'password_' . $i}) {
                $user = new _cms_user();
                $user->title = $this->{'user_' . $i};
                $user->password = $this->{'password_' . $i};
                $user->ulid = ($this->{'user_level_' . $i} ? : 1);
            }
            $i++;
        } while (isset($this->{'user_' . $i}));
        ajax::current()->redirect = '/cms/login';
    }

}
