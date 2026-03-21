<?php
require_once __DIR__ . '/session_auth.php';
require_authenticated_session(['Customer'], 'auto');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

mysqli_begin_transaction($connection);

try {
    $payment_method = isset($data['payment_method']) ? mysqli_real_escape_string($connection, $data['payment_method']) : '';
    $address_raw = isset($data['address']) ? trim((string)$data['address']) : '';
    if (strlen($address_raw) < 5) {
        throw new Exception('Please enter a complete delivery address (at least 5 characters).');
    }
    $address_esc = mysqli_real_escape_string($connection, $address_raw);
    $city_esc = mysqli_real_escape_string($connection, 'New Hargeisa');

    if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
        throw new Exception('No items in order');
    }

    $subtotal = 0.0;
    $validated_items = [];
    $removed_items = [];
    foreach ($data['items'] as $item) {
        $menu_item_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        if ($menu_item_id <= 0 || $quantity <= 0) {
            throw new Exception('Invalid item or quantity');
        }
        $price_sql = "SELECT price FROM menuitem WHERE item_id = $menu_item_id AND is_available = TRUE";
        $price_res = mysqli_query($connection, $price_sql);
        if (!$price_res || mysqli_num_rows($price_res) === 0) {
            $removed_items[] = $menu_item_id;
            continue;
        }
        $row = mysqli_fetch_assoc($price_res);
        $price = floatval($row['price']);
        $subtotal += $price * $quantity;
        $validated_items[] = [
            'id' => $menu_item_id,
            'quantity' => $quantity,
            'price' => $price,
        ];
    }
    if (count($validated_items) === 0) {
        mysqli_rollback($connection);
        echo json_encode([
            'success' => false,
            'error' => 'Your cart contains only unavailable items. They have been removed.',
            'removed_items' => $removed_items
        ]);
        exit();
    }
    $delivery_fee = isset($data['delivery_fee']) ? floatval($data['delivery_fee']) : 2.99;
    $tax = $subtotal * 0.10;
    $total_amount = $subtotal + $delivery_fee + $tax;

    $payment_status = 'Pending';
    if ($payment_method && in_array(strtolower($payment_method), ['card','digital'])) {
        $payment_status = 'Pending';
    }
    
    $order_sql = "INSERT INTO orders (user_id, total_amount, status, payment_status) 
                  VALUES ($user_id, $total_amount, 'Pending', '$payment_status')";
    
    if (!mysqli_query($connection, $order_sql)) {
        throw new Exception('Error creating order: ' . mysqli_error($connection));
    }
    
    $order_id = mysqli_insert_id($connection);

    $address_sql = "INSERT INTO address (order_id, address, city, postal_code) 
                    VALUES ($order_id, '$address_esc', '$city_esc', NULL)";
    if (!mysqli_query($connection, $address_sql)) {
        throw new Exception('Could not save delivery address: ' . mysqli_error($connection));
    }
    
    foreach ($validated_items as $vi) {
        $menu_item_id = $vi['id'];
        $quantity = $vi['quantity'];
        $price = $vi['price'];

        $item_sql = "INSERT INTO orderitem (order_id, menu_item_id, quantity, price) 
                     VALUES ($order_id, $menu_item_id, $quantity, $price)";

        if (!mysqli_query($connection, $item_sql)) {
            throw new Exception('Error adding order item: ' . mysqli_error($connection));
        }
    }
    
    mysqli_commit($connection);
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'removed_items' => $removed_items
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($connection);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>