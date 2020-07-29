<?php

namespace PayPro;

/**
 * Class PayPro
 *
 * @package PayPro
 */
class PayPro {
    // @var string Base url of the API
    public static $apiUrl = 'http://pp.test:3000/post_api';

    // @var string File location of the certificate bundle
    public static $caBundleFile = '../data/ca-bundle.crt';

    const VERSION = '1.0.2';
    const API_VERSION = 'v1';
}
