<?php
class PayPro_WC_Api
{
	/**
	 * Parses the API url to an order.
	 * Only usable on an WooCommerce API call
	 */
	public function getOrderFromApiUrl()
    {
        // Check if the request has valid query params
        if(!isset($_GET['order_id']) || empty($_GET['order_id']) || !isset($_GET['order_key']) || empty($_GET['order_key']))
        {
            header(' ', true, 400);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Invalid PayPro return url.');
            exit;
        }

        $order_id = $_GET['order_id'];
        $order_key = $_GET['order_key'];

        // Check if order_id is a known order
        $order = PayPro_WC_Plugin::$woocommerce->getOrder($order_id);

        if(!$order)
        {
            header(' ', true, 404);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Order not found - id: ' . $order_key);
            exit;
        }

        // Check if order_key is valid
        if(!$order->key_is_valid($order_key))
        {
            header(' ', true, 401);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Invalid ' . $order_key . ' for ' . $order_id);
            exit;
        }

        return $order;
    }

	/**
	 * Returns a payment hash by order
	 * Only usable on an WooCommerce API call
	 */
    public function getPaymentHashFromOrder($order)
    {
        // Get payment hash
        $payment_hash = PayPro_WC_Plugin::$woocommerce->getOrderPaymentHash($order->id);

        if(!$payment_hash)
        {
            header(' ', true, 401);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Not a valid payment hash found for this order - id: ' . $order_id);
            exit;
        }

        return $payment_hash;
    }

	/**
	 * Get PayPro sale status from payment hash
	 * Only usable on an WooCommerce API call
	 */
    public function getSaleStatusFromPaymentHash($payment_hash)
    {   
        // Get status of this order from PayPro API
        $result = PayPro_WC_Plugin::$paypro_api->getSaleStatus($payment_hash);

        if($result['errors'])
        {
            header(' ', true, 500);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Failed to get sale status from PayPro API - message: ' . $result['message'] . ', payment_hash: ' . $payment_hash);
            exit;
        }

        return $result['data']['current_status'];
    }
}