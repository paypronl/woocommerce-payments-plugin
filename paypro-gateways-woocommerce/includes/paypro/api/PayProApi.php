<?php

class PayProApi {
    static $cert_file = 'ca-bundle.crt';

    var $command;
    var $params = array();
    var $apikey;

    function __construct($apikey, $command=null, $params=null) {
        $this->apikey = $apikey;
        $this->command = $command;
        if (is_array($params)) {
            $this->params = $params;
        }
    }

    function execute() {
        $data_to_post = array(
            'apikey'  => $this->apikey,
            'command' => $this->command,
            'params'  => json_encode($this->params)
        );

        $url = 'https://www.paypro.nl/post_api/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_post);
        curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__) . '/'. PayProApi::$cert_file));

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $params = array();
        return $response;
    }

    function set_param($param, $value) {
        $this->params[$param] = $value;
    }
}
