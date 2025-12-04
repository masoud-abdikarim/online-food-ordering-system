<?php
session_start();
require_once('config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.html");
    exit();
}

// Route based on user type
switch ($_SESSION['user_type']) {
    case 'Admin':
        header("Location: admin_dashboard.php");
        break;
    case 'Delivery':
        header("Location: delivery_dashboard.php");
        break;
    case 'Customer':
    default:
        header("Location: customer_dashboard.php");
        break;
}
exit();
?>