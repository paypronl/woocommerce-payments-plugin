<?php

namespace PayPro;

use PayPro\Error;

/**
 * Class Client
 *
 * @package PayPro
 */

class Client {
    private $apikey = null;
    private $command = null;
    private $params = array();

    function __construct($apikey) {
        $this->apikey = $apikey;
    }

    /**
     * Executes the command. Connects with the PayPro API and returns the response.
     *
     * On correct API calls it will return an array with all the fields.
     *
     * @throws Error\Connection if the connection can not be established.
     * @throws Error\InvalidResponse if there is an error with the response.
     */
    function execute() {
        $data_to_post = array(
            'apikey'  => $this->apikey,
            'command' => $this->command,
            'params'  => json_encode($this->params)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PayPro::$apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_post);
        curl_setopt($ch, CURLOPT_CAINFO, $this->caBundleFile());

        $body = curl_exec($ch);

        if ($body === false) {
            $errno = curl_errno($ch);
            $message = curl_error($ch);

            curl_close($ch);

            $msg = "Could not connect to the PayPro API - [errno: $errno]: $message";
            throw new Error\Connection($msg);
        }

        curl_close($ch);

        $decodedResponse = json_decode($body, true); 

        if (is_null($decodedResponse)) {
            $msg = "The API request returned an error or is invalid: $body";
            throw new Error\InvalidResponse($msg);
        }
        
        $params = array();
        return $decodedResponse;
    }

    /**
     * Sets the command to be executed.
     */
    function setCommand($command) {
        $this->command = $command;
    }

    /**
     * Sets a single parameter for the API call.
     */
    function setParam($param, $value) {
        $this->params[$param] = $value;
    }

    /**
     * Sets an associative array as param where the keys are the name of the param.
     */
    function setParams($params) {
        foreach($params as $param => $value) {
            $this->params[$param] = $value;
        }
    }

    /**
     * Returns the full path of the ca bundle file.
     */
    private function caBundleFile() {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . PayPro::$caBundleFile);
    }
}
