![PayPro](https://paypro.nl/images/logo-ie.png) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
# PayPro Gateways - WooCommerce

With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop. Currently the plugin supports the following gateways:

- iDEAL 
- PayPal
- MisterCash / Bancontact
- Sofort
- Afterpay
- SEPA Incasso
- Mastercard
- Visa


## Requirements

- PHP version 5.3 or greater
- PHP extension cUrl
- PHP extension OpenSSL
- Wordpress 3.8 or greater
- WooCommerce 2.2 or greater

## Installation

### Automatic installation
1. In the Wordpress admin panel go to Plugins -> New Plugin. Search for 'PayPro Gateways - WooCommerce'.
2. Go to Plugins -> Installed plugins. Here activate the plugin named 'PayPro Gateways - WooCommerce'.
3. If you get no pop-ups the plugin has succesfully activated, if not reslove the issues that are displayed.
4. Set your PayPro API key at WooCommerce -> Settings -> Checkout under the section PayPro.
5. Now select the payment methods you want and activate them.
6. Your webshop is now ready to use PayPro gateways.

### Manual installation
1. Download the package at [wordpress](https://wordpress.org/plugins/paypro-gateways-woocommerce) or clone the repository.
2. Upload the directory 'paypro-gateways-woocommerce' to the plugin directory. You can find this in the 'wp-content' directory.
3. Go to Plugins -> Installed plugins. Here activate the plugin named 'PayPro Gateways - WooCommerce'.
4. If you get no pop-ups the plugin has succesfully activated, if not reslove the issues that are displayed.
5. Set your PayPro API key at WooCommerce -> Settings -> Checkout under the section PayPro.
6. Now select the payment methods you want and activate them.
7. Your webshop is now ready to use PayPro gateways.

### Support
Do you need help installing the PayPro plugin, please contact support@paypro.nl.

## FAQ

### Where do I find my PayPro API key?
You can find your PayPro API key at [https://www.paypro.nl/api](https://www.paypro.nl/api) or in your dashboard at 'Webshop Koppelen'

### When do I need to add a product ID?
When you use affiliate marketing or you want to use the mastercard or visa gateway, you have to add a product ID.

### Where do I find my product ID?
You can find your product ID at 'Webshop Koppelen'.

## Contributing
If you want to contribute to this project you can fork the repository. Create a new branch, add your feature and create a pull request. We will look at your request and determine if we want to add it.

## License
[MIT license](http://opensource.org/licenses/MIT)
