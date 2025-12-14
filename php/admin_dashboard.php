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
$message = '';
$error = '';

// ========== USER MANAGEMENT FUNCTIONS ==========

// 1. CREATE USER (Admin/Delivery/Customer)
if (isset($_POST['create_user'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($connection, $_POST['user_type']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if(empty($name) || empty($phone) || empty($password) || empty($confirm_password)){
        $errors[] = "All fields are required";
    }
    
    if(!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)){
        $errors[] = "Please enter a valid phone number (10-15 digits)";
    }
    
    if(!empty($password) && !empty($confirm_password) && $password !== $confirm_password){
        $errors[] = "Passwords do not match";
    }
    
    if(!empty($password) && strlen($password) < 6){
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // Check if phone already exists
    if(empty($errors)) {
        $check_sql = "SELECT user_id FROM user WHERE phone = '$phone'";
        $check_result = mysqli_query($connection, $check_sql);
        
        if(mysqli_num_rows($check_result) > 0){
            $errors[] = "Phone number already registered";
        }
    }
    
    if(empty($errors)){
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database
        $sql = "INSERT INTO user (name, phone, password, user_type, is_active) 
                VALUES ('$name', '$phone', '$hashed_password', '$user_type', $is_active)";
        
        if(mysqli_query($connection, $sql)){
            $message = ucfirst($user_type) . " account created successfully!";
        } else {
            $error = "Error creating user: " . mysqli_error($connection);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// 2. UPDATE USER
if (isset($_POST['update_user'])) {
    $user_id_update = intval($_POST['user_id']);
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $user_type = mysqli_real_escape_string($connection, $_POST['user_type']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Don't allow current admin to change their own role
    if ($user_id_update == $_SESSION['user_id']) {
        $user_type = 'Admin'; // Force admin role for current user
        $is_active = 1; // Can't deactivate yourself
    }
    
    // Check if phone already exists for another user
    $check_sql = "SELECT user_id FROM user WHERE phone = '$phone' AND user_id != $user_id_update";
    $check_result = mysqli_query($connection, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0){
        $error = "Phone number already registered to another user";
    } else {
        $sql = "UPDATE user SET 
                name = '$name', 
                phone = '$phone', 
                user_type = '$user_type',
                is_active = $is_active 
                WHERE user_id = $user_id_update";
        
        if (mysqli_query($connection, $sql)) {
            $message = "User updated successfully!";
        } else {
            $error = "Error updating user: " . mysqli_error($connection);
        }
    }
}

// 3. DELETE/DEACTIVATE USER
if (isset($_POST['delete_user'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // Don't allow deleting yourself
    if ($delete_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $sql = "DELETE FROM user WHERE user_id = $delete_id";
        
        if (mysqli_query($connection, $sql)) {
            $message = "User deleted successfully!";
        } else {
            // If delete fails (due to foreign key constraints), deactivate instead
            $sql = "UPDATE user SET is_active = 0 WHERE user_id = $delete_id";
            if (mysqli_query($connection, $sql)) {
                $message = "User deactivated successfully!";
            } else {
                $error = "Error: " . mysqli_error($connection);
            }
        }
    }
}

// 4. ACTIVATE USER
if (isset($_POST['activate_user'])) {
    $activate_id = intval($_POST['activate_id']);
    $sql = "UPDATE user SET is_active = 1 WHERE user_id = $activate_id";
    
    if (mysqli_query($connection, $sql)) {
        $message = "User activated successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle menu item actions
if (isset($_POST['add_menu_item'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = mysqli_real_escape_string($connection, $_POST['image_url']);
    $category = mysqli_real_escape_string($connection, $_POST['category']);
    
    $sql = "INSERT INTO menuitem (name, description, price, image_url, category) 
            VALUES ('$name', '$description', $price, '$image_url', '$category')";
    
    if (mysqli_query($connection, $sql)) {
        $message = "Menu item added successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle menu item update
if (isset($_POST['update_menu_item'])) {
    $item_id = intval($_POST['item_id']);
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = mysqli_real_escape_string($connection, $_POST['image_url']);
    $category = mysqli_real_escape_string($connection, $_POST['category']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    $sql = "UPDATE menuitem SET 
            name = '$name', 
            description = '$description', 
            price = $price, 
            image_url = '$image_url',
            category = '$category',
            is_available = $is_available 
            WHERE item_id = $item_id";
    
    if (mysqli_query($connection, $sql)) {
        $message = "Menu item updated successfully!";
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle menu item deletion
if (isset($_POST['delete_menu_item'])) {
    $item_id = intval($_POST['item_id']);
    $sql = "UPDATE menuitem SET is_available = FALSE WHERE item_id = $item_id";
    
    if (mysqli_query($connection, $sql)) {
        $message = "Menu item removed successfully!";
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
        $sql = "UPDATE orders SET status = 'Rejected' WHERE order_id = $order_id";
        $message = "Order rejected!";
    }
    
    mysqli_query($connection, $sql);
}

// Handle delivery assignment
if (isset($_POST['assign_delivery'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_person_id = intval($_POST['delivery_person_id']);
    
    // Prevent assigning a busy delivery person
    $busy_sql = "SELECT COUNT(*) AS cnt FROM delivery 
                 WHERE delivery_person_id = $delivery_person_id 
                 AND status IN ('Assigned','Picked Up','On the Way')";
    $busy_res = mysqli_query($connection, $busy_sql);
    $busy_row = $busy_res ? mysqli_fetch_assoc($busy_res) : ['cnt' => 0];
    if (intval($busy_row['cnt']) > 0) {
        $error = "Selected delivery person is currently busy";
    } else {
        // Check if delivery already exists
        $check_sql = "SELECT * FROM delivery WHERE order_id = $order_id";
        $check_result = mysqli_query($connection, $check_sql);
        
        if (mysqli_num_rows($check_result) == 0) {
            $sql = "INSERT INTO delivery (order_id, delivery_person_id, status) 
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
}

// Get dashboard statistics
$stats = [];

// Total orders
$sql = "SELECT COUNT(*) as total FROM orders";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['total_orders'] = mysqli_fetch_assoc($result)['total'];
} else {
    $stats['total_orders'] = 0;
}

// Total revenue (sum of all orders' total amounts)
$sql = "SELECT SUM(total_amount) as revenue FROM orders";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['total_revenue'] = mysqli_fetch_assoc($result)['revenue'] ?: 0;
} else {
    $stats['total_revenue'] = 0;
}

// Total customers
$sql = "SELECT COUNT(*) as customers FROM user WHERE user_type = 'Customer' AND is_active = 1";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['total_customers'] = mysqli_fetch_assoc($result)['customers'];
} else {
    $stats['total_customers'] = 0;
}

// Total admins
$sql = "SELECT COUNT(*) as admins FROM user WHERE user_type = 'Admin' AND is_active = 1";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['total_admins'] = mysqli_fetch_assoc($result)['admins'];
} else {
    $stats['total_admins'] = 0;
}

// Total delivery personnel
$sql = "SELECT COUNT(*) as delivery FROM user WHERE user_type = 'Delivery' AND is_active = 1";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['total_delivery'] = mysqli_fetch_assoc($result)['delivery'];
} else {
    $stats['total_delivery'] = 0;
}

// Pending orders
$sql = "SELECT COUNT(*) as pending FROM orders WHERE status = 'Pending'";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['pending_orders'] = mysqli_fetch_assoc($result)['pending'];
} else {
    $stats['pending_orders'] = 0;
}

// Get menu items for display (first 8 items)
$menu_items_sql = "SELECT * FROM menuitem WHERE is_available = TRUE ORDER BY item_id DESC LIMIT 8";
$menu_items_result = mysqli_query($connection, $menu_items_sql);

// Get all users for management
$all_users_sql = "SELECT * FROM user ORDER BY 
                  CASE user_type 
                    WHEN 'Admin' THEN 1
                    WHEN 'Delivery' THEN 2
                    WHEN 'Customer' THEN 3
                  END, name ASC";
$all_users_result = mysqli_query($connection, $all_users_sql);

// Get pending orders for approval
$pending_orders_sql = "SELECT o.*, u.name as customer_name 
                      FROM orders o 
                      JOIN user u ON o.user_id = u.user_id 
                      WHERE o.status = 'Pending' 
                      ORDER BY o.order_date DESC";
$pending_orders_result = mysqli_query($connection, $pending_orders_sql);

// Get preparing orders for delivery assignment
$preparing_orders_sql = "SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         JOIN user u ON o.user_id = u.user_id 
                         WHERE o.status = 'Preparing' 
                         AND o.order_id NOT IN (
                            SELECT order_id FROM delivery 
                            WHERE status IN ('Assigned','Picked Up','On the Way')
                         )
                         ORDER BY o.order_date DESC";
$preparing_orders_result = mysqli_query($connection, $preparing_orders_sql);

// Get active delivery personnel with availability
$delivery_personnel_sql = "SELECT u.*, 
                           (SELECT COUNT(*) FROM delivery d 
                            WHERE d.delivery_person_id = u.user_id 
                            AND d.status IN ('Assigned','Picked Up','On the Way')) AS active_deliveries 
                           FROM user u 
                           WHERE u.user_type = 'Delivery' AND u.is_active = TRUE";
$delivery_personnel_result = mysqli_query($connection, $delivery_personnel_sql);

// Get categories
$categories_sql = "SELECT DISTINCT category FROM menuitem WHERE category IS NOT NULL AND category != ''";
$categories_result = mysqli_query($connection, $categories_sql);

// Get all orders with delivery info
$all_orders_sql = "SELECT o.*, 
                          u.name as customer_name, 
                          d.status as delivery_status, 
                          u2.name as delivery_person_name 
                   FROM orders o 
                   JOIN user u ON o.user_id = u.user_id 
                   LEFT JOIN delivery d ON d.order_id = o.order_id 
                   LEFT JOIN user u2 ON d.delivery_person_id = u2.user_id 
                   ORDER BY o.order_date DESC";
$all_orders_result = mysqli_query($connection, $all_orders_sql);

// History queries (Approved/Completed)
$history_today_sql = "SELECT o.*, u.name as customer_name, u2.name as delivery_person_name 
                      FROM orders o 
                      JOIN user u ON o.user_id = u.user_id 
                      LEFT JOIN delivery d ON d.order_id = o.order_id 
                      LEFT JOIN user u2 ON d.delivery_person_id = u2.user_id 
                      WHERE DATE(o.order_date) = CURDATE() 
                      AND o.status IN ('Preparing','On the way','Delivered') 
                      ORDER BY o.order_date DESC";
$history_today_result = mysqli_query($connection, $history_today_sql);

$history_yesterday_sql = "SELECT o.*, u.name as customer_name, u2.name as delivery_person_name 
                          FROM orders o 
                          JOIN user u ON o.user_id = u.user_id 
                          LEFT JOIN delivery d ON d.order_id = o.order_id 
                          LEFT JOIN user u2 ON d.delivery_person_id = u2.user_id 
                          WHERE DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                          AND o.status IN ('Preparing','On the way','Delivered') 
                          ORDER BY o.order_date DESC";
$history_yesterday_result = mysqli_query($connection, $history_yesterday_sql);

$history_older_sql = "SELECT o.*, u.name as customer_name, u2.name as delivery_person_name 
                      FROM orders o 
                      JOIN user u ON o.user_id = u.user_id 
                      LEFT JOIN delivery d ON d.order_id = o.order_id 
                      LEFT JOIN user u2 ON d.delivery_person_id = u2.user_id 
                      WHERE DATE(o.order_date) < DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                      AND o.status IN ('Preparing','On the way','Delivered') 
                      ORDER BY o.order_date DESC";
$history_older_result = mysqli_query($connection, $history_older_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ateye albailk</title>
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
        
        .current-date {
            background: var(--light-bg);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
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
        
        /* Sections */
        .section {
            background: white;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .section-header {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
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
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            padding: 0 30px 25px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        th, td {
            padding: 15px;
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
            padding: 5px 12px;
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
        
        /* User Management */
        .user-type-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-admin {
            background: #9C27B0;
            color: white;
        }
        
        .badge-delivery {
            background: #2196F3;
            color: white;
        }
        
        .badge-customer {
            background: #FF9800;
            color: white;
        }
        
        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 30px;
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
            height: 200px;
            position: relative;
            overflow: hidden;
        }
        
        .menu-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
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
        
        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light-text);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-checkbox input {
            width: auto;
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
        
        /* Delete Confirmation */
        .delete-confirmation {
            text-align: center;
            padding: 40px 30px;
        }
        
        .delete-confirmation i {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .delete-confirmation h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .delete-confirmation p {
            color: var(--light-text);
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .delete-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
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
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .mobile-menu-toggle {
                display: block;
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
        }
        
        /* Mobile Menu Toggle (hidden on desktop) */
        .mobile-menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <div class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>
    
    <!-- Fixed Sidebar -->
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
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="#menu-management" onclick="showSection('menu-management')">
                        <i class="fas fa-utensils"></i> Menu Management
                    </a>
                </li>
                <li>
                    <a href="#user-management" onclick="showSection('user-management')">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <li>
                    <a href="#order-approval" onclick="showSection('order-approval')">
                        <i class="fas fa-check-circle"></i> Order Approval
                    </a>
                </li>
                <li>
                    <a href="#orders-list" onclick="showSection('orders-list')">
                        <i class="fas fa-receipt"></i> All Orders
                    </a>
                </li>
                <li>
                    <a href="#delivery-assignment" onclick="showSection('delivery-assignment')">
                        <i class="fas fa-motorcycle"></i> Delivery Assignment
                    </a>
                </li>
                <li>
                    <a href="#order-history" onclick="showSection('order-history')">
                        <i class="fas fa-history"></i> Order History
                    </a>
                </li>
                <li>
                    <a href="admin_profile.php">
                        <i class="fas fa-user-shield"></i> Profile
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
                <h1>Admin Dashboard</h1>
                <p>System Overview & Statistics</p>
            </div>
            <div class="header-right">
                <span class="current-date"><?php echo date('F d, Y'); ?></span>
            </div>
        </header>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
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
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #fce4ec;">
                    <i class="fas fa-user-shield" style="color: #c2185b;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_admins']; ?></h3>
                    <p>Administrators</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8eaf6;">
                    <i class="fas fa-motorcycle" style="color: #303f9f;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_delivery']; ?></h3>
                    <p>Delivery Personnel</p>
                </div>
            </div>
        </div>

        <!-- Section 1: Menu Items Display -->
        <div id="menu-display" class="section active-section">
            <div class="section-header">
                <h2>Featured Menu Items</h2>
                <button class="btn btn-primary" onclick="showSection('menu-management')">
                    <i class="fas fa-cog"></i> Manage Menu
                </button>
            </div>
            
            <div class="menu-grid">
                <?php if($menu_items_result && mysqli_num_rows($menu_items_result) > 0): ?>
                    <?php while($item = mysqli_fetch_assoc($menu_items_result)): ?>
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
                                <div class="action-buttons">
                                    <button class="btn btn-primary btn-sm" onclick="editMenuItem(<?php echo $item['item_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteItem(<?php echo $item['item_id']; ?>, '<?php echo addslashes($item['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <i class="fas fa-utensils" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No menu items available</h3>
                        <p>Add some delicious food items to get started!</p>
                        <button class="btn btn-primary" onclick="showAddMenuModal()">
                            <i class="fas fa-plus"></i> Add First Item
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 2: Menu Management -->
        <div id="menu-management" class="section" style="display: none;">
            <div class="section-header">
                <h2>Menu Management</h2>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showAddMenuModal()">
                        <i class="fas fa-plus"></i> Add Menu Item
                    </button>
                    <button class="btn btn-secondary" onclick="showSection('menu-display')">
                        <i class="fas fa-eye"></i> View Items
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="menu-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($menu_items_result, 0);
                        if($menu_items_result && mysqli_num_rows($menu_items_result) > 0): ?>
                            <?php while($item = mysqli_fetch_assoc($menu_items_result)): ?>
                            <tr>
                                <td><?php echo $item['item_id']; ?></td>
                                <td>
                                    <img src="<?php echo $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($item['description'], 0, 80)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($item['category'] ?: 'Uncategorized'); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <?php if($item['is_available']): ?>
                                        <span class="status-badge status-delivered">Available</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn btn-primary btn-sm" onclick="editMenuItem(<?php echo $item['item_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteItem(<?php echo $item['item_id']; ?>, '<?php echo addslashes($item['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No menu items found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: User Management -->
        <div id="user-management" class="section" style="display: none;">
            <div class="section-header">
                <h2>User Management</h2>
                <button class="btn btn-primary" onclick="showCreateUserModal()">
                    <i class="fas fa-user-plus"></i> Create New User
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($all_users_result && mysqli_num_rows($all_users_result) > 0): ?>
                            <?php while($user = mysqli_fetch_assoc($all_users_result)): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <?php if($user['user_id'] == $_SESSION['user_id']): ?>
                                        <br><small style="color: var(--secondary-color);">(Current User)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <span class="user-type-badge badge-<?php echo strtolower($user['user_type']); ?>">
                                        <?php echo $user['user_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($user['is_active']): ?>
                                        <span class="status-badge status-delivered">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-primary btn-sm" onclick="editUserModal(
                                        <?php echo $user['user_id']; ?>,
                                        '<?php echo addslashes($user['name']); ?>',
                                        '<?php echo addslashes($user['phone']); ?>',
                                        '<?php echo addslashes($user['user_type']); ?>',
                                        <?php echo $user['is_active']; ?>
                                    )">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                        <?php if($user['is_active']): ?>
                                            <button class="btn btn-warning btn-sm" onclick="confirmDeactivateUser(<?php echo $user['user_id']; ?>, '<?php echo addslashes($user['name']); ?>')">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm" onclick="activateUser(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDeleteUser(<?php echo $user['user_id']; ?>, '<?php echo addslashes($user['name']); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 4: Order Approval -->
        <div id="order-approval" class="section" style="display: none;">
            <div class="section-header">
                <h2>Order Approval</h2>
                <span class="badge" style="background: var(--warning-color); color: white; padding: 5px 10px; border-radius: 10px;">
                    <?php echo $pending_orders_result ? mysqli_num_rows($pending_orders_result) : 0; ?> pending
                </span>
            </div>
            
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_orders_result && mysqli_num_rows($pending_orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($pending_orders_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-pending">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" name="approve_order" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" name="approve_order" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No pending orders</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 5: Delivery Assignment -->
        <div id="delivery-assignment" class="section" style="display: none;">
            <div class="section-header">
                <h2>Delivery Assignment</h2>
                <span class="badge" style="background: var(--secondary-color); color: white; padding: 5px 10px; border-radius: 10px;">
                    <?php echo $preparing_orders_result ? mysqli_num_rows($preparing_orders_result) : 0; ?> ready for delivery
                </span>
            </div>
            
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Assign Delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($preparing_orders_result && mysqli_num_rows($preparing_orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($preparing_orders_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-preparing">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="assign-delivery-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="delivery_person_id" required style="padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-right: 10px;">
                                            <option value="">Select Delivery Person</option>
                                            <?php 
                                            if($delivery_personnel_result):
                                                mysqli_data_seek($delivery_personnel_result, 0);
                                                while($delivery = mysqli_fetch_assoc($delivery_personnel_result)): ?>
                                            <option value="<?php echo $delivery['user_id']; ?>" <?php echo ($delivery['active_deliveries'] ?? 0) > 0 ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($delivery['name']); ?>
                                                <?php echo ($delivery['active_deliveries'] ?? 0) > 0 ? ' (Busy)' : ' (Available)'; ?>
                                            </option>
                                                <?php endwhile; 
                                            endif; ?>
                                        </select>
                                        <button type="submit" name="assign_delivery" class="btn btn-primary btn-sm">
                                            <i class="fas fa-motorcycle"></i> Assign
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No orders ready for delivery assignment</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Currently Assigned Deliveries -->
        <div class="section" id="assigned-deliveries" style="display: block;">
            <div class="section-header">
                <h2>Currently Assigned Deliveries</h2>
            </div>
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Delivery Person</th>
                            <th>Delivery Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($assigned_orders_admin_result && mysqli_num_rows($assigned_orders_admin_result) > 0): ?>
                            <?php while($ao = mysqli_fetch_assoc($assigned_orders_admin_result)): ?>
                            <tr>
                                <td><?php echo $ao['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($ao['customer_name']); ?></td>
                                <td><strong>$<?php echo number_format($ao['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($ao['delivery_person_name'] ?: '—'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $ao['delivery_status'])); ?>">
                                        <?php echo $ao['delivery_status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No active assignments</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 6: All Orders -->
        <div id="orders-list" class="section" style="display: none;">
            <div class="section-header">
                <h2>All Customer Orders</h2>
                <span class="badge" style="background: var(--primary-color); color: white; padding: 5px 10px; border-radius: 10px;">
                    <?php echo $all_orders_result ? mysqli_num_rows($all_orders_result) : 0; ?> total
                </span>
            </div>
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($all_orders_result && mysqli_num_rows($all_orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($all_orders_result)): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo $order['payment_status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" style="background:#fafafa">
                                    <div style="display:flex; gap:20px; align-items:center;">
                                        <div><strong>Delivery:</strong> <?php echo htmlspecialchars($order['delivery_person_name'] ?: 'Not assigned'); ?></div>
                                        <?php if($order['delivery_status']): ?>
                                            <div>
                                                <strong>Status:</strong> 
                                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['delivery_status'])); ?>">
                                                    <?php echo $order['delivery_status']; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 7: Order History -->
        <div id="order-history" class="section" style="display: none;">
            <div class="section-header">
                <h2>Order History</h2>
            </div>
            <div class="table-responsive">
                <h3>Today's Orders</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Delivery Person</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_today_result && mysqli_num_rows($history_today_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($history_today_result)): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['delivery_person_name'] ?: '—'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No orders</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top: 30px;">Yesterday's Orders</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Delivery Person</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_yesterday_result && mysqli_num_rows($history_yesterday_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($history_yesterday_result)): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['delivery_person_name'] ?: '—'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No orders</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top: 30px;">Older Approved Orders</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Delivery Person</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_older_result && mysqli_num_rows($history_older_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($history_older_result)): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['delivery_person_name'] ?: '—'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No orders</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <!-- Add Menu Item Modal -->
    <div id="addMenuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Menu Item</h2>
                <button class="modal-close" onclick="closeModal('addMenuModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addMenuForm">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter item name">
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" class="form-control" rows="3" required placeholder="Enter item description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="">Select Category</option>
                            <?php if($categories_result): 
                                while($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                                <?php endwhile; 
                            endif; ?>
                        </select>
                        <small>Or enter new category: <input type="text" name="new_category" class="form-control" style="margin-top: 5px;" placeholder="New category name"></small>
                    </div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                        <small>Leave empty for default image</small>
                    </div>
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="is_available" id="is_available" value="1" checked>
                            <label for="is_available">Item is available</label>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" name="add_menu_item" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <i class="fas fa-plus"></i> Add Menu Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Menu Item Modal -->
    <div id="editMenuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Menu Item</h2>
                <button class="modal-close" onclick="closeModal('editMenuModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editMenuForm">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price ($) *</label>
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="edit_category" class="form-control">
                            <option value="">Select Category</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            if($categories_result):
                                while($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                                <?php endwhile; 
                            endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="url" name="image_url" id="edit_image_url" class="form-control">
                    </div>
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="is_available" id="edit_is_available" value="1">
                            <label for="edit_is_available">Item is available</label>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" name="update_menu_item" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <i class="fas fa-save"></i> Update Menu Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <div class="delete-confirmation">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Confirm Deletion</h3>
                <p id="deleteMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
                <div class="delete-actions">
                    <button class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="item_id" id="delete_item_id">
                        <button type="submit" name="delete_menu_item" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New User</h2>
                <button class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="createUserForm">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="10-15 digits">
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm password">
                    </div>
                    <div class="form-group">
                        <label>User Type *</label>
                        <select name="user_type" class="form-control" required>
                            <option value="Admin">Administrator</option>
                            <option value="Delivery">Delivery Partner</option>
                            <option value="Customer" selected>Customer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="is_active" id="create_is_active" value="1" checked>
                            <label for="create_is_active">Active Account</label>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" name="create_user" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <i class="fas fa-user-plus"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" id="edit_user_phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>User Type *</label>
                        <select name="user_type" id="edit_user_type" class="form-control" required>
                            <option value="Admin">Administrator</option>
                            <option value="Delivery">Delivery Partner</option>
                            <option value="Customer">Customer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="is_active" id="edit_user_is_active" value="1">
                            <label for="edit_user_is_active">Active Account</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <small class="text-muted">Note: To change password, ask user to use "Forgot Password" feature</small>
                    </div>
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" name="update_user" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="delete-confirmation">
                <i class="fas fa-user-slash"></i>
                <h3>Delete User</h3>
                <p id="deleteUserMessage">Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="delete-actions">
                    <button class="btn btn-secondary" onclick="closeModal('deleteUserModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" id="deleteUserForm" style="display: inline;">
                        <input type="hidden" name="delete_id" id="delete_user_id">
                        <button type="submit" name="delete_user" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivate User Confirmation Modal -->
    <div id="deactivateUserModal" class="modal">
        <div class="modal-content">
            <div class="delete-confirmation">
                <i class="fas fa-ban"></i>
                <h3>Deactivate User</h3>
                <p id="deactivateUserMessage">Are you sure you want to deactivate this user? They will not be able to login.</p>
                <div class="delete-actions">
                    <button class="btn btn-secondary" onclick="closeModal('deactivateUserModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-warning" onclick="submitDeactivate()">
                        <i class="fas fa-ban"></i> Deactivate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Show/hide sections
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
            }
            
            // Update active nav
            document.querySelectorAll('.sidebar-nav li').forEach(item => {
                item.classList.remove('active');
            });
            
            // Update active state in sidebar
            const navItems = document.querySelectorAll('.sidebar-nav a');
            navItems.forEach(item => {
                if (item.getAttribute('href') === '#' + sectionId) {
                    item.parentElement.classList.add('active');
                }
            });
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function showAddMenuModal() {
            openModal('addMenuModal');
        }
        
        function showCreateUserModal() {
            openModal('createUserModal');
        }
        
        // Edit menu item
        function editMenuItem(itemId) {
            // Fetch item details via AJAX
            fetch(`get_menu_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_item_id').value = data.item.item_id;
                        document.getElementById('edit_name').value = data.item.name;
                        document.getElementById('edit_description').value = data.item.description;
                        document.getElementById('edit_price').value = data.item.price;
                        document.getElementById('edit_category').value = data.item.category || '';
                        document.getElementById('edit_image_url').value = data.item.image_url || '';
                        document.getElementById('edit_is_available').checked = data.item.is_available == 1;
                        
                        openModal('editMenuModal');
                    } else {
                        alert('Error loading menu item details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading menu item details');
                });
        }
        
        // Confirm delete item
        function confirmDeleteItem(itemId, itemName) {
            document.getElementById('delete_item_id').value = itemId;
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${itemName}"? This action cannot be undone.`;
            openModal('deleteConfirmModal');
        }
        
        // Edit user modal
        function editUserModal(userId, name, phone, userType, isActive) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_user_name').value = name;
            document.getElementById('edit_user_phone').value = phone;
            document.getElementById('edit_user_type').value = userType;
            document.getElementById('edit_user_is_active').checked = isActive == 1;
            
            // Disable editing for current user
            const isCurrentUser = userId == <?php echo $_SESSION['user_id']; ?>;
            document.getElementById('edit_user_type').disabled = isCurrentUser;
            document.getElementById('edit_user_is_active').disabled = isCurrentUser;
            
            openModal('editUserModal');
        }
        
        // Confirm delete user
        let userToDelete = null;
        function confirmDeleteUser(userId, userName) {
            userToDelete = userId;
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('deleteUserMessage').textContent = `Are you sure you want to permanently delete user "${userName}"? This action cannot be undone.`;
            openModal('deleteUserModal');
        }
        
        // Confirm deactivate user
        let userToDeactivate = null;
        function confirmDeactivateUser(userId, userName) {
            userToDeactivate = userId;
            document.getElementById('deactivateUserMessage').textContent = `Are you sure you want to deactivate user "${userName}"? They will not be able to login until reactivated.`;
            openModal('deactivateUserModal');
        }
        
        // Activate user
        function activateUser(userId) {
            if (confirm('Activate this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="activate_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function submitDeactivate() {
            if (userToDeactivate) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="user_id" value="${userToDeactivate}">
                    <input type="hidden" name="is_active" value="0">
                    <input type="hidden" name="update_user" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Form validation
        document.getElementById('createUserForm')?.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
        });
        
        // Auto-submit delete form and redirect to menu management
        document.getElementById('deleteForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            
            // Submit form
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(() => {
                closeModal('deleteConfirmModal');
                showSection('menu-management');
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting item');
            });
        });
        
        // Show menu display by default
        document.addEventListener('DOMContentLoaded', function() {
            showSection('menu-display');
        });
    </script>
</body>
</html>
