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
}
