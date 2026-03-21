<?php
require_once __DIR__ . '/session_auth.php';
require_authenticated_session(['Customer'], 'html');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id == 0) {
    header("Location: customer_orders.php");
    exit();
}

// Get order details
$order_sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
              FROM orders o 
              JOIN `user` u ON o.user_id = u.user_id 
              WHERE o.order_id = $order_id AND o.user_id = $user_id";
$order_result = mysqli_query($connection, $order_sql);

if ($order_result === false || mysqli_num_rows($order_result) == 0) {
    header("Location: customer_orders.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_sql = "SELECT oi.*, m.name as item_name, m.image_url 
              FROM orderitem oi 
              JOIN menuitem m ON oi.menu_item_id = m.item_id 
              WHERE oi.order_id = $order_id";
$items_result = mysqli_query($connection, $items_sql);
if ($items_result === false) {
    $items_result = null;
}

// Get delivery info if exists
$delivery_sql = "SELECT d.*, u.name as delivery_person_name 
                 FROM delivery d 
                 LEFT JOIN `user` u ON d.delivery_person_id = u.user_id 
                 WHERE d.order_id = $order_id";
$delivery_result = mysqli_query($connection, $delivery_sql);
$delivery = null;
if ($delivery_result !== false && mysqli_num_rows($delivery_result) > 0) {
    $delivery = mysqli_fetch_assoc($delivery_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Kaah Fast Food</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/order-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="light-mode">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-utensils"></i> Kaah Fast Food</h2>
                <span class="user-role">Order Details</span>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p>Customer</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="customer_dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="customer_orders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                    </li>
                    <li>
                        <a href="customer_profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="customer_cart.php">
                            <i class="fas fa-shopping-cart"></i> My Cart
                        </a>
                    </li>
                    <li class="active">
                        <a href="customer_order_view.php?id=<?php echo $order_id; ?>">
                            <i class="fas fa-eye"></i> Order Details
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Order #<?php echo $order_id; ?></h1>
                    <p>Placed on <?php echo date('F d, Y - h:i A', strtotime($order['order_date'])); ?></p>
                </div>
                <div class="header-right">
                    <a href="customer_orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                    <?php if($order['status'] == 'Pending'): ?>
                        <a href="cancel_order.php?id=<?php echo $order_id; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Cancel this order?')">
                            <i class="fas fa-times"></i> Cancel Order
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Order Status Timeline -->
            <?php
            $os = $order['status'];
            $step1_done = $os !== 'Pending';
            $step2_done = in_array($os, ['Assigned', 'On the way', 'Delivered'], true);
            $step3_done = $os === 'Delivered';
            $step2_active = in_array($os, ['Approved', 'Preparing'], true);
            $step3_active = in_array($os, ['Assigned', 'On the way'], true);
            ?>
            <div class="order-timeline">
                <div class="timeline-step <?php echo $os === 'Pending' ? 'active' : ($step1_done ? 'completed' : ''); ?>">
                    <div class="step-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="step-info">
                        <h4>Pending</h4>
                        <p>Awaiting admin approval</p>
                    </div>
                </div>
                
                <div class="timeline-step <?php echo $step2_active ? 'active' : ($step2_done && !$step2_active ? 'completed' : ''); ?>">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="step-info">
                        <h4>Approved</h4>
                        <p>Approved — waiting for a driver to be assigned</p>
                    </div>
                </div>
                
                <div class="timeline-step <?php echo $step3_active ? 'active' : ($step3_done ? 'completed' : ''); ?>">
                    <div class="step-icon">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div class="step-info">
                        <h4>Out for delivery</h4>
                        <p>Driver assigned — on the way to you</p>
                    </div>
                </div>
                
                <div class="timeline-step <?php echo $os === 'Delivered' ? 'active completed' : ''; ?>">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="step-info">
                        <h4>Delivered</h4>
                        <p>Order delivered successfully</p>
                    </div>
                </div>
            </div>

            <div class="order-details-container">
                <!-- Order Items -->
                <div class="order-section">
                    <h3><i class="fas fa-utensils"></i> Order Items</h3>
                    
                    <div class="order-items">
                        <?php if ($items_result && mysqli_num_rows($items_result) > 0): ?>
                            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="<?php echo $item['image_url'] ? $item['image_url'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                    <span class="item-quantity">Quantity: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-price">
                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-items">No items found for this order.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-section">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    
                    <div class="summary-card">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>$2.99</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax</span>
                            <span>$<?php echo number_format($order['total_amount'] * 0.10, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <strong>Total</strong>
                            <strong>$<?php echo number_format($order['total_amount'] + 2.99 + ($order['total_amount'] * 0.10), 2); ?></strong>
                        </div>
                        
                        <div class="payment-status">
                            <strong>Payment Status:</strong>
                            <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                <?php echo $order['payment_status']; ?>
                            </span>
                        </div>
                        
                        <div class="order-status">
                            <strong>Order Status:</strong>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <?php if($delivery): ?>
                <div class="order-section">
                    <h3><i class="fas fa-motorcycle"></i> Delivery Information</h3>
                    
                    <div class="delivery-info">
                        <div class="info-row">
                            <i class="fas fa-user"></i>
                            <div>
                                <strong>Delivery Person</strong>
                                <span><?php echo htmlspecialchars($delivery['delivery_person_name']); ?></span>
                            </div>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-flag"></i>
                            <div>
                                <strong>Delivery Status</strong>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $delivery['status'])); ?>">
                                    <?php echo $delivery['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Assigned At</strong>
                                <span><?php echo date('M d, Y - h:i A', strtotime($delivery['assigned_at'])); ?></span>
                            </div>
                        </div>
                        <?php if($delivery['delivered_at']): ?>
                        <div class="info-row">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Delivered At</strong>
                                <span><?php echo date('M d, Y - h:i A', strtotime($delivery['delivered_at'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../js/session_idle.js" defer></script>
</body>
</html>
