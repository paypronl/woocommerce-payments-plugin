<?php

defined('ABSPATH') || exit;

/**
 * Class to handle the payment on returning the WC store.
 */
class PayPro_WC_PaymentHandler {
    /**
     * Initializes the actions for the return and cancel URL.
     */
    public function init() {
        add_action('woocommerce_api_paypro_return', [ $this, 'onReturn' ]);
        add_action('woocommerce_api_paypro_cancel', [ $this, 'onReturn' ]);
    }

    /**
     * Runs when the customer returns to the WC store after the payment succeeded.
     */
    public function onReturn() {
        $current_url = PayPro_WC_Helper::currentUrl();
        PayPro_WC_Logger::log("onReturn - URL: {$current_url}");

        $order = $this->getOrderFromUrl('onReturn');

        // Only handle order if it is still pending.
        if ($order->hasStatus('pending')) {
            $payment_ids = $order->getPayments();

            // Get all payments with statuses.
            $payments = [];
            foreach ($payment_ids as $payment_id) {
                try {
                    $payment = PayPro_WC_Plugin::$paypro_api->getPayment($payment_id);
                    array_push($payments, $payment);
                } catch (\PayPro\Exception\ApiErrorException $e) {
                    PayPro_WC_Logger::log("onReturn - Failed to get payment $payment_id from API");
                }
            }

            if (empty($payments)) {
                PayPro_WC_Logger::log('onReturn - No payments found for this order. Cannot redirect customer.');

                status_header(401);
                exit;
            }

            // Map payment states to WC order states.
            $result = null;

            foreach ($payments as $payment) {
                if ('paid' === $payment->state || 'completed' === $payment->state) {
                    $result = [
                        'id'    => $payment->id,
                        'state' => 'order_completed',
                    ];

                    break;
                } elseif ('canceled' === $payment->state) {
                    $result = [
                        'id'    => $payment->id,
                        'state' => 'order_canceled',
                    ];
                } elseif ('processing' === $payment->state) {
                    $result = [
                        'id'    => $payment->id,
                        'state' => 'order_received',
                    ];
                } else {
                    $result = [
                        'id'    => $payment->id,
                        'state' => 'order_failed',
                    ];
                }
            }

            $redirect_url = null;

            // Handle WC order states and redirect the customer.
            if ('order_completed' === $result['state']) {
                $order->complete($result['id']);

                PayPro_WC_Logger::log("onReturn - Payment ({$result['id']}) paid for order: {$order->getId()}");
                $redirect_url = $order->getOrderReceivedUrl();
            } elseif ('order_canceled' === $result['state']) {
                $order->cancel($result['id']);

                PayPro_WC_Logger::log("onReturn - Payment ({$result['id']}) canceled for order: {$order->getId()}");
                $redirect_url = $order->getCancelOrderUrl();
            } elseif ('order_received' === $result['state']) {
                PayPro_WC_Logger::log("onReturn - Payment ({$result['id']}) processing for order: {$order->getId()}");
                $redirect_url = $order->getOrderReceivedUrl();
            } else {
                PayPro_WC_Logger::log("onReturn - Payment failed ({$result['id']}) for order: {$order->getId()}");
                $redirect_url = WC()->cart->get_checkout_url();
            }

            wp_safe_redirect($redirect_url);
            exit;
        }

        PayPro_WC_Logger::log('onReturn - Order is not pending, redirect to order received page');
        wp_safe_redirect($order->getOrderReceivedUrl());
        exit;
    }

    /**
     * Parses the return URL and returns a PayPro_WC_Order if the WC order can be found.
     */
    private function getOrderFromUrl() {
        $order_id  = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT) ?? null;
        $order_key = filter_input(INPUT_GET, 'order_key', FILTER_SANITIZE_SPECIAL_CHARS) ?? null;

        // Check if the request has valid query params.
        if (empty($order_id) || empty($order_key)) {
            PayPro_WC_Logger::log("$context: Invalid PayPro return url.");

            status_header(400);
            exit;
        }

        // Check if order_id is a known order.
        $order = new PayPro_WC_Order($order_id);

        if (!$order->exists()) {
            PayPro_WC_Logger::log("$context: Order not found - id: $order_id");

            status_header(404);
            exit;
        }

        // Check if order_key is valid.
        if (!$order->validKey($order_key)) {
            PayPro_WC_Logger::log("$context: Invalid $order_key for $order_id");

            status_header(401);
            exit;
        }

        return $order;
    }
}
