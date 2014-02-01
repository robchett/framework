<?php
namespace core\object;

class log {

    const OFF = -1;
    const DEBUG = 0;
    const INFO = 1;
    const NOTICE = 2;
    const WARNING = 3;
    const ERROR = 4;
    const CRITICAL = 5;
    const ALERT = 6;
    const EMERGENCY = 7;

    protected $log_file;
    protected $log_level = 0;
    protected $level;
    protected $contents;

    public function __construct($level, $file) {
        $this->log_level = $level;
        $this->log_file = fopen(root . $file, 'a');
    }

    public function __destruct() {
        fclose($this->log_file);
    }

    protected function log($message, $level) {
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