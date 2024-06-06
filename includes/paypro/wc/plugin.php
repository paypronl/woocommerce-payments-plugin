<?php

defined('ABSPATH') || exit;

class PayPro_WC_Plugin
{
    const PLUGIN_ID = 'paypro-gateways-woocommerce';
    const PLUGIN_TITLE = 'PayPro Gateways - WooCommerce';
    const PLUGIN_VERSION = '2.0.2';

    public static $paypro_api;

    private static $gateway_classes = [
        'PayPro_WC_Gateway_Ideal',
        'PayPro_WC_Gateway_Paypal',
        'PayPro_WC_Gateway_Bancontact',
        'PayPro_WC_Gateway_Afterpay',
        'PayPro_WC_Gateway_BankTransfer',
        'PayPro_WC_Gateway_Sofort',
        'PayPro_WC_Gateway_Creditcard',
    ];

    private static $gateways = [];

    private static $initialized = false;

    private function __construct() {}

    /**
     * Initalizes the plugin
     */
    public static function init() 
    {
        if(self::$initialized)
            return;

        // Add filters and actions
        add_filter('plugin_action_links',                     [__CLASS__, 'addSettingsActionLink']);

        add_action('before_woocommerce_init',                 [__CLASS__, 'setupHPOSCompatibility']);

        add_filter('woocommerce_payment_gateways',            [__CLASS__, 'addGateways']);

        add_action('woocommerce_blocks_loaded',               [__CLASS__, 'setupBlockSupport']);

        add_filter('woocommerce_get_settings_pages',          [__CLASS__, 'setupSettingsPage']);

        add_action('admin_notices',                           [__CLASS__, 'addApiKeyReminder']);

        // Setup all classes
        self::$paypro_api = new PayProApiHelper();

        if (PayPro_WC_Settings::apiKey()) {
            self::$paypro_api->init(PayPro_WC_Settings->apiKey());
        }

        $payment_handler = new PayPro_WC_PaymentHandler();
        $payment_handler->init();

        $webhook_handler = new PayPro_WC_WebhookHandler();
        $webhook_handler->init();

        self::setupGateways();

        $initialized = true;
    }

    /**
     * Adds all PayPro gateways to the list of gateways
     */
    public static function addGateways(array $gateways)
    {
        return array_merge($gateways, self::$gateway_classes);
    }

    /**
     * Get plugin URL
     */
    public static function getPluginUrl($path = '')
    {
        return PAYPRO_WC_PLUGIN_URL . $path;
    }

    /**
     * Shows a reminder when the API key is not set.
     */
    public static function addApiKeyReminder()
    {
        if(!PayPro_WC_Settings::apiKey())
        {
            echo sprintf(
                '<div class="error"><p><strong>PayPro</strong> - %s</p></div>', 
                __('PayPro API key not set. PayPro payment methods will not be displayed in the checkout process.', 
                    'paypro-gateways-woocommerce')
            );
        }
    }

    /**
     * Enables WooCommerce blocks support for our gateways
     */
    public static function setupBlockSupport() {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once('blocks.php');

            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    foreach(self::$gateways as $gateway) {
                        $payment_method_registry->register(new PayPro_WC_Blocks_Support($gateway));
                    }
                }
            );
        }
    }

    /**
     * Adds the plugin settings to the WooCommerce settings
     */
    public static function setupSettingsPage($settings) {
        $settings_page = include 'settings-page.php';
        $settings[] = $settings_page;

        return $settings;
    }

    /**
     *  Adds our settings link to the plugin links
     */
    public static function addSettingsActionLink($links) {
        $plugin_links = [
            '<a href="admin.php?page=wc-settings&tab=paypro_wc_settings">' . esc_html__('Settings', 'paypro-gateways-woocommerce') . '</a>'
        ];

        return array_merge($links, $plugin_links);
    }

    /**
     * Declare that the plugins supports HPOS
     */
    public static function setupHPOSCompatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PAYPRO_WC_PLUGIN_FILE, true);
        }
    }

    private static function setupGateways() {
        foreach(self::$gateway_classes as $gateway_class) {
            self::$gateways[] = new $gateway_class();
        }
    }
}
