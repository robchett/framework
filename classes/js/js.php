<?php
namespace core\classes\js;

use classes\get;
use classes\interfaces\asset;

class js extends asset {

    public $cached_name;
    protected $files;
    public $last_modified;

    public function compile() {
        if ($this->cached_name) {
            $file_name = root . '/.cache/' . $this->cached_name . $this->last_modified . '.js';
            if (file_exists($file_name)) {
                return file_get_contents($file_name);
            }
        }
        if ($this->cached_name) {
            foreach (glob(root . '/.cache/' . $this->cached_name . '*.js') as $link) {
                unlink($link);
            };
            $js = '';
            foreach ($this->files as $file) {
                if (debug) {
                    //$js .= '/* ' . $file . ' */';
                }
                $js .= file_get_contents($file);
            }
            $file_name = root . '/.cache/' . $this->cached_name . $this->last_modified . '.js';
            file_put_contents($file_name, $js);
        }
        return $js;
    }

    public function add_files($files) {
        if (is_array($files)) {
            foreach ($files as $file) {
                if (($time = filemtime($file)) > $this->last_modified) {
                    $this->last_modified = $time;
                }
            }
            $this->files = array_merge($this->files, $files);
        } else {
            if (($time = filemtime($files)) > $this->last_modified) {
                $this->last_modified = $time;
            }
            $this->files[] = $files;
        }
    }


    public static function get_js() {
        $expires = 60 * 60 * 24;
        header('Content-type: text/javascript');
        header('Content-Length: ' . ob_get_length());
        header('Cache-Control: max-age=' . $expires . ', must-revalidate');
        header('Pragma: public');

        $output = new self;
        $output->cached_name = 'global';
        foreach (ini::get('js', 'files', []) as $file) {
            $output->add_files(core_dir . '/js/' . $file . '.js');
        }
        $output->add_resource_root(root . '/js');
        echo $output->compile();
        die();
    }

    public function add_resource_root($root) {
        $files = glob(trim($root, '/') . '/*.js', GLOB_MARK);
        foreach ($files as $file) {
            $this->add_files($file);
        }
    }
}
 