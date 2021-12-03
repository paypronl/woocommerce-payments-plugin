<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Mastercard extends PayPro_WC_Gateway
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'creditcard/mastercard';

        $this->gateway_title = __('Creditcard', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
