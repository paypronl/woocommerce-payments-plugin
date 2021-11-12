<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Visa extends PayPro_WC_Gateway_Abstract
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'creditcard/visa';

        $this->gateway_title = __('Visa', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
