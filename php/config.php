<?php
$host = "localhost";
$user = "root";
$password = "maskax470";
$database = "examProject";

$connection = mysqli_connect($host, $user, $password, $database);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}
?>