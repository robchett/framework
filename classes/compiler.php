<?php
namespace core\classes;

use classes\compiler_page;

class compiler {

    const MODE_MEMCACHED = 2;
    const MODE_REDIS = 1;
    const MODE_FILE = 0;

    protected static $disabled = 0;
    protected static $mode = 0;

    public static $dependants = [];

    protected static $ignore_tables = ['_cache_dependants', '_compiler_keys'];

    public static function break_cache($table) {
        if (in_array($table, static::$ignore_tables)) {
            return;
        }
        $res = db::select('_compiler_keys')
                 ->add_field_to_retrieve('file')
                 ->filter('`dependants` LIKE "%' . $table . '%"')
                 ->execute();
        while ($row = db::fetch($res)) {
            switch (static::$mode) {
                case static::MODE_FILE :
                    static::break_file($row->file);
                    break;
                case static::MODE_REDIS:
                    //$this->break_redis($row->file);
                    throw new \Exception('Page caching method is not implemented - Redis');
                    break;
                case static::MODE_MEMCACHED :
                    static::break_memcached($row->file);
                    break;
            }
        }
        db::delete('_compiler_keys')
          ->filter('`dependants` LIKE "%' . $table . '%"')
          ->execute();
    }

    protected static function  break_file($key) {
        unlink(root . '/.cache/compile/' . $key);
    }

    protected static function  break_redis($key) { }

    protected static function  break_memcached($key) {
        cache::remove($key);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return compiler_page
     * @throws \Exception
     */
    public function load($url, $parameters = []) {
        $key = md5($url . serialize($parameters));
        switch (static::$mode) {
            case static::MODE_FILE:
                return $this->load_file($key);
            case static::MODE_REDIS:
                //return $this->load_redis($key);
                throw new \Exception('Page caching method is not implemented - Redis');
            case static::MODE_MEMCACHED:
                return $this->load_memcached($key);
        }
    }

    protected function load_file($key) {
        $file = root . '/.cache/compile/' . $key;
        if (file_exists($file)) {
            /** @var compiler_page $content */
            eval('$content = ' . file_get_contents($file) . ';');
            return $content;
        }
        throw new \Exception('Compiled file not found ' . $file);
    }

    protected function load_memcached($key) {
        return cache::get($key, []);
    }

    public function save($url, compiler_page $content, $parameters = []) {
        if (static::$disabled === 0) {
            $key = md5($url . serialize($parameters));
            $dependents = implode(',', array_unique(self::$dependants));
            db::insert('_compiler_keys')
              ->add_value('file', $key)
              ->add_value('dependants', $dependents)
              ->execute();
            switch (static::$mode) {
                case static::MODE_FILE :
                    $this->save_file($key, $content);
                    break;
                case static::MODE_REDIS:
                    //$this->save_redis($file, $content);
                    throw new \Exception('Page caching method is not implemented - Redis');
                    break;
                case static::MODE_MEMCACHED :
                    $this->save_memcached($key, $content);
                    break;
            }
        }
    }

    protected function save_file($key, $contents) {
        if(!local) {
            file_put_contents(root . '/.cache/compile/' . $key, $contents->serialize());
        }
    }

    protected function save_memcached($key, $contents) {
        cache::set([$key => $contents], []);
    }

    public static function disable() {
        static::$disabled++;
    }

    public static function allow() {
        static::$disabled--;
    }
}

