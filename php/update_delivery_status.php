<?php
require_once __DIR__ . '/session_auth.php';
require_authenticated_session(['Delivery'], 'auto');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_id = intval($_POST['delivery_id']);
    $raw = isset($_POST['status']) ? (string)$_POST['status'] : '';
    $allowed = ['Assigned', 'Picked Up', 'On the way', 'Delivered'];
    if (!in_array($raw, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    $status = mysqli_real_escape_string($connection, $raw);
    $sql = ($raw === 'Delivered')
        ? "UPDATE delivery SET status = '$status', delivered_at = NOW() WHERE delivery_id = $delivery_id"
        : "UPDATE delivery SET status = '$status' WHERE delivery_id = $delivery_id";

    if (mysqli_query($connection, $sql)) {
        if ($raw === 'Delivered') {
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
