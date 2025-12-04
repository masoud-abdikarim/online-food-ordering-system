<?php
session_start();
require_once('config.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Process actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$error = '';

// Handle menu item actions
if (isset($_POST['add_menu_item'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = mysqli_real_escape_string($connection, $_POST['image_url']);
    
    $sql = "INSERT INTO MenuItem (name, description, price, image_url) 
            VALUES ('$name', '$description', '$price', '$image_url')";
    
    if (mysqli_query($connection, $sql)) {
        $message = "Menu item added successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle order status update
if (isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    
    $sql = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
    
    if (mysqli_query($connection, $sql)) {
        $message = "Order status updated successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle order approval/rejection
if (isset($_POST['approve_order'])) {
    $order_id = intval($_POST['order_id']);
    $action = mysqli_real_escape_string($connection, $_POST['action']);
    
    if ($action == 'approve') {
        $sql = "UPDATE orders SET status = 'Preparing' WHERE order_id = $order_id";
        $message = "Order approved and moved to preparation!";
    } else {
        $sql = "UPDATE orders SET status = 'Pending', payment_status = 'Failed' WHERE order_id = $order_id";
        $message = "Order rejected!";
    }
    
    mysqli_query($connection, $sql);
}

// Handle delivery assignment
if (isset($_POST['assign_delivery'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_person_id = intval($_POST['delivery_person_id']);
    
    // Check if delivery already exists
    $check_sql = "SELECT * FROM Delivery WHERE order_id = $order_id";
    $check_result = mysqli_query($connection, $check_sql);
    
    if (mysqli_num_rows($check_result) == 0) {
        $sql = "INSERT INTO Delivery (order_id, delivery_person_id, status) 
                VALUES ($order_id, $delivery_person_id, 'Assigned')";
        
        if (mysqli_query($connection, $sql)) {
            $message = "Delivery assigned successfully!";
        } else {
            $error = "Error: " . mysqli_error($connection);
        }
    } else {
        $error = "Delivery already assigned to this order!";
    }
}

// Handle menu item deletion
if (isset($_GET['delete_item'])) {
    $item_id = intval($_GET['delete_item']);
    $sql = "UPDATE MenuItem SET is_available = FALSE WHERE item_id = $item_id";
    
    if (mysqli_query($connection, $sql)) {
        $message = "Menu item removed successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Get dashboard statistics
$stats = [];

// Total orders
$sql = "SELECT COUNT(*) as total FROM orders";
$result = mysqli_query($connection, $sql);
$stats['total_orders'] = mysqli_fetch_assoc($result)['total'];

// Total revenue
$sql = "SELECT SUM(total_amount) as revenue FROM orders WHERE payment_status = 'Paid'";
$result = mysqli_query($connection, $sql);
$stats['total_revenue'] = mysqli_fetch_assoc($result)['revenue'] ?: 0;

// Total customers
$sql = "SELECT COUNT(*) as customers FROM User WHERE user_type = 'Customer'";
$result = mysqli_query($connection, $sql);
$stats['total_customers'] = mysqli_fetch_assoc($result)['customers'];

// Pending orders
$sql = "SELECT COUNT(*) as pending FROM orders WHERE status = 'Pending'";
$result = mysqli_query($connection, $sql);
$stats['pending_orders'] = mysqli_fetch_assoc($result)['pending'];

// Recent orders
$recent_orders_sql = "SELECT o.*, u.name as customer_name 
                     FROM orders o 
                     JOIN User u ON o.user_id = u.user_id 
                     ORDER BY o.order_date DESC LIMIT 10";
$recent_orders_result = mysqli_query($connection, $recent_orders_sql);

// Get pending orders for approval
$pending_orders_sql = "SELECT o.*, u.name as customer_name 
                      FROM orders o 
                      JOIN User u ON o.user_id = u.user_id 
                      WHERE o.status = 'Pending' 
                      ORDER BY o.order_date DESC";
$pending_orders_result = mysqli_query($connection, $pending_orders_sql);

// Get menu items
$menu_items_sql = "SELECT * FROM MenuItem WHERE is_available = TRUE ORDER BY item_id DESC";
$menu_items_result = mysqli_query($connection, $menu_items_sql);

// Get delivery personnel
$delivery_personnel_sql = "SELECT * FROM User WHERE user_type = 'Delivery' AND is_active = TRUE";
$delivery_personnel_result = mysqli_query($connection, $delivery_personnel_sql);

// Get unassigned orders
$unassigned_orders_sql = "SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         JOIN User u ON o.user_id = u.user_id 
                         WHERE o.status IN ('Preparing', 'On the way') 
                         AND o.order_id NOT IN (SELECT order_id FROM Delivery) 
                         ORDER BY o.order_date DESC";
$unassigned_orders_result = mysqli_query($connection, $unassigned_orders_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        }
    </style>
</head>
<body class="light-mode">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-utensils"></i> Ateye albailk</h2>
                <span class="user-role">Admin Dashboard</span>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p>Administrator</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="admin_dashboard.php">
                            <i class="fas fa-chart-bar"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#menu-management" onclick="showSection('menu-management')">
                            <i class="fas fa-utensils"></i> Menu Management
                        </a>
                    </li>
                    <li>
                        <a href="#order-approval" onclick="showSection('order-approval')">
                            <i class="fas fa-check-circle"></i> Order Approval
                        </a>
                    </li>
                    <li>
                        <a href="#delivery-assignment" onclick="showSection('delivery-assignment')">
                            <i class="fas fa-motorcycle"></i> Delivery Assignment
                        </a>
                    </li>
                    <li>
                        <a href="#order-status" onclick="showSection('order-status')">
                            <i class="fas fa-sync-alt"></i> Update Order Status
                        </a>
                    </li>
                    <li>
                        <a href="admin_users.php">
                            <i class="fas fa-users"></i> Users
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
                    <h1>Admin Dashboard</h1>
                    <p>System Overview & Statistics</p>
                </div>
                <div class="header-right">
                    <span class="current-date"><?php echo date('F d, Y'); ?></span>
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
                        <i class="fas fa-shopping-bag" style="color: #1976d2;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f3e5f5;">
                        <i class="fas fa-dollar-sign" style="color: #7b1fa2;"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">
                        <i class="fas fa-users" style="color: #388e3c;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_customers']; ?></h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">
                        <i class="fas fa-clock" style="color: #f57c00;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>

            <!-- Section 1: Menu Management -->
            <div id="menu-management" class="section active-section">
                <div class="section-header">
                    <h2>Menu Management</h2>
                    <button class="btn btn-primary" onclick="showAddMenuModal()">
                        <i class="fas fa-plus"></i> Add Menu Item
                    </button>
                </div>
                
                <div class="menu-items-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($menu_items_result) > 0): ?>
                                <?php while($item = mysqli_fetch_assoc($menu_items_result)): ?>
                                <tr>
                                    <td><?php echo $item['item_id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></td>
                                    <td>$<?php echo $item['price']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $item['is_available'] ? 'status-delivered' : 'status-pending'; ?>">
                                            <?php echo $item['is_available'] ? 'Available' : 'Not Available'; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="admin_menu_edit.php?id=<?php echo $item['item_id']; ?>" class="btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete_item=<?php echo $item['item_id']; ?>" 
                                           class="btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to remove this item?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No menu items found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 2: Order Approval -->
            <div id="order-approval" class="section" style="display: none;">
                <div class="section-header">
                    <h2>Order Approval</h2>
                    <span class="badge"><?php echo mysqli_num_rows($pending_orders_result); ?> pending</span>
                </div>
                
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($pending_orders_result) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($pending_orders_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo $order['total_amount']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" name="approve_order" class="btn-sm btn-success">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" name="approve_order" class="btn-sm btn-danger">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                        <a href="admin_order_view.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No pending orders</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Delivery Assignment -->
            <div id="delivery-assignment" class="section" style="display: none;">
                <div class="section-header">
                    <h2>Delivery Assignment</h2>
                    <span class="badge"><?php echo mysqli_num_rows($unassigned_orders_result); ?> unassigned</span>
                </div>
                
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Assign Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($unassigned_orders_result) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($unassigned_orders_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>$<?php echo $order['total_amount']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="assign-delivery-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="delivery_person_id" required style="padding: 5px; margin-right: 10px;">
                                                <option value="">Select Delivery Person</option>
                                                <?php 
                                                mysqli_data_seek($delivery_personnel_result, 0);
                                                while($delivery = mysqli_fetch_assoc($delivery_personnel_result)): ?>
                                                <option value="<?php echo $delivery['user_id']; ?>">
                                                    <?php echo htmlspecialchars($delivery['name']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <button type="submit" name="assign_delivery" class="btn-sm">
                                                <i class="fas fa-motorcycle"></i> Assign
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">All orders are assigned or delivered</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 4: Update Order Status -->
            <div id="order-status" class="section" style="display: none;">
                <div class="section-header">
                    <h2>Update Order Status</h2>
                </div>
                
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Current Status</th>
                                <th>Update Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($recent_orders_result, 0);
                            if(mysqli_num_rows($recent_orders_result) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="update-status-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="status" required style="padding: 5px; margin-right: 10px;">
                                                <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Preparing" <?php echo $order['status'] == 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                <option value="On the way" <?php echo $order['status'] == 'On the way' ? 'selected' : ''; ?>>On the way</option>
                                                <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            </select>
                                    </td>
                                    <td>
                                            <button type="submit" name="update_order_status" class="btn-sm">
                                                <i class="fas fa-sync-alt"></i> Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Menu Item Modal -->
            <div id="addMenuModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeAddMenuModal()">&times;</span>
                    <h2>Add New Menu Item</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" name="name" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" required class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Price ($)</label>
                            <input type="number" step="0.01" name="price" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>
                        <button type="submit" name="add_menu_item" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </form>
                </div>
            </div>
        </main>
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
        
        // Modal functions
        function showAddMenuModal() {
            document.getElementById('addMenuModal').style.display = 'block';
        }
        
        function closeAddMenuModal() {
            document.getElementById('addMenuModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addMenuModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Show menu management by default
        document.addEventListener('DOMContentLoaded', function() {
            showSection('menu-management');
        });
    </script>
</body>
</html>