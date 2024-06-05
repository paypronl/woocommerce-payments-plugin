<?php

defined('ABSPATH') || exit;

abstract class PayPro_WC_Gateway_Abstract extends WC_Payment_Gateway
{
    protected $default_status;

    protected $display_logo;

    protected $issuer;

    /**
     * Constructs a Payment Gateway
     */ 
    public function __construct()
    {
        $this->plugin_id = 'paypro';
        $this->id = strtolower(get_class($this));
        $this->method_title = 'PayPro - ' . $this->getTitle();

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->display_logo = $this->get_option('display_logo') == 'yes';

        if($this->display_logo)
            $this->icon = $this->getIconUrl();

        $this->description = $this->get_option('description');
        $this->default_status = 'pending';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'addCheckoutStyles'));

        if(!$this->isValid())
            $this->enabled = 'no';
    }

    /**
     * This generates the options fields for specific gateways
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'       => __('Enable/Disable', 'woocommerce-paypro'),
                'type'        => 'checkbox',
                'label'       => sprintf(__('Enable %s', 'woocommerce-paypro'), $this->getTitle()),
                'default'     => 'no'
            ],
            'title' => [
                'title'       => __('Title', 'woocommerce-paypro'),
                'type'        => 'text',
                'description' => sprintf(__('This controls the title which the user sees during checkout. Default <code>%s</code>', 'woocommerce-paypro'), $this->getTitle()),
                'default'     => $this->getTitle(),
                'desc_tip'    => true,
            ],
            'display_logo' => [
                'title'       => __('Display logo', 'woocommerce-paypro'),
                'type'        => 'checkbox',
                'label'       => __('Display logo on checkout page. Default <code>enabled</code>', 'paypro-payments-gateways-woocommerce'),
                'default'     => 'yes'
            ],
            'description' => [
                'title'       => __('Description', 'woocommerce-paypro'),
                'type'        => 'textarea',
                'description' => sprintf(__('Payment method description that the customer will see on your checkout. Default <code>%s</code>', 'woocommerce-paypro'), $this->getDescription()),
                'default'     => $this->getDescription(),
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Overrides the process payment function
     * Here we handle the actual payment
     */
    public function process_payment($order_id)
    {
        // Get order from Woocommerce
        $order = new PayPro_WC_Order($order_id);

        // Check if order is found, otherwise debug and failure
        if(!$order->exists())
        {
            PayPro_WC_Logger::log("$this->id: Could not find order, id: $order_id");
            return ['result' => 'failure'];
        }

        // Update order to default status
        $order->updateStatus($this->default_status, __('Awaiting payment confirmation', 'woocommerce-paypro'));

        $product_id = PayPro_WC_Settings::productId();

        // Create or find a PayPro customer
        // TODO: Handle WooCommerce guest and customer accounts

        $result = $order->updateCustomer();

        if (!$result) {
            PayPro_WC_Logger::log("$this->id: Failed to update customer ({$order->getCustomerId()}) for order $order_id");
            return ['result' => 'failure'];
        }

        // Create payment
        $payment_data = $order->getPaymentData();
        $payment_data['pay_methods'] = [$this->issuer];
        $payment_data = array_merge_recursive($payment_data, $this->getAdditionalPaymentData());

        try {
            $payment = PayPro_WC_Plugin::$paypro_api->createPayment($payment_data);
        } catch(\PayPro\Exception\ApiErrorException $e) {
            PayPro_WC_Logger::log("$this->id: Failed to create payment for order $order_id - Message: {$e->getMessage()}");
            // wc_add_notice($error_msg, 'error');

            return ['result' => 'failure'];
        }

        // Succesfull payment created, lets log it and add a note to the payment
        PayPro_WC_Logger::log("$this->id: Payment created for $order_id - Payment ID: $payment->id");

        // Set order information
        $message = sprintf(__('%s payment in process (%s)', 'woocommerce-paypro'), $this->method_title, $payment->id);
        $order->addOrderNote($message);
        $order->addPayment($payment->id);

        return ['result' => 'success', 'redirect' => esc_url_raw($payment->links['checkout'])];
    }

    /**
     * Adds the checkout styles to the checkout page
     */
    public function addCheckoutStyles() {
        wp_register_style(
            'paypro-checkout',
            PAYPRO_WC_PLUGIN_URL . 'assets/styles/paypro-checkout.css',
            [],
            PAYPRO_WC_VERSION
        );

        wp_enqueue_style('paypro-checkout');
    }

    /**
     * Checks if the gateway is valid and ready for use
     */
    public function isValid()
    {
        if((!PayPro_WC_Settings::apiKey()) && $this->enabled === 'yes')
        {
            PayPro_WC_Logger::log($this->id . ': Cannot enable PayPro payment methods without setting the API key first.');
            return false;
        }

        return true;
    }

    /**
     * Returns the icon url for this gateway
     */
    public function getIconUrl()
    {
        return PAYPRO_WC_PLUGIN_URL . 'assets/images/' . $this->id . '.png';
    }

    protected function getAdditionalPaymentData()
    {
        return [];
    }

    abstract public function getTitle();  

    abstract public function getDescription();
}
