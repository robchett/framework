<?php
namespace core\objects;

class log {

    const OFF       = -1;
    const DEBUG     = 1;
    const INFO      = 2;
    const NOTICE    = 4;
    const WARNING   = 8;
    const ERROR     = 16;
    const CRITICAL  = 32;
    const ALERT     = 64;
    const EMERGENCY = 128;

    protected $log_file;
    protected $log_level = 0;
    protected $level;
    protected $contents;

    public function __construct($level, $file) {
        $this->log_level = $level;
        $this->log_file = fopen(root . str_replace(root, '', $file), 'a');
    }

    public function __destruct() {
        fclose($this->log_file);
    }

    protected function log($message, $level) {
        \classes\l8::write($level, \classes\l8::LOG_STATEMENT, $message, '', '', []);
        if ($this->log_level <= $level) {
            fwrite($this->log_file, sprintf("%21s %80s\r\n", date('Y-m-d H:i:s'), $message));
        }
    }

    public function debug($message) {
        $this->log($message, static::DEBUG);
    }

    public function info($message) {
        $this->log($message, static::INFO);
    }

    public function notice($message) {
        $this->log($message, static::NOTICE);
    }

    public function warning($message) {
        $this->log($message, static::WARNING);
    }

    public function error($message) {
        $this->log($message, static::ERROR);
    }

    public function critical($message) {
        $this->log($message, static::CRITICAL);
    }

    public function alert($message) {
        $this->log($message, static::ALERT);
    }

    public function emergency($message) {
        $this->log($message, static::EMERGENCY);
    }

}
