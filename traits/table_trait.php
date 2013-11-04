<?php
namespace core\traits;

trait table_trait {

    public static $fields = null;
    public static $retrieve_unlive = false;
    public static $retrieve_deleted = true;
    /** @var self */
    public static $singleton;
}
 