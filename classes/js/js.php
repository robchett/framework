<?php
namespace core\classes\js;

use classes\interfaces\asset;

class js extends asset {

    public function compile() {
        // TODO: Implement compile() method.
    }

    public static function get_js() {
        $output = "";
        $core_files = ['jquery', '_ajax', '_default', 'colorbox'];
        if (isset($core_files)) {
            foreach ($core_files as $file) {
                $output .= file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/.core/js/' . $file . '.js');
            }
        }
        $files = glob($_SERVER['DOCUMENT_ROOT'] . "/js/*.js", GLOB_MARK);
        foreach ($files as $file) {
            $output .= file_get_contents($file);
        }
        ob_start();
        echo $output;
        $expires = 60 * 60 * 24;
        header('Content-type: text/javascript');
        header('Content-Length: ' . ob_get_length());
        header('Cache-Control: max-age=' . $expires . ', must-revalidate');
        header('Pragma: public');
        ob_end_flush();
        die();

    }

    public function add_files($files) {
        // TODO: Implement add_files() method.
    }

    public function add_resource_root($root) {
        // TODO: Implement add_resource_root() method.
    }
}
 