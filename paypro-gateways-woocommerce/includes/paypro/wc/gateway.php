<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway extends WC_Payment_Gateway
{
	/**
	 * panther restapi responses
	 */
	protected const PAYPROAPIRES_NOTSUBSCRIPTED = "Not subscribed to money transfer service";

    protected $default_title;

    protected $default_logo;

    protected $issuer;
    protected $gateway_title = '';
    protected $gateway_description = '';
    public $has_fields = FALSE;

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
            $paymentDescription = 'Order ' . $order->get_order_number();

        // Get all order information
        $order_key = PayPro_WC_Plugin::$woocommerce->getOrderKey($order);
        $first_name = PayPro_WC_Plugin::$woocommerce->getFirstName($order);
        $last_name = PayPro_WC_Plugin::$woocommerce->getLastName($order);
        $address = PayPro_WC_Plugin::$woocommerce->getAddress($order);
        $postcode = PayPro_WC_Plugin::$woocommerce->getPostcode($order);
        $city = PayPro_WC_Plugin::$woocommerce->getCity($order);
        $country = PayPro_WC_Plugin::$woocommerce->getCountry($order);
        $phonenumber = PayPro_WC_Plugin::$woocommerce->getPhonenumber($order);
        $email = PayPro_WC_Plugin::$woocommerce->getEmail($order);
        $shippingAddress = PayPro_WC_Plugin::$woocommerce->getShippingAddress($order);
        $shippingCity = PayPro_WC_Plugin::$woocommerce->getShippingCity($order);
        $shippingCountry = PayPro_WC_Plugin::$woocommerce->getShippingCountry($order);
        $shippingPostcode = PayPro_WC_Plugin::$woocommerce->getShippingPostcode($order);

        // Set the order variables for PayPro
        $data = array(
            'pay_method'        => $this->getSelectedIssuer(),
            'amount'            => round($order->get_total() * 100),
            'description'       => $paymentDescription,
            'return_url'        => $this->getReturnUrl($order),
            'postback_url'      => $this->getCallbackUrl($order),
            'cancel_url'        => $this->getCancelUrl($order),
            'custom'            => $order_id . '|' . $order_key,
            'consumer_name'     => $first_name . ' ' . $last_name,
            'consumer_address'  => $address,
            'consumer_postal'   => $postcode,
            'consumer_city'     => $city,
            'consumer_country'  => $country,
            'consumer_phoneno'  => $phonenumber,
            'consumer_email'    => $email,
            'shipping_address'  => $shippingAddress,
            'shipping_postal'   => $shippingPostcode,
            'shipping_city'     => $shippingCity,
            'shipping_country'  => $shippingCountry
        );

        // Add product_id if the setting is set
        if(is_int($product_id) && $product_id > 0)
            $data['product_id'] = $product_id;

        // Call PayPro API to create a payment
        $result = PayPro_WC_Plugin::$paypro_api->createPayment($data);

        // If there is an error log it
        if($result['errors'])
        {
            PayPro_WC_Plugin::debug($this->id . ': Failed to create payment for order ' . $order_id . ' - Message: ' .$result['message']);

            // display error to check out
            switch ($result['message']) {
                case self::PAYPROAPIRES_NOTSUBSCRIPTED:
                    $error_msg = get_bloginfo('name') . ' ' . __( 'is not subscribed to this payment method, please try a different method.', 'paypro-gateways-woocommerce');
                    break;
                default:
                    $error_msg = __("Could not use this payment method, please try again.", 'paypro-gateways-woocommerce');
                    break;
            }
            wc_add_notice($error_msg,'error');

            return array('result' => 'failure');
        }

        // Sanitize and validate payment hash
        $payment_hash = sanitize_key($result['data']['payment_hash']);
        if(empty($payment_hash) && !strlen($payment_hash) === 40)
        {
            PayPro_WC_Plugin::debug($this->id . ': Invalid payment hash for order ' . $order_id . ' - Payment hash: ' . $payment_hash);
            return array('result' => 'failure');
        }

        // Succesfull payment created, lets log it and add a note to the payment
        PayPro_WC_Plugin::debug($this->id . ': Payment created for ' . $order_id . ' - Payment hash: ' . $payment_hash);

        // Set order information
        $order->add_order_note(sprintf(__('%s payment in process (%s)', 'woocommerce-paypro'), $this->method_title, $payment_hash));
        PayPro_WC_Plugin::$woocommerce->addOrderPaymentHash($order_id, $result['data']['payment_hash']);

        return array('result' => 'success', 'redirect' => esc_url_raw($result['data']['payment_url']));
    }

    /**
     * Callback that gets called when the postback URL is called
     */
    public function callback()
    {
        PayPro_WC_Plugin::debug($this->id . ': Callback - URL: http' . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

        // Get order from 
        $order = PayPro_WC_Plugin::$wc_api->getOrderFromApiUrl();
        $order_id = PayPro_WC_Plugin::$woocommerce->getOrderId($order);

        // Only handle order if it is still pending
        if(PayPro_WC_Plugin::$woocommerce->hasOrderStatus($order, 'pending'))
        {
            $payment_hashes = PayPro_WC_Plugin::$wc_api->getPaymentHashesFromOrder($order);
            $sale = PayPro_WC_Plugin::$wc_api->getSaleStatusFromPaymentHashes($payment_hashes);

            // Check status and do appropiate response
            if(strcasecmp($sale['status'], 'cancelled') === 0)
            {
                PayPro_WC_Plugin::$woocommerce->cancelOrder($order, $sale['hash']);
                PayPro_WC_Plugin::debug($this->id . ': Callback - Payment cancelled for order: ' . $order_id);
            }
            else
            {
                if(strcasecmp($sale['status'], 'open') !== 0)
                {
                    PayPro_WC_Plugin::$woocommerce->completeOrder($order, $sale['hash']);
                    PayPro_WC_Plugin::debug($this->id . ': Callback - Payment completed for order: ' . $order_id);
                }
                else
                {
                    $order->add_order_note(__('PayPro payment pending (' .  $sale['hash'] . ')'));
                    PayPro_WC_Plugin::debug($this->id . ': Callback - Payment still open for order: ' . $order_id);
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
           return strval($_POST[$issuer_id]);
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
        $order_id = PayPro_WC_Plugin::$woocommerce->getOrderId($order);
        $order_key = PayPro_WC_Plugin::$woocommerce->getOrderKey($order);

        $return_url = WC()->api_request_url('paypro_return');
        return add_query_arg(array('order_id' => $order_id, 'order_key' => $order_key), $return_url);
    }

    /**
     * Returns the cancel url which PayPro calls if a customer cancels the transaction
     */
    protected function getCancelUrl(WC_Order $order)
    {
        $order_id = PayPro_WC_Plugin::$woocommerce->getOrderId($order);
        $order_key = PayPro_WC_Plugin::$woocommerce->getOrderKey($order);

        $cancel_url = WC()->api_request_url('paypro_cancel');
        return add_query_arg(array('order_id' => $order_id, 'order_key' => $order_key), $cancel_url);
    }

    /**
     * Returns the callback url used for settings order statuses
     */
    protected function getCallbackUrl(WC_Order $order)
    {
        $order_id = PayPro_WC_Plugin::$woocommerce->getOrderId($order);
        $order_key = PayPro_WC_Plugin::$woocommerce->getOrderKey($order);

        $callback_url = WC()->api_request_url(strtolower(get_class($this)));
        return add_query_arg(array('order_id' => $order_id, 'order_key' => $order_key,), $callback_url);
    }

    public function getTitle() {
        return $this->gateway_title;
    }

    public function getDescription() {
        return $this->gateway_description;
    }
}
