<?php
require_once __DIR__ . '/session_auth.php';

$wants_json = session_wants_json_response();
if ($wants_json) {
    header('Content-Type: application/json');
}

require_authenticated_session(['Customer'], $wants_json ? 'json' : 'html');

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    if ($wants_json) {
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    } else {
        header('Location: customer_orders.php?status=invalid');
    }
    exit();
}

$check_sql = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id AND status = 'Pending'";
$check_result = mysqli_query($connection, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    if ($wants_json) {
        echo json_encode(['success' => false, 'error' => 'Order not found or cannot be cancelled']);
    } else {
        header('Location: customer_orders.php?status=not_cancellable');
    }
    exit();
}

$sql = "UPDATE orders SET status = 'Rejected' WHERE order_id = $order_id";

if (mysqli_query($connection, $sql)) {
    if ($wants_json) {
        echo json_encode(['success' => true]);
    } else {
        header('Location: customer_orders.php?status=cancelled');
    }
} else {
    if ($wants_json) {
        echo json_encode(['success' => false, 'error' => mysqli_error($connection)]);
    } else {
        header('Location: customer_orders.php?status=error');
    }
}
?>
