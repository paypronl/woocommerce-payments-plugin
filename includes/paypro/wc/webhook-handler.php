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

        // TODO: Validate webhook signature


        $request_body = file_get_contents('php://input');
        $request_headers = array_change_key_case($this->getRequestHeaders(), CASE_UPPER);





        status_header(200);
        exit;
    }

    private function getRequestHeaders() {
        if (!function_exists('getallheaders')) {
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                if ('HTTP_' === substr( $name, 0, 5)) {
                    $headers[ str_replace( ' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))) ] = $value;
                }
            }

            return $headers;          
        } else {
            return getallheaders();
        }
    }
}
