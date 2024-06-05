<?php

defined('ABSPATH') || exit;

class PayPro_WC_Order
{
    const CUSTOMER_META_DATA_KEY = '_paypro_customer_id';
    const PAYMENT_META_DATA_KEY = '_paypro_payment_id';

    /**
     * WooCommerce Order
     * 
     * @var WC_Order
     */
    private $order;

    /**
     * PayPro Customer
     * 
     * @var /PayPro/Customer
     */
    private $customer;

    public function __construct(int $order_id)
    {
        $this->order = wc_get_order($order_id);
    }

    public function findOrCreateCustomer()
    {
        if ($this->customer)
            return $this->customer;

        $customer_id = $this->getCustomerId();

        if ($customer_id) {
            try {
                $this->customer = PayPro_WC_Plugin::$paypro_api->getCustomer($customer_id);
            } catch(\PayPro\Exception\ApiErrorException $e) {
                $customer_id = null;
            }
        }

        if (empty($customer_id)) {
            try {
                $this->customer = PayPro_WC_Plugin::$paypro_api->createCustomer();
            } catch(\PayPro\Exception\ApiErrorException $e) {
                PayPro_WC_Logger::log("Failed to create customer for order $order_id - Message: {$e->getMessage()}");
                return null;
            }
        }

        $this->setCustomerId($this->customer->id);
        return $this->customer;
    }

    public function updateCustomer()
    {
        $this->findOrCreateCustomer();
        $updateData = $this->getCustomerUpdateData();

        try {
            $this->customer->update($updateData);
        } catch(\PayPro\Exception\ApiErrorException $e) {
            PayPro_WC_Logger::log("Failed to update customer ({$this->getCustomerId()}) for order $order_id - Message: {$e->getMessage()}");
            return false;
        }

        return true;        
    }

    public function complete($payment_id)
    {
        $status = PayPro_WC_Settings::paymentCompleteStatus();
        if(empty($status))
            $status = 'wc-processing';

        /* translators: %s contains the payment id of the PayPro payment */
        $message = sprintf(__('PayPro payment (%s) succeeded', 'paypro-gateways-woocommerce'), $payment_id);
        $this->order->update_status($status, $message);

        wc_reduce_stock_levels($this->getId());
        $this->order->payment_complete();

        $this->removeAllPayments();
    }

    public function cancel($payment_id)
    {
        /* translators: %s contains the payment id of the PayPro payment */
        $message = sprintf(__('PayPro payment (%s) cancelled ', 'paypro-gateways-woocommerce'), $payment_id);

        if(PayPro_WC_Settings::automaticCancellation())
        {
            WC()->cart->empty_cart();
            $this->order->update_status('cancelled', $message);
        }
        else
        {
            $this->order->add_order_note($message);
        }

        $this->removeAllPayments();
    }

    public function updateStatus($status, $message)
    {
        $this->order->update_status($status, $message);
    }

    public function setCustomerId($customer_id)
    {
        $this->order->add_meta_data(self::CUSTOMER_META_DATA_KEY, $customer_id, true);
        $this->order->save();
    }

    public function getCustomerId()
    {
        return $this->order->get_meta(self::CUSTOMER_META_DATA_KEY, true);
    }

    public function addOrderNote($message)
    {
        $this->order->add_order_note($message);
    }

    public function addPayment($payment_id)
    {
        $this->order->add_meta_data(self::PAYMENT_META_DATA_KEY, $payment_id, false);
        $this->order->save();
    }

    public function getPayments()
    {
        $meta_data_entries = $this->order->get_meta(self::PAYMENT_META_DATA_KEY, false);
        return array_map(fn($meta_data) => $meta_data->value, $meta_data_entries);
    }

    public function removeAllPayments()
    {
        $this->order->delete_meta_data(self::PAYMENT_META_DATA_KEY);
        $this->order->save();
    }

    public function exists()
    {
        return !empty($this->order);
    }

    public function validKey($key)
    {
        return $this->order->key_is_valid($key);
    }

    public function hasStatus($status)
    {
        return $this->order->has_status($status);
    }

    public function getFirstName()
    {
        return $this->order->get_billing_first_name();
    }

    public function getLastName()
    {
        return $this->order->get_billing_last_name();
    }

    public function getAddress()
    {
        return $this->order->get_billing_address_1();
    }

    public function getPostcode()
    {
        return $this->order->get_billing_postcode();
    }

    public function getCity()
    {
        return $this->order->get_billing_city();
    }

    public function getCountry()
    {
        return $this->order->get_billing_country();
    }

    public function getPhone()
    {
        return $this->order->get_billing_phone();
    }

    public function getEmail()
    {
        return $this->order->get_billing_email();
    }

    public function getAmountInCents()
    {
        return round($this->order->get_total() * 100);
    }

    public function getDescription()
    {
        $payment_description = PayPro_WC_Settings::paymentDescription();

        if(empty($payment_description))
            $payment_description = "Order {$this->getNumber()}";

        return $payment_description;
    }

    public function getCancelUrl()
    {
        $cancel_url = WC()->api_request_url('paypro_cancel');
        return add_query_arg(array('order_id' => $this->getId(), 'order_key' => $this->getKey()), $cancel_url);
    }

    public function getReturnUrl()
    {
        $return_url = WC()->api_request_url('paypro_return');
        return add_query_arg(array('order_id' => $this->getId(), 'order_key' => $this->getKey()), $return_url);
    }

    public function getCancelOrderUrl()
    {
        return $this->order->get_cancel_order_url();
    }

    public function getOrderReceivedUrl()
    {
        return $this->order->get_checkout_order_received_url();
    }

    public function getId()
    {
        return $this->order->get_id();
    }

    public function getKey()
    {
        return $this->order->get_order_key();
    }

    public function getCurrency()
    {
        return $this->order->get_currency();
    }

    public function getNumber()
    {
        return $this->order->get_order_number();
    }

    public function getPaymentData()
    {
        return [
            'amount' => $this->getAmountInCents(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'return_url' => $this->getReturnUrl(),
            'cancel_url' => $this->getCancelUrl(),
            'customer' => $this->customer->id,
            'metadata' => [
                'order_id' => $this->getId(),
                'order_key' => $this->getKey()
            ]
        ];
    }

    public function getCustomerUpdateData()
    {
        return [
            'email' => $this->getEmail(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'address' => $this->getAddress(),
            'postal' => $this->getPostcode(),
            'city' => $this->getCity(),
            'country' => $this->getCountry(),
            'phone_number' => $this->getPhone()
        ];
    }
}
