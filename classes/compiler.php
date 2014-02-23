<?php
namespace core\classes;

use classes\compiler_page;

class compiler {

    protected static $disabled = 0;

    public static $dependants = [];

    protected static $ignore_tables = ['_compiler_keys'];

    public static function break_cache($table) {
        if (in_array($table, static::$ignore_tables)) {
            return;
        }
        $res = db::select('_compiler_keys')->add_field_to_retrieve('file')->filter('`dependants` LIKE "%' . $table . '%"')->execute();
        while ($row = db::fetch($res)) {
            unlink(root . '/.cache/compile/' . $row->file);
        }
        db::delete('_compiler_keys')->filter('`dependants` LIKE "%' . $table . '%"')->execute();
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return compiler_page
     * @throws \Exception
     */
    public function load($url, $parameters = []) {
        $file = root . '/.cache/compile/' . md5($url . serialize($parameters));
        if (file_exists($file)) {
            /** @var compiler_page $content */
            eval('$content = ' . file_get_contents($file) . ';');
            return $content;
        }
        throw new \Exception('Compiled file not found ' . $file);
    }

    public function save($url, compiler_page $content, $parameters = []) {
        if (static::$disabled === 0) {
            $file = md5($url . serialize($parameters));
            $dependents = implode(',', array_unique(self::$dependants));
            db::insert('_compiler_keys')->add_value('file', $file)->add_value('dependants', $dependents)->execute();
            file_put_contents(root . '/.cache/compile/' . $file, $content->serialize());
        }
    }

    public static function disable() {
        static::$disabled++;
    }

    public static function allow() {
        static::$disabled--;
    }
}

