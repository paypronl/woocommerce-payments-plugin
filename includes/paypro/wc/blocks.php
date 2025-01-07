<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

/**
 * Class to setup blocks support for WooCommerce block based checkout.
 */
final class PayPro_WC_Blocks_Support extends AbstractPaymentMethodType {
    /**
     * The PayPro_WC_Gateway to be used
     *
     * @var $gateway
     */
    private $gateway;

    /**
     * Constructor
     *
     * @param PayPro_WC_Gateway_Abstract $gateway       Gateway to setup block support for.
     */
    public function __construct($gateway) {
        $this->gateway       = $gateway;
        $this->name          = $gateway->id;
    }

    /**
     * Override method to do initialization.
     */
    public function initialize() { }

    /**
     * Override method to check if the block based checkout is active.
     */
    public function is_active() {
        return $this->gateway->enabled;
    }

    /**
     * Override method to setup the styles and scripts.
     */
    public function get_payment_method_script_handles() {
        $script_url = PAYPRO_WC_PLUGIN_URL . 'build/index.js';
        $style_url  = PAYPRO_WC_PLUGIN_URL . 'build/index.css';

        $script_asset_path = PAYPRO_WC_PLUGIN_PATH . 'build/index.asset.php';
        $script_asset      = require $script_asset_path;

        wp_enqueue_style(
            'wc-paypro-gateway-blocks-checkout-style',
            $style_url,
            [],
            $script_asset['version']
        );

        wp_register_script(
            'wc-paypro-gateway-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_set_script_translations('wc-paypro-gateway-blocks', 'paypro-gateways-woocommerce', PAYPRO_WC_PLUGIN_PATH . 'languages/');

        return [ 'wc-paypro-gateway-blocks' ];
    }

    /**
     * Override method to pass data to the frontend.
     */
    public function get_payment_method_data() {
        return [
            'title'    => $this->gateway->getTitle(),
            'iconUrl'  => $this->gateway->getIconUrl(),
            'supports' => $this->gateway->supports,
        ];
    }
}
