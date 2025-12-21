<?php
session_start();
require_once('config.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';

// Build SQL query based on filter
$where_clause = "o.user_id = $user_id";
if ($filter == 'pending') {
    $where_clause .= " AND o.status = 'Pending'";
} elseif ($filter == 'preparing') {
    $where_clause .= " AND o.status = 'Preparing'";
} elseif ($filter == 'delivery') {
    $where_clause .= " AND o.status = 'On the way'";
} elseif ($filter == 'delivered') {
    $where_clause .= " AND o.status = 'Delivered'";
}

if (!empty($search)) {
    $where_clause .= " AND (o.order_id LIKE '%$search%' OR o.status LIKE '%$search%')";
}

// Get user's orders
$orders_sql = "SELECT o.*, 
               (SELECT COUNT(*) FROM orderitem oi WHERE oi.order_id = o.order_id) as item_count,
               (SELECT GROUP_CONCAT(m.name SEPARATOR ', ') 
                FROM orderitem oi 
                JOIN menuitem m ON oi.menu_item_id = m.item_id 
                WHERE oi.order_id = o.order_id LIMIT 2) as item_names
               FROM orders o 
               WHERE $where_clause 
               ORDER BY o.order_date DESC";
$orders_result = mysqli_query($connection, $orders_sql);

// Get order statistics
$stats_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Preparing' THEN 1 ELSE 0 END) as preparing,
                SUM(CASE WHEN status = 'On the way' THEN 1 ELSE 0 END) as on_the_way,
                SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(total_amount) as total_spent
              FROM orders 
              WHERE user_id = $user_id AND payment_status = 'Paid'";
$stats_result = mysqli_query($connection, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="light-mode">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-utensils"></i> Ateye albailk</h2>
                <span class="user-role">My Orders</span>
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
                    <li class="active">
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
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>My Orders</h1>
                    <p>View and track your order history</p>
                </div>
                <div class="header-right">
                    <a href="customer_dashboard.php#menu" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Order
                    </a>
                </div>
            </header>

            <!-- Order Statistics -->
            <div class="order-stats">
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders'] ?: 0; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending'] ?: 0; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['on_the_way'] ?: 0; ?></h3>
                        <p>On The Way</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['delivered'] ?: 0; ?></h3>
                        <p>Delivered</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-section">
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                        All Orders
                    </a>
                    <a href="?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="?filter=preparing" class="filter-tab <?php echo $filter == 'preparing' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> Preparing
                    </a>
                    <a href="?filter=delivery" class="filter-tab <?php echo $filter == 'delivery' ? 'active' : ''; ?>">
                        <i class="fas fa-shipping-fast"></i> On The Way
                    </a>
                    <a href="?filter=delivered" class="filter-tab <?php echo $filter == 'delivered' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Delivered
                    </a>
                </div>
                
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by order ID or status..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </div>
                </form>
            </div>

            <!-- Orders List -->
            <div class="section">
                <div class="section-header">
                    <h2>Order History</h2>
                    <span class="order-count"><?php echo mysqli_num_rows($orders_result); ?> orders</span>
                </div>
                
                <?php if(mysqli_num_rows($orders_result) > 0): ?>
                    <div class="orders-list">
                        <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h3>Order #<?php echo $order['order_id']; ?></h3>
                                    <span class="order-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('F d, Y - h:i A', strtotime($order['order_date'])); ?>
                                    </span>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                    <span class="order-amount">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <div class="order-items">
                                    <h4><i class="fas fa-utensils"></i> Items (<?php echo $order['item_count']; ?>):</h4>
                                    <p><?php echo $order['item_names'] ? htmlspecialchars($order['item_names']) . '...' : 'No items found'; ?></p>
                                </div>
                                
                                <div class="order-info">
                                    <div class="info-item">
                                        <i class="fas fa-box"></i>
                                        <span><?php echo $order['item_count']; ?> items</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-credit-card"></i>
                                        <span class="payment-status <?php echo strtolower($order['payment_status']); ?>">
                                            <?php echo $order['payment_status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <a href="customer_order_view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if($order['status'] == 'Delivered'): ?>
                                    <button class="btn btn-sm btn-success">
                                        <i class="fas fa-star"></i> Rate Order
                                    </button>
                                <?php elseif($order['status'] == 'On the way'): ?>
                                    <button class="btn btn-sm btn-primary track-order" data-order-id="<?php echo $order['order_id']; ?>">
                                        <i class="fas fa-map-marker-alt"></i> Track Order
                                    </button>
                                <?php endif; ?>
                                <?php if($order['status'] == 'Pending'): ?>
                                    <button class="btn btn-sm btn-danger cancel-order" data-order-id="<?php echo $order['order_id']; ?>">
                                        <i class="fas fa-times"></i> Cancel Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Found</h3>
                        <p>
                            <?php if(!empty($search) || $filter != 'all'): ?>
                                Try changing your search or filter
                            <?php else: ?>
                                You haven't placed any orders yet.
                            <?php endif; ?>
                        </p>
                        <a href="customer_dashboard.php#menu" class="btn btn-primary">
                            <i class="fas fa-utensils"></i> Order Now
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Pagination (for future implementation) -->
                <div class="pagination">
                    <a href="#" class="page-link disabled"><i class="fas fa-chevron-left"></i></a>
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/script.js"></script>
    <script>
        // Cancel order confirmation
        document.querySelectorAll('.cancel-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                if(confirm('Are you sure you want to cancel this order?')) {
                    fetch('cancel_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `order_id=${orderId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            alert('Order cancelled successfully!');
                            location.reload();
                        } else {
                            alert('Error cancelling order: ' + data.message);
                        }
                    });
                }
            });
        });
        
        // Track order
        document.querySelectorAll('.track-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                window.location.href = `track_order.php?id=${orderId}`;
            });
        });
    </script>
</body>
</html>
