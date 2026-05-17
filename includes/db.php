<?php
// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0775, true);
}

// Function to log DB-related errors
if (!function_exists('logDbError')) {
    function logDbError($message) {
        $logFile = __DIR__ . '/logs/db.log';
        $timestamp = date("Y-m-d H:i:s");
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}


// Database configuration (prevent redefinition)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'dotrackdb');

// Create connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error to file (ensure logs directory exists)
    logDbError($e->getMessage());

    // Show user-friendly message
    die("We're experiencing technical difficulties. Please try again later.");
}
?>