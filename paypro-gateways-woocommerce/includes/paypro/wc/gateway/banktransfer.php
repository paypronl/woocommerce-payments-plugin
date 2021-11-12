<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_BankTransfer extends PayPro_WC_Gateway_Abstract
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'banktransfer/sepa';

        $this->gateway_title = __('Bank Transfer', 'paypro-gateways-woocommerce');

        parent::__construct();
    }
}
