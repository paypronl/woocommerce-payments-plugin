<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Plugin
{
    const PLUGIN_ID = 'paypro-gateways-woocommerce';
    const PLUGIN_TITLE = 'PayPro Gateways - WooCommerce';
    const PLUGIN_VERSION = '1.0.0';

    public static $paypro_gateways = array(
        'PayPro_WC_Gateway_Ideal',
        'PayPro_WC_Gateway_Paypal',
        'PayPro_WC_Gateway_Mistercash',
        'PayPro_WC_Gateway_Afterpay',
        'PayPro_WC_Gateway_BankTransfer',
        'PayPro_WC_Gateway_Sofort',
        'PayPro_WC_Gateway_Mastercard',
        'PayPro_WC_Gateway_Visa',
    );

    public static $paypro_api;
    public static $settings;
    public static $woocommerce;
    public static $wc_api;

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
        add_filter('woocommerce_payment_gateways_settings',   array(__CLASS__, 'addSettingsFields'));

        add_filter('woocommerce_payment_gateways',            array(__CLASS__, 'addGateways'));

        add_action('woocommerce_api_paypro_return',           array(__CLASS__, 'onReturn'));

        add_action('woocommerce_api_paypro_cancel',           array(__CLASS__, 'onCancel'));

        add_action('admin_notices',                           array(__CLASS__, 'addApiKeyReminder'));

        // Initialize all PayPro classes we need
        self::$settings = new PayPro_WC_Settings();
        self::$woocommerce = new PayPro_WC_Woocommerce();
        self::$wc_api = new PayPro_WC_Api();
        self::$paypro_api = new PayProApiHelper();
        self::$paypro_api->init(self::$settings->apiKey(), self::$settings->testMode());

        $initialized = true;
    }

    /**
     * Callback function that gets called when PayPro redirects back to the site
     */
    public static function onReturn()
    {
        self::debug(__CLASS__ . ': OnReturn - URL: http' . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

        $order = self::$wc_api->getOrderFromApiUrl();

        // Only handle order if it is still pending
        if(self::$woocommerce->hasOrderStatus($order, 'pending'))
        {
            $payment_hash = self::$wc_api->getPaymentHashFromOrder($order);
            $payment_status = self::$wc_api->getSaleStatusFromPaymentHash($payment_hash);

            // Check status and do appropiate response
            if(strcasecmp($payment_status, 'cancelled') === 0)
            {
                self::$woocommerce->cancelOrder($order, $payment_hash);
                self::debug(__CLASS__ . ': OnReturn - Payment cancelled for order: ' . $order->id);

                wp_safe_redirect($order->get_cancel_order_url());
                exit;
            } 
            else
            {
                if(strcasecmp($payment_status, 'open') !== 0)
                {
                    self::$woocommerce->completeOrder($order, $payment_hash);
                    self::debug(__CLASS__ . ': OnReturn - Payment completed for order: ' . $order->id);
                }
                else
                {
                    $order->add_order_note(__('PayPro payment pending (' .  $payment_hash . ')'));
                    self::debug(__CLASS__ . ': OnReturn - Payment still open for order: ' . $order->id);
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

        $order->add_order_note(__('PayPro - Customer cancelled payment. Redirected him back to his cart.'));
        self::debug(__CLASS__ . ': OnCancel - Payment cancelled by customer for order: ' . $order->id . '. Redirecting back to cart.');

        wp_safe_redirect(WC_Cart::get_cart_url());
        exit;
    }

    /**
     * Adds all PayPro gateways to the list of gateways
     */
    public static function addGateways (array $gateways)
    {
        return array_merge($gateways, self::$paypro_gateways);
    }

    /**
     * Adds plugin settings to the checkout options
     */
    public static function addSettingsFields(array $settings)
    {
        $paypro_settings = array(
            array(
                'id'         => self::getSettingId('title'),
                'title'      => __('PayPro settings', 'paypro-gateways-woocommerce'),
                'type'       => 'title',
                'desc'       => __('The following options are required to use the plugin and are used by all PayPro payment methods', 'paypro-gateways-woocommerce'),
            ),
            array(
                'id'         => self::getSettingId('api-key'),
                'title'      => __('PayPro API key', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('API key used by the PayPro API.', 'paypro-gateways-woocommerce'), 
            ),
            array(
                'id'         => self::getSettingId('product-id'),
                'title'      => __('PayPro Product ID', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('Product ID to connect a sale to a product. Not required.', 'paypro-gateways-woocommerce'), 
            ),
            array(
                'id'         => self::getSettingId('payment-description'),
                'title'      => __('Description', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('Payment description send to PayPro.', 'paypro-gateways-woocommerce'),
                'css'        => 'width: 350px',
            ),
            array(
                'id'         => self::getSettingId('automatic-cancellation'),
                'title'      => __('Enable automatic cancellation', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('If a payment is cancelled automatically set the order on cancelled too.', 'paypro-gateways-woocommerce'),
            ),
            array(
                'id'         => self::getSettingId('test-mode'),
                'title'      => __('Enable test mode', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('Puts the API in test mode.', 'paypro-gateways-woocommerce'),
            ),
            array(
                'id'         => self::getSettingId('debug-mode'),
                'title'      => __('Enable debug mode', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('Enables the PayPro plugin to output debug information to the Woocommerce logs.', 'paypro-gateways-woocommerce'),
            ),
            array(
                'id'         => self::getSettingId('sectionend'),
                'type'       => 'sectionend',
            ),
        );

        return self::mergeSettings($settings, $paypro_settings);
    }

    /**
     * Returns a setting ID by its name
     */
    public static function getSettingId ($setting)
    {
        return PayPro_WC_Plugin::PLUGIN_ID . '_' . trim($setting);
    }

    /**
     * Writes a debug line to the WooCommerce logger
     */
    public static function debug($message)
    {
        // Convert not strings to strings
        if(!is_string($message))
            $message = print_r($message, true);

        // Only write log if debug mode enabled
        if(self::$settings->debugMode())
        {
            static $logger;

            if(empty($logger))
                $logger = new WC_Logger();

            $logger->add(self::PLUGIN_ID . '-' . date('Y-m-d'), $message);
        }
    }

    /** 
     * Merge the checkout settings with the PayPro settings
     */ 
    protected static function mergeSettings(array $settings, array $paypro_settings)
    {
        $insert_after_index = NULL;
        // Find payment gateway options index
        foreach ($settings as $index => $setting) {
            if (isset($setting['id']) && $setting['id'] == 'payment_gateways_options'
                && (!isset($setting['type']) || $setting['type'] != 'sectionend')
            ) {
                $insert_after_index = $index;
                break;
            }
        }
        // Payment gateways setting found
        if ($insert_after_index !== NULL)
        {
            // Insert PayPro settings before payment gateways setting
            array_splice($settings, $insert_after_index, 0, $paypro_settings);
        }
        else
        {
            // Append PayPro settings
            $settings = array_merge($settings, $paypro_settings);
        }
        return $settings;
    }

    /**
     * Get plugin URL
     */
    public static function getPluginUrl ($path = '')
    {
        return untrailingslashit(plugins_url($path, plugin_basename(self::PLUGIN_ID . '/' . self::PLUGIN_ID . '.php')));
    }

    public static function addApiKeyReminder()
    {
        if(empty(self::$settings->apiKey()))
        {
            echo sprintf(
                '<div class="error"><p><strong>PayPro</strong> - %s</p></div>', 
                __('PayPro API key not set. PayPro payment methods will not be displayed in the checkout process.', 
                    'paypro-gateways-woocommerce')
            );
        }
    }
}
