<?php
namespace core\classes;

use traits\var_dump_import;

abstract class compiler_page implements \Serializable {
    use var_dump_import;

    /** @var  bool|\classes\push_state */
    public $push_state = false;
    /** @var  string */
    public $content;
    /** @var  ajax */
    public $ajax;

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize() {
        return var_export($this, true);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized) {
        //
    }
}
 