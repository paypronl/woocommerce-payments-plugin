<?php

class PayProApiHelper
{
    public $apiKey;
    public $api;

    public $testMode;

    public function __construct() {}

    public function init($apiKey, $testMode = false)
    {
        $this->api = new \PayPro\Client($apiKey);
        $this->testMode = $testMode ? true : false;
    }

    public function getIdealIssuers()
    {
        $result = $this->api->payMethods->list();

        $result = array_filter($result['data'], function($method) {
            return $method->id === 'ideal';
        });

        $result = array_values($result);

        $result['issuers'] = $result[0]['details']['issuers'];
        unset($result['data']);
        return $result;
    }

    public function getCustomer($id)
    {
        return $this->api->customers->get($id);
    }

    public function createCustomer(array $data = [])
    {
        return $this->api->customers->create($data);
    }

    public function getPayment($id)
    {
        return $this->api->payments->get($id);
    }

    public function createPayment(array $data)
    {
        return $this->api->payments->create($data);
    }
}
