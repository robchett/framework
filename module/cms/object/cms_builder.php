<?php
namespace core\module\cms\object;

use classes\db;
use classes\get;
use classes\table;
use module\cms\object\_cms_field;
use module\cms\object\_cms_group;
use module\cms\object\_cms_module;
use module\cms\object\_cms_user;
use module\cms\object\field_type;

class cms_builder {

    public static $current_version = 4;

    protected static function get_structure($database) {
        $file = file_get_contents(core_dir . '/db/structures/' . $database . '.json');
        $file = preg_replace_callback('#//@include \'(.*?)\'#', function ($matches) {
                $sub_file = core_dir . '/db/structures/' . $matches[1];
                return file_get_contents($sub_file);
            }, $file
        );
        return json_decode($file);
    }

    public function create_module_base(&$structure) {
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

    public function create_field_base($structure, $key, &$field) {
        $field->id = db::insert('_cms_field')
            ->add_value('field_name', $key)
            ->add_value('title', isset($field->title) ? $field->title : ucwords(str_replace('_', ' ', $key)))
            ->add_value('type', $field->type)
            ->add_value('mid', $structure->mid)
            ->add_value('position', 1)
            ->execute();
    }

    public function create_field_base_link($structure, $key, $field) {
        $link_module = db::select('_cms_module')->retrieve(['mid'])->filter_field('table_name', $field->module)->execute()->fetchObject();
        $link_field = db::select('_cms_field')->retrieve(['fid'])->filter(['mid=:mid', 'field_name=:field_name'],['mid'=>$link_module->mid, 'field_name' => $key])->execute()->fetchObject();
        $field->id = db::update('_cms_field')
            ->add_value('link_module', $link_module->mid)
            ->add_value('link_field', $link_field->fid)
            ->filter_field('fid', $field->fid)
            ->execute();
    }

    public function build() {
        db::create_table_json($this->get_structure('_cms_group'));
        db::create_table_json($this->get_structure('_cms_module'));
        db::create_table_json($this->get_structure('_cms_field'));
        db::create_table_json($this->get_structure('field_type'));

        $modules_json[] = $this->get_structure('_cms_field');
        $modules_json[] = $this->get_structure('_cms_module');
        $modules_json[] = $this->get_structure('_cms_group');
        $modules_json[] = $this->get_structure('field_type');

        // Create base _cms_modules
        foreach ($modules_json as $structure) {
            $this->create_module_base($structure);
        }
        // Create basic fields
        foreach ($modules_json as &$structure) {
            foreach ($structure->fieldset as $key => &$field) {
                $this->create_field_base($structure, $key, $field);
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

    public static function create_from_structure($database) {
        $json = self::get_structure($database);
        db::create_table_json($json);
        $_group_id = _cms_group::create($json->group)->get_primary_key();
        $module_id = _cms_module::create($json->title, $json->tablename, $json->primary_key, $_group_id, $json->namespace)->get_primary_key();
        foreach ($json->fieldset as $field => $structure) {
            if (!isset($structure->is_default) || !$structure->is_default) {
                _cms_field::create($field, $structure, $module_id);
            }
        }
        foreach ($json->fieldset as $field => $structure) {
            if (isset($structure->module) && isset($structure->field) && $structure->module == $json->tablename) {
                $cms_field = new _cms_field();
                $cms_field->do_retrieve([], ['where_equals' => ['mid' => $module_id, 'field_name' => $field]]);
                if (!$cms_field->link_field) {
                    $link_cms_field = new _cms_field();
                    $link_cms_field->do_retrieve([], ['where_equals' => ['mid' => $module_id, 'field_name' => $structure->field]]);
                    if ($link_cms_field->get_primary_key()) {
                        $cms_field->link_field = $link_cms_field->get_primary_key();
                    }
                }
            }
        }
    }

    /**
     * Adds always there fields and reorders them and set the correct types
     * */
    public function patch_v1() {
        $modules = db::select('_cms_module')->retrieve(['table_name', 'mid'])->execute();
        while ($module = db::fetch($modules)) {
            $json = self::get_structure($module->table_name);
            if ($json) {
                $fields = self::get_structure($module->table_name)->fieldset;
                $previous_key = false;
                foreach ($fields as $key => $row) {
                    if (!db::column_exists($module->table_name, $key)) {
                        db::add_column($module->table_name, $key, db::get_column_type_json($row), $previous_key ? ' AFTER `' . $previous_key . '`' : ' FIRST');
                    } else {
                        db::move_column($module->table_name, $key, db::get_column_type_json($row), $previous_key ? ' AFTER `' . $previous_key . '`' : ' FIRST');
                    }
                    if (!isset($row->is_default) || !$row->is_default) {
                        $_field = db::select('_cms_field')->retrieve(['fid'])->filter(['mid=:mid', 'field_name=:key'], ['mid' => $module->mid, 'key' => $key])->execute();
                        if (!$_field->rowCount()) {
                            $this->create_field_base($module, $key, $row);
                        }
                    }
                    $previous_key = $key;
                }
            }
        }
        table::reload_table_definitions();
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

    /** Add
     * ---CMS User
     * */
    public function patch_v4() {
        if (!db::table_exists('_cms_user')) {
            self::create_from_structure('_cms_user');
            table::reload_table_definitions();
            $cms_user = new _cms_user();
            $cms_user->title = ***REMOVED***;
            $cms_user->password = '***REMOVED***';
            $cms_user->do_save();
        }
    }

    public function run_patch($patch) {
        $function = 'patch_v' . $patch;
        $this->$function();
        db::update('_cms_setting')->add_value('value', $patch)->filter(['`key`="cms_version"'])->execute();
    }
}
 