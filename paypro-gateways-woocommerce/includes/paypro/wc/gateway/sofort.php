<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Sofort extends PayPro_WC_Gateway
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'sofort/physical';

        $this->gateway_title = __('Sofort', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
