<?php
namespace core\module\cms\object;

use classes\db;
use classes\get;
use classes\glob;
use classes\table;
use core\db\stub\field;
use core\db\stub\module;
use module\cms\object\_cms_field;
use module\cms\object\_cms_group;
use module\cms\object\_cms_module;
use module\cms\object\_cms_user;
use module\cms\object\_cms_user_level;
use module\cms\object\field_type;

class cms_builder {

    public static $current_version = 4;

    public function create_module_base(module &$structure) {
        $gid = db::select('_cms_group')
            ->retrieve(['gid'])->filter(['title=:title'], ['title' => $structure->group])
            ->execute();
        if (!$gid->rowCount()) {
            $_group_id = db::insert('_cms_group')
                ->add_value('title', $structure->group)
                ->execute();
        } else {
            $_group_id = $gid->fetchObject()->gid;
        }
        $structure->mid = db::insert('_cms_module')
            ->add_value('gid', $_group_id)
            ->add_value('primary_key', $structure->primary_key)
            ->add_value('title', $structure->title)
            ->add_value('table_name', $structure->tablename)
            ->add_value('namespace', isset($structure->namespace) ? $structure->namespace : '')
            ->execute();
    }

    public function create_field_base($structure, $key, field &$field, $cnt = 0) {
        $field->id = db::insert('_cms_field')
            ->add_value('field_name', $key)
            ->add_value('title', $field->title ? $field->title : ucwords(str_replace('_', ' ', $key)))
            ->add_value('type', $field->type)
            ->add_value('mid', $structure->mid)
            ->add_value('position', $cnt)
            ->add_value('list', $field->list)
            ->add_value('editable', $field->filter)
            ->add_value('required', $field->required)
            ->add_value('editable', $field->editable)
            ->execute();
    }

    public function create_field_base_link(module $structure, $key, $field) {
        $_module = db::select('_cms_module')->retrieve(['mid'])->filter_field('table_name', $structure->tablename)->execute()->fetchObject();
        $_field = db::select('_cms_field')->retrieve(['fid'])->filter(['`mid`=:mid', '`field_name`=:field_name'], ['mid' => $_module->mid, 'field_name' => $key])->execute()->fetchObject();

        $link_module = db::select('_cms_module')->retrieve(['mid'])->filter_field('table_name', $field->module)->execute()->fetchObject();
        $link_field = db::select('_cms_field')->retrieve(['fid'])->filter(['`mid`=:mid', '`field_name`=:field_name'], ['mid' => $link_module->mid, 'field_name' => $field->field])->execute()->fetchObject();
        $field->id = db::update('_cms_field')
            ->add_value('link_module', $link_module->mid)
            ->add_value('link_field', $link_field->fid)
            ->filter_field('fid', $_field->fid)
            ->execute();
    }

    public function build() {
        db::create_table_json(module::create('_cms_group'));
        db::create_table_json(module::create('_cms_module'));
        db::create_table_json(module::create('_cms_field'));
        db::create_table_json(module::create('field_type'));

        /** @var module[] $modules_json */
        $modules_json[] = module::create('_cms_field');
        $modules_json[] = module::create('_cms_module');
        $modules_json[] = module::create('_cms_group');
        $modules_json[] = module::create('field_type');

        // Create base _cms_modules
        foreach ($modules_json as $structure) {
            $this->create_module_base($structure);
        }
        // Create basic fields
        foreach ($modules_json as &$structure) {
            $cnt = 0;
            foreach ($structure->fieldset as $key => &$field) {
                if (!$field->is_default) {
                    $this->create_field_base($structure, $key, $field, $cnt++);
                }
            }
        }
        // Reset pointers
        unset($structure);
        unset($field);
        // Create joins
        foreach ($modules_json as &$structure) {
            foreach ($structure->fieldset as $key => &$field) {
                if ($field->type == 'link') {
                    $this->create_field_base_link($structure, $key, $field);
                }
            }
        }
        // Add base field types
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
        table::reload_table_definitions();
    }

    public function build_settings() {
        self::create_from_structure('_cms_setting');
        db::insert('_cms_setting')
            ->add_value('type', 'string')
            ->add_value('title', 'CMS Version')
            ->add_value('key', 'cms_version')
            ->add_value('value', 0)
            ->execute();
    }

    public function manage() {
        set_time_limit(0);
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

    public static function modify_link_field($source_field, $destination_module, $destination_field) {
        if (!$source_field->link_module) {
            $link_cms_module = new _cms_module();
            $link_cms_module->do_retrieve([], ['where_equals' => ['table_name' => $destination_module]]);
            if ($link_cms_module->get_primary_key()) {
                $source_field->link_module = $link_cms_module->get_primary_key();
            } else {
                try {
                    self::create_from_structure($destination_module);
                } catch (\Exception $e) {
                    die('Missing dependency: ' . $destination_module);
                }
            }
        }
        if (!$source_field->link_field) {
            $link_cms_field = new _cms_field();
            $link_cms_field->do_retrieve([], ['where_equals' => ['mid' => $source_field->link_module, 'field_name' => $destination_field]]);
            if ($link_cms_field->get_primary_key()) {
                $source_field->link_field = $link_cms_field->get_primary_key();
            }
        }
        $source_field->do_save();
    }

    public static function create_from_structure($database) {
        $json = module::create($database);
        db::create_table_json($json);
        foreach ($json->dependencies as $dependant) {
            if (!db::table_exists($dependant)) {
                static::create_from_structure($dependant);
            }
        }
        $_group_id = _cms_group::create($json->group)->get_primary_key();
        $module_id = _cms_module::create($json->title, $json->tablename, $json->primary_key, $_group_id, $json->namespace)->get_primary_key();
        $cnt = 0;
        foreach ($json->fieldset as $field => $structure) {
            if (!$structure->is_default) {
                _cms_field::create($field, $structure, $module_id, $cnt++);
            }
        }
        foreach ($json->fieldset as $field => $structure) {
            if ($structure->module && $structure->field) {
                $cms_field = new _cms_field();
                $cms_field->do_retrieve([], ['where' => '(mid = :mid OR mid = 0) AND field_name = :field_name', 'parameters' => ['mid' => $module_id, 'field_name' => $field]]);

                if ($structure->type == 'mlink') {
                    if (!db::table_exists($database . '_link_' . $structure->module)) {
                        db::create_table_join($database, $structure->module);
                    }
                }
                static::modify_link_field($cms_field, $structure->module, $structure->field);
            }
        }
    }

    /**
     * Adds always there fields and reorders them and set the correct types
     * */
    public function patch_v1() {
        $glob = new glob(core_dir . '/db/structures/*.json');
        $glob->iterate(function ($file) {
                $module = pathinfo($file, PATHINFO_FILENAME);
                $this->set_default_fields($module);
            }
        );
        table::reload_table_definitions();
    }

    protected function set_default_fields($module) {
        try {
            $json = module::create($module);
            $_module = $_field = db::select('_cms_module')->retrieve(['mid'])->filter(['table_name=:table_name'], ['table_name' => $json->tablename])->execute()->fetchObject();
            if ($json && $_module) {
                $fields = $json->fieldset;
                $previous_key = false;
                foreach ($fields as $key => $row) {
                    $format = db::get_column_type_json($row);
                    if ($format) {
                        if (!db::column_exists($json->tablename, $key)) {
                            db::add_column($json->tablename, $key, $format, $previous_key ? ' AFTER `' . $previous_key . '`' : ' FIRST');
                        } else {
                            db::move_column($json->tablename, $key, $format, $previous_key ? ' AFTER `' . $previous_key . '`' : ' FIRST');
                        }
                    }
                    if (!$row->is_default) {
                        $_field = db::select('_cms_field')->retrieve(['fid'])->filter(['mid=:mid', 'field_name=:key'], ['mid' => $_module->mid, 'key' => $key])->execute();
                        if (!$_field->rowCount()) {
                            $this->create_field_base($_module, $key, $row);
                        }
                    }
                    $previous_key = $key;
                }
            }
        } catch (\Exception $e) {}
    }

    /** Add
     * ---Page
     * */
    public function patch_v2() {
        if (!db::table_exists('page')) {
            self::create_from_structure('page');
        }
    }

    /** Add
     * ---Image Format
     * ---Image Crop
     * ---Image Size
     * */
    public function patch_v3() {
        if (!db::table_exists('image_format')) {
            self::create_from_structure('image_crop');
            self::create_from_structure('image_format');
            self::create_from_structure('image_size');
            db::insert('image_format')
                ->add_value('title', 'PNG')
                ->execute();
            db::insert('image_format')
                ->add_value('title', 'JPG')
                ->execute();
            db::insert('image_format')
                ->add_value('title', 'GIF')
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
        }
    }

    /** Add user level management
     * ---CMS User
     * ---CMS User level
     * ---CMS User --> CMS Module
     * */
    public function patch_v4() {
        if (!db::table_exists('_cms_user')) {
            self::create_from_structure('_cms_user');
            table::reload_table_definitions();

            $user_level = new _cms_user_level();
            $user_level->title = 'User';
            $user_level->do_save();

            $user_level->ulid = 0;
            $user_level->title = 'Webmaster';
            $user_level->do_save();

            $user_level->ulid = 0;
            $user_level->title = 'Admin';
            $user_level->do_save();

            $cms_user = new _cms_user();
            $cms_user->title = ***REMOVED***;
            $cms_user->password = '***REMOVED***';
            $cms_user->ulid = 3;
            $cms_user->do_save();

            $_module = new _cms_module();
            $_module->do_retrieve([], ['where_equals' => ['table_name' => '_cms_module']]);

            $_field = new _cms_field();
            $_field->do_retrieve([], ['where_equals' => ['mid' => $_module->get_primary_key(), 'field_name' => 'user_level_view']]);
            static::modify_link_field($_field, '_cms_user_level', 'title');

            $_field = new _cms_field();
            $_field->do_retrieve([], ['where_equals' => ['mid' => $_module->get_primary_key(), 'field_name' => 'user_level_add']]);
            static::modify_link_field($_field, '_cms_user_level', 'title');

            $_field = new _cms_field();
            $_field->do_retrieve([], ['where_equals' => ['mid' => $_module->get_primary_key(), 'field_name' => 'user_level_delete']]);
            static::modify_link_field($_field, '_cms_user_level', 'title');
        }
    }

    public function run_patch($patch) {
        $function = 'patch_v' . $patch;
        $this->$function();
        db::update('_cms_setting')->add_value('value', $patch)->filter(['`key`="cms_version"'])->execute();
    }
}