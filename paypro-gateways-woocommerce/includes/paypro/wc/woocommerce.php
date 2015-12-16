<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Woocommerce
{
    var $post_data_key = '_paypro_payment_hash';

    /**
     * Returns a WooCommerce order by id
     */
    public function getOrder($order_id)
    {
        return wc_get_order($order_id);
    }

    /** 
     * Returns order status of a WooCommerce order
     */
    public function getOrderStatus(WC_Order $order)
    {
        return $order->get_status();
    }

    /**
     * Checks if the WooCommerce order has this specific status
     */
    public function hasOrderStatus(WC_Order $order, $status)
    {
        return $order->has_status($status);
    }

    /**
     * Sets a payment hash for an order id
     */
    public function setOrderPaymentHash($order_id, $payment_hash)
    {
        add_post_meta($order_id, $this->post_data_key, $payment_hash, true);
    }

    /**
     * Gets a payment hash by order id
     */
    public function getOrderPaymentHash($order_id)
    {
        return get_post_meta($order_id, $this->post_data_key, true);
    }

    /**
     * Removes an order id from the post meta
     */
    public function removeOrderPaymentHash($order_id)
    {
        delete_post_meta($order_id, $this->post_data_key, true);
    }

    /**
     * Cancel a WooCommerce order
     */
    public function cancelOrder($order, $payment_hash)
    {
        if(PayPro_WC_Plugin::$settings->automaticCancellation())
            $order->cancel_order(sprintf(__('PayPro payment cancelled (%s)', 'paypro-gateways-woocommerce'), $payment_hash));
        else
            $order->add_order_note(sprintf(__('PayPro payment cancelled (%s)', 'paypro-gateways-woocommerce'), $payment_hash));

        $this->removeOrderPaymentHash($order->id);
    }

    /**
     * Complete a WooCommerce order
     */ 
    public function completeOrder(WC_Order $order, $payment_hash)
    {
        $order->update_status('processing', sprintf(__('PayPro payment succeeded (%s)', 'paypro-gateways-woocommerce'), $payment_hash));
        $order->reduce_order_stock();
        $order->payment_complete();

        $this->removeOrderPaymentHash($order->id);
    }
}