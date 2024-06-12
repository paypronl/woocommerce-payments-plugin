<?php

defined('ABSPATH') || exit;

/**
 * Class to be used for logging in the plugin.
 */
class PayPro_WC_Logger {
    const WC_LOGGER_SOURCE = 'paypro-gateways-woocommerce';

    /**
     * Singleton object for the WC logger class.
     *
     * @var WC_Logger_Interface $logger
     */
    public static $logger;

    /**
     * Log to a message to the WC logger
     *
     * @param mixed $message The message to be logged.
     */
    public static function log($message) {
        if (empty(self::$logger)) {
            self::$logger = wc_get_logger();
        }

        // Only write log if debug mode enabled.
        if (!PayPro_WC_Settings::debugMode()) {
            return;
        }

        // Convert not strings to strings.
        if (!is_string($message)) {
            //phpcs:ignore WordPress.PHP.DevelopmentFunctions
            $message = print_r($message, true);
        }

        self::$logger->debug($message, [ 'source' => self::WC_LOGGER_SOURCE ]);
    }
}
