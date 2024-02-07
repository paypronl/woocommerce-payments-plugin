<?php

defined('ABSPATH') || exit;

class PayPro_WC_Gateway_Mistercash extends PayPro_WC_Gateway_Abstract
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'bancontact/mrcash';
        $this->has_fields = FALSE;

        parent::__construct();
    }

    public function getTitle()
    {
        return __('Bancontact', 'paypro-gateways-woocommerce');
    }

    public function getDescription()
    {
        return __('', 'paypro-gateways-woocommerce');
    }
}
