<?php

class PayProApiHelper
{
    public $apiKey;
    public $api;

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
