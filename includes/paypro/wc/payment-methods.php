<?php

defined('ABSPATH') || exit;

/**
 * Class to handle the payment methods.
 */
class PayPro_WC_PaymentMethods {
    const TRANSIENT_KEY = 'paypro-wc-pay-methods';

    /**
     * The API class object
     *
     * @var PayPro_WC_Api $api
     */
    private $api;

    /**
     * Constructor
     *
     * @param PayPro_WC_Api $api The API helper object.
     */
    public function __construct($api) {
        $this->api = $api;
    }

    /**
     * Gets all iDEAL issuers from the cache or reload through the API.
     */
    public function getIdealIssuers() {
        $payment_methods = $this->getPaymentMethods();

        if (empty($payment_methods)) {
            return [];
        }

        $ideal_pay_methods = array_filter(
            $payment_methods['data'],
            function ($method) {
                return 'ideal' === $method->id;
            }
        );

        if (empty($ideal_pay_methods)) {
            return [];
        }

        return reset($ideal_pay_methods)->details['issuers'];
    }

    /**
     * Gets all pay methods from the API and caches them for an hour.
     */
    public function getPaymentMethods() {
        try {
            $pay_methods = get_transient(self::TRANSIENT_KEY);

            if (is_array($pay_methods)) {
                return $pay_methods;
            }

            $pay_methods = $this->api->getPayMethods();
            set_transient(self::TRANSIENT_KEY, $pay_methods, HOUR_IN_SECONDS);

            return $pay_methods;
        } catch (\PayPro\Exception\ApiErrorException $e) {
            // Set the transient to an empty array for 5 min to avoid calling the API too often.
            set_transient(self::TRANSIENT_KEY, [], MINUTE_IN_SECONDS * 5);

            PayPro_WC_Logger::log("Failed to load payment methods from API - Message: {$e->getMessage()}");
        }

        return [];
    }
}
