<?php
session_start();
require_once('config.php');

// Check if user is logged in and is delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Delivery') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Process status update
$message = '';
$error = '';

// Handle status update
if (isset($_POST['update_delivery_status'])) {
    $delivery_id = intval($_POST['delivery_id']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    $order_id = intval($_POST['order_id']);
    
    // Update delivery status
    $sql = "UPDATE delivery SET status = '$status' WHERE delivery_id = $delivery_id";
    
    if (mysqli_query($connection, $sql)) {
        // Also update order status if delivered
        if ($status == 'Delivered') {
            $order_sql = "UPDATE orders SET status = 'Delivered' WHERE order_id = $order_id";
            mysqli_query($connection, $order_sql);
            
            // Mark payment as paid if not already
            $payment_sql = "UPDATE orders SET payment_status = 'Paid' WHERE order_id = $order_id AND payment_status = 'Pending'";
            mysqli_query($connection, $payment_sql);
        } elseif ($status == 'Picked Up') {
            // Update order status to "On the way" when picked up
            $order_sql = "UPDATE orders SET status = 'On the way' WHERE order_id = $order_id";
            mysqli_query($connection, $order_sql);
        }
        
        $message = "Status updated successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle complete delivery
if (isset($_POST['complete_delivery'])) {
    $delivery_id = intval($_POST['delivery_id']);
    $order_id = intval($_POST['order_id']);
    
    // Update delivery status to Delivered
    $sql = "UPDATE delivery SET status = 'Delivered' WHERE delivery_id = $delivery_id";
    
    if (mysqli_query($connection, $sql)) {
        // Update order status
        $order_sql = "UPDATE orders SET status = 'Delivered' WHERE order_id = $order_id";
        mysqli_query($connection, $order_sql);
        
        // Mark payment as paid
        $payment_sql = "UPDATE orders SET payment_status = 'Paid' WHERE order_id = $order_id";
        mysqli_query($connection, $payment_sql);
        
        $message = "Delivery marked as completed!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Get delivery person's assigned orders
$assigned_orders_sql = "SELECT 
    d.delivery_id,
    d.order_id,
    d.status as delivery_status,
    o.*,
    u.name as customer_name,
    u.phone as customer_phone
FROM delivery d
JOIN orders o ON d.order_id = o.order_id
JOIN user u ON o.user_id = u.user_id
WHERE d.delivery_person_id = $user_id
AND d.status != 'Delivered'
ORDER BY 
    CASE d.status 
        WHEN 'Picked Up' THEN 1
        WHEN 'On the Way' THEN 2
        WHEN 'Assigned' THEN 3
        ELSE 4
    END,
    o.order_date DESC";

$assigned_orders_result = mysqli_query($connection, $assigned_orders_sql);

// Check for query error
if (!$assigned_orders_result) {
    $error = "Database error: " . mysqli_error($connection);
}

// Get completed deliveries
$completed_deliveries_sql = "SELECT 
    d.delivery_id,
    d.order_id,
    d.status as delivery_status,
    o.*,
    u.name as customer_name
FROM delivery d
JOIN orders o ON d.order_id = o.order_id
JOIN user u ON o.user_id = u.user_id
WHERE d.delivery_person_id = $user_id
AND d.status = 'Delivered'
ORDER BY o.order_date DESC
LIMIT 10";

$completed_deliveries_result = mysqli_query($connection, $completed_deliveries_sql);

// Check for query error
if (!$completed_deliveries_result) {
    $error = "Database error: " . mysqli_error($connection);
}

// Get delivery statistics
$stats = [];

// Total assigned deliveries
$sql = "SELECT COUNT(*) as total FROM delivery WHERE delivery_person_id = $user_id";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['total_assigned'] = mysqli_fetch_assoc($result)['total'];
} else {
    $stats['total_assigned'] = 0;
}

// Today's deliveries
$sql = "SELECT COUNT(*) as today FROM orders o 
        JOIN delivery d ON o.order_id = d.order_id
        WHERE d.delivery_person_id = $user_id 
        AND DATE(o.order_date) = CURDATE()";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['today_deliveries'] = mysqli_fetch_assoc($result)['today'];
} else {
    $stats['today_deliveries'] = 0;
}

// Pending deliveries
$sql = "SELECT COUNT(*) as pending FROM delivery 
        WHERE delivery_person_id = $user_id 
        AND status IN ('Assigned', 'Picked Up', 'On the Way')";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['pending_deliveries'] = mysqli_fetch_assoc($result)['pending'];
} else {
    $stats['pending_deliveries'] = 0;
}

// Completed deliveries
$sql = "SELECT COUNT(*) as completed FROM delivery 
        WHERE delivery_person_id = $user_id 
        AND status = 'Delivered'";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['completed_deliveries'] = mysqli_fetch_assoc($result)['completed'];
} else {
    $stats['completed_deliveries'] = 0;
}

// Get delivery person info
$delivery_info_sql = "SELECT * FROM user WHERE user_id = $user_id";
$delivery_info_result = mysqli_query($connection, $delivery_info_sql);
if ($delivery_info_result) {
    $delivery_info = mysqli_fetch_assoc($delivery_info_result);
} else {
    $delivery_info = [];
}

$__sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
if ($__sn === '' || $__sn[0] !== '/') {
    $__sn = '/' . ltrim($__sn, '/');
}
$__app_root = str_replace('\\', '/', dirname(dirname($__sn)));
if ($__app_root === '/' || $__app_root === '.' || $__app_root === '\\') {
    $delivery_css_href = '/css/kaah-delivery.css';
} else {
    $delivery_css_href = rtrim($__app_root, '/') . '/css/kaah-delivery.css';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Kaah Fast Food</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($delivery_css_href, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="kaah-delivery">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="kaah-brand">
                <div class="kaah-brand__logo"><i class="fas fa-motorcycle"></i></div>
                <div class="kaah-brand__text">
                    <strong>Kaah Fast Food</strong>
                    <span><i class="fas fa-location-dot"></i> New Hargeisa · Delivery</span>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p>Delivery Partner</p>
                <p><small>ID: <?php echo $user_id; ?></small></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="#assigned-orders" onclick="showSection('assigned-orders'); return false;">
                            <i class="fas fa-clipboard-list"></i> Active deliveries
                        </a>
                    </li>
                    <li>
                        <a href="#completed-deliveries" onclick="showSection('completed-deliveries'); return false;">
                            <i class="fas fa-circle-check"></i> Completed
                        </a>
                    </li>
                    <li>
                        <a href="delivery_profile.php">
                            <i class="fas fa-user"></i> My Profile
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
                    <h1>Delivery console</h1>
                    <p><?php echo htmlspecialchars($user_name); ?> · Kaah Fast Food, <strong>New Hargeisa</strong> — pick up, update status, deliver fast.</p>
                </div>
                <div class="header-right">
                    <span class="current-date"><?php echo date('F d, Y'); ?></span>
                    <span class="current-time" id="currentTime"></span>
                </div>
            </header>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd;">
                        <i class="fas fa-clipboard-list" style="color: #1976d2;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_assigned']; ?></h3>
                        <p>Total Assigned</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f3e5f5;">
                        <i class="fas fa-clock" style="color: #7b1fa2;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_deliveries']; ?></h3>
                        <p>Pending Deliveries</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">
                        <i class="fas fa-check-circle" style="color: #388e3c;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed_deliveries']; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">
                        <i class="fas fa-calendar-day" style="color: #f57c00;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['today_deliveries']; ?></h3>
                        <p>Today's Deliveries</p>
                    </div>
                </div>
            </div>

            <!-- Section 1: Assigned Orders -->
            <div id="assigned-orders" class="section active-section">
                <div class="table-header">
                    <h2>Assigned Orders</h2>
                    <p>Orders assigned to you for delivery</p>
                </div>
                
                <div class="orders-table">
                    <?php if($assigned_orders_result && mysqli_num_rows($assigned_orders_result) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Order Date</th>
                                    <th>Amount</th>
                                    <th>Delivery Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($assigned_orders_result)): 
                                    // Map delivery status to CSS class
                                    $status_class = 'status-assigned';
                                    if ($order['delivery_status'] == 'Picked Up') {
                                        $status_class = 'status-picked';
                                    } elseif ($order['delivery_status'] == 'On the Way') {
                                        $status_class = 'status-ontheway';
                                    } elseif ($order['delivery_status'] == 'Delivered') {
                                        $status_class = 'status-delivered';
                                    }
                                ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo $order['total_amount']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $order['delivery_status']; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <button onclick="showOrderDetails(<?php echo $order['order_id']; ?>)" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        
                                        <?php if($order['delivery_status'] == 'Assigned'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delivery_id" value="<?php echo $order['delivery_id']; ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <input type="hidden" name="status" value="Picked Up">
                                                <button type="submit" name="update_delivery_status" class="btn btn-success btn-sm">
                                                    <i class="fas fa-box"></i> Pick Up
                                                </button>
                                            </form>
                                        <?php elseif($order['delivery_status'] == 'Picked Up'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delivery_id" value="<?php echo $order['delivery_id']; ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <input type="hidden" name="status" value="On the Way">
                                                <button type="submit" name="update_delivery_status" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-truck"></i> Start Delivery
                                                </button>
                                            </form>
                                        <?php elseif($order['delivery_status'] == 'On the Way'): ?>
                                            <button onclick="showDeliveryCompleteModal(<?php echo $order['delivery_id']; ?>, <?php echo $order['order_id']; ?>)" 
                                                    class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Complete Delivery
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="kd-empty">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>No active deliveries</h3>
                            <p>New assignments will appear here automatically.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section 2: Completed Deliveries -->
            <div id="completed-deliveries" class="section" style="display: none;">
                <div class="table-header">
                    <h2>Completed Deliveries</h2>
                    <p>Your recently completed deliveries</p>
                </div>
                
                <div class="orders-table">
                    <?php if($completed_deliveries_result && mysqli_num_rows($completed_deliveries_result) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Order Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($completed_deliveries_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo $order['total_amount']; ?></td>
                                    <td>
                                        <span class="status-badge status-delivered">
                                            <?php echo $order['delivery_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="showOrderDetails(<?php echo $order['order_id']; ?>)" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="kd-empty">
                            <i class="fas fa-circle-check"></i>
                            <h3>No completed deliveries yet</h3>
                            <p>Finished drops show up here for your records.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeOrderDetailsModal()">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetailsContent">
                <!-- Content will be loaded via AJAX -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Complete Modal -->
    <div id="deliveryCompleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeliveryCompleteModal()">&times;</span>
            <h2>Complete Delivery</h2>
            <p>Are you sure you want to mark this delivery as completed?</p>
            
            <form method="POST" id="completeDeliveryForm">
                <input type="hidden" name="delivery_id" id="complete_delivery_id">
                <input type="hidden" name="order_id" id="complete_order_id">
                
                <div class="form-group">
                    <label>Delivery Notes (Optional)</label>
                    <textarea name="delivery_notes" class="form-control" rows="3" placeholder="Any notes about the delivery..."></textarea>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeliveryCompleteModal()" style="flex: 1;">
                        Cancel
                    </button>
                    <button type="submit" name="complete_delivery" class="btn btn-success" style="flex: 1;">
                        <i class="fas fa-check"></i> Mark as Delivered
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide sections
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active-section');
            });
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                section.classList.add('active-section');
            }
            document.querySelectorAll('.sidebar-nav li').forEach(li => li.classList.remove('active'));
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                const href = link.getAttribute('href');
                if (href === '#' + sectionId) {
                    link.parentElement.classList.add('active');
                }
            });
        }
        
        // Show order details
        function showOrderDetails(orderId) {
            // Load order details via AJAX
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                    document.getElementById('orderDetailsModal').style.display = 'flex';
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading order details. Please try again.
                        </div>
                    `;
                    document.getElementById('orderDetailsModal').style.display = 'flex';
                });
        }
        
        function closeOrderDetailsModal() {
            document.getElementById('orderDetailsModal').style.display = 'none';
        }
        
        // Delivery complete modal
        function showDeliveryCompleteModal(deliveryId, orderId) {
            document.getElementById('complete_delivery_id').value = deliveryId;
            document.getElementById('complete_order_id').value = orderId;
            document.getElementById('deliveryCompleteModal').style.display = 'flex';
        }
        
        function closeDeliveryCompleteModal() {
            document.getElementById('deliveryCompleteModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['orderDetailsModal', 'deliveryCompleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            document.getElementById('currentTime').textContent = timeString;
        }
        
        // Update time every second
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();
        
        // Auto-refresh page every 30 seconds to check for new assignments
        setTimeout(function() {
            window.location.reload();
        }, 30000); // 30 seconds
        
        // Show assigned orders by default
        document.addEventListener('DOMContentLoaded', function() {
            showSection('assigned-orders');
        });
    </script>
</body>
</html>
