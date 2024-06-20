<?php

defined('ABSPATH') || exit;

/**
 * Class used for getting the PayPro plugin settings.
 */
class PayPro_WC_Settings {
    /**
     * Returns if plugin is in debug mode
     */
    public static function debugMode() {
        return trim(get_option(self::getId('debug-mode'))) === 'yes';
    }

    /**
     * Returns the API key
     */
    public static function apiKey() {
        return trim(get_option(self::getId('api-key')));
    }

    /**
     * Returns the product id
     */
    public static function productId() {
        return intval(trim(get_option(self::getId('product-id'))));
    }

    /**
     * Returns the webhook id
     */
    public static function webhookId() {
        return trim(get_option(self::getId('webhook-id')));
    }

    /**
     * Returns the webhook secret
     */
    public static function webhookSecret() {
        return trim(get_option(self::getId('webhook-secret')));
    }

    /**
     * Returns the payment description
     */
    public static function paymentDescription() {
        return trim(get_option(self::getId('payment-description')));
    }

    /**
     * Returns if plugin has automatic cancellation enabled
     */
    public static function automaticCancellation() {
        return trim(get_option(self::getId('automatic-cancellation'))) === 'yes';
    }

    /**
     * Returns the order status setting for when a payment has been completed
     */
    public static function paymentCompleteStatus() {
        return trim(get_option(self::getId('payment-complete-status')));
    }

    /**
     * Returns the ID for getting the PayPro settings.
     *
     * @param string $setting The key of the setting.
     *
     * @return string The ID used for getting the PayPro settings.
     */
    public static function getId($setting) {
        return PayPro_WC_Plugin::PLUGIN_ID . '_' . trim($setting);
    }
}
