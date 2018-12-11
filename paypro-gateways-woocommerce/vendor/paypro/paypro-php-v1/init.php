<?php

// This file can be used if not using Composer.
// Use require_once('init.php') or your own loader to include all necessary files.

// Main API files
require(dirname(__FILE__) . '/lib/PayPro.php');
require(dirname(__FILE__) . '/lib/Client.php');

// Errors
require(dirname(__FILE__) . '/lib/Error/Connection.php');
require(dirname(__FILE__) . '/lib/Error/InvalidResponse.php');
