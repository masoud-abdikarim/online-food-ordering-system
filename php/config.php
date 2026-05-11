<?php
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    exit('Server missing MySQLi extension.');
}

// Detect environment
$is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';

if ($is_local) {
    // Localhost configuration
    $host = "localhost";
    $user = "root";
    $password = "123456"; 
    $database = "kaah";
} else {
    // InfinityFree configuration
    // Based on user provided details: 
    // Host: sql313.infinityfree.com
    // User: if0_41577923
    // Pass: eWe3GzljdI0X
    // DB: if0_41577923_kaah
    $host = "sql313.infinityfree.com";
    $user = "if0_41577923";
    $password = "eWe3GzljdI0X"; 
    $database = "if0_41577923_kaah";
}

// For troubleshooting: append ?debug=1 to your URL to see errors on the live site
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$connection = mysqli_connect($host, $user, $password, $database);

if (!$connection) {
    http_response_code(500);
    // Log error to a file instead of displaying it (unless debug is on)
    $error_msg = "DB connection failed: " . mysqli_connect_error();
    error_log($error_msg);
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        exit($error_msg);
    }
    exit('Database connection error. Please check your configuration.');
}

mysqli_set_charset($connection, 'utf8mb4');
