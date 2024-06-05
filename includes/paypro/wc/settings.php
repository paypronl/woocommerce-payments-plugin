<?php

defined('ABSPATH') || exit;

class PayPro_WC_Settings
{
    public static function getId($setting) {
        return PayPro_WC_Plugin::PLUGIN_ID . '_' . trim($setting);
    }

    /**
     * Returns if plugin is in test mode
     */
    public static function testMode()
    {
        return trim(get_option(self::getId('test-mode'))) === 'yes';
    }

    /**
     * Returns if plugin is in debug mode
     */
    public static function debugMode()
    {
        return trim(get_option(self::getId('debug-mode'))) === 'yes';
    }

    /**
     * Returns the API key
     */
    public static function apiKey()
    {
        return trim(get_option(self::getId('api-key')));
    }

    /**
     * Returns the product id
     */
    public static function productId()
    {
        return intval(trim(get_option(self::getId('product-id'))));
    }

    /**
     * Returns the payment description
     */
    public static function paymentDescription()
    {
        return trim(get_option(self::getId('payment-description')));
    }

    /**
     * Returns if plugin has automatic cancellation enabled
     */
    public static function automaticCancellation()
    {
        return trim(get_option(self::getId('automatic-cancellation'))) === 'yes';
    }

    /**
     * Returns the order status setting for when a payment has been completed
     */
    public static function paymentCompleteStatus()
    {
        return trim(get_option(self::getId('payment-complete-status')));
    }
}
