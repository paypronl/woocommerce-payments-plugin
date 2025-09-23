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
    private $gateway = null;

    /**
     * Constructor
     *
     * @param String $gateway_id ID of the gateway to use for this block.
     */
    public function __construct($gateway_id) {
        $this->name = $gateway_id;
    }

    /**
     * Override method to do initialization.
     */
    public function initialize() { }

    /**
     * Override method to check if the block based checkout is active.
     */
    public function is_active() {
        return $this->gateway()->enabled;
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
        $gateway = $this->gateway();

        return [
            'title'    => $gateway->getTitle(),
            'iconUrl'  => $gateway->getIconUrl(),
            'supports' => $gateway->supports,
        ];
    }

    /**
     * Returns an instance of a gateway based on the ID provided.
     *
     * If a gateway has already been set we return it.
     */
    protected function gateway() {
        if (null !== $this->gateway) {
            return $this->gateway;
        }

        $this->gateway = PayPro_WC_Gateways::getGatewayById($this->name);
        return $this->gateway;
    }
}
