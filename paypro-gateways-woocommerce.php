<?php

defined('ABSPATH') || exit;

/**
 * Plugin Name: PayPro Gateways - WooCommerce
 * Plugin URI: https://www.paypro.nl/
 * Description: With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop.
 * Version: 2.0.2
 * Author: PayPro
 * Author URI: https://www.paypro.nl/
 * Requires at least: 5.0
 * Tested up to: 6.5.3
 * Text Domain: paypro-gateways-woocommerce
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.9.3
 * Requires PHP: 7.2
 */

define('PAYPRO_WC_PLUGIN_FILE', __FILE__);
define('PAYPRO_WC_PLUGIN_BASENAME', plugin_basename(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_PLUGIN_PATH', plugin_dir_path(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_PLUGIN_URL', plugin_dir_url(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_VERSION', '2.0.2');

require_once 'vendor/autoload.php';

load_plugin_textdomain('paypro-gateways-woocommerce', false, 'paypro-gateways-woocommerce/languages');

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
        // blocks.php and settings-page.php are loaded seperately.
        require_once __DIR__ . '/includes/paypro/wc/api.php';
        require_once __DIR__ . '/includes/paypro/wc/helper.php';
        require_once __DIR__ . '/includes/paypro/wc/logger.php';
        require_once __DIR__ . '/includes/paypro/wc/order.php';
        require_once __DIR__ . '/includes/paypro/wc/payment-handler.php';
        require_once __DIR__ . '/includes/paypro/wc/payment-methods.php';
        require_once __DIR__ . '/includes/paypro/wc/plugin.php';
        require_once __DIR__ . '/includes/paypro/wc/settings.php';
        require_once __DIR__ . '/includes/paypro/wc/subscription-handler.php';
        require_once __DIR__ . '/includes/paypro/wc/webhook-handler.php';

        require_once __DIR__ . '/includes/paypro/wc/gateways/abstract.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/afterpay.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/bancontact.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/banktransfer.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/creditcard.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/directdebit.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/ideal.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/paypal.php';
        require_once __DIR__ . '/includes/paypro/wc/gateways/sofort.php';

        PayPro_WC_Plugin::init();
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
        if (WC()->version >= '5.0.0') {
            $error_list .= '<td>WooCommerce version is good</td><td style="color: green;">Ok</td>';
        } else {
            $error_list .= '<td>WooCommerce version (' . WC()->version . ') is wrong, should be >=2.2</td><td style="color: red;">Error</td>';
            $errors      = true;
        }
    } else {
        $error_list .= '<td>WooCommerce plugin is not active</td><td style="color: red;">Error</td>';
        $errors      = true;
    }

    $error_list .= '</tr></table>';

    // Show error page if there are errors.
    if ($errors) {
        $html                 = '<h1><strong>' . $title . '</strong></h1><br />' . $error_list;
        $message              = __('Could not activate plugin WooCommerce PayPro', 'paypro-gateways-woocommerce');
        $allowed_html_entiies = [
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

        wp_die(wp_kses($html, $allowed_html_entiies), esc_html($message), [ 'back_link' => true ]);
        return;
    }
}

register_activation_hook(__FILE__, 'paypro_wc_plugin_activation');

add_action('plugins_loaded', 'paypro_plugin_init');
