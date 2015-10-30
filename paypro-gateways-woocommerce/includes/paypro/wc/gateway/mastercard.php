<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Mastercard extends PayPro_WC_Gateway_Abstract
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'creditcard/mastercard';
        $this->has_fields = FALSE;

        parent::__construct();
    }

    public function getTitle()
    {
        return __('Mastercard', 'paypro-gateways-woocommerce');
    }

    public function getDescription()
    {
        return __('', 'paypro-gateways-woocommerce');
    }
}
