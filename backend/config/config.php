<?php
// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Define constants
define('APP_NAME', 'Purple Engineering Strainer API');
define('APP_VERSION', '1.0.0');
define('FRONTEND_URL', 'http://localhost:5173'); // Change this to your frontend URL

// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');                  // Change this to your database username
define('DB_PASSWORD', '');                  // Change this to your database password
define('DB_NAME', 'purple_engineering');    // Using the correct database name

// Email settings
define('EMAIL_TO', 'dev@prpl.com.au');
define('EMAIL_FROM', 'noreply@prpl.com.au'); // Changed to match domain
define('EMAIL_FROM_NAME', 'Purple Engineering Strainer Wizard');
define('EMAIL_REPLY_TO', ''); // Leave empty to use EMAIL_FROM

// Include database configuration
require_once __DIR__ . '/database.php';