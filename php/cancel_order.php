<?php
session_start();
require_once('config.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id > 0) {
    // Check if order belongs to user and is pending
    $check_sql = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id AND status = 'Pending'";
    $check_result = mysqli_query($connection, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Cancel the order
        $cancel_sql = "UPDATE orders SET status = 'Cancelled', payment_status = 'Failed' WHERE order_id = $order_id";
        
        if (mysqli_query($connection, $cancel_sql)) {
            $_SESSION['success'] = "Order #$order_id has been cancelled successfully.";
        } else {
            $_SESSION['error'] = "Error cancelling order: " . mysqli_error($connection);
        }
    } else {
        $_SESSION['error'] = "Order not found or cannot be cancelled.";
    }
}

// Redirect back to dashboard
header("Location: customer_dashboard.php");
exit();
?>