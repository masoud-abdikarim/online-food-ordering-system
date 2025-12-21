<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_id = intval($_POST['delivery_id']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    
    // Update delivery status
    $sql = "UPDATE delivery SET status = '$status' WHERE delivery_id = $delivery_id";
    
    if (mysqli_query($connection, $sql)) {
        // If delivered, also update order status
        if ($status == 'Delivered') {
            $order_sql = "UPDATE orders SET status = 'Delivered' 
                         WHERE order_id = (SELECT order_id FROM delivery WHERE delivery_id = $delivery_id)";
            mysqli_query($connection, $order_sql);
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($connection)]);
    }
}
?>
