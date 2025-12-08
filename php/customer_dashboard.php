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

// Get user's orders by status
$pending_orders_sql = "SELECT * FROM orders WHERE user_id = $user_id AND status = 'Pending' ORDER BY order_date DESC";
$preparing_orders_sql = "SELECT * FROM orders WHERE user_id = $user_id AND status = 'Preparing' ORDER BY order_date DESC";
$ontheway_orders_sql = "SELECT o.*, d.delivery_person_id, u.name as delivery_person_name 
                        FROM orders o 
                        LEFT JOIN delivery d ON o.order_id = d.order_id 
                        LEFT JOIN user u ON d.delivery_person_id = u.user_id 
                        WHERE o.user_id = $user_id AND o.status = 'On the way' 
                        ORDER BY o.order_date DESC";
$delivered_orders_sql = "SELECT * FROM orders WHERE user_id = $user_id AND status = 'Delivered' ORDER BY order_date DESC LIMIT 10";
$cancelled_orders_sql = "SELECT * FROM orders WHERE user_id = $user_id AND status = 'Rejected' ORDER BY order_date DESC LIMIT 5";

// Execute queries
$pending_orders_result = mysqli_query($connection, $pending_orders_sql);
$preparing_orders_result = mysqli_query($connection, $preparing_orders_sql);
$ontheway_orders_result = mysqli_query($connection, $ontheway_orders_sql);
$delivered_orders_result = mysqli_query($connection, $delivered_orders_sql);
$cancelled_orders_result = mysqli_query($connection, $cancelled_orders_sql);

// Get total spent
$total_sql = "SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = $user_id AND payment_status = 'Paid'";
$total_result = mysqli_query($connection, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_spent = $total_row['total_spent'] ? $total_row['total_spent'] : 0;

// Get total order count
$count_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = $user_id";
$count_result = mysqli_query($connection, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$order_count = $count_row['order_count'];

// Get menu items for display
$menu_sql = "SELECT * FROM menuitem WHERE is_available = TRUE ORDER BY item_id DESC LIMIT 12";
$menu_result = mysqli_query($connection, $menu_sql);

// Get cart items from session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_total = 0;
$cart_count = 0;

if (!empty($cart)) {
    foreach ($cart as $item) {
        $cart_total += $item['price'] * $item['quantity'];
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Ateye albailk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --sidebar-width: 280px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Fixed Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-role {
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        
        .user-info {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
        }
        
        .user-info h3 {
            margin-bottom: 5px;
            font-size: 1.2rem;
        }
        
        .user-info p {
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .sidebar-nav li.active a {
            background: rgba(52, 152, 219, 0.1);
            color: white;
            border-left-color: var(--secondary-color);
        }
        
        .sidebar-nav i {
            width: 20px;
            text-align: center;
        }
        
        .cart-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            margin-left: auto;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .header-left p {
            color: var(--light-text);
        }
        
        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .header-cart {
            position: relative;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .stat-info p {
            color: var(--light-text);
            font-size: 0.95rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: var(--light-text);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn.active {
            background: var(--secondary-color);
            color: white;
        }
        
        .tab-btn:hover:not(.active) {
            background: #f8f9fa;
            color: var(--primary-color);
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Orders Table */
        .orders-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .table-header {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
        }
        
        .table-header h3 {
            font-size: 1.3rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 18px 25px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-preparing {
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
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .menu-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .menu-card-image {
            height: 180px;
            position: relative;
            overflow: hidden;
        }
        
        .menu-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .menu-card:hover .menu-card-image img {
            transform: scale(1.05);
        }
        
        .menu-card-content {
            padding: 20px;
        }
        
        .menu-card-content h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .menu-card-content p {
            color: var(--light-text);
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.5;
            min-height: 60px;
        }
        
        .menu-card-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--success-color);
        }
        
        .btn-add-to-cart {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }
        
        .btn-add-to-cart:hover {
            background: #2980b9;
        }
        
        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 25px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .cart-sidebar.active {
            right: 0;
        }
        
        .cart-header {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-header h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light-text);
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px;
        }
        
        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .cart-item-price {
            color: var(--success-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 25px;
            height: 25px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cart-item-quantity span {
            min-width: 30px;
            text-align: center;
        }
        
        .cart-item-remove {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .cart-footer {
            padding: 25px 30px;
            border-top: 1px solid #eee;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .cart-total-amount {
            color: var(--success-color);
        }
        
        /* Cart Overlay */
        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .cart-overlay.active {
            display: block;
        }
        
        /* Checkout Modal */
        .checkout-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1001;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .checkout-modal.active {
            display: flex;
        }
        
        .checkout-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Delivery Info */
        .delivery-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            th, td {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <div class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>
    
    <!-- Cart Overlay -->
    <div class="cart-overlay" onclick="closeCart()"></div>
    
    <!-- Fixed Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-utensils"></i> Ateye albailk</h2>
            <span class="user-role">Customer Dashboard</span>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3><?php echo htmlspecialchars($user_name); ?></h3>
            <p>Welcome back!</p>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="customer_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="#orders" onclick="showTab('orders')">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </a>
                </li>
                <li>
                    <a href="#menu" onclick="showTab('menu')">
                        <i class="fas fa-utensils"></i> Order Food
                    </a>
                </li>
                <li>
                    <a href="#cart" onclick="openCart()">
                        <i class="fas fa-shopping-cart"></i> My Cart
                        <span class="cart-badge" id="sidebarCartCount"><?php echo $cart_count; ?></span>
                    </a>
                </li>
                <li>
                    <a href="customer_profile.php">
                        <i class="fas fa-user"></i> Profile
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Order delicious food from our menu</p>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" onclick="openCart()">
                    <i class="fas fa-shopping-cart"></i> View Cart
                    <span class="cart-badge" style="margin-left: 8px;"><?php echo $cart_count; ?></span>
                </button>
                <span class="current-date"><?php echo date('F d, Y'); ?></span>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" onclick="showTab('pending')">
                <div class="stat-icon" style="background: #fff3cd;">
                    <i class="fas fa-clock" style="color: #856404;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_orders_result ? mysqli_num_rows($pending_orders_result) : 0; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="stat-card" onclick="showTab('ontheway')">
                <div class="stat-icon" style="background: #d4edda;">
                    <i class="fas fa-shipping-fast" style="color: #155724;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $ontheway_orders_result ? mysqli_num_rows($ontheway_orders_result) : 0; ?></h3>
                    <p>On The Way</p>
                </div>
            </div>
            
            <div class="stat-card" onclick="showTab('delivered')">
                <div class="stat-icon" style="background: #c3e6cb;">
                    <i class="fas fa-check-circle" style="color: #155724;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $delivered_orders_result ? mysqli_num_rows($delivered_orders_result) : 0; ?></h3>
                    <p>Delivered Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f3e5f5;">
                    <i class="fas fa-dollar-sign" style="color: #7b1fa2;"></i>
                </div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($total_spent, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('orders')">
                <i class="fas fa-list"></i> All Orders
            </button>
            <button class="tab-btn" onclick="showTab('pending')">
                <i class="fas fa-clock"></i> Pending
            </button>
            <button class="tab-btn" onclick="showTab('preparing')">
                <i class="fas fa-utensils"></i> Preparing
            </button>
            <button class="tab-btn" onclick="showTab('ontheway')">
                <i class="fas fa-shipping-fast"></i> On The Way
            </button>
            <button class="tab-btn" onclick="showTab('delivered')">
                <i class="fas fa-check-circle"></i> Delivered
            </button>
            <button class="tab-btn" onclick="showTab('menu')">
                <i class="fas fa-utensils"></i> Order Food
            </button>
        </div>

        <!-- Tab: All Orders -->
        <div id="orders-tab" class="tab-content active">
            <div class="orders-table">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> All Orders</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_orders = [];
                        
                        // Combine all order results
                        $queries = [
                            'Pending' => $pending_orders_result,
                            'Preparing' => $preparing_orders_result,
                            'On the way' => $ontheway_orders_result,
                            'Delivered' => $delivered_orders_result,
                            'Cancelled' => $cancelled_orders_result
                        ];
                        
                        foreach ($queries as $status => $result) {
                            if ($result) {
                                mysqli_data_seek($result, 0);
                                while ($order = mysqli_fetch_assoc($result)) {
                                    $all_orders[] = $order;
                                }
                            }
                        }
                        
                        // Sort by date (newest first)
                        usort($all_orders, function($a, $b) {
                            return strtotime($b['order_date']) - strtotime($a['order_date']);
                        });
                        
                        if (!empty($all_orders)):
                            foreach ($all_orders as $order):
                                // Get item count
                                $items_sql = "SELECT COUNT(*) as item_count FROM orderitem WHERE order_id = " . $order['order_id'];
                                $items_result = mysqli_query($connection, $items_sql);
                                $item_count = $items_result ? mysqli_fetch_assoc($items_result)['item_count'] : 0;
                        ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $item_count; ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $order['status'])); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                                
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if($order['status'] == 'Pending'): ?>
                                    <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 40px;">
                                <i class="fas fa-shopping-bag" style="font-size: 2rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No orders yet. <a href="#menu" onclick="showTab('menu')">Start ordering!</a></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Pending Orders -->
        <div id="pending-tab" class="tab-content">
            <div class="orders-table">
                <div class="table-header">
                    <h3><i class="fas fa-clock"></i> Pending Orders (Awaiting Approval)</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_orders_result && mysqli_num_rows($pending_orders_result) > 0): 
                            mysqli_data_seek($pending_orders_result, 0);
                            while($order = mysqli_fetch_assoc($pending_orders_result)): 
                                $items_sql = "SELECT COUNT(*) as item_count FROM orderitem WHERE order_id = " . $order['order_id'];
                                $items_result = mysqli_query($connection, $items_sql);
                                $item_count = $items_result ? mysqli_fetch_assoc($items_result)['item_count'] : 0;
                        ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $item_count; ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 40px;">
                                <i class="fas fa-check-circle" style="font-size: 2rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No pending orders</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Preparing Orders -->
        <div id="preparing-tab" class="tab-content">
            <div class="orders-table">
                <div class="table-header">
                    <h3><i class="fas fa-utensils"></i> Preparing Orders</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($preparing_orders_result && mysqli_num_rows($preparing_orders_result) > 0): 
                            mysqli_data_seek($preparing_orders_result, 0);
                            while($order = mysqli_fetch_assoc($preparing_orders_result)): 
                                $items_sql = "SELECT COUNT(*) as item_count FROM orderitem WHERE order_id = " . $order['order_id'];
                                $items_result = mysqli_query($connection, $items_sql);
                                $item_count = $items_result ? mysqli_fetch_assoc($items_result)['item_count'] : 0;
                        ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $item_count; ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-preparing">
                                    <?php echo $order['status']; ?>
                                </span>
                                <br><small>Your food is being prepared</small>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 40px;">
                                <i class="fas fa-utensils" style="font-size: 2rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No orders being prepared</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: On The Way Orders -->
        <div id="ontheway-tab" class="tab-content">
            <div class="orders-table">
                <div class="table-header">
                    <h3><i class="fas fa-shipping-fast"></i> Orders On The Way</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Delivery Person</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($ontheway_orders_result && mysqli_num_rows($ontheway_orders_result) > 0): 
                            mysqli_data_seek($ontheway_orders_result, 0);
                            while($order = mysqli_fetch_assoc($ontheway_orders_result)): 
                                $items_sql = "SELECT COUNT(*) as item_count FROM orderitem WHERE order_id = " . $order['order_id'];
                                $items_result = mysqli_query($connection, $items_sql);
                                $item_count = $items_result ? mysqli_fetch_assoc($items_result)['item_count'] : 0;
                        ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $item_count; ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <small>Order is on the way</small>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 40px;">
                                <i class="fas fa-shipping-fast" style="font-size: 2rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No orders on the way</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Delivered Orders -->
        <div id="delivered-tab" class="tab-content">
            <div class="orders-table">
                <div class="table-header">
                    <h3><i class="fas fa-check-circle"></i> Delivered Orders (History)</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($delivered_orders_result && mysqli_num_rows($delivered_orders_result) > 0): 
                            mysqli_data_seek($delivered_orders_result, 0);
                            while($order = mysqli_fetch_assoc($delivered_orders_result)): 
                                $items_sql = "SELECT COUNT(*) as item_count FROM orderitem WHERE order_id = " . $order['order_id'];
                                $items_result = mysqli_query($connection, $items_sql);
                                $item_count = $items_result ? mysqli_fetch_assoc($items_result)['item_count'] : 0;
                        ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $item_count; ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-delivered">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 40px;">
                                <i class="fas fa-history" style="font-size: 2rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No delivered orders yet</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Order Food -->
        <div id="menu-tab" class="tab-content">
            <div class="menu-grid">
                <?php if($menu_result && mysqli_num_rows($menu_result) > 0): ?>
                    <?php while($item = mysqli_fetch_assoc($menu_result)): ?>
                    <div class="menu-card">
                        <div class="menu-card-image">
                            <img src="<?php echo $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="menu-card-content">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                            <div class="menu-card-details">
                                <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                                <button class="btn-add-to-cart" 
                                        data-item-id="<?php echo $item['item_id']; ?>"
                                        data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                        data-item-price="<?php echo $item['price']; ?>"
                                        data-item-image="<?php echo $item['image_url']; ?>">
                                    <i class="fas fa-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <i class="fas fa-utensils" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No menu items available</h3>
                        <p>Check back later for delicious food options!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Cart Sidebar -->
    <aside class="cart-sidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart"></i> Your Cart</h3>
            <button class="cart-close" onclick="closeCart()">&times;</button>
        </div>
        
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be loaded here -->
            <?php if(empty($cart)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some delicious food to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach($cart as $item): ?>
                <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                    <div class="cart-item-image">
                        <img src="<?php echo $item['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                            <span><?php echo $item['quantity']; ?></span>
                            <button class="quantity-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                        </div>
                    </div>
                    <button class="cart-item-remove" onclick="removeCartItem(<?php echo $item['id']; ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span class="cart-total-amount" id="cartTotal">$<?php echo number_format($cart_total, 2); ?></span>
            </div>
            <button class="btn btn-primary" style="width: 100%; padding: 15px;" onclick="checkout()" <?php echo empty($cart) ? 'disabled' : ''; ?>>
                <i class="fas fa-shopping-bag"></i> Proceed to Checkout
            </button>
        </div>
    </aside>

    <!-- Checkout Modal -->
    <div class="checkout-modal" id="checkoutModal">
        <div class="checkout-content">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-bag"></i> Checkout</h2>
                <button class="modal-close" onclick="closeCheckout()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="checkoutForm">
                    <div class="form-group">
                        <label>Delivery Address *</label>
                        <textarea name="address" class="form-control" rows="3" required placeholder="Enter your delivery address"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" required value="<?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Cash">Cash on Delivery</option>
                            <option value="Card">Credit/Debit Card</option>
                            <option value="Digital">Digital Wallet</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Special Instructions (Optional)</label>
                        <textarea name="instructions" class="form-control" rows="2" placeholder="Any special instructions for your order..."></textarea>
                    </div>
                    <div class="order-summary" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
                        <h4 style="margin-bottom: 15px;">Order Summary</h4>
                        <div id="orderSummary">
                            <!-- Order summary will be loaded here -->
                        </div>
                        <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 1.1rem; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <span>Total:</span>
                            <span id="orderTotal">$0.00</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Show/hide tabs
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            const tab = document.getElementById(tabName + '-tab');
            if (tab) {
                tab.classList.add('active');
            }
            
            // Update active tab button
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            event.currentTarget.classList.add('active');
            
            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('active');
            }
        }
        
        // Cart functionality
        function openCart() {
            document.querySelector('.cart-sidebar').classList.add('active');
            document.querySelector('.cart-overlay').classList.add('active');
            updateCartDisplay();
        }
        
        function closeCart() {
            document.querySelector('.cart-sidebar').classList.remove('active');
            document.querySelector('.cart-overlay').classList.remove('active');
        }
        
        function updateCartDisplay() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const cartCounts = document.querySelectorAll('.cart-badge, #sidebarCartCount');
            
            let total = 0;
            let count = 0;
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Add some delicious food to get started!</p>
                    </div>
                `;
            } else {
                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item" data-item-id="${item.id}">
                        <div class="cart-item-image">
                            <img src="${item.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'}" 
                                 alt="${item.name}">
                        </div>
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                            <div class="cart-item-quantity">
                                <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, 1)">+</button>
                            </div>
                        </div>
                        <button class="cart-item-remove" onclick="removeCartItem(${item.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('');
                
                // Calculate total and count
                cart.forEach(item => {
                    total += item.price * item.quantity;
                    count += item.quantity;
                });
            }
            
            // Update totals
            cartTotal.textContent = '$' + total.toFixed(2);
            cartCounts.forEach(badge => {
                badge.textContent = count;
            });
            
            // Update checkout button
            const checkoutBtn = document.querySelector('.cart-footer .btn-primary');
            checkoutBtn.disabled = cart.length === 0;
        }
        
        // Add to cart
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-add-to-cart') || e.target.closest('.btn-add-to-cart')) {
                const button = e.target.classList.contains('btn-add-to-cart') 
                    ? e.target 
                    : e.target.closest('.btn-add-to-cart');
                
                const itemId = parseInt(button.dataset.itemId);
                const itemName = button.dataset.itemName;
                const itemPrice = parseFloat(button.dataset.itemPrice);
                const itemImage = button.dataset.itemImage;
                
                addItemToCart(itemId, itemName, itemPrice, itemImage);
            }
        });
        
        function addItemToCart(id, name, price, image) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    image: image,
                    quantity: 1
                });
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
            showNotification(`${name} added to cart!`, 'success');
        }
        
        function removeCartItem(itemId) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const targetId = parseInt(itemId);
            cart = cart.filter(item => parseInt(item.id) !== targetId);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
            showNotification('Item removed from cart', 'warning');
        }
        
        function updateCartQuantity(itemId, change) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const targetId = parseInt(itemId);
            const item = cart.find(item => parseInt(item.id) === targetId);
            
            if (item) {
                item.quantity += change;
                if (item.quantity < 1) {
                    cart = cart.filter(it => parseInt(it.id) !== targetId);
                    localStorage.setItem('cart', JSON.stringify(cart));
                    updateCartDisplay();
                    return;
                }
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
        }
        
        // Checkout
        function checkout() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            // Update order summary
            const orderSummary = document.getElementById('orderSummary');
            const orderTotal = document.getElementById('orderTotal');
            
            let summaryHTML = '';
            let total = 0;
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                summaryHTML += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>${item.name} x${item.quantity}</span>
                        <span>$${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            });
            
            orderSummary.innerHTML = summaryHTML;
            orderTotal.textContent = '$' + total.toFixed(2);
            
            // Show checkout modal
            document.getElementById('checkoutModal').classList.add('active');
        }
        
        function closeCheckout() {
            document.getElementById('checkoutModal').classList.remove('active');
        }
        
        // Submit checkout form
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Calculate total
            let total = 0;
            cart.forEach(item => {
                total += item.price * item.quantity;
            });
            
            // Prepare order data
            const orderData = {
                user_id: <?php echo $user_id; ?>,
                total_amount: total,
                address: formData.get('address'),
                phone: formData.get('phone'),
                payment_method: formData.get('payment_method'),
                instructions: formData.get('instructions'),
                items: cart
            };
            
            // Submit order via AJAX
            fetch('place_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    if (Array.isArray(data.removed_items) && data.removed_items.length > 0) {
                        let cart = JSON.parse(localStorage.getItem('cart')) || [];
                        const removedSet = new Set(data.removed_items.map(id => parseInt(id)));
                        cart = cart.filter(item => !removedSet.has(parseInt(item.id)));
                        localStorage.setItem('cart', JSON.stringify(cart));
                        updateCartDisplay();
                        showNotification('Unavailable items were removed from your cart.', 'warning');
                        return;
                    }
                    alert('Error placing order: ' + (data.error || 'Unknown error'));
                    return;
                }
                // success
                    // Clear cart
                    localStorage.removeItem('cart');
                    updateCartDisplay();
                    closeCart();
                    closeCheckout();
                    
                    // Show success message
                    showNotification('Order placed successfully! Your order is pending approval.', 'success');
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error placing order');
            });
        });
        
        // Order actions
        function viewOrder(orderId) {
            window.location.href = `customer_order_view.php?id=${orderId}`;
        }
        
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch(`cancel_order.php?id=${orderId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Order cancelled successfully', 'warning');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            alert('Error cancelling order: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error cancelling order');
                    });
            }
        }
        
        // Notification system
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification-popup ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                border-left: 4px solid ${type === 'success' ? '#27ae60' : '#e74c3c'};
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
            
            // Add slideOut animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Initialize cart display
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            showTab('orders');
        });
    </script>
</body>
</html>
