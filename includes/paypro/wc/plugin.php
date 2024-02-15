<?php

defined('ABSPATH') || exit;

class PayPro_WC_Plugin
{
    const PLUGIN_ID = 'paypro-gateways-woocommerce';
    const PLUGIN_TITLE = 'PayPro Gateways - WooCommerce';
    const PLUGIN_VERSION = '2.0.0';

    public static $paypro_api;
    public static $settings;
    public static $woocommerce;
    public static $wc_api;

    private static $gateway_classes = [
        'PayPro_WC_Gateway_Ideal',
        'PayPro_WC_Gateway_Idealqr',
        'PayPro_WC_Gateway_Paypal',
        'PayPro_WC_Gateway_Mistercash',
        'PayPro_WC_Gateway_Afterpay',
        'PayPro_WC_Gateway_BankTransfer',
        'PayPro_WC_Gateway_Sofort',
        'PayPro_WC_Gateway_Mastercard',
        'PayPro_WC_Gateway_Visa',
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

        add_action('woocommerce_api_paypro_return',           [__CLASS__, 'onReturn']);

        add_action('woocommerce_api_paypro_cancel',           [__CLASS__, 'onCancel']);

        add_action('admin_notices',                           [__CLASS__, 'addApiKeyReminder']);

        // Initialize all PayPro classes we need
        self::$settings = new PayPro_WC_Settings();
        self::$woocommerce = new PayPro_WC_Woocommerce();
        self::$wc_api = new PayPro_WC_Api();
        self::$paypro_api = new PayProApiHelper();
        self::$paypro_api->init(self::$settings->apiKey(), self::$settings->testMode());

        self::setupGateways();

        $initialized = true;
    }

    /**
     * Callback function that gets called when PayPro redirects back to the site
     */
    public static function onReturn()
    {
        self::debug(__CLASS__ . ': OnReturn - URL: http' . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

        $order = self::$wc_api->getOrderFromApiUrl();
        $order_id = $order->get_id();

        // Only handle order if it is still pending
        if(self::$woocommerce->hasOrderStatus($order, 'pending'))
        {
            $payment_hashes = self::$wc_api->getPaymentHashesFromOrder($order);
            $sale = self::$wc_api->getSaleStatusFromPaymentHashes($payment_hashes);

            // Check status and do appropiate response
            if(strcasecmp($sale['status'], 'cancelled') === 0)
            {
                self::$woocommerce->cancelOrder($order, $sale['hash']);
                self::debug(__CLASS__ . ': OnReturn - Payment cancelled for order: ' . $order_id);

                wp_safe_redirect($order->get_cancel_order_url());
                exit;
            } 
            else
            {
                if(strcasecmp($sale['status'], 'open') !== 0)
                {
                    self::$woocommerce->completeOrder($order, $sale['hash']);
                    self::debug(__CLASS__ . ': OnReturn - Payment completed for order: ' . $order_id);
                }
                else
                {
                    $order->add_order_note(__('PayPro payment pending (' .  $sale['hash'] . ')'));
                    self::debug(__CLASS__ . ': OnReturn - Payment still open for order: ' . $order_id);
                }

                wp_safe_redirect($order->get_checkout_order_received_url());
                exit;
            }
        }

        self::debug(__CLASS__ . ': OnReturn - Order is not pending, redirect to order received page');
        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }

    /**
     * Callback function that gets called when a customer cancels the payment directly
     */
    public static function onCancel()
    {
        self::debug(__CLASS__ . ': OnCancel - URL: http' . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

        $order = self::$wc_api->getOrderFromApiUrl();
        $order_id = $order->get_id();

        $order->add_order_note(__('PayPro - Customer cancelled payment. Redirected him back to his cart.'));
        self::debug(__CLASS__ . ': OnCancel - Payment cancelled by customer for order: ' . $order_id . '. Redirecting back to cart.');

        wp_safe_redirect(WC_Cart::get_cart_url());
        exit;
    }

    /**
     * Adds all PayPro gateways to the list of gateways
     */
    public static function addGateways(array $gateways)
    {
        return array_merge($gateways, self::$gateway_classes);
    }

    /**
     * Writes a debug line to the WooCommerce logger
     */
    public static function debug($message)
    {
        // Only write log if debug mode enabled
        if(!self::$settings->debugMode()) return;

        // Convert not strings to strings
        if(!is_string($message))
            $message = print_r($message, true);

        $logger = wc_get_logger();
        $context = ['source' => 'paypro-gateways-woocommerce'];

        $logger->debug($message, $context);
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
        if(!self::$settings->apiKey())
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
        $settings_page = new PayPro_WC_Settingspage();
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
