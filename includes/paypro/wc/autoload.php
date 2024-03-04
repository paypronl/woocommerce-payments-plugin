<?php

defined('ABSPATH') || exit;

class PayPro_WC_Autoload
{
    static $registered = false;

    /**
     * Register the autoloader
     */ 
    public static function register()
    {
        if(self::$registered)
            return false;

        return spl_autoload_register(array(__CLASS__, 'autoload')); 
    }

    /**
     * Autoloads all PayPro plugin and API classes
     */
    public static function autoload($class_name)
    {
        $base_path = dirname(dirname(dirname(__FILE__)));

        if(stripos($class_name, 'PayPro_WC') === 0)
        {
            $class_path = $base_path . '/' . str_replace('_', '/', strtolower($class_name)) . '.php';

            if(file_exists($class_path))
            {
                require_once($class_path); 
            }
        }
        elseif(stripos($class_name, 'PayProApi') === 0)
        {
            require_once($base_path . '/paypro/api/PayProApiHelper.php');
        }
    }
}