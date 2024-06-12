<?php

defined('ABSPATH') || exit;

/**
 * Gateway to handle AfterPay on the checkout.
 */
class PayPro_WC_Gateway_Afterpay extends PayPro_WC_Gateway_Abstract {
    /**
     * Constructor
     */
    public function __construct() {
        $this->supports = [
            'products',
        ];

        $this->issuer     = 'afterpay';
        $this->has_fields = false;

        parent::__construct();
    }

    /**
     * Returns the title of the gateway.
     *
     * @return string Title of the gateway
     */
    public function getTitle() {
        return __('Riverty', 'paypro-gateways-woocommerce');
    }

    /**
     * Returns the description of the gateway.
     *
     * @return string Description of the gateway
     */
    public function getDescription() {
        return '';
    }
}
