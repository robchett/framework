<?php
namespace core\db\stub;

class field {

    public $type = 'text';
    public $title;
    public $length = false;
    public $default = false;
    public $module = 0;
    public $field = 0;
    public $is_default = false;
    public $list = true;
    public $filter = true;
    public $required = true;
    public $editable = true;
    public $autoincrement = false;

    public static function create($structure) {
        $field = new self;
        foreach ($structure as $attribute => $value) {
            $field->$attribute = $value;
        }
        return $field;
    }
}