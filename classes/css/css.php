<?php
namespace core\classes\css;

class css {

    protected $files = [];
    /** @var  \classes\css\compiler */
    protected $compiler_class;
    protected $compiler = 'css';
    protected $last_modified = 0;
    public $cached_name = '';

    public function __construct($compiler = 'css') {
        $class = '\\classes\\css\\' . $compiler . '_compiler';
        if (class_exists($class)) {
            $this->compiler_class = new $class();
        } else {
            throw new \RuntimeException('Class ' . $class . ' was not found so can\'t compile your css!');
        }
    }

    public function add_resource_root($root) {
        $files = glob(root . $root . '*.' . $this->compiler_class->file_extension);
        foreach ($files as $file) {
            $this->files[] = $file;
            if (($time = filemtime($file)) > $this->last_modified) {
                $this->last_modified = $time;
            }
        }
    }

    public function add_files($files) {
        if (is_array($files)) {
            $this->files = array_merge($this->files, $files);
        } else {
            $this->files[] = $files;
        }
    }

    public function compile() {
        if ($this->cached_name) {
            $file_name = root . '/.cache/' . $this->cached_name . $this->last_modified . '.css';
            if (file_exists($file_name)) {
                return file_get_contents($file_name);
            }
        }
        foreach ($this->files as $file) {
            $this->compiler_class->add_file($file);
        }
        $css = $this->compiler_class->compile();
        if ($this->cached_name) {
            $file_name = root . '/.cache/' . $this->cached_name . $this->last_modified . '.css';
            file_put_contents($file_name, $css);
        }
        return $css;
    }
}
 