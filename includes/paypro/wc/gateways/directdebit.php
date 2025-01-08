<?php

defined('ABSPATH') || exit;

/**
 * Gateway to handle Direct debit on the checkout.
 */
class PayPro_WC_Gateway_DirectDebit extends PayPro_WC_Gateway_Abstract {
    /**
     * Constructor
     */
    public function __construct() {
        $this->pay_method_code        = 'direct-debit';
        $this->supports_subscriptions = true;
        $this->has_fields             = false;

        parent::__construct();
    }

    /**
     * Returns the title of the gateway.
     *
     * @return string Title of the gateway
     */
    public function getTitle() {
        return __('SEPA Direct Debit', 'paypro-gateways-woocommerce');
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
