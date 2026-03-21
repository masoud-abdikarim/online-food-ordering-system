<?php
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    exit('Server missing MySQLi extension.');
}

// Database configuration
// IMPORTANT: Update these credentials for your InfinityFree account
$host = "localhost";
$user = "root";
$password = "123456"; // <-- put your real vPanel password
$database = "kaah";

// Enable error reporting for debugging (disable in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$connection = mysqli_connect($host, $user, $password, $database);

if (!$connection) {
    http_response_code(500);
    // Log error to a file instead of displaying it
    error_log("DB connection failed: " . mysqli_connect_error());
    exit('Database connection error. Please check your configuration.');
}

mysqli_set_charset($connection, 'utf8mb4');
