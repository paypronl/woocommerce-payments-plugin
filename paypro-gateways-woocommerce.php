<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Plugin Name: PayPro Gateways - WooCommerce
 * Plugin URI: https://www.paypro.nl/
 * Description: With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop.
 * Version: 1.3.3
 * Author: PayPro
 * Author URI: https://www.paypro.nl/
 * Text Domain: paypro-gateways-woocommerce
 *
 * @author PayPro BV
 */

define('PAYPRO_WC_PLUGIN_FILE', __FILE__);
define('PAYPRO_WC_PLUGIN_PATH', plugin_dir_path(PAYPRO_WC_PLUGIN_FILE));
define('PAYPRO_WC_PLUGIN_URL', plugin_dir_url(PAYPRO_WC_PLUGIN_FILE));

require_once('vendor/autoload.php');
require_once('includes/paypro/wc/autoload.php');

load_plugin_textdomain('paypro-gateways-woocommerce', false, 'paypro-gateways-woocommerce/languages');

/**
 * Entry point of the plugin.
 * Checks if Woocommerce is active, autoloads classes and initializes the plugin.
 */
function paypro_plugin_init()
{
    // Check if WooCommerce is active
    if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option( 'active_plugins'))) || class_exists('WooCommerce')) 
    {
        PayPro_WC_Autoload::register();
        PayPro_WC_Plugin::init();
    }
}

/**
 * Is called when the plugin gets activated. 
 * Checks if the the requirments are met and shows errors if not.
 */ 
function paypro_wc_plugin_activation()
{
    $errors = false;
    $error_list = '<table style="width: 600px;">';

    // Check if OpenSSL is activated
    $error_list .= '<tr>';
    if(function_exists('openssl_sign') && defined('OPENSSL_VERSION_TEXT'))
    {
        $error_list .= '<td>OpenSSL installed</td><td style="color: green;">Ok</td>';
    }
    else
    {
        $error_list .= '<td>OpenSSL not installed</td><td><span style="color: red;">Error</td>';    
        $errors = true;
    }
    $error_list .= '</tr>';

    // Check if Curl is activated
    if(function_exists('curl_init'))
    {
        $error_list .= '<td>Curl installed</td><td style="color: green;">Ok</td>';
    }
    else
    {
        $error_list .= '<td>Curl not installed</td><td><span style="color: red;">Error</td>';   
        $errors = true;
    }

    // Check if the WooCommerce plugin is active
    $error_list .= '<tr>';
    if (is_plugin_active('woocommerce/woocommerce.php'))
    {
        $error_list .= '<td>WooCommerce plugin is active</td><td style="color: green;">Ok</td>';

        $error_list .= '</tr><tr>';

        // Check if WooCommerce is the correct version (>= 2.2)
        if (WC()->version >= '2.2.0')
        {
            $error_list .= '<td>WooCommerce version is good</td><td style="color: green;">Ok</td>';
        }
        else
        {
            $error_list .= '<td>WooCommerce version (' . WC()->version . ') is wrong, should be >=2.2</td><td style="color: red;">Error</td>';
            $errors = true;
        }
    }
    else
    {
        $error_list .= '<td>WooCommerce plugin is not active</td><td style="color: red;">Error</td>';
        $errors = true;
    }

    $error_list .= '</tr></table>';

    // Show error page if there are errors
    if($errors)
    {
        $title = __('Could not activate plugin WooCommerce PayPro', 'paypro-gateways-woocommerce');
        $content = '<h1><strong>' . $title . '</strong></h1><br />' . $error_list;
        wp_die($content, $title, array('back_link' => true));
        return;
    }
}

add_action('woocommerce_blocks_loaded', 'paypro_woocommerce_blocks_support');

function paypro_woocommerce_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once('includes/paypro/wc/blocks.php');

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new PayPro_WC_Blocks_Support('paypro_wc_gateway_ideal', 'iDEAL', PAYPRO_WC_PLUGIN_URL . 'assets/images/paypro_wc_gateway_ideal.png'));
                $payment_method_registry->register(new PayPro_WC_Blocks_Support('paypro_wc_gateway_mistercash', 'Bancontact', PAYPRO_WC_PLUGIN_URL . 'assets/images/paypro_wc_gateway_mistercash.png'));
            }
        );
    }
}

register_activation_hook(__FILE__, 'paypro_wc_plugin_activation');

add_action('init', 'paypro_plugin_init');