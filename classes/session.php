<?php
namespace core\classes;

class session {

    protected static $started = false;
    protected static $modified = false;

    protected static function start() {
        if (!static::$started) {
            session_start();
            \classes\compiler::disable();
            static::$started = true;
        }
    }

    public static function stop() {
        if (static::$started) {
            session_write_close();
        }
    }

    /**
     * @ ...string
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public static function get(/** ...$input */) {
        $input = func_get_args();
        if (count($input) == 0) {
            throw new \BadMethodCallException('Please pass at least one parameter to session::get');
        }
        static::start();
        $var = $_SESSION;
        foreach ($input as $key) {
            if (!isset($var[$key])) {
                throw new \InvalidArgumentException('$_SESSION[' . implode('][', $input) . '] has not been set');
            }
            $var = $var[$key];
        }
        return $var;
    }

    /**
     * @ ...string
     * @return mixed
     * @throws \BadMethodCallException
     */
    public static function is_set(/** ...$input */) {
        $input = func_get_args();
        if (count($input) == 0) {
            throw new \BadMethodCallException('Please pass at least one parameter to session::is_set');
        }
        static::start();
        $var = $_SESSION;
        foreach ($input as $key) {
            if (!isset($var[$key])) {
                return false;
            }
            $var = $var[$key];
        }
        return true;
    }

    /**
     * @ ...string
     * @return mixed
     * @throws \BadMethodCallException
     */
    public static function un_set(/** ...$input */) {
        $input = func_get_args();
        if (count($input) == 0) {
            throw new \BadMethodCallException('Please pass at least one parameter to session::is_set');
        }
        static::start();
        static::$modified = true;
        $final = array_pop($input);
        $var = &$_SESSION;
        foreach ($input as $key) {
            if (!isset($var[$key])) {
                $var[$key] = [];
            }
            $var = &$var[$key];
        }
        unset($var[$final]);
    }

    /**
     * @ mixed $value
     * @ ...string
     * @return mixed
     * @throws \BadMethodCallException
     */
    public static function set(/** $value, ...$input */) {
        $input = func_get_args();
        if (count($input) < 2) {
            throw new \BadMethodCallException('Please pass at least two parameters to session::set');
        }
        static::start();
        static::$modified = true;
        $value = array_shift($input);
        $final = array_pop($input);
        $var = &$_SESSION;
        foreach ($input as $key) {
            if (!isset($var[$key])) {
                $var[$key] = [];
            }
            $var = &$var[$key];
        }
        $var[$final] = $value;
    }
}
 