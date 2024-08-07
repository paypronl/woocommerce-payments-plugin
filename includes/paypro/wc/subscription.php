<?php

defined('ABSPATH') || exit;

/**
 * Wrapper for a WC subscription.
 */
class PayPro_WC_Subscription {
    const CUSTOMER_META_DATA_KEY = '_paypro_customer_id';
    const MANDATE_META_DATA_KEY  = '_paypro_mandate_id';

    /**
     * WooCommerce Subscription
     *
     * @var WC_Subscription $subscription
     */
    private $subscription;

    /**
     * PayPro Customer
     *
     * @var /PayPro/Customer $customer
     */
    private $customer;

    /**
     * Constructor
     *
     * @param int $subscription_id The ID of the WC subscription.
     */
    public function __construct(int $subscription_id) {
        $this->subscription = wcs_get_subscription($subscription_id);
    }

    /**
     * Check if the WC subscription actually exists.
     */
    public function exists() {
        return !empty($this->subscription);
    }

    /**
     * Updates the status of the WC subscription.
     *
     * @param string $status The status to change to.
     * @param string $message The message to be logged.
     */
    public function updateStatus($status, $message) {
        $this->subscription->update_status($status, $message);
    }

    /**
     * Get the customer ID from the WC subscription metadata.
     */
    public function getCustomerId() {
        return $this->subscription->get_meta(self::CUSTOMER_META_DATA_KEY, true);
    }

    /**
     * Set the customer ID in the WC subscription metadata.
     *
     * @param string $customer_id The ID of the customer.
     */
    public function setCustomerId($customer_id) {
        $this->subscription->add_meta_data(self::CUSTOMER_META_DATA_KEY, $customer_id, true);
        $this->subscription->save();
    }

    /**
     * Get the mandate ID from the WC subscription metadata.
     */
    public function getMandateId() {
        return $this->subscription->get_meta(self::MANDATE_META_DATA_KEY, true);
    }

    /**
     * Set the mandate ID in the WC subscription metadata.
     *
     * @param string $mandate_id The ID of the mandate.
     */
    public function setMandateId($mandate_id) {
        $this->subscription->add_meta_data(self::MANDATE_META_DATA_KEY, $mandate_id, true);
        $this->subscription->save();
    }

    /**
     * Returns the subscription ID of the WC subscription
     */
    public function getId() {
        return $this->subscription->get_id();
    }

    /**
     * Returns the subscription status of the WC subscription.
     */
    public function getStatus() {
        return $this->subscription->get_status();
    }

    /**
     * Returns the retry date in days from now.
     */
    public function getRetryDays() {
        $this->subscription->get_date('payment_retry');
    }
}
