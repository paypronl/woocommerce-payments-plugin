<?php
class PayPro_WC_Api
{
    // EDITABLE API KEYS
    // woocommerce_product_id => paypro_api_key
    const PRODUCT_API_KEYS = [
        11 => '83827ac0eaa430a39d94e49eb5376b26',
        12 => '6a8517e80fda90c04d39f982f992541e'
    ];
    // END EDITABLE API KEYS

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

        $order_id = intval($_GET['order_id']);
        $order_key = sanitize_text_field($_GET['order_key']);

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
     * Get API key based on order ID. Will return the specific API key for the first product from the order.
     * If empty, returns the API key from the settings.
     */

    public function getApiKeyFromOrder($order)
    {
        $productId = current(
            array_map(
                function($orderItem) {
                    return $orderItem['product_id'];
                },
                $order->get_items()
            )
        );

        return self::PRODUCT_API_KEYS[$productId] ?? null;
    }

    /**
     * Returns all payment hashes by order
     * Only usable on an WooCommerce API call
     */
    public function getPaymentHashesFromOrder($order)
    {
        $order_id = PayPro_WC_Plugin::$woocommerce->getOrderId($order);

        // Get payment hash
        $payment_hashes = PayPro_WC_Plugin::$woocommerce->getOrderPaymentHashes($order_id);

        if(empty($payment_hashes))
        {
            header(' ', true, 401);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Not a valid payment hash found for this order - id: ' . $order_id);
            exit;
        }

        return $payment_hashes;
    }

    /**
     * Get PayPro sale status from payment hash
     * Only usable on an WooCommerce API call
     */
    public function getSaleStatusFromPaymentHashes($payment_hashes)
    {   
        // Get status of this order from PayPro API
        $results = array();
        foreach($payment_hashes as $payment_hash)
        {
            $result = PayPro_WC_Plugin::$paypro_api->getSaleStatus($payment_hash);
            if($result['errors'])
                PayPro_WC_Plugin::debug(__CLASS__ . ': Failed to get sale status from PayPro API - message: ' . $result['message'] . ', payment_hash: ' . $payment_hash);
            else
                array_push($results, array('hash' => $payment_hash, 'status' => $result['data']['current_status']));
        }

        // Could not get status, so throw and log
        if(empty($results))
        {
            header(' ', true, 500);
            PayPro_WC_Plugin::debug(__CLASS__ . ': Could not get the status for these payment hashes: ' . implode(', ', $payment_hashes));
            exit;
        }

        return $this->determineSaleStatus($results);
    }

    /**
     * Determines what the order status should be, based on the payment statuses
     * Returns an array with the status and the payment_hash
     */
    private function determineSaleStatus($sale_statuses)
    {
        foreach($sale_statuses as $sale)
        {
            if($sale['status'] != 'open')
                return array('hash' => $sale['hash'], 'status' => 'completed');
            else
                $result = $sale;
        }

        return empty($result) ? array('status' => 'open', 'hash' => 'unknown') : $result;
    }
}
