<?php

defined('ABSPATH') || exit;

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
     * Adds a payment hashes to the order post meta
     */
    public function addOrderPaymentHash(WC_Order $order, $payment_hash)
    {
        $order->add_meta_data($this->post_data_key, $payment_hash, false);
    }

    /**
     * Gets all payment hashes for an order from the post meta
     */
    public function getOrderPaymentHashes(WC_Order $order)
    {
        return $order->get_meta($this->post_data_key, false);
    }

    /**
     * Removes all payments hashes for an order from the post meta
     */
    public function removeOrderPaymentHashes(WC_Order $order)
    {
        $order->delete_meta_data($this->post_data_key);
    }

    /**
     * Cancel a WooCommerce order
     */
    public function cancelOrder(WC_Order $order, $payment_hash)
    {
        /* translators: %s contains the payment hash of the PayPro payment */
        $message = sprintf(__('PayPro payment cancelled (%s)', 'paypro-gateways-woocommerce'), $payment_hash);

        if(PayPro_WC_Plugin::$settings->automaticCancellation())
            $order->cancel_order($message);
        else
            $order->add_order_note($message);

        $this->removeOrderPaymentHash($order);
    }

    /**
     * Complete a WooCommerce order
     */ 
    public function completeOrder(WC_Order $order, $payment_hash)
    {
        $status = PayPro_WC_Plugin::$settings->paymentCompleteStatus();
        if(empty($status))
            $status = 'wc-processing';

        /* translators: %s contains the payment hash of the PayPro payment */
        $message = sprintf(__('PayPro payment succeeded (%s)', 'paypro-gateways-woocommerce'), $payment_hash);
        $order->update_status($status, $message);

        wc_reduce_stock_levels($order->get_id());
        $order->payment_complete();

        $this->removeOrderPaymentHashes($order);
    }

    /**
     * Gets the first name
     */
    public function getFirstname($order)
    {
        if(is_a($order, 'WC_Order'))
            $first_name = $order->get_billing_first_name();
        else
            $first_name = NULL;

        return $first_name;
    }

    /**
     * Gets the last name
     */
    public function getLastName($order)
    {
        if(is_a($order, 'WC_Order'))
            $order_key = $order->get_billing_last_name();
        else
            $order_key = NULL;

        return $order_key;
    }

    /**
     * Gets the address
     */
    public function getAddress($order)
    {
        if(is_a($order, 'WC_Order'))
            $address = $order->get_billing_address_1();
        else
            $address = NULL;

        return $address;
    }

    /**
     * Gets the shipping address
     */
    public function getShippingAddress($order)
    {
        if(is_a($order, 'WC_Order'))
            $address = $order->get_shipping_address_1();
        else
            $address = NULL;

        return $address;
    }

    /**
     * Gets the postcode
     */
    public function getPostcode($order)
    {
        if(is_a($order, 'WC_Order'))
            $postcode = $order->get_billing_postcode();
        else
            $postcode = NULL;

        return $postcode;
    }

    /**
     * Gets the shipping postcode
     */
    public function getShippingPostcode($order)
    {
        if(is_a($order, 'WC_Order'))
            $postcode = $order->get_shipping_postcode();
        else
            $postcode = NULL;

        return $postcode;
    }

    /**
     * Gets the city
     */
    public function getCity($order)
    {
        if(is_a($order, 'WC_Order'))
            $city = $order->get_billing_city();
        else
            $city = NULL;

        return $city;
    }

    /**
     * Gets the shipping city
     */
    public function getShippingCity($order)
    {
        if(is_a($order, 'WC_Order'))
            $city = $order->get_shipping_city();
        else
            $city = NULL;

        return $city;
    }

    /**
     * Gets the country
     */
    public function getCountry($order)
    {
        if(is_a($order, 'WC_Order'))
            $country = $order->get_billing_country();
        else
            $country = NULL;

        return $country;
    }

    /**
     * Gets the shipping country
     */
    public function getShippingCountry($order)
    {
        if(is_a($order, 'WC_Order'))
            $country = $order->get_shipping_country();
        else
            $country = NULL;

        return $country;
    }

     /**
     * Gets the phonenumber
     */
    public function getPhonenumber($order)
    {
        if(is_a($order, 'WC_Order'))
            $phonenumber = $order->get_billing_phone();
        else
            $phonenumber = NULL;

        return $phonenumber;
    }

    /**
     * Gets the email
     */
    public function getEmail($order)
    {
        if(is_a($order, 'WC_Order'))
            $email = $order->get_billing_email();
        else
            $email = NULL;

        return $email;
    }
}
