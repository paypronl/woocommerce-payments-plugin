<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

abstract class PayPro_WC_Gateway_Abstract extends WC_Payment_Gateway
{
    protected $default_title;

    protected $default_description;

    protected $default_logo;

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

        add_action('woocommerce_api_' . $this->id, array($this, 'callback'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        if(!$this->isValid())
            $this->enabled = 'no';
    }

    /**
     * This generates the options fields for specific gateways
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'woocommerce-paypro'),
                'type'        => 'checkbox',
                'label'       => sprintf(__('Enable %s', 'woocommerce-paypro'), $this->getTitle()),
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'woocommerce-paypro'),
                'type'        => 'text',
                'description' => sprintf(__('This controls the title which the user sees during checkout. Default <code>%s</code>', 'woocommerce-paypro'), $this->getTitle()),
                'default'     => $this->getTitle(),
                'desc_tip'    => true,
            ),
            'display_logo' => array(
                'title'       => __('Display logo', 'woocommerce-paypro'),
                'type'        => 'checkbox',
                'label'       => __('Display logo on checkout page. Default <code>enabled</code>', 'paypro-payments-gateways-woocommerce'),
                'default'     => 'yes'
            ),
            'description' => array(
                'title'       => __('Description', 'woocommerce-paypro'),
                'type'        => 'textarea',
                'description' => sprintf(__('Payment method description that the customer will see on your checkout. Default <code>%s</code>', 'woocommerce-paypro'), $this->getDescription()),
                'default'     => $this->getDescription(),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Overrides the process payment function
     * Here we handle the actual payment
     */
    public function process_payment($order_id)
    {
        // Get order from Woocommerce
        $order = PayPro_WC_Plugin::$woocommerce->getOrder($order_id);

        // Check if order is found, otherwise debug and failure
        if(empty($order))
        {
            PayPro_WC_Plugin::debug($this->id . ': Could not find order, id: ' . $order_id);
            return array('result' => 'failure');
        }

        // Update order to default status
        $order->update_status($this->default_status, __('Awaiting payment confirmation', 'woocommerce-paypro'));
        
        $paymentDescription = PayPro_WC_Plugin::$settings->paymentDescription();
        $product_id = PayPro_WC_Plugin::$settings->productId();

        if(empty($paymentDescription))
            $paymentDescription = 'Order ' . $order->id;

        // Set the order variables for PayPro
        $data = array(
            'pay_method'        => $this->getSelectedIssuer(),
            'amount'            => round($order->get_total() * 100),
            'description'       => $paymentDescription,
            'return_url'        => $this->getReturnUrl($order),
            'postback_url'      => $this->getCallbackUrl($order),
            'cancel_url'        => $this->getCancelUrl($order),
            'custom'            => $order->id . '|' . $order->order_key,
            'consumer_name'     => $order->billing_first_name . ' ' . $order->billing_last_name,
            'consumer_address'  => $order->billing_address_1,
            'consumer_postal'   => $order->billing_postcode,
            'consumer_city'     => $order->billing_city,
            'consumer_country'  => $order->billing_country,
            'consumer_phoneno'  => $order->billing_phone,
            'consumer_email'    => $order->billing_email
        );

        // Add product_id if the setting is set
        if(!empty($product_id))
            $data['product_id'] = (int)$product_id;

        // Call PayPro API to create a payment
        $result = PayPro_WC_Plugin::$paypro_api->createPayment($data);

        // If there is an error log it
        if($result['errors'] === true)
        {
            PayPro_WC_Plugin::debug($this->id . ': Failed to create payment for order ' . $order->id . ' - Message: ' .$result['message']);
            return array('result' => 'failure');
        }

        // Succesfull payment created, lets log it and add a note to the payment
        PayPro_WC_Plugin::debug($this->id . ': Payment created for ' . $order->id . ' - Payment hash: ' . $result['data']['payment_hash']);

        // Set order information
        $order->add_order_note(sprintf(__('%s payment in process (%s)', 'woocommerce-paypro'), $this->method_title, $result['data']['payment_hash']));
        PayPro_WC_Plugin::$woocommerce->setOrderPaymentHash($order->id, $result['data']['payment_hash']);

        return array('result' => 'success', 'redirect' => $result['data']['payment_url']);
    }

    /**
     * Callback that gets called when the postback URL is called
     */
    public function callback()
    {
        PayPro_WC_Plugin::debug($this->id . ': Callback - URL: http' . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

        // Get order from 
        $order = PayPro_WC_Plugin::$wc_api->getOrderFromApiUrl();

        // Only handle order if it is still pending
        if(PayPro_WC_Plugin::$woocommerce->hasOrderStatus($order, 'pending'))
        {
            $payment_hash = PayPro_WC_Plugin::$wc_api->getPaymentHashFromOrder($order);
            $payment_status = PayPro_WC_Plugin::$wc_api->getSaleStatusFromPaymentHash($payment_hash);

            // Check status and do appropiate response
            if(strcasecmp($payment_status, 'cancelled') === 0)
            {
                PayPro_WC_Plugin::$woocommerce->cancelOrder($order, $payment_hash);
                PayPro_WC_Plugin::debug($this->id . ': Callback - Payment cancelled for order: ' . $order->id);
            }
            else
            {
                if(strcasecmp($payment_status, 'open') !== 0)
                {
                    PayPro_WC_Plugin::$woocommerce->completeOrder($order, $payment_hash);
                    PayPro_WC_Plugin::debug($this->id . ': Callback - Payment completed for order: ' . $order->id);
                }
                else
                {
                    $order->add_order_note(__('PayPro payment pending (' .  $payment_hash . ')'));
                    PayPro_WC_Plugin::debug($this->id . ': Callback - Payment still open for order: ' . $order->id);
                }
            }

            return;
        }

        PayPro_WC_Plugin::debug($this->id . ': Callback - Order is not pending, so leaving it alone');
    }

    /**
     * Checks if the gateway is valid and ready for use
     */
    protected function isValid()
    {
        PayPro_WC_Plugin::debug(PayPro_WC_Plugin::$settings->apiKey());
        PayPro_WC_Plugin::debug($this->enabled);

        if((!PayPro_WC_Plugin::$settings->apiKey()) && $this->enabled === 'yes')
        {
            PayPro_WC_Plugin::debug($this->id . ': Cannot enable PayPro payment methods without setting the API key first.');
            return false;
        }

        return true;
    }

    /**
     * Returns the icon url for this gateway
     */
    protected function getIconUrl()
    {
        return PayPro_WC_Plugin::getPluginUrl('assets/images/' . $this->id . '.png');
    }

    /**
     * Return the selected issuer
     */
    protected function getSelectedIssuer()
    {
        $issuer_id = PayPro_WC_Plugin::PLUGIN_ID . '_issuer_' . $this->id;
        if(!empty($_POST[$issuer_id]))
            return $_POST[$issuer_id];
        elseif(!empty($this->issuer))
            return $this->issuer;
        else
            return NULL;
    }

    /**
     * Returns the return url which PayPro calls after the tranaction
     */
    protected function getReturnUrl(WC_Order $order)
    {
        $return_url = WC()->api_request_url('paypro_return');
        return add_query_arg(array('order_id' => $order->id, 'order_key' => $order->order_key), $return_url);
    }

    /**
     * Returns the cancel url which PayPro calls if a customer cancels the transaction
     */
    protected function getCancelUrl(WC_Order $order)
    {
        $cancel_url = WC()->api_request_url('paypro_cancel');
        return add_query_arg(array('order_id' => $order->id, 'order_key' => $order->order_key), $cancel_url);
    }

    /**
     * Returns the callback url used for settings order statuses
     */ 
    protected function getCallbackUrl(WC_Order $order)
    {
        $callback_url = WC()->api_request_url(strtolower(get_class($this)));
        return add_query_arg(array('order_id' => $order->id, 'order_key' => $order->order_key,), $callback_url);
    }

    abstract public function getTitle();  

    abstract public function getDescription();
}
