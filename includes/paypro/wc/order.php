<?php

defined('ABSPATH') || exit;

/**
 * Wrapper for a WC order.
 */
class PayPro_WC_Order {
    const ACTIVE_PAYMENT_META_DATA_KEY = '_paypro_active_payment_id';
    const CUSTOMER_META_DATA_KEY       = '_paypro_customer_id';
    const PAYMENT_META_DATA_KEY        = '_paypro_payment_id';

    /**
     * WooCommerce Order
     *
     * @var WC_Order $order
     */
    private $order;

    /**
     * PayPro Customer
     *
     * @var /PayPro/Customer $customer
     */
    private $customer;

    /**
     * If the order is part of a subscription
     * 
     * @var boolean $has_subscription
     */
    private $has_subscription;

    /**
     * Constructor
     *
     * @param int $order_id The ID of the WC order.
     */
    public function __construct(int $order_id) {
        $this->order = wc_get_order($order_id);
    }

    /**
     * Finds or creates a PayPro customer to be used for this order.
     */
    public function findOrCreateCustomer() {
        if ($this->customer) {
            return $this->customer;
        }

        $customer_id = $this->getCustomerId();

        if ($customer_id) {
            try {
                $this->customer = PayPro_WC_Plugin::$paypro_api->getCustomer($customer_id);
            } catch (\PayPro\Exception\ApiErrorException $e) {
                $customer_id = null;
            }
        }

        if (empty($customer_id)) {
            try {
                $this->customer = PayPro_WC_Plugin::$paypro_api->createCustomer();
            } catch (\PayPro\Exception\ApiErrorException $e) {
                PayPro_WC_Logger::log("Failed to create customer for order $order_id - Message: {$e->getMessage()}");
                return null;
            }
        }

        $this->setCustomerId($this->customer->id);
        return $this->customer;
    }

    /**
     * Updates the customer according to the WC order details. We update this every payment attempt
     * because the details could have changed.
     */
    public function updateCustomer() {
        $this->findOrCreateCustomer();
        $update_data = $this->getCustomerUpdateData();

        try {
            $this->customer->update($update_data);
        } catch (\PayPro\Exception\ApiErrorException $e) {
            PayPro_WC_Logger::log("Failed to update customer ({$this->getCustomerId()}) for order $order_id - Message: {$e->getMessage()}");
            return false;
        }

        return true;
    }

    /**
     * Complete the WC order and log the results.
     *
     * @param string $payment_id The ID of the PayPro payment.
     */
    public function complete($payment_id) {
        $status = PayPro_WC_Settings::paymentCompleteStatus();

        if (empty($status)) {
            $status = 'wc-processing';
        }

        /* translators: %s contains the payment id of the PayPro payment */
        $message = sprintf(__('PayPro payment (%s) succeeded', 'paypro-gateways-woocommerce'), $payment_id);
        $this->order->update_status($status, $message);

        wc_reduce_stock_levels($this->getId());
        $this->order->payment_complete();

        $this->removeAllPayments();
        $this->setActivePayment($payment_id);
    }

    /**
     * Cancel the WC order and log the results.
     *
     * @param string $payment_id The ID of the PayPro payment.
     */
    public function cancel($payment_id) {
        /* translators: %s contains the payment id of the PayPro payment */
        $message = sprintf(__('PayPro payment (%s) cancelled ', 'paypro-gateways-woocommerce'), $payment_id);

        if (PayPro_WC_Settings::automaticCancellation()) {
            WC()->cart->empty_cart();
            $this->order->update_status('cancelled', $message);
        } else {
            $this->order->add_order_note($message);
        }

        $this->removeAllPayments();
    }

    /**
     * Checks if this order contains a subscription.
     */
    public function hasSubscription() {
        if (!isset($this->has_subscription)) {
            $this->has_subscription = function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($this->getId());
        } 

        return $this->has_subscription;
    }

    /**
     * Returns the subscriptions as part of the renewal of this order.
     */
    public function getSubscriptionsForRenewal() {
        return wcs_get_subscriptions_for_renewal_order($this->getId());
    }

    /**
     * Updates the status of the WC order.
     *
     * @param string $status The status to change to.
     * @param string $message The message to be logged.
     */
    public function updateStatus($status, $message) {
        $this->order->update_status($status, $message);
    }

    /**
     * Set the customer ID in the WC order metadata.
     *
     * @param string $customer_id The ID of the customer.
     */
    public function setCustomerId($customer_id) {
        $this->order->add_meta_data(self::CUSTOMER_META_DATA_KEY, $customer_id, true);
        $this->order->save();
    }

    /**
     * Get the customer ID from the WC order metadata.
     */
    public function getCustomerId() {
        return $this->order->get_meta(self::CUSTOMER_META_DATA_KEY, true);
    }

    /**
     * Write a note to the WC order.
     *
     * @param string $message The message to be written in the notes.
     */
    public function addOrderNote($message) {
        $this->order->add_order_note($message);
    }

    /**
     * Set a payment ID in the WC order metadata.
     *
     * @param string $payment_id The iD of the payment.
     */
    public function addPayment($payment_id) {
        $this->order->add_meta_data(self::PAYMENT_META_DATA_KEY, $payment_id, false);
        $this->order->save();
    }

    /**
     * Get all payment IDs saved in the WC order metadata.
     */
    public function getPayments() {
        $meta_data_entries = $this->order->get_meta(self::PAYMENT_META_DATA_KEY, false);
        return array_map(fn($meta_data) => $meta_data->value, $meta_data_entries);
    }

    /**
     * Set active payment.
     *
     * @param string $payment_id The ID of the payment.
     */
    public function setActivePayment($payment_id) {
        $this->order->add_meta_data(self::ACTIVE_PAYMENT_META_DATA_KEY, $payment_id, true);
        $this->order->save();
    }

    /**
     * Get active payment.
     */
    public function getActivePayment() {
        return $this->order->get_meta(self::ACTIVE_PAYMENT_META_DATA_KEY, true);
    }

    /**
     * Remove all payment IDs saved in the WC order metadata.
     */
    public function removeAllPayments() {
        $this->order->delete_meta_data(self::PAYMENT_META_DATA_KEY);
        $this->order->save();
    }

    /**
     * Check if the WC order actually exists.
     */
    public function exists() {
        return !empty($this->order);
    }

    /**
     * Check if the given key is a valid WC order key.
     *
     * @param string $key Key to be checked.
     */
    public function validKey($key) {
        return $this->order->key_is_valid($key);
    }

    /**
     * Check if the WC order has the status.
     *
     * @param string $status Status to be checked.
     */
    public function hasStatus($status) {
        return $this->order->has_status($status);
    }

    /**
     * Returns the billing first name of the WC order.
     */
    public function getFirstName() {
        return $this->order->get_billing_first_name();
    }

    /**
     * Returns the billing last name of the WC order.
     */
    public function getLastName() {
        return $this->order->get_billing_last_name();
    }

    /**
     * Returns the billing address of the WC order.
     */
    public function getAddress() {
        return $this->order->get_billing_address_1();
    }

    /**
     * Returns the billing postcode of the WC order.
     */
    public function getPostcode() {
        return $this->order->get_billing_postcode();
    }

    /**
     * Returns the billing city of the WC order.
     */
    public function getCity() {
        return $this->order->get_billing_city();
    }

    /**
     * Returns the billing country of the WC order.
     */
    public function getCountry() {
        return $this->order->get_billing_country();
    }

    /**
     * Returns the billing phone numer of the WC order.
     */
    public function getPhone() {
        return $this->order->get_billing_phone();
    }

    /**
     * Returns the billing email of the WC order.
     */
    public function getEmail() {
        return $this->order->get_billing_email();
    }

    /**
     * Returns the WC order amount in cents.
     */
    public function getAmountInCents() {
        return PayPro_WC_Helper::decimalToCents($this->order->get_total());
    }

    /**
     * Returns the description to be used for the payment.
     */
    public function getDescription() {
        $payment_description = PayPro_WC_Settings::paymentDescription();

        if (empty($payment_description)) {
            $payment_description = "Order {$this->getNumber()}";
        }

        return $payment_description;
    }

    /**
     * Returns the cancel URL to be used for the payment.
     */
    public function getCancelUrl() {
        $cancel_url = WC()->api_request_url('paypro_cancel');

        return add_query_arg(
            [
                'order_id'  => $this->getId(),
                'order_key' => $this->getKey(),
            ],
            $cancel_url
        );
    }

    /**
     * Returns the return URL to be used for the payment.
     */
    public function getReturnUrl() {
        $return_url = WC()->api_request_url('paypro_return');

        return add_query_arg(
            [
                'order_id'  => $this->getId(),
                'order_key' => $this->getKey(),
            ],
            $return_url
        );
    }

    /**
     * Returns the cancel order URL of the WC order
     */
    public function getCancelOrderUrl() {
        return $this->order->get_cancel_order_url();
    }

    /**
     * Returns the order received URL of the WC order
     */
    public function getOrderReceivedUrl() {
        return $this->order->get_checkout_order_received_url();
    }

    /**
     * Returns the order ID of the WC order
     */
    public function getId() {
        return $this->order->get_id();
    }

    /**
     * Returns the order key of the WC order
     */
    public function getKey() {
        return $this->order->get_order_key();
    }

    /**
     * Returns the order currency of the WC order
     */
    public function getCurrency() {
        return $this->order->get_currency();
    }

    /**
     * Returns the order number of the WC order
     */
    public function getNumber() {
        return $this->order->get_order_number();
    }

    /**
     * Returns the data to be used when creating the PayPro payment.
     */
    public function getPaymentData() {
        return [
            'amount'      => $this->getAmountInCents(),
            'currency'    => $this->getCurrency(),
            'description' => $this->getDescription(),
            'return_url'  => $this->getReturnUrl(),
            'cancel_url'  => $this->getCancelUrl(),
            'customer'    => $this->customer->id,
            'metadata'    => [
                'order_id'  => $this->getId(),
                'order_key' => $this->getKey(),
            ],
        ];
    }

    /**
     * Returns the data to be used when updating the PayPro customer.
     */
    public function getCustomerUpdateData() {
        return [
            'email'        => $this->getEmail(),
            'first_name'   => $this->getFirstName(),
            'last_name'    => $this->getLastName(),
            'address'      => $this->getAddress(),
            'postal'       => $this->getPostcode(),
            'city'         => $this->getCity(),
            'country'      => $this->getCountry(),
            'phone_number' => $this->getPhone(),
        ];
    }
}
