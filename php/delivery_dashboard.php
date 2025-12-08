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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... (keep all your existing CSS styles) ... */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f5f5f5;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
        }
        
        .orders-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-assigned {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-picked {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-ontheway {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #c3e6cb;
            color: #155724;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 50%;
            max-width: 500px;
        }
        
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .customer-info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            width: 120px;
            font-weight: 600;
            color: #7f8c8d;
        }
        
        .info-value {
            flex: 1;
            color: #2c3e50;
        }
        
        .delivery-status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            position: relative;
        }
        
        .delivery-status-timeline::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #eee;
            z-index: 1;
        }
        
        .timeline-step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #7f8c8d;
        }
        
        .step-active .step-icon {
            background: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        .step-completed .step-icon {
            background: #27ae60;
            border-color: #27ae60;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .step-active .step-label {
            color: #3498db;
            font-weight: 600;
        }
        
        .step-completed .step-label {
            color: #27ae60;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-motorcycle"></i> Ateye albailk</h2>
                <span class="user-role">Delivery Dashboard</span>
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
                        <a href="delivery_dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#assigned-orders" onclick="showSection('assigned-orders')">
                            <i class="fas fa-clipboard-list"></i> Assigned Orders
                        </a>
                    </li>
                    <li>
                        <a href="#completed-deliveries" onclick="showSection('completed-deliveries')">
                            <i class="fas fa-check-circle"></i> Completed Deliveries
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
                    <h1>Delivery Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</p>
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
                        <div style="padding: 40px; text-align: center; color: #7f8c8d;">
                            <i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No assigned orders</h3>
                            <p>You don't have any orders assigned to you at the moment.</p>
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
                        <div style="padding: 40px; text-align: center; color: #7f8c8d;">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No completed deliveries</h3>
                            <p>You haven't completed any deliveries yet.</p>
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
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active-section');
            });
            
            // Show selected section
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                section.classList.add('active-section');
            }
            
            // Update active nav
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.parentElement.classList.remove('active');
            });
            event.target.parentElement.classList.add('active');
        }
        
        // Show order details
        function showOrderDetails(orderId) {
            // Load order details via AJAX
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                    document.getElementById('orderDetailsModal').style.display = 'block';
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading order details. Please try again.
                        </div>
                    `;
                    document.getElementById('orderDetailsModal').style.display = 'block';
                });
        }
        
        function closeOrderDetailsModal() {
            document.getElementById('orderDetailsModal').style.display = 'none';
        }
        
        // Delivery complete modal
        function showDeliveryCompleteModal(deliveryId, orderId) {
            document.getElementById('complete_delivery_id').value = deliveryId;
            document.getElementById('complete_order_id').value = orderId;
            document.getElementById('deliveryCompleteModal').style.display = 'block';
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