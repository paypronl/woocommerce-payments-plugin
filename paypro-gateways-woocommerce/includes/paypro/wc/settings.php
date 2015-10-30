<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Settings
{
    /**
     * Returns if plugin is in test mode
     */
    public function testMode()
    {
        return trim(get_option(PayPro_WC_Plugin::getSettingId('test-mode'))) === 'yes';
    }

    /**
     * Returns if plugin is in debug mode
     */
    public function debugMode()
    {
        return trim(get_option(PayPro_WC_Plugin::getSettingId('debug-mode'))) === 'yes';
    }

    /**
     * Returns the API key
     */
    public function apiKey()
    {
        return trim(get_option(PayPro_WC_Plugin::getSettingId('api-key')));
    }

    /**
     * Returns the product id
     */
    public function productId()
    {
        return trim(get_option(PayPro_WC_Plugin::getSettingId('product-id')));
    }

    /**
     * Returns the payment description
     */
    public function paymentDescription()
    {
        return trim(get_option(PayPro_WC_Plugin::getSettingId('payment-description')));
    }

    /**
     * Returns if plugin has automatic cancellation enabled
     */
    public function automaticCancellation()
    {
        return trim(get_option(PayPro_WC_Plugin::getSettingId('automatic-cancellation'))) === 'yes';
    }
}