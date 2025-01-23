<?php

defined('ABSPATH') || exit;

/**
 * Class to handle the webhook requests.
 */
class PayPro_WC_WebhookHandler {
    /**
     * The webhook secret used to validate webhook requests.
     *
     * @var string $secret
     */
    private $secret;

    /**
     * Constructor
     *
     * @param string $secret The webhook secret.
     */
    public function __construct($secret) {
        $this->secret = $secret;
    }

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

        $request_body    = file_get_contents('php://input');
        $request_headers = array_change_key_case($this->getRequestHeaders(), CASE_UPPER);

        $result = $this->validateWebhook($request_body, $request_headers);

        if ($result['error']) {
            PayPro_WC_Logger::log("onWebhookRequest - {$result['message']}");
        } else {
            $json_body = json_decode($request_body);
            $this->processWebhook($json_body);
        }

        status_header(200);
        exit;
    }

    /**
     * Validates the webhook based on the signature.
     *
     * @param string $body    The HTTP body of the request.
     * @param array  $headers The HTTP headers of the request.
     */
    private function validateWebhook($body, $headers) {
        $signature = $headers['PAYPRO-SIGNATURE'];
        $timestamp = intval($headers['PAYPRO-TIMESTAMP']);

        if (empty($signature)) {
            return [
                'error'   => true,
                'message' => 'Empty signature',
            ];
        }

        if (empty($timestamp)) {
            return [
                'error'   => true,
                'message' => 'Empty timestamp',
            ];
        }

        try {
            $signature_validator = new \PayPro\Signature($body, $timestamp, $this->secret);
            $signature_validator->verify($signature);

            return [ 'error' => false ];
        } catch (\PayPro\Exception\InvalidArgumentException $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
            ];
        } catch (\PayPro\Exception\SignatureVerificationException $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delegates the processing of the webhook based on the event type
     *
     * @param string $event The event payload.
     */
    private function processWebhook($event) {
        PayPro_WC_Logger::log("onWebhookRequest - Event received ($event->event_type)");

        switch ($event->event_type) {
            case 'payment.paid':
                $this->processPaymentPaid($event);
                break;
            case 'payment.canceled':
                $this->processPaymentCanceled($event);
                break;
        }
    }

    /**
     * Handle the payment paid event
     *
     * @param string $event The event payload.
     */
    private function processPaymentPaid($event) {
        $payment  = $event->payload;
        $order_id = $payment->metadata->order_id;

        $order = new PayPro_WC_Order($order_id);

        if (!$order->exists()) {
            PayPro_WC_Logger::log("onWebhookRequest - Cannot find order for payment ({$payment->id})");
            return;
        }

        // If the order is already processed we ignore the webhook.
        if (!$order->hasStatus('pending')) {
            PayPro_WC_Logger::log("onWebhookRequest - Order is already processed, ignoring payment ({$payment->id})");
            return;
        }

        PayPro_WC_Logger::log("onWehookRequest - Order is pending and payment paid so complete order for payment ({$payment->id})");
        $order->complete($payment);
    }

    /**
     * Handle the payment canceled event
     *
     * @param string $event The event payload.
     */
    private function processPaymentCanceled($event) {
        $payment  = $event->payload;
        $order_id = $payment->metadata->order_id;

        $order = new PayPro_WC_Order($order_id);

        if (!$order->exists()) {
            PayPro_WC_Logger::log("onWebhookRequest - Cannot find order for payment ({$payment->id})");
            return;
        }

        // If the order is already processed we ignore the webhook.
        if (!$order->hasStatus('pending')) {
            PayPro_WC_Logger::log("onWebhookRequest - Order is already processed, ignoring payment ({$payment->id})");
            return;
        }

        PayPro_WC_Logger::log("onWehookRequest - Order is pending and payment canceled ({$payment->id})");
        $order->cancel($payment);
    }

    /**
     * Returns the headers from the webhook request
     */
    private function getRequestHeaders() {
        if (!function_exists('getallheaders')) {
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                if ('HTTP_' === substr($name, 0, 5)) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }

            return $headers;
        } else {
            return getallheaders();
        }
    }
}
