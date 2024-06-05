<?php

defined('ABSPATH') || exit;

class PayPro_WC_Logger
{
    public static $logger;

    const WC_LOGGER_SOURCE = 'paypro-gateways-woocommerce';

    /**
     * Log to a message to the WC logger
     */ 
    public static function log($message)
    {
        if(empty(self::$logger))
            self::$logger = wc_get_logger();
        
        // Only write log if debug mode enabled
        if(!PayPro_WC_Settings::debugMode())
            return;

        // Convert not strings to strings
        if(!is_string($message))
            $message = print_r($message, true);

        self::$logger->debug($message, ['source' => self::WC_LOGGER_SOURCE]);
    }
}
