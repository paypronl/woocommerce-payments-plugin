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
     * Adds a payment hashes to the order post meta
     */
    public function addOrderPaymentHash($order_id, $payment_hash)
    {
        add_post_meta($order_id, $this->post_data_key, $payment_hash, false);
    }

    /**
     * Gets all payment hashes for an order from the post meta
     */
    public function getOrderPaymentHashes($order_id)
    {
        return get_post_meta($order_id, $this->post_data_key, false);
    }

    /**
     * Removes all payments hashes for an order from the post meta
     */
    public function removeOrderPaymentHashes($order_id)
    {
        delete_post_meta($order_id, $this->post_data_key);
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

        $this->removeOrderPaymentHash($this->getOrderId($order));
    }

    /**
     * Complete a WooCommerce order
     */ 
    public function completeOrder(WC_Order $order, $payment_hash)
    {
        $status = PayPro_WC_Plugin::$settings->paymentCompleteStatus();
        if(empty($status))
            $status = 'wc-processing';

        $order->update_status($status, sprintf(__('PayPro payment succeeded (%s)', 'paypro-gateways-woocommerce'), $payment_hash));
        $this->woocommerce3() ? wc_reduce_stock_levels($this->getOrderId($order)) : $order->reduce_order_stock();
        $order->payment_complete();

        $this->removeOrderPaymentHashes($this->getOrderId($order));
    }

    /**
     * Checks if this is woocommerce 3.0+
     */
    public function woocommerce3()
    {
        if(version_compare(WC()->version, '3.0', '>='))
            return true;
        return false;
    }

    /**
     * Gets the order id
     */
    public function getOrderId($order)
    {
        if(is_a($order, 'WC_Order'))
            $order_id = $this->woocommerce3() ? $order->get_id() : $order->id;
        else
            $order_id = NULL;

        return $order_id;
    }

    /**
     * Gets the order key
     */
    public function getOrderKey($order)
    {
        if(is_a($order, 'WC_Order'))
            $order_key = $this->woocommerce3() ? $order->get_order_key() : $order->order_key;
        else
            $order_key = NULL;

        return $order_key;
    }

    /**
     * Gets the first name
     */
    public function getFirstname($order)
    {
        if(is_a($order, 'WC_Order'))
            $first_name = $this->woocommerce3() ? $order->get_billing_first_name() : $order->billing_first_name;
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
            $order_key = $this->woocommerce3() ? $order->get_billing_last_name() : $order->billing_last_name;
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
            $address = $this->woocommerce3() ? $order->get_billing_address_1() : $order->billing_address_1;
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
            $address = $this->woocommerce3() ? $order->get_shipping_address_1() : $order->shipping_address_1;
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
            $postcode = $this->woocommerce3() ? $order->get_billing_postcode() : $order->billing_postcode;
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
            $postcode = $this->woocommerce3() ? $order->get_shipping_postcode() : $order->shipping_postcode;
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
            $city = $this->woocommerce3() ? $order->get_billing_city() : $order->billing_city;
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
            $city = $this->woocommerce3() ? $order->get_shipping_city() : $order->shipping_city;
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
            $country = $this->woocommerce3() ? $order->get_billing_country() : $order->billing_country;
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
            $country = $this->woocommerce3() ? $order->get_shipping_country() : $order->shipping_country;
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
            $phonenumber = $this->woocommerce3() ? $order->get_billing_phone() : $order->billing_phone;
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
            $email = $this->woocommerce3() ? $order->get_billing_email() : $order->billing_email;
        else
            $email = NULL;

        return $email;
    }

    /**
     * Gets the vat percentage of the order
     */
    public function getVatPercentage($order)
    {
        $vatPercentageSetting = PayPro_WC_Plugin::$settings->vatPercentageSetting();

        if (!is_a($order, 'WC_Order')) {
            return null;
        } elseif ($vatPercentageSetting == 'fixed' && is_numeric(PayPro_WC_Plugin::$settings->vatPercentageFixedValue())) {
            return PayPro_WC_Plugin::$settings->vatPercentageFixedValue();
        } elseif ($vatPercentageSetting == 'highest_in_order') {
            $order_rate_percentages = array_map(
                function( $order_item ) {
                    return $order_item->get_rate_percent();
                },
                $order->get_items( 'tax' )
            );

            return max($order_rate_percentages);
        } else {
            return null;
        }
    }
}
