<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Afterpay extends PayPro_WC_Gateway
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'afterpay/giro';

        $this->gateway_title = __('Afterpay', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
