<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

final class PayPro_WC_Blocks_Support extends AbstractPaymentMethodType {
    private $title;

    private $iconUrl;

    public function __construct($name, $title, $iconUrl) {
        $this->name = $name;
        $this->title = $title;
        $this->iconUrl = $iconUrl;
    }

    public function initialize() {

    }

    public function is_active() {
        return true;
    }

    public function get_payment_method_script_handles() {
        $script_url = PAYPRO_WC_PLUGIN_URL . 'build/index.js';
        $style_url = PAYPRO_WC_PLUGIN_URL . 'build/index.css';

        $script_asset_path = PAYPRO_WC_PLUGIN_PATH . 'build/index.asset.php';
        $script_asset = require($script_asset_path);

        wp_enqueue_style(
            'wc-paypro-gateway-blocks-checkout-style',
            $style_url,
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

        return ['wc-paypro-gateway-blocks'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->title,
            'iconUrl' => $this->iconUrl,
            'issuers' => PayPro_WC_Plugin::$paypro_api->getIdealIssuers()['issuers']
        ];
    }
}
