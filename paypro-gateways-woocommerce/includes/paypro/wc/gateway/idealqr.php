<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Idealqr extends PayPro_WC_Gateway_Abstract
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'ideal_qr';

        $this->gateway_title = __('iDEAL QR', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
