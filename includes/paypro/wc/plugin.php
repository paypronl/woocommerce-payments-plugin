<?php

defined('ABSPATH') || exit;

/**
 * The main class to initialize the plugin
 */
class PayPro_WC_Plugin {
    const PLUGIN_ID      = 'paypro-gateways-woocommerce';
    const PLUGIN_TITLE   = 'PayPro Gateways - WooCommerce';
    const PLUGIN_VERSION = '3.0.1';

    /**
     * The PayPro API helper class.
     *
     * @var PayPro_WC_Api $paypro_api
     */
    public static $paypro_api;

    /**
     * The iDEAL issuers list
     *
     * @var array $ideal_issuers
     */
    public static $ideal_issuers = [];

    /**
     * All available gateways.
     *
     * @var array $gateway_classes
     */
    public static $gateway_classes = [
        'PayPro_WC_Gateway_Ideal',
        'PayPro_WC_Gateway_Paypal',
        'PayPro_WC_Gateway_Bancontact',
        'PayPro_WC_Gateway_Afterpay',
        'PayPro_WC_Gateway_BankTransfer',
        'PayPro_WC_Gateway_Sofort',
        'PayPro_WC_Gateway_Creditcard',
        'PayPro_WC_Gateway_DirectDebit',
    ];

    /**
     * Array of all the initialized gateway objects.
     *
     * @var array $gateways
     */
    private static $gateways = [];

    /**
     * If the plugin is initialized. Avoids double initializations.
     *
     * @var boolean $initialized
     */
    private static $initialized = false;

    /**
     * If the API key supplied is valid.
     *
     * @var boolean $valid_api_key
     */
    private static $valid_api_key = true;

    /**
     * Private constructor
     */
    private function __construct() {}

    /**
     * Initalizes the plugin
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }

        self::setupApi();

        if (self::apiValid()) {
            $payment_methods_service = new PayPro_WC_PaymentMethods(self::$paypro_api);
            self::$ideal_issuers     = $payment_methods_service->getIdealIssuers();
        }

        self::setupGateways();

        // Add filters and actions.
        add_filter('plugin_action_links_' . PAYPRO_WC_PLUGIN_BASENAME, [ __CLASS__, 'addSettingsActionLink' ]);

        add_action('before_woocommerce_init', [ __CLASS__, 'setupHPOSCompatibility' ]);

        add_filter('woocommerce_payment_gateways', [ __CLASS__, 'addGateways' ]);

        add_action('woocommerce_blocks_loaded', [ __CLASS__, 'setupBlockSupport' ]);

        add_filter('woocommerce_get_settings_pages', [ __CLASS__, 'setupSettingsPage' ]);

        // Setup other classes.
        $payment_handler = new PayPro_WC_PaymentHandler();
        $payment_handler->init();

        $webhook_handler = new PayPro_WC_WebhookHandler(PayPro_WC_Settings::webhookSecret());
        $webhook_handler->init();

        $initialized = true;
    }

    /**
     * Getter to check if the API key is valid.
     */
    public static function apiValid() {
        return self::$valid_api_key;
    }

    /**
     * Adds all PayPro gateways to the list of gateways
     *
     * @param array $gateways Hook to register the gateways.
     */
    public static function addGateways(array $gateways) {
        return array_merge($gateways, self::$gateways);
    }

    /**
     * Add admin notice to the view
     *
     * @param string $level   The level of the admin notice.
     * @param string $message The message to be put in the notice.
     */
    public static function addAdminNotice($level, $message) {
        add_action(
            'admin_notices',
            function () use ($level, $message) {
                ?>
                    <div class="notice <?php echo esc_attr($level); ?>" style="padding: 12px 12px">
                        <strong>PayPro</strong> - <?php echo wp_kses_post($message); ?>
                    </div>
                <?php
            }
        );
    }

    /**
     * Enables WooCommerce blocks support for our gateways
     */
    public static function setupBlockSupport() {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once 'blocks.php';

            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    foreach (self::$gateways as $gateway) {
                        $payment_method_registry->register(new PayPro_WC_Blocks_Support($gateway, self::$ideal_issuers));
                    }
                }
            );
        }
    }

    /**
     * Hook to add the plugin settings to the WC settings
     *
     * @param array $settings The settings array from WC.
     */
    public static function setupSettingsPage($settings) {
        $settings_page = include 'settings-page.php';
        $settings[]    = $settings_page;

        return $settings;
    }

    /**
     * Hook to adds our settings page link to the plugin links.
     *
     * @param array $links The links from WP.
     */
    public static function addSettingsActionLink($links) {
        $plugin_links = [
            '<a href="admin.php?page=wc-settings&tab=paypro_wc_settings">' . esc_html__('Settings', 'paypro-gateways-woocommerce') . '</a>',
        ];

        return array_merge($links, $plugin_links);
    }

    /**
     * Hook to declare that the plugin supports HPOS.
     */
    public static function setupHPOSCompatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PAYPRO_WC_PLUGIN_FILE, true);
        }
    }

    /**
     * Loads all the gateways from the gateway classes array.
     */
    private static function setupGateways() {
        foreach (self::$gateway_classes as $gateway_class) {
            self::$gateways[] = new $gateway_class();
        }
    }

    /**
     * Create the API helper object used to make API calls. Also checks and notifies if the API key
     * is invalid.
     */
    private static function setupApi() {
        if (empty(PayPro_WC_Settings::apiKey())) {
            $message = __(
                'API key not set. PayPro payment methods will not be displayed in the checkout process. You can find your API keys in the <a href="https://app.paypro.nl/developers/api-keys" target="_blank">PayPro Dashboard</a>',
                'paypro-gateways-woocommerce'
            );

            self::$valid_api_key = false;
            self::addAdminNotice('error', $message);
        }

        self::$paypro_api = new PayPro_WC_Api();

        if (PayPro_WC_Settings::apiKey()) {
            try {
                self::$paypro_api->setApiKey(PayPro_WC_Settings::apiKey());
            } catch (\PayPro\Exception\InvalidArgumentException $e) {
                $message = __(
                    'API key is invalid. Make sure you supply a valid PayPro API key. You can find your API keys in the <a href="https://app.paypro.nl/developers/api-keys" target="_blank">PayPro Dashboard</a>',
                    'paypro-gateways-woocommerce'
                );

                self::$valid_api_key = false;
                self::addAdminNotice('error', $message);
            }
        }
    }
}
