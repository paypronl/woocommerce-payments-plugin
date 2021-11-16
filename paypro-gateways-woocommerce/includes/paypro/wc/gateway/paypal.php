<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Paypal extends PayPro_WC_Gateway
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'paypal/direct';

        $this->gateway_title = __('Paypal', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
