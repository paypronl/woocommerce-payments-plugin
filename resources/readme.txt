=== PayPro Gateways - WooCommerce ===
Contributors: paypro
Tags: paypro, payments, betalingen, psp, gateways, woocommerce, ideal, bank transfer, paypal, afterpay, creditcard, visa, mastercard, mistercash, bancontact, sepa, overboeking, incasso
Requires at least: 5.0
Tested up to: 6.5.3
Stable tag: 2.0.2
Requires PHP: 7.2
License: GPLv2
License URI: http://opensource.org/licenses/GPL-2.0

With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop.

== Description ==

This plugin is the official PayPro plugin for WooCommerce. It is easy to use, quick to install and actively maintained by PayPro. 

Currently the plugin supports the following payment methods:

* iDEAL
* iDEAL QR 
* PayPal
* Bancontact
* Sofort
* Afterpay
* SEPA Credit Transfer

Credit cards:

* Visa
* Mastercard

= Features =

* Support for all PayPro payment methods
* Settings for each payment method
* WordPress Multisite support
* Translations for English and Dutch
* Test mode support
* Debug mode for easy debugging
* Automatic status changes

= Note =

In order to use this plugin you need to have an approved PayPro account.

== Installation ==

= Requirements =

* PHP version 7.2 or greater
* WordPress 5.0 or greater
* WooCommerce 5.0 or greater

= Automatic installation =

1. In the WordPress admin panel go to Plugins -> New Plugin. Search for 'PayPro Gateways - WooCommerce'.
2. Go to Plugins -> Installed Plugins. Activate the plugin named 'PayPro Gateways - WooCommerce'.
3. Set your PayPro API key at WooCommerce -> Settings -> PayPro.
4. Now select the payment methods you want to use and enable them.
5. Your webshop is now ready to accept PayPro payments.

= Manual installation =

1. Download the package
2. Unpack the zip file and upload the 'paypro-gateways-woocommerce' to the plugin directory. You can find the plugin directory in the 'wp-content' directory.
3. Go to Plugins -> Installed plugins. Activate the plugin named 'PayPro Gateways - WooCommerce'.
4. Set your PayPro API key at WooCommerce -> Settings -> PayPro.
5. Now select the PayPro payment methods you want to use at WooCommerce -> Settings -> Payments.
6. Your webshop is now ready to accept PayPro payments.

Do you need help installing the PayPro plugin, please contact support@paypro.nl

== Frequently Asked Questions ==

= Where do I find my PayPro API key? =
You can find your PayPro API key in your dashboard at 'Webshop Koppelen' in the PayPro dashboard.

= When do I need to add a product ID? =
If you want to make use of affiliate marketing or you want to use the mastercard, visa or sofort gateway you have to supply a product ID.

= Where do I find my product ID? =
You can find your product id at 'Webshop Koppelen' in the PayPro dashboard.

== Screenshots ==

1. Overview of the PayPro settings.
2. Settings for an individual payment method.
3. Example of the checkout payment method selection.

== Changelog == 

= 2.0.2 =

* Fixed large icons in the legacy checkout for some themes

= 2.0.1 =

* Fixed a bug where order would not be automatically cancelled when payment is cancelled
* Return 'ok' in the callback response body

= 2.0.0 =

IMPORTANT 2.0.0 is a major update and changes the versions of WordPress, PHP and WooCommerce that are supported. Make sure before upgrading to validate your webshop is up-to-date.

* Drop support for WordPress below 5.0
* Drop support for WooCommerce below 5.0
* Drop support for PHP below 7.2
* Update plugin to be tested with WordPress 6.4.3
* Update plugin to be compatible with WooCommerce 8.5
* Add WooCommerce checkout blocks support
* Add WooCommerce HPOS support
* Update logos for all payment methods
* Move PayPro settings page to its own tab: WooCommerce -> Settings -> PayPro

= 1.3.2 =

* Fixed a bug where order_key would be sanitized incorrectly

= 1.3.1 =

* Fix missing files

= 1.3.0 =

* Added iDEAL QR pay method
* Implemented new PayPro API client
* Updates for compatability with Wordpress 5.0 and WooCommerce 3.5
* Updated various translations

= 1.2.4 =

* Correctly post shipping fields when provided

= 1.2.3 =

* Fixed a bug where the product ID would not be sent correctly

= 1.2.2 =

* Fixed a bug where product_id would be posted while it's invalid
* Fixed some small typos

= 1.2.1 =

* Fixed stock update call for WooCommerce 2.6

= 1.2.0 =

* Improved compatibability with WooCommerce 3.0 and 3.1
* Updated certificate bundle
* Updated Bancontact image and default title

= 1.1.0 =

* Reworked status updates for orders. Fixes the bugs with updating orders if there are multiple payments.
* Added an option to select the status for an order when a payment is completed.
* Multiple small fixes.

= 1.0.1 =

* Fixed a bug where orders with multiple payments would not update correctly.
* Fixed a bug where the layout of the PayPro settings would be wrong.
* Changed payment gateway images to the same size.
* Added extra sanitization.

= 1.0.0 =

First stable release
