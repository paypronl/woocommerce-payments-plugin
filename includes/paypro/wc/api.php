<?php

defined('ABSPATH') || exit;

/**
 * Helper class to call the PayPro API.
 */
class PayPro_WC_Api {
    /**
     * The PayPro API client
     *
     * @var \PayPro\Client $api_client
     */
    public $api_client;

    /**
     * Sets the API key by creating a new client with the correct key.
     *
     * @param string $api_key The API key to be used for API calls.
     */
    public function setApiKey($api_key) {
        $this->api_client = new \PayPro\Client(['api_key' => $api_key, 'api_url' => 'http://api.paypro.test:3000']);
    }

    /**
     * Get the webhook by ID from the API.
     *
     * @param string $id The ID of the webhook.
     */
    public function getWebhook($id) {
        return $this->api_client->webhooks->get($id);
    }

    /**
     * Create a new Webhook resource.
     *
     * @param string $name The name for the webhook.
     * @param string $description The description for the webhook.
     * @param string $url The URL of the endpoint for the webhook.
     */
    public function createWebhook($name, $description, $url) {
        return $this->api_client->webhooks->create(
            [
                'name'        => $name,
                'description' => $description,
                'url'         => $url,
            ]
        );
    }

    /**
     * Get all pay methods from the API.
     */
    public function getPayMethods() {
        return $this->api_client->payMethods->list();
    }

    /**
     *  Get a customer from the API.
     *
     * @param string $id The ID of the customer.
     */
    public function getCustomer($id) {
        return $this->api_client->customers->get($id);
    }

    /**
     * Create a new Customer resource.
     *
     * @param array $data The data to be passed when creating the Customer.
     */
    public function createCustomer(array $data = []) {
        return $this->api_client->customers->create($data);
    }

    /**
     * Get a payment from the API.
     *
     * @param string $id The ID of the payment.
     */
    public function getPayment($id) {
        return $this->api_client->payments->get($id);
    }

    /**
     * Create a new Payment resource.
     *
     * @param array $data The data to be passed when creating the Payment.
     */
    public function createPayment(array $data) {
        return $this->api_client->payments->create($data);
    }

    /**
     * Get a mandate from the API.
     *
     * @param string $id The ID of the mandate.
     */
    public function getMandate($id) {
        return $this->api_client->mandates->get($id);
    }
}
