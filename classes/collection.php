<?php
namespace core\classes;

use classes\filter_field;
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

    public function unshift($int = 1)
    {
        $sub_array = [];
        foreach ($this as $key => $index) {
            if ($key >= $int) {
                $sub_array[] = $index;
            }
        }
        $this->exchangeArray($sub_array);
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
        $res->count = $count;
        return $res;
    }

    public function filter_unique(filter_field $field)
    {
        $values = [];
        $objects = [];
        if ($field->inner_field() instanceof field_mlink) {
            $this->iterate(function ($object) use (&$values, &$objects, $field) {
                    foreach ($object->{$field->field_name} as $key => $link) {
                        if (!isset($values[$link])) {
                            $values[$link]['count'] = 1;
                            $values[$link]['title'] = $object->{$field->field_name . '_elements'}[$key]->get_title();
                        } else {
                            $values[$link]['count']++;
                        }
                    }
                }
            );
        } else if ($field->inner_field() instanceof field_link) {
            $field_name = get::__class_name($field->inner_field()->get_link_module());
            $this->iterate(function ($object) use (&$values, &$objects, $field, $field_name) {
                    $key = $object->$field_name->get_primary_key();
                    if (!isset($values[$key])) {
                        $values[$key]['count'] = 1;
                        $values[$key]['title'] = $object->$field_name->get_title();
                    } else {
                        $values[$key]['count']++;
                    }
                }
            );
        } else {
            $this->iterate(function ($object) use (&$values, $field) {
                    if (!isset($values[$object->{$field->field_name}])) {
                        $values[$object->{$field->field_name}]['count'] = 1;
                        $values[$object->{$field->field_name}]['title'] = $object->get_title();
                    } else {
                        $values[$object->{$field->field_name}]['count']++;
                    }
                }
            );
        }

        if (isset($field->options['order'])) {
            $order = $field->options['order'] == 'title' ? 'title' : 'count';
            $reverse = (isset($field->options['order_dir']) && $field->options['order_dir'] == 'desc');
            usort($values, function ($a, $b) use ($order, $reverse) {
                    if ($reverse) {
                        return ($a[$order] > $b[$order] ? 1 : -1);
                    } else {
                        return ($a[$order] < $b[$order] ? 1 : -1);
                    }
                }
            );
        }

        $return = [];
        foreach ($values as $key => $value) {
            $return[$key] = $value['title'] . ' (' . $value['count'] . ')';
        }

        return $return;
    }

    public function get_id()
    {
        return str_replace('\\', '_', _get::__class_name($this));
    }
}
 