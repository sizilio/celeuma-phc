<?php

// Includes
require_once 'config.php';
require_once API_ROOT . '/src/app.php';
require_once API_ROOT . '/src/callers/api.php';
require_once API_ROOT . '/src/callers/webhook.php';
require_once API_ROOT . '/src/callers/rest.php';
require_once API_ROOT . '/src/objects/order.php';
require_once API_ROOT . '/src/objects/client.php';
require_once API_ROOT . '/src/objects/product.php';

// Error reporting
if (!API_PROD) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}