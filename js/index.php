<?php

header('Content-type: text/javascript');

$output = "";

if (isset($core_files)) {
    foreach ($core_files as $file) {
        $output .= file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/.core/js/' . $file . '.js');
    }
}
$files = glob("../js/*.js", GLOB_MARK);
foreach ($files as $file) {
    $output .= file_get_contents($file);
}
ob_start();
echo $output;
$expires = 60 * 60 * 24;
header("Content-type: x-javascript");
header('Content-Length: ' . ob_get_length());
header('Cache-Control: max-age='.$expires.', must-revalidate');
header('Pragma: public');
//$output = preg_replace('/(\s|;|^|,)\/\/.*$/m','$1',$output);
//$output = preg_replace('/\s+/',' ',$output);
ob_end_flush();
