<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Creditcard extends PayPro_WC_Gateway
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'creditcard';

        $this->gateway_title = __('Creditcard', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
