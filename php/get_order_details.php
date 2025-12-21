<?php
session_start();
require_once('config.php');

// Check if user is logged in and is delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Delivery') {
    die("Unauthorized access");
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    die("Invalid order ID");
}

// Get order details
$sql = "SELECT 
    o.*,
    u.name as customer_name,
    u.phone as customer_phone,
    a.address as delivery_address,
    a.city,
    a.postal_code,
    d.status as delivery_status,
    d.assigned_at,
    d.delivered_at
FROM orders o
JOIN user u ON o.user_id = u.user_id
LEFT JOIN address a ON o.order_id = a.order_id
LEFT JOIN delivery d ON o.order_id = d.order_id AND d.delivery_person_id = $user_id
WHERE o.order_id = $order_id";

$result = mysqli_query($connection, $sql);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    die("Order not found");
}

// Get order items
$items_sql = "SELECT 
    oi.*,
    mi.name as item_name,
    mi.price as item_price,
    mi.image_url
FROM orderitem oi
JOIN menuitem mi ON oi.item_id = mi.item_id
WHERE oi.order_id = $order_id";

$items_result = mysqli_query($connection, $items_sql);
?>

<div class="customer-info-card">
    <h3>Customer Information</h3>
    <div class="info-row">
        <span class="info-label">Name:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Phone:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
    </div>
    <?php if($order['delivery_address']): ?>
    <div class="info-row">
        <span class="info-label">Delivery Address:</span>
        <span class="info-value">
            <?php echo htmlspecialchars($order['delivery_address']); ?><br>
            <?php echo htmlspecialchars($order['city']); ?> <?php echo htmlspecialchars($order['postal_code']); ?>
        </span>
    </div>
    <?php endif; ?>
</div>

<div class="customer-info-card">
    <h3>Order Information</h3>
    <div class="info-row">
        <span class="info-label">Order ID:</span>
        <span class="info-value">#<?php echo $order['order_id']; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Order Date:</span>
        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Total Amount:</span>
        <span class="info-value">$<?php echo $order['total_amount']; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Payment Status:</span>
        <span class="info-value">
            <span style="color: <?php echo $order['payment_status'] == 'Paid' ? 'green' : ($order['payment_status'] == 'Pending' ? 'orange' : 'red'); ?>">
                <?php echo $order['payment_status']; ?>
            </span>
        </span>
    </div>
</div>

<?php if($order['delivery_status']): ?>
<div class="customer-info-card">
    <h3>Delivery Status</h3>
    <div class="delivery-status-timeline">
        <div class="timeline-step <?php echo in_array($order['delivery_status'], ['Assigned', 'Picked Up', 'On the way', 'Delivered']) ? 'step-completed' : ''; ?>">
            <div class="step-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="step-label">Assigned</div>
        </div>
        <div class="timeline-step <?php echo in_array($order['delivery_status'], ['Picked Up', 'On the way', 'Delivered']) ? 'step-completed' : ''; ?>">
            <div class="step-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="step-label">Picked Up</div>
        </div>
        <div class="timeline-step <?php echo in_array($order['delivery_status'], ['On the way', 'Delivered']) ? 'step-completed' : ''; ?>">
            <div class="step-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="step-label">On the way</div>
        </div>
        <div class="timeline-step <?php echo $order['delivery_status'] == 'Delivered' ? 'step-completed' : ''; ?>">
            <div class="step-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="step-label">Delivered</div>
        </div>
    </div>
    
    <div class="info-row">
        <span class="info-label">Current Status:</span>
        <span class="info-value">
            <strong><?php echo $order['delivery_status']; ?></strong>
        </span>
    </div>
    <?php if($order['assigned_at']): ?>
    <div class="info-row">
        <span class="info-label">Assigned At:</span>
        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($order['assigned_at'])); ?></span>
    </div>
    <?php endif; ?>
    <?php if($order['delivered_at']): ?>
    <div class="info-row">
        <span class="info-label">Delivered At:</span>
        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($order['delivered_at'])); ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="customer-info-card">
    <h3>Order Items</h3>
    <?php if(mysqli_num_rows($items_result) > 0): ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 10px; border-bottom: 1px solid #eee;">Item</th>
                    <th style="padding: 10px; border-bottom: 1px solid #eee;">Quantity</th>
                    <th style="padding: 10px; border-bottom: 1px solid #eee;">Price</th>
                    <th style="padding: 10px; border-bottom: 1px solid #eee;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo $item['quantity']; ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        $<?php echo $item['item_price']; ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        $<?php echo number_format($item['quantity'] * $item['item_price'], 2); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No items found for this order.</p>
    <?php endif; ?>
</div>
