![PayPro](https://paypro.nl/images/logo-ie.png)
# PayPro Gateways - WooCommerce
[![Software License](https://img.shields.io/badge/license-GPLv2-brightgreen.svg?style=flat-square)](LICENSE.md)

With this plugin you easily add all PayPro payment gateways to your WooCommerce webshop. Currently the plugin supports the following gateways:

- iDEAL
- PayPal
- Bancontact
- Sofort
- Riverty
- SEPA Credit Transfer
- Credit card (Visa and Mastercard)

## Requirements

- PHP version 7.2 or greater
- Wordpress 5.0 or greater
- WooCommerce 5.0 or greater

## Installation

### Automatic installation

1. In the WordPress admin panel go to Plugins -> New Plugin. Search for 'PayPro Gateways - WooCommerce'.
2. Go to Plugins -> Installed Plugins. Activate the plugin named 'PayPro Gateways - WooCommerce'.
3. Set your PayPro API key at WooCommerce -> Settings -> PayPro -> Settings
4. Create a new Webhook at WooCommerce -> Settings -> PayPro -> Webhook
5. Now select the payment methods you want to use and enable them.
6. Your webshop is now ready to accept PayPro payments.

### Manual installation

1. Download the package at [wordpress.org](https://wordpress.org/plugins/paypro-gateways-woocommerce).
2. Unpack the zip file and upload the 'paypro-gateways-woocommerce' to the plugin directory. You can find the plugin directory in the 'wp-content' directory.
3. Go to Plugins -> Installed plugins. Activate the plugin named 'PayPro Gateways - WooCommerce'.
4. Set your PayPro API key at WooCommerce -> Settings -> PayPro -> Settings
5. Create a new Webhook at WooCommerce -> Settings -> PayPro -> Webhook
6. Now select the PayPro payment methods you want to use at WooCommerce -> Settings -> Payments.
7. Your webshop is now ready to accept PayPro payments.

### Support
Do you need help installing the PayPro plugin, please contact [support@paypro.nl](mailto:support@paypro.nl).

## FAQ

#### Where do I find my PayPro API key?
You can find your PayPro API key in the (PayPro dashboard)[https://app.paypro.nl/developers/api-keys].

## Contributing
If you want to contribute to this project you can fork the repository. Create a new branch, add your feature and create a pull request. We will look at your request and determine if we want to add it.

## Bugs
Did you find a bug and want to report it? Create a new issue where you clearly specify what the issue is and how it can be reproduced. Also make sure it is the actual plugin that creates the bug by disabling all unnecessary plugins.

## License
[GPLv2](http://opensource.org/licenses/GPL-2.0)
