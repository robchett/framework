<?php
namespace core\classes\curl;

class controller {

    public $connection_list;
    public $job_list;
    protected $setting_list;
    protected $mc;
    protected $last_request;
    protected $last_response;

    public function __construct($options = array('active' => true, 'max_connections' => 30)) {
        $this->setting_list = $options;
        $this->connection_list = array();
        $this->job_list = array();

        $this->mc = curl_multi_init();
    }

    public function run(\classes\curl\request $request, $callback = null) {
        $c = curl_init();

        foreach($request->get_options() as $key => $value)
            curl_setopt($c, $key, $value);

        if($callback) {
            $this->job_list[] = array('request' => $request, 'handle' => $c, 'callback' => $callback);
        }
        else {
            $r = new \classes\curl\response();

            $header_list = array();

            ob_start();
            $r->data = curl_exec($c);
            ob_end_clean();

            $r->request = $request;
            $r->info = curl_getinfo($c);
            $r->status_code = curl_getinfo($c, CURLINFO_HTTP_CODE);

            curl_close($c);

            return $r;
        }

        $this->last_request = $request;
    }

    public function update() {
        if(!$this->setting_list['active'])
            return;

        while(count($this->connection_list) < $this->setting_list['max_connections'] && count($this->job_list) > 0) {

            $job = array_shift($this->job_list);

            $host = $job['request']->get_option(CURLOPT_URL);
            var_dump($host);
            if(!$host)
                return $job['callback'](null);

            if(strpos($host, 'http') !== 0)
                $job['request']->set_option(CURLOPT_URL, 'http://' . $host);

            $host = parse_url($job['request']->get_option(CURLOPT_URL), PHP_URL_HOST);

            // check if the domain is bad and will block multicurl
            if(!$this->is_host_active($host)) {
                var_dump('wtf');
                if($job['callback'] != null)
                    if(phpversion() >= 5.3)
                        $job['callback'](null);
                    else
                        call_user_func_array($job['callback'], array(null));

                continue;
            }

            $this->connection_list[$job['handle']] = array(
                'request' => $job['request'],
                'handle' => $job['handle'],
                'callback' => $job['callback']
            );

            curl_multi_add_handle($this->mc, $job['handle']);
        }

        while(($status = curl_multi_exec($this->mc, $running)) == CURLM_CALL_MULTI_PERFORM)
            continue;

        if($status != CURLM_OK)
            return;

        while($item = curl_multi_info_read($this->mc)) {
            usleep(20000);

            $handle = $item['handle'];

            $connection = $this->connection_list[$handle];

            $info = curl_getinfo($handle);

            $data = curl_multi_getcontent($handle);

            curl_multi_remove_handle($this->mc, $handle);

            unset($this->connection_list[$handle]);

            $response = new \classes\curl\response();
            $response->request = $connection['request'];
            $response->data = $data;
            $response->info = $info;
            $response->status_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            $this->last_response = $response;

            if($connection['callback'] != null)
                if(phpversion() >= 5.3)
                    $connection['callback']($response);
                else
                    call_user_func_array($connection['callback'], array($response));
        }
    }

    public function is_host_active($host) {
        if(!$host)
            return false;

        // if this isn't linux don't check it
        if(!stristr(PHP_OS, "linux"))
            return true;

        // if this is an IP don't check it
        if(long2ip(ip2long($host)) == $host)
            return true;

        //$x1 = shell_exec("nslookup " . $host);

        return true;//!stristr($x1, " find");
    }

    public function get_last_request() {
        return $this->last_request;
    }

    public function get_last_response() {
        return $this->last_response;
    }

    public function set_setting_list($setting_list) {
        foreach($setting_list as $name => $value)
            $this->setting_list[$name] = $value;
    }

    public function set_setting($name, $value) {
        $this->setting_list[$name] = $value;
    }

    public function get() {
        return $this->mc;
    }
}