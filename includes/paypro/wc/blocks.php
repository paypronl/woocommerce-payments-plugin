<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

final class PayPro_WC_Blocks_Support extends AbstractPaymentMethodType {
    protected $name = 'paypro';

    public function initialize() {

    }

    public function is_active() {
        return true;
    }

    public function get_payment_method_script_handles() {

    }

    public function get_payment_method_data() {
        return [];
    }
}
