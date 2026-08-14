<?php

defined('ABSPATH') || exit;

/**
 * Plugin Name: PayPro Gateways - WooCommerce
 * Plugin URI: https://www.paypro.nl/
 * Description: With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop.
 * Version: 3.2.3
 * Author: PayPro
 * Author URI: https://www.paypro.nl/
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Text Domain: paypro-gateways-woocommerce
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 10.0.4
 * Requires PHP: 7.2
 */

define('PAYPRO_WC_PLUGIN_FILE', __FILE__);
define('PAYPRO_WC_PLUGIN_BASENAME', plugin_basename(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_PLUGIN_PATH', plugin_dir_path(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_PLUGIN_URL', plugin_dir_url(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_MINIMUM_WC_VERSION', '5.0');
define('PAYPRO_WC_VERSION', '3.2.3');

/**
 * Requires a file, throwing an exception instead of a fatal error if it's missing.
 *
 * @param string $file Absolute path of the file to require.
 */
function paypro_wc_require_or_fail($file) {
    if (!file_exists($file)) {
        throw new \Exception("Required file not found: {$file}");
    }

    require_once $file;
}

/**
 * Logs a failure to load the plugin's files and shows an admin notice, instead of letting
 * the site crash with an uncaught fatal error.
 *
 * @param \Throwable $e The error or exception that was caught.
 */
function paypro_wc_handle_load_failure($e) {
    static $notified = false;

    error_log('PayPro Gateways - WooCommerce failed to load: ' . $e->getMessage());

    if ($notified) {
        return;
    }
    $notified = true;

    add_action(
        'admin_notices',
        function () use ($e) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>PayPro Gateways - WooCommerce</strong> failed to load correctly and has been disabled for this request.
                    Please try reinstalling the plugin. If the problem persists, contact PayPro support.<br />
                    Details: <?php echo esc_html($e->getMessage()); ?>
                </p>
            </div>
            <?php
        }
    );
}

try {
    paypro_wc_require_or_fail(__DIR__ . '/vendor/autoload.php');
} catch (\Throwable $e) {
    paypro_wc_handle_load_failure($e);
    return;
}

/**
 * Entry point of the plugin.
 * Checks if Woocommerce is active, loads classes and initializes the plugin.
 */
function paypro_plugin_init() {
    /**
     * Get the active plugins.
     *
     * @since 1.0.0
     */
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

    if (in_array('woocommerce/woocommerce.php', $active_plugins, true) || class_exists('WooCommerce')) {
        try {
            // blocks.php, settings-page.php, gateways.php and all gateways are loaded seperately.
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/api.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/helper.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/gateways.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/logger.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/order.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/payment-handler.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/plugin.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/settings.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/subscription.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/webhook-handler.php');

            PayPro_WC_Plugin::init();
        } catch (\Throwable $e) {
            paypro_wc_handle_load_failure($e);
        }
    }
}

/**
 * Is called when the plugin gets activated. Checks if the the requirments are met and shows errors
 * if not.
 */
function paypro_wc_plugin_activation() {
    $errors     = false;
    $error_list = '<table style="width: 600px;">';

    // Check if OpenSSL is activated.
    $error_list .= '<tr>';
    if (function_exists('openssl_sign') && defined('OPENSSL_VERSION_TEXT')) {
        $error_list .= '<td>OpenSSL installed</td><td style="color: green;">Ok</td>';
    } else {
        $error_list .= '<td>OpenSSL not installed</td><td><span style="color: red;">Error</td>';
        $errors      = true;
    }
    $error_list .= '</tr>';

    // Check if Curl is activated.
    if (function_exists('curl_init')) {
        $error_list .= '<td>Curl installed</td><td style="color: green;">Ok</td>';
    } else {
        $error_list .= '<td>Curl not installed</td><td><span style="color: red;">Error</td>';
        $errors      = true;
    }

    // Check if the WooCommerce plugin is active.
    $error_list .= '<tr>';
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        $error_list .= '<td>WooCommerce plugin is active</td><td style="color: green;">Ok</td>';
        $error_list .= '</tr><tr>';

        // Check if WooCommerce is the correct version (>= 5.0.0).
        if (version_compare(WC()->version, PAYPRO_WC_MINIMUM_WC_VERSION, '>=')) {
            $error_list .= '<td>WooCommerce version is good</td><td style="color: green;">Ok</td>';
        } else {
            $error_list .= '<td>WooCommerce version (' . WC()->version . ') is wrong, should be >= 5.0</td><td style="color: red;">Error</td>';
            $errors      = true;
        }
    } else {
        $error_list .= '<td>WooCommerce plugin is not active</td><td style="color: red;">Error</td>';
        $errors      = true;
    }

    $error_list .= '</tr></table>';

    // Show error page if there are errors.
    if ($errors) {
        $message              = 'Could not activate PayPro plugin for WooCommerce.<br /><br />';
        $html                 = '<h1><strong>PayPro Gateways - WooCommerce</strong></h1><br />' . $message . $error_list;
        $allowed_html_entities = [
            'h1'     => [],
            'strong' => [],
            'br'     => [],
            'table'  => [
                'style' => [],
            ],
            'tr'     => [],
            'td'     => [
                'style' => [],
            ],
        ];

        wp_die(wp_kses($html, $allowed_html_entities), '', [ 'back_link' => true ]);
        return;
    }
}

register_activation_hook(__FILE__, 'paypro_wc_plugin_activation');

add_action('init', 'paypro_plugin_init');

// We need to load this before the 'init' action and therefore cannot have it in the main plugin file.
add_action('woocommerce_blocks_loaded', function() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        try {
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/blocks.php');
            paypro_wc_require_or_fail(__DIR__ . '/includes/paypro/wc/gateways.php');

            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    foreach (PayPro_WC_Gateways::getGatewayIds() as $gateway_id) {
                        $payment_method_registry->register(new PayPro_WC_Blocks_Support($gateway_id));
                    }
                }
            );
        } catch (\Throwable $e) {
            paypro_wc_handle_load_failure($e);
        }
    }
});

// We need to load this before the 'init' action and therefore cannot have it in the main plugin file.
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PAYPRO_WC_PLUGIN_FILE, true);
    }
});
