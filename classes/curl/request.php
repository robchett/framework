<?php
namespace core\classes\curl;


class request {
    public $options;

    public function __construct($url = null, $params = array('set_default' => true)) {
        if($params['set_default'])
            $this->set_default();

        if($url)
            $this->set_url($url);
    }

    public function set_default() {
        $this->options[CURLOPT_RETURNTRANSFER] = true;
    }

    public function set_timeout($timeout) {
        $this->options[CURLOPT_CONNECTTIMEOUT] = $timeout;
        $this->options[CURLOPT_TIMEOUT] = $timeout;
    }

    public function set_options($options) {
        foreach($options as $key => $value)
            $this->options[$key] = $value;
    }

    public function set_option($key, $value) {
        $this->options[$key] = $value;
    }

    public function get_option($key) {
        if(!isset($this->options[$key]))
            return NULL;

        return $this->options[$key];
    }

    public function get_options() {
        return $this->options;
    }

    public function set_url($url) {
        $this->options[CURLOPT_URL] = $url;

        if(strpos($url, 'https') === 0)
            $this->options[CURLOPT_SSL_VERIFYPEER] = false;
    }

    public function set_referer($url) {
        $this->options[CURLOPT_REFERER] = $url;
    }

    public function disable_redirects() {
        $this->options[CURLOPT_FOLLOWLOCATION] = false;
    }

    public function set_authentication($username, $password) {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
    }

    public function set_cookies($file_path) {
        // clear the cookies
        //fclose(fopen($file_path, 'w'));

        $this->options[CURLOPT_COOKIEJAR] = $file_path;
        $this->options[CURLOPT_COOKIEFILE] = $file_path;
    }

    public function set_cookie_data($data) {
        $this->options[CURLOPT_COOKIE] = $data;
    }

    public function set_proxy($type, $host, $port, $username = NULL, $password = NULL) {
        if($type == 'http')
            $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        else if($type == 'socks4')
            $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
        else if($type == 'socks5')
            $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;

        $this->options[CURLOPT_PROXY] = $host . ':' . $port;

        if($username && $password)
            $this->options[CURLOPT_PROXYUSERPWD] = $username . ':' . $password;
    }

    public function set_post($data) {
        $this->options[CURLOPT_CUSTOMREQUEST] = 'POST';
        $this->options[CURLOPT_POST] = true;
        $this->options[CURLOPT_POSTFIELDS] = $data;
    }

    public function set_get() {
        $this->options[CURLOPT_CUSTOMREQUEST] = 'GET';
        $this->options[CURLOPT_POST] = false;
        $this->options[CURLOPT_POSTFIELDS] = '';
    }

    public function set_head() {
        $this->options[CURLOPT_CUSTOMREQUEST] = 'HEAD';
        $this->options[CURLOPT_POST] = false;
        $this->options[CURLOPT_POSTFIELDS] = '';
    }

    public function set_header($data) {
        $this->options[CURLOPT_HEADER] = true;
        $this->options[CURLOPT_HTTPHEADER] = $data;
    }
}