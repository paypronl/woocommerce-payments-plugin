<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Mistercash extends PayPro_WC_Gateway
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'bancontact/mrcash';

        $this->gateway_title = __('Bancontact', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
