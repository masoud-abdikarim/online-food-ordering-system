<?php
session_start();
require_once('config.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
    exit();
}

// Get menu item details
$sql = "SELECT * FROM menuitem WHERE item_id = $item_id";
$result = mysqli_query($connection, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $item = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Item not found']);
}
?>