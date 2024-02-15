<?php

defined('ABSPATH') || exit;

class PayPro_WC_Settingspage extends WC_Settings_Page
{
    public function __construct() {
        $this->id = 'paypro_wc_settings';
        $this->label = __('PayPro', 'paypro-gateways-woocommerce');

        parent::__construct();
    }

    protected function get_settings_for_default_section() {
        $settings = [
            [
                'id'         => $this->getSettingId('title'),
                'type'       => 'title',
                'title'      => __('PayPro settings', 'paypro-gateways-woocommerce'),
                'desc'       => __('The following options are required to use the plugin and are used by all PayPro payment methods', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('api-key'),
                'title'      => __('PayPro API key', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('API key used by the PayPro API.', 'paypro-gateways-woocommerce'), 
            ],
            [
                'id'         => $this->getSettingId('product-id'),
                'title'      => __('PayPro Product ID', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('Product ID to connect a sale to a product. Not required.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('payment-description'),
                'title'      => __('Description', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('Payment description send to PayPro.', 'paypro-gateways-woocommerce'),
                'css'        => 'width: 350px',
            ],
            [
                'id'         => $this->getSettingId('payment-complete-status'),
                'title'      => __('Payment Complete Status', 'paypro-gateways-woocommerce'),
                'type'       => 'select',
                'default'    => 'wc-processing',
                'options'    => wc_get_order_statuses(),
                'desc_tip'   => __('Set the status of the order after a completed payment. Default: Processing', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('automatic-cancellation'),
                'title'      => __('Enable automatic cancellation', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('If a payment is cancelled automatically set the order on cancelled too.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('test-mode'),
                'title'      => __('Enable test mode', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('Puts the API in test mode.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('debug-mode'),
                'title'      => __('Enable debug mode', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('Enables the PayPro plugin to output debug information to the Woocommerce logs.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('sectionend'),
                'type'       => 'sectionend',
            ]
        ];

        return apply_filters('woocommerce_paypro_wc_settings_settings', $settings);
    }

    private function getSettingId($setting) {
        return PayPro_WC_Settings::getId($setting);
    }
}
