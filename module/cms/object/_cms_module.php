<?php
namespace core\module\cms\object;

use classes\jquery;
use core\classes\table;
use module\cms\form\cms_change_group_form;
use traits\table_trait;

abstract class _cms_module extends table {

    use table_trait;

    public $namespace;
    public $primary_key;
    public $table_key = 'mid';
    public static $module_id = 20;
    public $table_name;
    public $title;
    public $gid;
    public $mid;


    public function get_class_name() {
        if ($this->namespace) {
            return '\\module\\' . $this->namespace . '\\object\\' . $this->table_name;
        } else {
            return '\\object\\' . $this->table_name;
        }
    }

    /** @return table */
    public function get_class() {
        $class = $this->get_class_name();
        return new $class();
    }

    /**
     *
     */
    public function get_cms_change_group() {
        $form = new cms_change_group_form();
        $form->mid = $_REQUEST['mid'];

        jquery::colorbox(array('html' => $form->get_html()->get()));
    }
}
