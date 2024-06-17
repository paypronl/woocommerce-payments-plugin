<?php

defined('ABSPATH') || exit;

/**
 * Gateway to handle Bancontact on the checkout.
 */
class PayPro_WC_Gateway_Bancontact extends PayPro_WC_Gateway_Abstract {
    /**
     * Constructor
     */
    public function __construct() {
        $this->supports = [
            'products',
        ];

        $this->issuer     = 'bancontact';
        $this->has_fields = false;

        parent::__construct();
    }

    /**
     * Returns the title of the gateway.
     *
     * @return string Title of the gateway
     */
    public function getTitle() {
        return __('Bancontact', 'paypro-gateways-woocommerce');
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
