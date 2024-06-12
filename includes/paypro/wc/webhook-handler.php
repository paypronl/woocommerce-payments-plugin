<?php

defined('ABSPATH') || exit;

/**
 * Class to handle the webhook requests.
 */
class PayPro_WC_WebhookHandler {
    /**
     * Initializes the action for the webhook endpoint.
     */
    public function init() {
        add_action('woocommerce_api_paypro_webhook', [ $this, 'onWebhookRequest' ]);
    }

    /**
     * Called when the webhook endpoint is called.
     */
    public function onWebhookRequest() {
        $current_url = PayPro_WC_Helper::currentUrl();
        PayPro_WC_Logger::log("onWehookRequest - URL: {$current_url}");

        // Implement webhook handeling.

        status_header(200);
        exit;
    }
}
