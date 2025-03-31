<?php
// Set headers for CORS and JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include configuration and helper files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Response.php';

// Parse the request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/backend/'; // Change this if your API is at a different path
$endpoint = str_replace($base_path, '', $request_uri);

// Route the request to the appropriate endpoint
try {
    switch (true) {
        // Products endpoint
        case (preg_match('/^products(\/?|\/(.*))?$/', $endpoint, $matches)):
            require __DIR__ . '/api/products.php';
            break;

        // Orders endpoint
        case (preg_match('/^orders(\/?|\/(.*))?$/', $endpoint, $matches)):
            require __DIR__ . '/api/orders.php';
            break;

        // Email endpoint
        case (preg_match('/^email(\/?|\/(.*))?$/', $endpoint, $matches)):
            require __DIR__ . '/api/email.php';
            break;

        // Default - endpoint not found
        default:
            Response::json_error('Endpoint not found', 404);
            break;
    }
} catch (Exception $e) {
    Response::json_error('Server error: ' . $e->getMessage(), 500);
}