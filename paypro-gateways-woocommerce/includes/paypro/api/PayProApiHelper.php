<?php

class PayProApiHelper 
{
    var $apiKey;
    var $api;

    var $testMode;

    public function __construct() {}

    public function init($apiKey, $testMode = false)
    {
        $this->api = new \PayPro\Client($apiKey);
        $this->testMode = $testMode ? true : false;
    }

    public function getIdealIssuers()
    {
        $this->api->command = 'get_all_pay_methods';
        $result = $this->execute();

        $result['issuers'] = $result['data']['data']['ideal']['methods'];
        unset($result['data']);
        return $result;
    }

    public function createPayment(array $data)
    {
        $this->api->command = 'create_payment';
        $this->api->setParams($data);
        return $this->execute();
    }

    public function getSaleStatus($payment_hash)
    {
        $this->api->command = 'get_sale';
        $this->api->set_param('payment_hash', $payment_hash);
        return $this->execute();
    }

    private function execute()
    {
        if($this->testMode) $this->api->set_param('test_mode', 'true'); else $this->api->set_param('test_mode', 'false');
    
        $result = $this->api->execute();

        if(isset($result['errors']))
        {
            if(strcmp($result['errors'], 'false') === 0)
                return array('errors' => false, 'data' => $result['return']);
            else
                return array('errors' => true, 'message' => $result['return']);
        }
        else
        {
        return array('errors' => true, 'message' => 'Invalid return from the API');
        } 
    }
}
