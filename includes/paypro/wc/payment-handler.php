<?php

defined('ABSPATH') || exit;

class PayPro_WC_PaymentHandler
{
    public function init()
    {
        add_action('woocommerce_api_paypro_return', [$this, 'onReturn']);
        add_action('woocommerce_api_paypro_cancel', [$this, 'onReturn']);
    }

    public function onReturn()
    {
        PayPro_WC_Logger::log("onReturn - URL: {$this->currentUrl()}");

        $order = $this->getOrderFromUrl('onReturn');

        // Only handle order if it is still pending
        if($order->hasStatus('pending'))
        {
            $payment_ids = $order->getPayments();

            // Get all payments with statuses
            $payments = [];
            foreach($payment_ids as $payment_id)
            {
                try {
                    $payment = PayPro_WC_Plugin::$paypro_api->getPayment($payment_id);
                    array_push($payments, $payment);
                } catch(\PayPro\Exception\ApiErrorException $e) {
                    PayPro_WC_Logger::log("onReturn - Failed to get payment $payment_id from API");
                }
            }

            if(empty($payments))
            {
                PayPro_WC_Logger::log('onReturn - No payments found for this order. Cannot redirect customer.');
            
                status_header(401);
                exit;
            }

            $result = null;

            foreach($payments as $payment)
            {
                if ($payment->state === 'paid' || $payment->state === 'completed') {
                    $result = ['id' => $payment->id, 'state' => 'order_completed'];
                    break;
                } else if ($payment->state === 'canceled') {
                    $result = ['id' => $payment->id, 'state' => 'order_canceled'];
                } else if ($payment->state === 'processing') {
                    $result = ['id' => $payment->id, 'state' => 'order_received'];
                } else {
                    $result = ['id' => $payment->id, 'state' => 'order_failed'];
                }
            }

            $redirect_url = null;

            if($result['state'] === 'order_completed') {
                $order->complete($result['id']);

                PayPro_WC_Logger::log("onReturn - Payment ({$result['id']}) paid for order: {$order->getId()}");
                $redirect_url = $order->getOrderReceivedUrl();
            } else if($result['state'] === 'order_canceled') {
                $order->cancel($result['id']);

                PayPro_WC_Logger::log("onReturn - Payment ({$result['id']}) canceled for order: {$order->getId()}");
                $redirect_url = $order->getCancelOrderUrl();
            } else if($result['state'] === 'order_received') {
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

    private function getOrderFromUrl()
    {
        // Check if the request has valid query params
        if(empty($_GET['order_id']) || empty($_GET['order_key']))
        {
            PayPro_WC_Logger::log("$context: Invalid PayPro return url.");

            status_header(400);
            exit;
        }

        $order_id = intval($_GET['order_id']);
        $order_key = sanitize_text_field($_GET['order_key']);

        // Check if order_id is a known order
        $order = new PayPro_WC_Order($order_id);

        if(!$order->exists())
        {
            PayPro_WC_Logger::log("$context: Order not found - id: $order_id");
            
            status_header(404);
            exit;
        }

        // Check if order_key is valid
        if(!$order->validKey($order_key))
        {
            PayPro_WC_Logger::log("$context: Invalid $order_key for $order_id");

            status_header(401);
            exit;
        }

        return $order;
    }

    private function currentUrl()
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        return "$protocol {$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}";
    }
}
