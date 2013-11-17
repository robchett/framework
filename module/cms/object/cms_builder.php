<?php
namespace core\module\cms\object;

use classes\collection;
use classes\db;
use classes\get;
use module\cms\object\field_type;

class cms_builder {

    public static $current_version = 3;

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

    public function patch_v2() {
        if (!db::table_exists('page')) {
            db::create_table('page', [
                    'pid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
                    'parent_pid' => 'SMALLINT NOT NULL',
                    'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
                    'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
                    'position' => 'SMALLINT NOT NULL',
                    'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
                    'ts' => 'TIMESTAMP NOT NULL',
                    'title' => 'varchar(255) NOT NULL',
                    'body' => 'TEXT NOT NULL',
                    'fn' => 'varchar(255) NOT NULL',
                ], ['PRIMARY KEY (`pid`)']
            );
            $_group_id = db::insert('_cms_group')
                ->add_value('title', 'Page')
                ->execute();
            $_module_insert_id = db::insert('_cms_module')
                ->add_value('gid', $_group_id)
                ->add_value('primary_key', 'pid')
                ->add_value('title', 'Pages')
                ->add_value('table_name', 'page')
                ->add_value('namespace', 'pages')
                ->execute();
            $_module_table_key = db::insert('_cms_field')
                ->add_value('field_name', 'pid')
                ->add_value('title', 'Page ID')
                ->add_value('type', 'int')
                ->add_value('mid', $_module_insert_id)
                ->add_value('position', 0)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'parent_pid')
                ->add_value('title', 'Parent page ID')
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
                ->add_value('field_name', 'body')
                ->add_value('title', 'Content')
                ->add_value('type', 'html')
                ->add_value('mid', $_module_insert_id)
                ->add_value('position', 3)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'fn')
                ->add_value('title', 'Filename')
                ->add_value('type', 'fn')
                ->add_value('mid', $_module_insert_id)
                ->add_value('position', 4)
                ->execute();
        }
    }

    public function patch_v3() {

        /** Add
         * ---Image Format
         * ---Image Crop
         * ---Image Size
         * */
        if (!db::table_exists('image_format')) {
            $_group_id = db::insert('_cms_group')
                ->add_value('title', 'Images')
                ->execute();
            db::create_table('image_format', [
                    'ifid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
                    'parent_ifid' => 'SMALLINT NOT NULL',
                    'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
                    'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
                    'position' => 'SMALLINT NOT NULL',
                    'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
                    'ts' => 'TIMESTAMP NOT NULL',
                    'title' => 'varchar(255) NOT NULL'
                ], ['PRIMARY KEY (`ifid`)']
            );
            $_module_insert_id = db::insert('_cms_module')
                ->add_value('gid', $_group_id)
                ->add_value('primary_key', 'ifid')
                ->add_value('title', 'Image Format')
                ->add_value('table_name', 'image_format')
                ->add_value('namespace', '')
                ->execute();
            $_field_insert_id = db::insert('_cms_field')
                ->add_value('field_name', 'ifid')
                ->add_value('title', 'Image Format ID')
                ->add_value('type', 'int')
                ->add_value('mid', $_module_insert_id)
                ->add_value('position', 0)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'parent_ifid')
                ->add_value('title', 'Parent Image Format')
                ->add_value('type', 'int')
                ->add_value('mid', $_module_insert_id)
                ->add_value('link_module', $_module_insert_id)
                ->add_value('link_field', $_field_insert_id)
                ->add_value('position', 1)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'title')
                ->add_value('title', 'Extension')
                ->add_value('type', 'string')
                ->add_value('mid', $_module_insert_id)
                ->add_value('position', 1)
                ->execute();
            db::insert('image_format')
                ->add_value('title', 'PNG')
                ->execute();
            db::insert('image_format')
                ->add_value('title', 'JPG')
                ->execute();
            db::insert('image_format')
                ->add_value('title', 'GIF')
                ->execute();

            db::create_table('image_crop', [
                    'icid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
                    'parent_icid' => 'SMALLINT NOT NULL',
                    'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
                    'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
                    'position' => 'SMALLINT NOT NULL',
                    'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
                    'ts' => 'TIMESTAMP NOT NULL',
                    'title' => 'varchar(255) NOT NULL'
                ], ['PRIMARY KEY (`icid`)']
            );
            $_cropmodule_insert_id = db::insert('_cms_module')
                ->add_value('gid', $_group_id)
                ->add_value('primary_key', 'icid')
                ->add_value('title', 'Image Crop')
                ->add_value('table_name', 'image_crop')
                ->add_value('namespace', '')
                ->execute();
            $_cropfield_insert_id = db::insert('_cms_field')
                ->add_value('field_name', 'icid')
                ->add_value('title', 'Image Crop ID')
                ->add_value('type', 'int')
                ->add_value('mid', $_cropmodule_insert_id)
                ->add_value('position', 0)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'parent_icid')
                ->add_value('title', 'Parent Image Crop')
                ->add_value('type', 'int')
                ->add_value('mid', $_cropmodule_insert_id)
                ->add_value('link_module', $_cropmodule_insert_id)
                ->add_value('link_field', $_cropfield_insert_id)
                ->add_value('position', 1)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'title')
                ->add_value('title', 'Method')
                ->add_value('type', 'string')
                ->add_value('mid', $_cropmodule_insert_id)
                ->add_value('position', 1)
                ->execute();
            db::insert('image_crop')
                ->add_value('title', 'Crop')
                ->execute();
            db::insert('image_crop')
                ->add_value('title', 'Scale Within Bounds')
                ->execute();
            db::insert('image_crop')
                ->add_value('title', 'Scale Within Height')
                ->execute();
            db::insert('image_crop')
                ->add_value('title', 'Scale Within Width')
                ->execute();

            db::create_table('image_size', [
                    'isid' => 'SMALLINT NOT NULL AUTO_INCREMENT',
                    'parent_isid' => 'SMALLINT NOT NULL',
                    'live' => 'tinyint(1) NOT NULL DEFAULT \'1\'',
                    'deleted' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
                    'position' => 'SMALLINT NOT NULL',
                    'created' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP()',
                    'ts' => 'TIMESTAMP NOT NULL',
                    'title' => 'varchar(255) NOT NULL',
                    'reference' => 'varchar(10) NOT NULL',
                    'min_width' => 'SMALLINT NOT NULL',
                    'min_height' => 'SMALLINT NOT NULL',
                    'max_width' => 'SMALLINT NOT NULL',
                    'max_heights' => 'SMALLINT NOT NULL',
                    'icid' => 'tinyint(1) NOT NULL',
                    'ifid' => 'SMALLINT NOT NULL',
                    'fid' => 'SMALLINT NOT NULL',
                    'default' => 'tinyint(1) NOT NULL',
                ], ['PRIMARY KEY (`isid`)']
            );
            $_sizes_module_insert_id = db::insert('_cms_module')
                ->add_value('gid', $_group_id)
                ->add_value('primary_key', 'isid')
                ->add_value('title', 'Image Size')
                ->add_value('table_name', 'image_size')
                ->execute();
            $__sizes_field_insert_key = db::insert('_cms_field')
                ->add_value('field_name', 'isid')
                ->add_value('title', 'Image Size ID')
                ->add_value('type', 'int')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 0)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'parent_isid')
                ->add_value('title', 'Parent Image Size')
                ->add_value('type', 'link')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('link_module', $_sizes_module_insert_id)
                ->add_value('link_field', $__sizes_field_insert_key)
                ->add_value('position', 1)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'title')
                ->add_value('title', 'Title')
                ->add_value('type', 'string')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 2)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'reference')
                ->add_value('title', 'Reference')
                ->add_value('type', 'string')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 3)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'min_width')
                ->add_value('title', 'Minimum Width')
                ->add_value('type', 'int')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 4)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'min_height')
                ->add_value('title', 'Minimum Height')
                ->add_value('type', 'int')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 5)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'max_width')
                ->add_value('title', 'Maximum Width')
                ->add_value('type', 'int')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 6)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'max_height')
                ->add_value('title', 'Maximum Height')
                ->add_value('type', 'int')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 7)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'ifid')
                ->add_value('title', 'Format Type')
                ->add_value('type', 'link')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('link_module', $_module_insert_id)
                ->add_value('link_field', (int)$_field_insert_id + 2)
                ->add_value('position', 8)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'icid')
                ->add_value('title', 'Crop Type')
                ->add_value('type', 'link')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('link_module', $_cropmodule_insert_id)
                ->add_value('link_field', (int)$_cropfield_insert_id + 2)
                ->add_value('position', 9)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'fid')
                ->add_value('title', 'Field')
                ->add_value('type', 'int')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 9)
                ->execute();
            db::insert('_cms_field')
                ->add_value('field_name', 'default')
                ->add_value('title', 'Is Default')
                ->add_value('type', 'boolean')
                ->add_value('mid', $_sizes_module_insert_id)
                ->add_value('position', 10)
                ->execute();
        }
    }

    public function run_patch($patch) {
        $function = 'patch_v' . $patch;
        $this->$function();
        db::update('_cms_setting')->add_value('value', $patch)->filter(['`key`="cms_version"'])->execute();
    }
}
 