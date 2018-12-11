<?php

// Include the PayPro API files
require_once('../init.php');

// Create a new PayPro client object with your API key.
// Login to PayPro and go to https://paypro.nl/api/keys to view and generate your API keys.
$payproClient = new \PayPro\Client('YOUR_API_KEY');

// Set the command you want to execute.
// Look at https://api.paypro.nl/reference for a full list of commands.
$payproClient->setCommand('create_payment');

// Define the parameters that you want to use.
// Look at https://api.paypro.nl/reference to see what parameters can be used.
$params = array(
    'amount' => 500,
    'consumer_email' => 'test@paypro.nl',
    'pay_method' => 'ideal/INGBNL2A'
);

// Set the params in the client.
$payproClient->setParams($params);

// Execute the API call and get the response
$response = $payproClient->execute();

// Response:
// array(2) {
//   ["errors"]=>
//   string(5) "false"
//   ["return"]=>
//   array(2) {
//     ["payment_hash"]=>
//     string(40) "f55d3684e12d186fe7ae7c3667e17d5f7347c931"
//     ["payment_url"]=>
//     string(70) "https://www.paypro.nl/betalen/f55d3684e12d186fe7ae7c3667e17d5f7347c931"
//   }
// }
