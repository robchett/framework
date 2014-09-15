<?php
namespace core\form;

use classes\collection;
use classes\db;
use classes\table;
use classes\table_array;
use form\field_file;
use html\node;
use object\image_size;

class field_image extends \form\field_file
{

    protected static $image_sizes;

    public function get_image_edit_link()
    {
        $count = db::count('image_size', 'isid')->filter_field('fid', $this->fid)->execute();
        return node::create('a', array('href' => '/cms/module/' . image_size::get_module_id() . '/!/fid/' . $this->fid), (int)$count . ' image sizes');
    }

    public function get_cms_list_wrapper($value, $object_class, $id)
    {
        return $this->get_default_image($id);
    }

    public function get_default_image($id, $options = [])
    {
        /** @var image_size $image_size */
        $image_size = $this->get_image_sizes($options);
        if ($image_size->count()) {
            $image_size = $image_size[0];
            $file = '/uploads/' . $this->parent_form->get_table_class() . '/' . $this->fid . '/' . $id . '_' . $image_size->reference . '.' . $image_size->get_format();
            if (file_exists(root . $file)) {
                return node::create('img', ['src' => $file]);
            }
        }
        return node::create('span', [], 'No Image');
    }

    public function get_image_sizes()
    {
        if (!isset(self::$image_sizes)) {
            self::$image_sizes = image_size::get_all([]);
        }
        $image_sizes = new table_array();
        foreach (self::$image_sizes as $image_size) {
            if ($image_size->fid == $this->fid) {
                $image_sizes[] = $image_size;
            }
        }
        return $image_sizes;
    }

    /**
     * @param string $size
     * @return image_size|bool
     */
    public function get_image_size($size)
    {
        foreach ($this->get_image_sizes() as $image_size) {
            if ($image_size->size == $size) {
                return $image_size;
            }
        }
        return false;;
    }

    public function get_html()
    {
        $html = '';
        $parent = $this->parent_form;
        if ($parent instanceof table) {
            $fields = $parent->get_fields();
        } else {
            $fields = $parent->fields;
        }
        $primary = reset($fields)->field_name;
        $html .= $this->get_default_image($this->parent_form->$primary, ['where_equals' => ['default' => true]]) . '<input name="' . $this->field_name . '" id="' . $this->field_name . '"  type="file"/>' . "\n";
        if (isset($this->parent_form->{$this->field_name})) {
            $path = pathinfo($this->parent_form->{$this->field_name} && $this->parent_form->{$this->field_name});
            $html .= '<p><a href="' . $this->parent_form->{$this->field_name} . '" target="_blank">' . $path['filename'] . '</a></p>';
        }
        return $html;

    }
}
 