<?php

defined('ABSPATH') || exit;

class PayPro_WC_WebhookHandler
{
    public function init()
    {
        add_action('woocommerce_api_paypro_webhook', [$this, 'onWebhookRequest']);
    }

    public function onWebhookRequest()
    {
        PayPro_WC_Logger::log("onWehookRequest - URL: {$this->currentUrl()}");

        // Implement webhook handeling

        status_header(200);
        exit;
    }

    private function currentUrl()
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        return "$protocol {$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}";
    }
}
