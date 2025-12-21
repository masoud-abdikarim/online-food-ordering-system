<?php
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    exit('Server missing MySQLi extension.');
}
$host = "sql311.ezyro.com";
$user = "ezyro_40730793";
$password = "maskax470";
$database = "ezyro_40730793_Ateye";
$connection = @mysqli_connect($host, $user, $password, $database);
if (!$connection) {
    http_response_code(500);
    error_log("DB connection failed: " . mysqli_connect_error());
    exit('Database connection error.');
}
mysqli_set_charset($connection, 'utf8mb4');
?>
