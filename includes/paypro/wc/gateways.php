<?php

defined('ABSPATH') || exit;

/**
 * Gateway repository that contains all the instances of the gateways that are supported.
 */
class PayPro_WC_Gateways {
    /**
     * All available gateways.
     *
     * @var array $gateway_classes
     */
    public static $gateway_classes = [
        'PayPro_WC_Gateway_Ideal',
        'PayPro_WC_Gateway_Paypal',
        'PayPro_WC_Gateway_Bancontact',
        'PayPro_WC_Gateway_Afterpay',
        'PayPro_WC_Gateway_BankTransfer',
        'PayPro_WC_Gateway_Sofort',
        'PayPro_WC_Gateway_Creditcard',
        'PayPro_WC_Gateway_DirectDebit',
    ];

    /**
     * Array of all the initialized gateway objects.
     *
     * @var array $gateways
     */
    private static $gateways = [];

    /**
     * Loads all the gateways from the gateway classes array.
     */
    public static function setupGateways() {
        require_once __DIR__ . '/gateways/abstract.php';
        require_once __DIR__ . '/gateways/afterpay.php';
        require_once __DIR__ . '/gateways/bancontact.php';
        require_once __DIR__ . '/gateways/banktransfer.php';
        require_once __DIR__ . '/gateways/creditcard.php';
        require_once __DIR__ . '/gateways/directdebit.php';
        require_once __DIR__ . '/gateways/ideal.php';
        require_once __DIR__ . '/gateways/paypal.php';
        require_once __DIR__ . '/gateways/sofort.php';

        foreach (self::$gateway_classes as $gateway_class) {
            self::$gateways[] = new $gateway_class();
        }
    }

    /**
     * Get all initialized gateways
     */
    public static function getGateways() {
        return self::$gateways;
    }

    /**
     * Returns a list of all available gateway IDs
     */
    public static function getGatewayIds() {
        $gateway_ids = [];

        foreach (self::$gateway_classes as $gateway_class) {
            $gateway_ids[] = strtolower($gateway_class);
        }

        return $gateway_ids;
    }

    /**
     * Gets the instance of the gateway based on the ID
     *
     * @param string $gateway_id ID of the gateway.
     */
    public static function getGatewayById($gateway_id) {
        foreach (self::$gateways as $gateway) {
            if ($gateway->id === $gateway_id) {
                return $gateway;
            }
        }

        return null;
    }
}
