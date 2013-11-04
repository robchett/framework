<?php
namespace core\module\cms\object;

use classes\collection;
use classes\db;
use classes\get;
use module\cms\object\field_type;

class cms_builder {

    public static $current_version = 1;

    public function __construct() {
        $this->_cms_module_fields = new collection([
            'mid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
            'parent_mid' => 'SMALLINT NOT NULL',
            'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
            'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'position' => 'SMALLINT NOT NULL',
            'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
            'ts' => 'TIMESTAMP NOT NULL',
            'gid' => 'SMALLINT NOT NULL',
            'primary_key' => 'varchar(15) NOT NULL',
            'title' => 'varchar(255) NOT NULL',
            'table_name' => 'varchar(255) NOT NULL',
            'namespace' => 'varchar(255) NOT NULL',
        ]);

        $this->_cms_group_fields = new collection([
            'gid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
            'parent_gid' => 'SMALLINT NOT NULL',
            'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
            'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'position' => 'SMALLINT NOT NULL',
            'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
            'ts' => 'TIMESTAMP NOT NULL',
            'title' => 'varchar(255) NOT NULL',
        ]);

        $this->_cms_field_fields = new collection([
            'fid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
            'parent_fid' => 'SMALLINT NOT NULL',
            'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
            'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'position' => 'SMALLINT NOT NULL',
            'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
            'ts' => 'TIMESTAMP NOT NULL',
            'field_name' => 'varchar(127) NOT NULL',
            'title' => 'varchar(127) NOT NULL',
            'type' => 'varchar(15) NOT NULL',
            'mid' => 'SMALLINT NOT NULL',
            'list' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
            'filter' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'required' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'link_module' => 'SMALLINT NOT NULL',
            'link_field' => 'SMALLINT NOT NULL',
        ]);

        $this->_field_type_fields = new collection([
            'ftid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
            'parent_gid' => 'SMALLINT NOT NULL',
            'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
            'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'position' => 'SMALLINT NOT NULL',
            'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
            'ts' => 'TIMESTAMP NOT NULL',
            'title' => 'varchar(255) NOT NULL',
        ]);
    }


    public function build() {
        db::create_table('_cms_group', $this->_cms_group_fields->getArrayCopy(), ['PRIMARY KEY (`gid`)']);
        db::create_table('_cms_module', $this->_cms_module_fields->getArrayCopy(), ['PRIMARY KEY (`mid`)']);
        db::create_table('_cms_field', $this->_cms_field_fields->getArrayCopy(), ['PRIMARY KEY (`fid`)']);


        // Create basic group
        $_group_id = db::insert('_cms_group')
            ->add_value('title', '_CMS_STRUCTURE')
            ->execute();

        // Create basic modules
        $_module_insert_id = db::insert('_cms_module')
            ->add_value('gid', $_group_id)
            ->add_value('primary_key', 'mid')
            ->add_value('title', 'CMS Module')
            ->add_value('table_name', '_cms_module')
            ->add_value('namespace', 'cms')
            ->execute();
        $_group_insert_id = db::insert('_cms_module')
            ->add_value('gid', $_group_id)
            ->add_value('primary_key', 'gid')
            ->add_value('title', 'CMS Group')
            ->add_value('table_name', '_cms_group')
            ->add_value('namespace', 'cms')
            ->execute();
        $_field_insert_id = db::insert('_cms_module')
            ->add_value('gid', $_group_id)
            ->add_value('primary_key', 'fid')
            ->add_value('title', 'CMS Field')
            ->add_value('table_name', '_cms_field')
            ->add_value('namespace', 'cms')
            ->execute();

        $_field_type_insert_id = db::insert('_cms_module')
            ->add_value('gid', $_group_id)
            ->add_value('primary_key', 'ftid')
            ->add_value('title', 'CMS Field Type')
            ->add_value('table_name', 'field_type')
            ->add_value('namespace', 'cms')
            ->execute();


        // Create basic fields

        // Group
        $_group_table_key = db::insert('_cms_field')
            ->add_value('field_name', 'gid')
            ->add_value('title', 'Group ID')
            ->add_value('type', 'int')
            ->add_value('mid', $_group_insert_id)
            ->add_value('position', 0)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'parent_gid')
            ->add_value('title', 'Parent group ID')
            ->add_value('type', 'link')
            ->add_value('mid', $_group_insert_id)
            ->add_value('link_module', $_group_insert_id)
            ->add_value('link_field', $_group_table_key)
            ->add_value('position', 1)
            ->execute();
        $group_title_id = db::insert('_cms_field')
            ->add_value('field_name', 'title')
            ->add_value('title', 'Title')
            ->add_value('type', 'string')
            ->add_value('mid', $_group_insert_id)
            ->add_value('position', 2)
            ->execute();

        // Module
        $_module_table_key = db::insert('_cms_field')
            ->add_value('field_name', 'mid')
            ->add_value('title', 'Module ID')
            ->add_value('type', 'int')
            ->add_value('mid', $_module_insert_id)
            ->add_value('position', 0)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'parent_mid')
            ->add_value('title', 'Parent module ID')
            ->add_value('type', 'link')
            ->add_value('mid', $_module_insert_id)
            ->add_value('link_module', $_module_insert_id)
            ->add_value('link_field', $_module_table_key)
            ->add_value('position', 1)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'title')
            ->add_value('title', 'Title')
            ->add_value('type', 'string')
            ->add_value('mid', $_module_insert_id)
            ->add_value('position', 2)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'table_name')
            ->add_value('title', 'Table name')
            ->add_value('type', 'string')
            ->add_value('mid', $_module_insert_id)
            ->add_value('position', 3)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'namespace')
            ->add_value('title', 'Object namespace')
            ->add_value('type', 'string')
            ->add_value('mid', $_module_insert_id)
            ->add_value('position', 4)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'gid')
            ->add_value('title', 'Module group')
            ->add_value('type', 'link')
            ->add_value('mid', $_module_insert_id)
            ->add_value('link_module', $_group_insert_id)
            ->add_value('link_field', $group_title_id)
            ->add_value('position', 5)
            ->execute();

        // Create field_type table
        db::create_table('field_type', $this->_field_type_fields->getArrayCopy(), ['PRIMARY KEY (`ftid`)']);

        $_field_type_key = db::insert('_cms_field')
            ->add_value('field_name', 'ftid')
            ->add_value('title', 'Field ID')
            ->add_value('type', 'int')
            ->add_value('mid', $_field_insert_id)
            ->add_value('position', 0)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'parent_ftid')
            ->add_value('title', 'Parent field ID')
            ->add_value('type', 'link')
            ->add_value('mid', $_field_type_insert_id)
            ->add_value('link_module', $_field_type_insert_id)
            ->add_value('link_field', $_field_type_key)
            ->add_value('position', 1)
            ->execute();
        db::insert('_cms_field')
            ->add_value('field_name', 'title')
            ->add_value('title', 'Title')
            ->add_value('type', 'string')
            ->add_value('mid', $_field_type_insert_id)
            ->add_value('position', 2)
            ->execute();

        $field_types = [
            'int',
            'boolean',
            'date',
            'datetime',
            'email',
            'file',
            'float',
            'link',
            'multi_select',
            'password',
            'radio',
            'textarea',
            'string',
            'time',
            'button',
            'file',
        ];
        foreach ($field_types as $field) {
            $field_type = new field_type();
            $field_type->title = $field;
            $field_type->do_save();
        }

    }

    public function build_settings() {
        db::create_table('_cms_setting', [
                'sid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
                'parent_sid' => 'SMALLINT NOT NULL',
                'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
                'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
                'position' => 'SMALLINT NOT NULL',
                'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
                'ts' => 'TIMESTAMP NOT NULL',
                'ftid' => 'SMALLINT NOT NULL',
                'title' => 'varchar(255) NOT NULL',
                'key' => 'varchar(255) NOT NULL',
                'value' => 'text NOT NULL DEFAULT \'\''],
            ['PRIMARY KEY (`sid`)']
        );
        db::insert('_cms_setting')
            ->add_value('ftid', '13')
            ->add_value('title', 'CMS Version')
            ->add_value('key', 'cms_version')
            ->add_value('value', 0)
            ->execute();
    }

    public function manage() {
        if (!db::table_exists('_cms_module')) {
            $this->build();
        }
        if (!db::table_exists('_cms_setting')) {
            $this->build_settings();
        }
        $var = (int) get::setting('cms_version');
        if ($var < self::$current_version) {
            for ($i = (int) $var + 1; $i <= self::$current_version; $i++) {
                $this->run_patch($i);
            }
        }
    }

    public function patch_v1() {
        $this->_cms_module_fields->reset_iterator();
        $key = '';
        $row = $this->_cms_module_fields->next($key);
        $previous_key = $key;
        if (!db::column_exists('_cms_module', $key)) {
            db::add_column('_cms_module', $key, $row, ' FIRST');
        } else {
            db::move_column('_cms_module', $key, $row, ' FIRST');
        }
        while ($row = $this->_cms_module_fields->next($key)) {
            if (!db::column_exists('_cms_module', $key)) {
                db::add_column('_cms_module', $key, $row, ' AFTER `' . $previous_key . '`');
            } else {
                db::move_column('_cms_module', $key, $row, ' AFTER `' . $previous_key . '`');
            }
            $previous_key = $key;
        }

        $always_there_fields = [
            'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
            'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
            'position' => 'SMALLINT NOT NULL',
            'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
            'ts' => 'TIMESTAMP NOT NULL',
        ];

        $modules = \module\cms\object\_cms_module::get_all(['table_name', 'primary_key']);
        while ($table = $modules->next()) {
            $fields = new collection(array_merge(
                [
                    $table->primary_key => 'SMALLINT NOT NULL AUTO_INCREMENT',
                    'parent_' . $table->primary_key => 'SMALLINT NOT NULL',
                ],
                $always_there_fields
            ));
            $fields->reset_iterator();
            $key = '';
            $row = $fields->next($key);
            $previous_key = $key;
            if (!db::column_exists($table->table_name, $key)) {
                db::add_column($table->table_name, $key, $row, ' FIRST');
            } else {
                db::move_column($table->table_name, $key, $row, ' FIRST');
            }
            while ($row = $fields->next($key)) {
                if (!db::column_exists($table->table_name, $key)) {
                    db::add_column($table->table_name, $key, $row, ' AFTER `' . $previous_key . '`');
                } else {
                    db::move_column($table->table_name, $key, $row, ' AFTER `' . $previous_key . '`');
                }
                $previous_key = $key;
            }
        }
    }

    public function run_patch($patch) {
        $function = 'patch_v' . $patch;
        $this->$function();
        db::update('_cms_setting')->add_value('value', $patch)->filter(['`key`="cms_version"'])->execute();
    }
}
 