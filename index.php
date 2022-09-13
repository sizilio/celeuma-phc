<?php

// Headers json
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Core
require_once 'config/core.php';

// Api class
$api = new Api\Callers\Api();

// Request
$headers = apache_request_headers();
$data = file_get_contents('php://input');
$payload = json_decode($data, true);

// Actions
// Call full url with "client", "order" or "product"
$action = $_GET['action'] ?? null;
if (empty($action) || !in_array($action, ['client', 'order', 'product']))
    $api->callback(['message' => 'No action defined.'], false);

// Data
if (empty($payload) && $action != 'product')
    $api->callback(['message' => 'Empty or invalid data.'], false);

try {

    // Log
    $api->logger(['headers' => $headers, 'payload' => $payload], $action);

    // App run
    $app = new Api\App($action, $headers, (!empty($payload) ? $payload : []));
    $app->run();
} catch (Exception $e) {
    $api->logger($e->getMessage(), 'Exception');
}

// Default return
$api->callback([], false);