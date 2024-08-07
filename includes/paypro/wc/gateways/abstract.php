<?php

defined('ABSPATH') || exit;

/**
 * Abstract class for all plugin gateways.
 */
abstract class PayPro_WC_Gateway_Abstract extends WC_Payment_Gateway {
    /**
     * The default order status of the gateway after a payment has started.
     *
     * @var string $default_status
     */
    protected $default_status;

    /**
     * The URL of the logo.
     *
     * @var string $display_logo
     */
    protected $display_logo;

    /**
     * The pay method code for the PayPro API.
     *
     * @var string $issuer
     */
    protected $issuer;

    /**
     * Indicates if the gateway supports subscriptions.
     *
     * @var boolean $supports_subscriptions
     */
    protected $supports_subscriptions = false;

    /**
     * Indicates if the gateway supports refunds.
     *
     * @var boolean $supports_refunds
     */
    protected $supports_refunds = true;

    /**
     * Constructs a Payment Gateway
     */
    public function __construct() {
        $this->plugin_id    = 'paypro';
        $this->id           = strtolower(get_class($this));
        $this->method_title = 'PayPro - ' . $this->getTitle();

        $this->init_form_fields();
        $this->init_settings();

        $this->initSupports();

        $this->title        = $this->get_option('title');
        $this->display_logo = 'yes' === $this->get_option('display_logo');

        if ($this->display_logo) {
            $this->icon = $this->getIconUrl();
        }

        $this->description    = $this->get_option('description');
        $this->default_status = 'pending';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ]);
        add_action('wp_enqueue_scripts', [ $this, 'addCheckoutStyles' ]);

        if (!$this->isValid()) {
            $this->enabled = 'no';
        }
    }

    /**
     * This generates the options fields for specific gateways
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled'      => [
                'title'   => __('Enable/Disable', 'paypro-gateways-woocommerce'),
                'type'    => 'checkbox',
                /* translators: %s contains the title of the gateway */
                'label'   => sprintf(__('Enable %s', 'paypro-gateways-woocommerce'), $this->getTitle()),
                'default' => 'no',
            ],
            'title'        => [
                'title'       => __('Title', 'paypro-gateways-woocommerce'),
                'type'        => 'text',
                /* translators: %s contains the default title for the gateway */
                'description' => sprintf(__('This controls the title which the user sees during checkout. Default <code>%s</code>', 'paypro-gateways-woocommerce'), $this->getTitle()),
                'default'     => $this->getTitle(),
                'desc_tip'    => true,
            ],
            'display_logo' => [
                'title'   => __('Display logo', 'paypro-gateways-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Display logo on checkout page. Default <code>enabled</code>', 'paypro-gateways-woocommerce'),
                'default' => 'yes',
            ],
            'description'  => [
                'title'       => __('Description', 'paypro-gateways-woocommerce'),
                'type'        => 'textarea',
                /* translators: %s contains the default description for the gateway */
                'description' => sprintf(__('Payment method description that the customer will see on your checkout. Default <code>%s</code>', 'paypro-gateways-woocommerce'), $this->getDescription()),
                'default'     => $this->getDescription(),
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Overrides the process payment function
     * Here we handle the actual payment
     *
     * @param int $order_id ID of the WC order.
     */
    public function process_payment($order_id) {
        // Get order from Woocommerce.
        $order = new PayPro_WC_Order($order_id);

        // Check if order is found, otherwise debug and failure.
        if (!$order->exists()) {
            PayPro_WC_Logger::log("$this->id: Could not find order, id: $order_id");
            return [ 'result' => 'failure' ];
        }

        // Check if order has a subscription and if the gateway supports it.
        if ($order->hasSubscription() && !$this->supportsSubscriptions()) {
            PayPro_WC_Logger::log("$this->id: Subscriptions are not supported for this gateway.");
            return [ 'result' => 'failure' ];
        }

        // Update order to default status.
        $order->updateStatus($this->default_status, __('Awaiting payment confirmation', 'paypro-gateways-woocommerce'));

        $product_id = PayPro_WC_Settings::productId();

        // Create or find a PayPro customer.
        // TODO: Handle WooCommerce guest and customer accounts.
        $result = $order->updateCustomer();

        if (!$result) {
            PayPro_WC_Logger::log("$this->id: Failed to update customer ({$order->getCustomerId()}) for order $order_id");
            return [ 'result' => 'failure' ];
        }

        // Create payment.
        $payment_data                = $order->getPaymentData();
        $payment_data['pay_methods'] = [ $this->issuer ];
        $payment_data                = array_merge_recursive($payment_data, $this->getAdditionalPaymentData());

        if ($order->hasSubscription()) {
            $payment_data = array_merge_recursive(
                $payment_data,
                [ 'setup_mandate' => true ]
            );
        }

        try {
            $payment = PayPro_WC_Plugin::$paypro_api->createPayment($payment_data);
        } catch (\PayPro\Exception\ApiErrorException $e) {
            PayPro_WC_Logger::log("$this->id: Failed to create payment for order $order_id - Message: {$e->getMessage()}");

            $error_message = __('Could not use this payment method, please try again.', 'paypro-gateways-woocommerce');
            wc_add_notice($error_message, 'error');

            return [ 'result' => 'failure' ];
        }

        // Succesfull payment created, lets log it and add a note to the payment.
        PayPro_WC_Logger::log("$this->id: Payment created for $order_id - Payment ID: $payment->id");

        // Set order information.
        /* translators: %1$s contains title of the gateway, %2$s contains the ID of the PayPro payment */
        $message = sprintf(__('%1$s payment in process (%2$s)', 'paypro-gateways-woocommerce'), $this->method_title, $payment->id);

        $order->addOrderNote($message);
        $order->addPayment($payment->id);

        return [
            'result'   => 'success',
            'redirect' => esc_url_raw($payment->links['checkout']),
        ];
    }

    /**
     * Overrides the process refund function. This is were we process the refund request created by
     * WC.
     *
     * @param int    $order_id ID of the WC order.
     * @param string $amount   Amount to refund.
     * @param string $reason   The given reason for the refund.
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        // Check if the gateway supports refunds.
        if (!$this->supportsRefunds()) {
            $debug_message = "$this->id: This payment method does not support refunds, id: {$order_id}";
            PayPro_WC_Logger::log($debug_message);

            $message = __('This payment method does not support refunds', 'paypro-gateways-woocommerce');
            return new WP_Error('1', $message);
        }

        // Get order from Woocommerce.
        $order = new PayPro_WC_Order($order_id);

        // Check if order is found, otherwise debug and failure.
        if (!$order->exists()) {
            $debug_message = "$this->id: Could not find order, id: $order_id";
            PayPro_WC_Logger::log($debug_message);

            $message = sprintf(
                /* translators: %1$s contains the order id. */
                __('Could not find the order: %1$s', 'paypro-gateways-woocommerce'),
                $order_id
            );

            return new WP_Error('1', $message);
        }

        $refund_amount = PayPro_WC_Helper::decimalToCents($amount);

        // Don't allow refund amount of 0.
        if (0 === $refund_amount) {
            $debug_message = "$this->id: Cannot refund for 0, id: $order_id";
            PayPro_WC_Logger::log($debug_message);

            $message = __('Cannot refund for zero, please refund at least 1 cent', 'paypro-gateways-woocommerce');
            return new WP_Error('1', $message);
        }

        // Check if there is an active payment for the WC order.
        $payment_id = $order->getActivePayment();

        if (!$payment_id) {
            $debug_message = "$this->id: No active payment found for the order ({$order_id}), cannot refund";
            PayPro_WC_Logger::log($debug_message);

            $message = __('Failed to refund, no PayPro payment found.', 'paypro-gateways-woocommerce');
            return new WP_Error('1', $message);
        }

        // Retrieve the payment from the PayPro API.
        try {
            $payment = PayPro_WC_Plugin::$paypro_api->getPayment($payment_id);
        } catch (\PayPro\Exception\ApiErrorException $e) {
            $debug_message = "$this->id: Retrieving PayPro payment failed. Message: {$e->getMessage()}";
            PayPro_WC_Logger::log($debug_message);

            $message = __('Failed to refund, could not retrieve PayPro payment details.', 'paypro-gateways-woocommerce');
            return new WP_Error('1', $message);
        }

        // Refund the PayPro payment.
        try {
            $refund = $payment->refund(
                [
                    'amount' => $refund_amount,
                    'reason' => $reason,
                ]
            );
        } catch (\PayPro\Exception\ApiErrorException $e) {
            $debug_message = "$this->id: Failed to create the PayPro refund. Message: {$e->getMessage()}";
            PayPro_WC_Logger::log($debug_message);

            $message = __('Failed to refund, could not create PayPro refund.', 'paypro-gateways-woocommerce');
            return new WP_Error('1', $message);
        }

        $debug_message = "$this->id: Refund created - refund: $refund->id, payment: $payment->id, order: {$order->getId()}";
        PayPro_WC_Logger::log($debug_message);

        $message = sprintf(
            /* translators: %1$s contains the refund amount, %2$s contains the payment id, %3$s contains the refund id */
            __('Refunded %1$s - Payment %2$s, Refund %3$s', 'paypro-gateways-woocommerce'),
            $amount,
            $payment->id,
            $refund->id
        );

        $order->addOrderNote($message);

        return true;
    }

    /**
     * Action to process a renewal order. It will attempt to create a payment with a mandate.
     *
     * @param string   $amount   Amount of the renewal.
     * @param WC_Order $wc_order The renewal order.
     */
    public function processRenewalPayment($amount, WC_Order $wc_order) {
        $order = new PayPro_WC_Order($wc_order->get_id());

        if (!$order->exists()) {
            PayPro_WC_Logger::log($this->id . ': Cannot load renewal order.');
            return [ 'result' => 'failure' ];
        }

        $subscription = $order->getSubscriptionForRenewal();

        if (empty($subscription)) {
            PayPro_WC_Logger::log("$this->id: No subscription found for renewal order, order: {$order->getId()}");
            return [ 'result' => 'failure' ];
        }

        PayPro_WC_Logger::log("$this->id: Found subscription {$subscription->getId()} for renewal order {$order->getId()}");

        $pay_method                  = PayPro_WC_Helper::getPayProPayMethod($order->getPaymentMethod());
        $recurring_wc_payment_method = PayPro_WC_Helper::getWCRecurringPayMethod($order->getPaymentMethod());
        $recurring_pay_method        = PayPro_WC_Helper::getRecurringPayMethod($pay_method);
        $mandate_id                  = $subscription->getMandateId();

        $chosen_mandate = null;

        // If subscription has a mandate ID try to use it for the renewal order.
        if (!empty($mandate_id)) {
            try {
                $mandate = PayPro_WC_Plugin::$paypro_api->getMandate($mandate_id);

                if ('approved' === $mandate->state && $mandate->pay_method === $recurring_pay_method) {
                    PayPro_WC_Logger::log("$this->id: Using previous mandate {$mandate_id} for renewal order {$order->getId()}");
                    $chosen_mandate = $mandate;
                }
            } catch (\PayPro\Exception\ApiErrorException $e) {
                PayPro_WC_Logger::log("$this->id: Failed to load mandate with ID $mandate_id - Message: {$e->getMessage()}");
            }
        }

        $customer_id = $subscription->getCustomerId();

        // If previous mandate was not found try to find other mandates for the customer.
        if (empty($chosen_mandate) && !empty($customer_id)) {
            PayPro_WC_Logger::log("$this->id: No previous mandate found try to find other mandates with customer {$customer_id} for renewal order {$order->getId()}");

            try {
                $customer = PayPro_WC_Plugin::$paypro_api->getCustomer($customer_id);
                $mandates = $customer->mandates();

                foreach ($mandates as $mandate) {
                    if ('approved' === $mandate->state && $mandate->pay_method === $recurring_pay_method) {
                        PayPro_WC_Logger::log("$this->id: Found a mandate {$mandate->id} with customer {$customer_id} for renewal order {$order->getId()}");
                        $chosen_mandate = $mandate;
                        break;
                    }
                }
            } catch (\PayPro\Exception\ApiErrorException $e) {
                PayPro_WC_Logger::log("$this->id: Failed to load other mandates with customer {$customer_id} for renewal order {$order->getId()} - Message: {$e->getMessage()}");
            }
        }

        // No valid mandate found stop here and report to merchant.
        if (empty($chosen_mandate)) {
            PayPro_WC_Logger::log("$this->id: Failed to create a payment because no valid mandate found for renewal order {$order->getId()}");

            $message = __('Failed to create renewal payment. No valid mandate found.', 'paypro-gateways-woocommerce');
            $order->updateStatus('failed', $message);

            return [ 'result' => 'failure' ];
        }

        try {
            $payment_data = [
                'amount'      => $order->getAmountInCents(),
                'currency'    => $order->getCurrency(),
                'description' => $order->getDescription(),
                'mandate'     => $chosen_mandate->id,
                'metadata'    => [
                    'order_id'  => $order->getId(),
                    'order_key' => $order->getKey(),
                ],
            ];

            $payment = PayPro_WC_Plugin::$paypro_api->createPayment($payment_data);

            $order->setMandateId($chosen_mandate->id);
            $subscription->setMandateId($chose_mandate->id);

            if ($order->getPaymentMethod() !== $recurring_wc_payment_method) {
                $order->setPaymentMethod($recurring_wc_payment_method);
            }

            // Payment created successfully, lets log it and add a note to the payment.
            PayPro_WC_Logger::log("$this->id: Payment created for {$order->getId()} - Payment ID: $payment->id");

            // Set order information.
            /* translators: %1$s contains title of the gateway, %2$s contains the ID of the PayPro payment */
            $message = sprintf(__('%1$s payment in process (%2$s)', 'paypro-gateways-woocommerce'), $this->method_title, $payment->id);

            $order->addOrderNote($message);
            $order->addPayment($payment->id);

            // Update subscription to 'Active' when using SEPA Direct Debit. This ensures the subscription is still usable
            // while the payment still processes.
            if ('paypro_wc_gateway_directdebit' === $recurring_wc_payment_method && 'active' !== $subscription->getStatus()) {

                // If the WooCommerce subscription uses the retry system don't update it.
                if (class_exists('WCS_Retry_Manager') && WCS_Retry_Manager::is_retry_enabled() && $subscription->getRetryDays() > 0) {
                    PayPro_WC_Logger::log("$this->id: Not updating subscription status to active. Subscription retry system is in use");
                } else {
                    $message = __("Change subscription from 'On hold' to 'Active' until the payment fails, because SEPA Direct Debit takes a longer time to process.", 'paypro-gateways-woocommerce');
                    $subscription->updateStatus('active', $message);
                }
            }

            return [ 'result' => 'success' ];
        } catch (\PayPro\Exception\ApiErrorException $e) {
            PayPro_WC_Logger::log("$this->id: Failed to create a payment for renewal order {$order->getId()} - Message: {$e->getMessage()}");

            $message = __('Failed to create renewal payment. Payment could not be created.', 'paypro-gateways-woocommerce');
            $order->updateStatus('failed', $message);
        }

        return [ 'result' => 'failure' ];
    }

    /**
     * Action to update the metadata of a subscription when a customer renews the subscription.
     *
     * @param WC_Subscription $wc_subscription The subscription to be updated.
     * @param WC_Order        $wc_order        The renewal order.
     */
    public function updateFailingPaymentMethod($wc_subscription, $wc_order) {
        $subscription = new PayPro_WC_Subscription($wc_subscription->get_id());
        $order        = new PayPro_WC_Order($wc_order->get_id());

        $subscription->setCustomerId($order->getCustomerId());
    }

    /**
     * Filter to add the PayPro Customer ID as editable meta data for admins. This allows them to
     * change the customer for a subscription.
     *
     * @param array           $payment_meta    Array of metadata settings.
     * @param WC_Subscription $wc_subscription The WC_Subscription that contains the metadata.
     */
    public function addSubscriptionPaymentMeta($payment_meta, $wc_subscription) {
        $subscription = new PayPro_WC_Subscription($wc_subscription->get_id());

        $customer_id = $subscription->getCustomerId();

        $payment_meta[$this->id] = [
            'post_meta' => [
                '_paypro_customer_id' => [
                    'value' => $customer_id,
                    'label' => 'PayPro Customer ID',
                ],
            ],
        ];

        return $payment_meta;
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
    public function isValid() {
        if (!PayPro_WC_Plugin::apiValid() && 'yes' === $this->enabled) {
            PayPro_WC_Logger::log($this->id . ': Cannot enable PayPro payment methods without setting the API key first.');
            return false;
        }

        return true;
    }

    /**
     * Returns the icon url for this gateway
     */
    public function getIconUrl() {
        return PAYPRO_WC_PLUGIN_URL . 'assets/images/' . $this->id . '.png';
    }

    /**
     * Check if the gateway supports refunds.
     */
    public function supportsRefunds() {
        return $this->supports_refunds;
    }

    /**
     * Check if the gateway supports subscriptions.
     */
    public function supportsSubscriptions() {
        return $this->supports_subscriptions;
    }

    /**
     * Returns additional payment data
     */
    protected function getAdditionalPaymentData() {
        return [];
    }

    /**
     * Returns the title of the gateway.
     */
    abstract public function getTitle();

    /**
     * Returns the description of the gateway.
     */
    abstract public function getDescription();

    /**
     * Sets the supports array of the gateway.
     */
    private function initSupports() {
        $supports = [ 'products' ];

        if ($this->supportsRefunds()) {
            $supports[] = 'refunds';
        }

        if (PayPro_WC_Helper::subscriptionsEnabled() && $this->supportsSubscriptions()) {
            $supports = array_merge(
                $supports,
                [
                    'subscriptions',
                    'subscription_cancellation',
                    'subscription_suspension',
                    'subscription_reactivation',
                    'subscription_amount_changes',
                    'subscription_date_changes',
                    'multiple_subscriptions',
                    'subscription_payment_method_change',
                    'subscription_payment_method_change_admin',
                ]
            );

            add_action("woocommerce_scheduled_subscription_payment_{$this->id}", [ $this, 'processRenewalPayment' ], 10, 2);

            add_filter('woocommerce_subscription_payment_meta', [ $this, 'addSubscriptionPaymentMeta' ], 10, 2);

            add_action("woocommerce_subscription_failing_payment_method_updated_{$this->id}", [ $this, 'updateFailingPaymentMethod' ], 10, 2);
        }

        $this->supports = $supports;
    }
}
