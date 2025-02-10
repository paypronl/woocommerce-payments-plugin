=== PayPro Gateways - WooCommerce ===
Contributors: paypro
Tags: paypro, payments, gateways, woocommerce, ideal
Requires at least: 5.0
Tested up to: 6.7.1
Stable tag: 3.1.1
Requires PHP: 7.2
License: GPLv2
License URI: http://opensource.org/licenses/GPL-2.0

With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop.

== Description ==

This plugin is the official PayPro plugin for WooCommerce. It is easy to use, quick to install and actively maintained by PayPro. 

Currently the plugin supports the following payment methods:

* iDEAL
* PayPal
* Bancontact
* Sofort
* Riverty
* SEPA Credit Transfer
* Credit card (Visa and Mastercard)

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
3. Set your PayPro API key at WooCommerce -> Settings -> PayPro -> Settings
4. Create a new Webhook at WooCommerce -> Settings -> PayPro -> Webhook
5. Now select the payment methods you want to use and enable them.
6. Your webshop is now ready to accept PayPro payments.

= Manual installation =

1. Download the package
2. Unpack the zip file and upload the 'paypro-gateways-woocommerce' to the plugin directory. You can find the plugin directory in the 'wp-content' directory.
3. Go to Plugins -> Installed plugins. Activate the plugin named 'PayPro Gateways - WooCommerce'.
4. Set your PayPro API key at WooCommerce -> Settings -> PayPro -> Settings
5. Create a new Webhook at WooCommerce -> Settings -> PayPro -> Webhook
6. Now select the PayPro payment methods you want to use at WooCommerce -> Settings -> Payments.
7. Your webshop is now ready to accept PayPro payments.

Do you need help installing the PayPro plugin, check our [guide](https://guide.paypro.nl/en/articles/4595222-woocommerce-plugin) or contact support@paypro.nl

== Frequently Asked Questions ==

= Where do I find my PayPro API key? =
You can find your PayPro API key in your [dashboard](https://app.paypro.nl/developers/api-keys) at 'Developers' in the PayPro dashboard.

= Why do we need to create a Webhook? =
The Webhook is part of the new notification system the plugin uses to update the orders in your WooCommerce shop.

== Screenshots ==

1. Overview of the PayPro settings.
2. Settings for an individual payment method.
3. Example of the checkout payment method selection.

== Changelog == 

= 3.1.1 =

* Fix a bug where processing a cancel webhook would return a 401

= 3.1.0 =

* Remove iDEAL issuer selection from payment page
* Change payment method name AfterPay to Riverty
* Update FAQ with links to our guide

= 3.0.2 =

* Add missing files

= 3.0.1 =

* Fix incorrect translation loading
* Update tested up to 6.7.1
* Update composer files to load properly

= 3.0.0 =

IMPORTANT 3.0.0 is a major update and implements the new PayPro API and webhook system. This requires you to use a new API key and creating a webhook before you can accept payments again.

* Implement new PayPro API
* Implement new PayPro webhook system
* Add WooCommerce subscriptions plugin support
* Add refund support for most payment methods
* Removed iDEAL QR
* Added PayPro webhook settings page to create a new webhook

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
