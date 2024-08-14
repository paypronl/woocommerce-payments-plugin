<?php

defined('ABSPATH') || exit;

/**
 * Adds static helper methods.
 */
class PayPro_WC_Helper {
    /**
     * Generates the current URL from server env variables.
     *
     * @return string The current URL.
     */
    public static function currentUrl() {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $host     = esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'] ?? ''));
        $uri      = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));

        return "$protocol {$host}{$uri}";
    }

    /**
     * Checks if WooCommerce subscription plugin is active
     *
     * @return boolean If subscription plugin is enabled.
     */
    public static function subscriptionsEnabled() {
        return class_exists('WC_Subscriptions') && class_exists('WC_Subscription');
    }

    /**
     * Transforms decimal WC amount to cents.
     *
     * @param string $amount Decimal amount string.
     *
     * @return int Amount in cents.
     */
    public static function decimalToCents($amount) {
        return (int) round($amount * 100);
    }

    /**
     * Returns the PayPro recurring pay method for the PayPro pay method
     *
     * @param string $pay_method The PayPro pay method.
     *
     * @return string The PayPro recurring pay method
     */
    public static function getRecurringPayMethod($pay_method) {
        return [
            'ideal'         => 'direct-debit',
            'bank-transfer' => 'direct-debit',
            'klarna-paynow' => 'direct-debit',
            'direct-debit'  => 'direct-debit',
            'creditcard'    => 'creditcard',
        ][$pay_method];
    }

    /**
     * Returns the WC recurring pay method for the WC pay method
     *
     * @param string $payment_method The WC pay method.
     *
     * @return string The WC recurring pay method.
     */
    public static function getWCRecurringPayMethod($payment_method) {
        return [
            'paypro_wc_gateway_ideal'        => 'paypro_wc_gateway_directdebit',
            'paypro_wc_gateway_banktransfer' => 'paypro_wc_gateway_directdebit',
            'paypro_wc_gateway_sofort'       => 'paypro_wc_gateway_directdebit',
            'paypro_wc_gateway_directdebit'  => 'paypro_wc_gateway_directdebit',
            'paypro_wc_gateway_creditcard'   => 'paypro_wc_gateway_creditcard',
        ][$payment_method];
    }

    /**
     * Returns he PayPro pay method based on the WC payment method
     *
     * @param string $wc_payment_method The WC payment method.
     *
     * @return string The PayPro pay method.
     */
    public static function getPayProPayMethod($wc_payment_method) {
        return [
            'paypro_wc_gateway_afterpay'     => 'afterpay',
            'paypro_wc_gateway_bancontact'   => 'bancontact',
            'paypro_wc_gateway_banktransfer' => 'bank-transfer',
            'paypro_wc_gateway_creditcard'   => 'creditcard',
            'paypro_wc_gateway_directdebit'  => 'direct-debit',
            'paypro_wc_gateway_ideal'        => 'ideal',
            'paypro_wc_gateway_paypal'       => 'paypal',
            'paypro_wc_gateway_sofort'       => 'klarna-paynow',
        ][$wc_payment_method];
    }
}
