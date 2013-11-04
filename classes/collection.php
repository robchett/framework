<?php
namespace core\classes;

use classes\get as _get;
use form\field_link;
use form\field_mlink;

abstract class
collection extends \ArrayObject
{

    private $first_index = 0;
    /** @var  \arrayIterator */
    public $iterator;


    public function __construct($input = [], $flags = 0, $iterator_class = "\\classes\\collection_iterator")
    {
        parent::__construct($input, $flags, $iterator_class);
    }

    public function first()
    {
        return $this[0];
    }

    public function first_index()
    {
        return $this->first_index;
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function next(&$key = '')
    {
        if ($this->iterator->valid()) {
            $key = $this->iterator->key();
            $value = $this->iterator->current();
        } else {
            return false;
        }
        $this->iterator->next();
        return $value;
    }

    public function push($object)
    {
        $this[] = $object;
    }

    public function getIterator()
    {
        if (!isset($this->iterator)) {
            $this->iterator = parent::getIterator();
        }
        return $this->iterator;
    }

    public function setIterator(\Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     *
     */
    public function reset_iterator()
    {
        $this->getIterator()->rewind();
    }

    /**
     * @param $function
     * @param int $cnt
     */
    public function iterate($function, &$cnt = 0)
    {
        foreach ($this as $object) {
            call_user_func_array($function, [$object, $cnt]);
            $cnt++;
        }
    }

    public function iterate_return($function, &$cnt = 0)
    {
        $res = '';
        foreach ($this as $object) {
            $res .= call_user_func_array($function, [$object, $cnt]);
            $cnt++;
        }
        return $res;
    }

    public function last()
    {
        return $this[$this->count() - 1];
    }

    public function remove_first($int = 1)
    {
        parent::__construct($this->subset($int));
    }

    public function remove_last($int = 0)
    {
        if ($int) {
            for ($i = 0; $i < $int; $i++)
                $this->remove_last();
        } else {
            $this->offsetUnset($this->count() - 1);
        }
    }

    /**
     * @param int $start
     * @param int $end
     * @return \LimitIterator
     */
    public function subset($start = 0, $end = null)
    {
        $count = ($end ? : $this->count()) - $start;
        $res = new \LimitIterator($this->getIterator(), $start, $count);
        return $res;
    }

    public function filter_unique($field)
    {
        $values = [];
        $objects = [];
        if ($field instanceof field_mlink) {
            $this->iterate(function ($object) use (&$values, &$objects, $field) {
                    foreach ($object->{$field->field_name} as $key => $link) {
                        if (!isset($values[$link])) {
                            $values[$link] = 1;
                            $objects[$link] = $object->{$field->field_name . '_elements'}[$key];
                        } else {
                            $values[$link]++;
                        }
                    }
                }
            );
        } else if ($field instanceof field_link) {
            $field_name = get::__class_name($field->get_link_module());
            $this->iterate(function ($object) use (&$values, &$objects, $field, $field_name) {
                    $key = $object->$field_name->get_primary_key();
                    if (!isset($values[$key])) {
                        $values[$key] = 1;
                        $objects[$key] = $object->$field_name;
                    } else {
                        $values[$key]++;
                    }
                }
            );
        } else {
            $this->iterate(function ($object) use (&$values, $field) {
                    if (!isset($values[$object->{$field->field_name}])) {
                        $values[$object->{$field->field_name}] = 1;
                    } else {
                        $values[$object->{$field->field_name}]++;
                    }
                }
            );
        }
        $return = [];
        if ($field instanceof field_link) {
            foreach ($values as $key => $value) {
                $object = $objects[$key];
                $return[$key] = $object->get_title() . ' (' . $value . ')';
            }
        } else {
            foreach ($values as $key => $value) {
                $return[$key] = $key . '(' . $value . ')';
            }
        }
        return $return;
    }

    public function get_id()
    {
        return str_replace('\\', '_', _get::__class_name($this));
    }
}
 