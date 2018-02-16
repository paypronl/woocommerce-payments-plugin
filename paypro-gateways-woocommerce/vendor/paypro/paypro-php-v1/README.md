![PayPro](https://paypro.nl/images/logo-ie.png)
# PayPro PHP API v1 Client
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Packagist](https://img.shields.io/packagist/v/paypro/paypro-php-v1.svg)](https://packagist.org/packages/paypro/paypro-php-v1)

This library provides a client to connect with the PayPro API.

## Requirements

 - PHP Version 5.3 or greater

## Dependencies

 - PHP extension cUrl
 - PHP extension json

If you use Composer these dependencies are automatically handled for you. For manual installation make sure these extensions are activated.

## Installation

We provide an installation guide on https://paypro.nl/developers/api-clients/php.

## Getting started

Example of creating a payment:

```php
$payproClient = new \PayPro\Client('YOUR_API_KEY');
$payproClient->setCommand('create_payment');
$payproClient->setParams(array('amount' => 500, 'consumer_email' => 'test@paypro.nl', 'pay_method' => 'ideal/INGBNL2A'));
$payproClient->execute();
```

## Documentation

For guides and code examples you can go to https://paypro.nl/developers/docs.

## Contributing
If you want to contribute to this project you can fork the repository. Create a new branch, add your feature and create a pull request. We will look at your request and determine if we want to add it.

## License
[MIT](https://github.com/paypronl/paypro-php-v1/blob/master/LICENSE)
