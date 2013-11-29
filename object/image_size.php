<?php
namespace core\object;

use classes\table;
use module\cms\object\_cms_field;
use module\cms\object\_cms_module;
use traits\table_trait;

class image_size extends table {

    use table_trait;



    public $isid;
    public $title;
    public $reference;
    public $min_width;
    public $min_height;
    public $max_width;
    public $max_height;
    public $ifid;
    public $icid;
    public $fid;
    public $default;

    public function get_format() {
        switch ($this->ifid) {
            case 1 :
                return 'png';
            case 2 :
                return 'jpg';
            case 3 :
                return 'gif';
        }
        return '';
    }

    public function reprocess() {
        $_field = new _cms_field([], $this->fid);
        $_module = new _cms_module([], $_field->mid);
        $base_path = root . '/uploads/' . $_module->table_name . '/' . $_field->fid . '/';
        /** @var table $class */
        $class = $_module->get_class_name();
        $objects = $class::get_all([]);
        $extensions = ['png', 'jpg', 'gif'];
        $objects->iterate(function (table $object) use ($base_path, $extensions) {
                foreach ($extensions as $extension) {
                    $path = $base_path . $object->get_primary_key() . '.' . $extension;
                    if (file_exists($path)) {
                        echo 'Reprocessing image ' . $object->get_primary_key() . '...';
                        $object->do_process_image($path, $this);
                        echo ' done<br/>';
                    }
                }
            }
        );
    }
}
 