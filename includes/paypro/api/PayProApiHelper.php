<?php

class PayProApiHelper
{
    var $apiKey;
    var $api;

    public function __construct() {}

    public function init($apiKey) {
        try {
            $this->api = new \PayPro\Client($apiKey);
        } catch(\Exception $e) {}
    }

    public function getWebhook($reference) {
        return $this->api?->webhooks->get($reference);
    }

    public function createWebhook() {
        $webhook_url = WC()->api_request_url('paypro_wc_plugin');

        return $this->api?->webhooks->create(
            [
                'name' => 'WooCommerce',
                'description' => 'Webhook for WooCommerce PayPro plugin.',
                'url' => $webhook_url
            ]
        );
    }

    public function getIdealIssuers()
    {
        $result = $this->api?->payMethods->list();

        if ($result) {
            $result = array_filter($result['data'], function($method) {
                return $method->id === 'ideal';
            });

            $result = array_values($result);

            $result['issuers'] = $result[0]['details']['issuers'];
            unset($result['data']);
        }

        return $result;
    }

    public function createPayment(array $data)
    {
        $this->api->setCommand('create_payment');
        $this->api->setParams($data);
        return $this->execute();
    }

    public function getSaleStatus($payment_hash)
    {
        $this->api->setCommand('get_sale');
        $this->api->setParam('payment_hash', $payment_hash);
        return $this->execute();
    }

    private function execute()
    {
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
