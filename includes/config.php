<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_NAME', 'hotel_reservation_system');

// Debug Settings
define('DEBUG_MODE', true); // Shows detailed errors in development

// Force error reporting in development
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
