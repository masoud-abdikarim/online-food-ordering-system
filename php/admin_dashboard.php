<?php
require_once __DIR__ . '/session_auth.php';
require_authenticated_session(['Admin'], 'html');

// Safely get session variables with defaults
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';
$user_phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Admin';

// Process actions
$message = '';
$error = '';

// Post/Redirect/Get: show success after redirect (avoids resubmit on refresh)
kaah_prg_flash_apply($message, $error);

// Post-redirect messages (workflow)
if (isset($_GET['msg']) && $_GET['msg'] === 'approved') {
    $message = 'Order approved. Open <strong>Dispatch</strong> — the order is waiting for a driver assignment.';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'rejected') {
    $message = 'Order was rejected.';
}

// ========== USER MANAGEMENT FUNCTIONS ==========

// 1. CREATE USER (Admin/Delivery/Customer)
if (isset($_POST['create_user'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, preg_replace('/\D+/', '', $_POST['phone'] ?? ''));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($connection, $_POST['user_type']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if(empty($name) || empty($phone) || empty($password) || empty($confirm_password)){
        $errors[] = "All fields are required";
    }
    
    if(!empty($phone) && !preg_match('/^[0-9]{6,10}$/', $phone)){
        $errors[] = "Please enter a valid phone number (6-10 digits)";
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
            kaah_prg_redirect('admin_dashboard.php', ucfirst($user_type) . " account created successfully!");
        } else {
            $error = "Error creating user: " . mysqli_error($connection);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// 2. UPDATE USER
// 2. UPDATE USER
if (isset($_POST['update_user'])) {
    $user_id_update = intval($_POST['user_id']);
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, preg_replace('/\D+/', '', $_POST['phone'] ?? ''));
    $user_type = mysqli_real_escape_string($connection, $_POST['user_type']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Don't allow current admin to change their own role or deactivate themselves
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
            kaah_prg_redirect('admin_dashboard.php', 'User updated successfully!');
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
            kaah_prg_redirect('admin_dashboard.php', 'User deleted successfully!');
        } else {
            // If delete fails (due to foreign key constraints), deactivate instead
            $sql = "UPDATE user SET is_active = 0 WHERE user_id = $delete_id";
            if (mysqli_query($connection, $sql)) {
                kaah_prg_redirect('admin_dashboard.php', 'User deactivated successfully!');
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
        kaah_prg_redirect('admin_dashboard.php', 'User activated successfully!');
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle menu item actions
if (isset($_POST['add_menu_item'])) {
    $img_raw = $_POST['image_url'] ?? '';
    if (strlen($img_raw) > 12000000) {
        $error = "Image URL or pasted image data is too large (max ~12 MB). Use a smaller image or host it online and paste a link.";
    } else {
        $name = mysqli_real_escape_string($connection, $_POST['name']);
        $description = mysqli_real_escape_string($connection, $_POST['description']);
        $price = floatval($_POST['price']);
        $image_url = mysqli_real_escape_string($connection, $img_raw);
        $category = mysqli_real_escape_string($connection, $_POST['category']);
        
        $sql = "INSERT INTO menuitem (name, description, price, image_url, category) 
                VALUES ('$name', '$description', $price, '$image_url', '$category')";
        
        if (mysqli_query($connection, $sql)) {
            kaah_prg_redirect('admin_dashboard.php', 'Menu item added successfully!');
        } else {
            $error = "Error: " . mysqli_error($connection);
        }
    }
}

// Handle menu item update
if (isset($_POST['update_menu_item'])) {
    $img_raw = $_POST['image_url'] ?? '';
    if (strlen($img_raw) > 12000000) {
        $error = "Image URL or pasted image data is too large (max ~12 MB). Use a smaller image or host it online and paste a link.";
    } else {
        $item_id = intval($_POST['item_id']);
        $name = mysqli_real_escape_string($connection, $_POST['name']);
        $description = mysqli_real_escape_string($connection, $_POST['description']);
        $price = floatval($_POST['price']);
        $image_url = mysqli_real_escape_string($connection, $img_raw);
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
            kaah_prg_redirect('admin_dashboard.php', 'Menu item updated successfully!');
        } else {
            $error = "Error: " . mysqli_error($connection);
        }
    }
}

// Handle menu item deletion
if (isset($_POST['delete_menu_item'])) {
    $item_id = intval($_POST['item_id']);
    $sql = "UPDATE menuitem SET is_available = FALSE WHERE item_id = $item_id";
    
    if (mysqli_query($connection, $sql)) {
        kaah_prg_redirect('admin_dashboard.php', 'Menu item removed successfully!');
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}

// Handle order approval/rejection (only while Pending)
if (isset($_POST['approve_order'])) {
    $order_id = intval($_POST['order_id']);
    $action = mysqli_real_escape_string($connection, $_POST['action']);
    $st_res = mysqli_query($connection, "SELECT status FROM orders WHERE order_id = $order_id");
    $st_row = $st_res ? mysqli_fetch_assoc($st_res) : null;
    if (!$st_row || $st_row['status'] !== 'Pending') {
        $error = 'Only pending orders can be approved or rejected.';
    } elseif ($action == 'approve') {
        $sql = "UPDATE orders SET status = 'Approved' WHERE order_id = $order_id";
        if (mysqli_query($connection, $sql)) {
            header('Location: admin_dashboard.php?section=delivery-assignment&msg=approved');
            exit();
        } else {
            $error = mysqli_error($connection);
        }
    } else {
        $sql = "UPDATE orders SET status = 'Rejected' WHERE order_id = $order_id";
        if (mysqli_query($connection, $sql)) {
            mysqli_query($connection, "DELETE FROM delivery WHERE order_id = $order_id");
            header('Location: admin_dashboard.php?section=order-approval&msg=rejected');
            exit();
        } else {
            $error = mysqli_error($connection);
        }
    }
}

// Admin: update order status (full lifecycle)
if (isset($_POST['admin_update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = isset($_POST['new_status']) ? mysqli_real_escape_string($connection, $_POST['new_status']) : '';
    $allowed = ['Pending', 'Approved', 'Assigned', 'Preparing', 'On the way', 'Delivered', 'Rejected'];
    if (!in_array($new_status, $allowed, true)) {
        $error = 'Invalid order status.';
    } else {
        mysqli_begin_transaction($connection);
        $ok = mysqli_query($connection, "UPDATE orders SET status = '$new_status' WHERE order_id = $order_id");
        if (!$ok) {
            mysqli_rollback($connection);
            $error = mysqli_error($connection);
        } else {
            if ($new_status === 'Delivered') {
                mysqli_query($connection, "UPDATE delivery SET status = 'Delivered', delivered_at = COALESCE(delivered_at, NOW()) WHERE order_id = $order_id");
                mysqli_query($connection, "UPDATE orders SET payment_status = 'Paid' WHERE order_id = $order_id");
            } elseif ($new_status === 'On the way') {
                mysqli_query($connection, "UPDATE delivery SET status = 'On the way' WHERE order_id = $order_id AND status IN ('Assigned','Picked Up')");
            } elseif ($new_status === 'Rejected') {
                mysqli_query($connection, "DELETE FROM delivery WHERE order_id = $order_id");
            }
            mysqli_commit($connection);
            kaah_prg_redirect('admin_dashboard.php', 'Order status updated.');
        }
    }
}

// Handle delivery assignment (first assignment)
if (isset($_POST['assign_delivery'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_person_id = intval($_POST['delivery_person_id']);
    $ord_chk = mysqli_query($connection, "SELECT status FROM orders WHERE order_id = $order_id");
    $ord_row = $ord_chk ? mysqli_fetch_assoc($ord_chk) : null;
    if (!$ord_row || $ord_row['status'] !== 'Approved') {
        $error = 'Order must be Approved and waiting for assignment before dispatch.';
    } elseif ($delivery_person_id <= 0) {
        $error = 'Please select a delivery person.';
    } else {
        $busy_sql = "SELECT COUNT(*) AS cnt FROM delivery 
                     WHERE delivery_person_id = $delivery_person_id 
                     AND status IN ('Assigned','Picked Up','On the way')
                     AND order_id != $order_id";
        $busy_res = mysqli_query($connection, $busy_sql);
        $busy_row = $busy_res ? mysqli_fetch_assoc($busy_res) : ['cnt' => 0];
        if (intval($busy_row['cnt']) > 0) {
            $error = 'Selected delivery person is currently busy with another order.';
        } else {
            $check_sql = "SELECT delivery_id FROM delivery WHERE order_id = $order_id";
            $check_result = mysqli_query($connection, $check_sql);
            if (mysqli_num_rows($check_result) == 0) {
                $sql = "INSERT INTO delivery (order_id, delivery_person_id, status) 
                        VALUES ($order_id, $delivery_person_id, 'Assigned')";
                if (mysqli_query($connection, $sql)) {
                    mysqli_query($connection, "UPDATE orders SET status = 'Assigned' WHERE order_id = $order_id");
                    kaah_prg_redirect('admin_dashboard.php', 'Delivery assigned — order is now with the driver.');
                } else {
                    $error = 'Error: ' . mysqli_error($connection);
                }
            } else {
                $error = 'Delivery already exists — use Reassign on the order list.';
            }
        }
    }
}

// Reassign delivery person (active deliveries only)
if (isset($_POST['reassign_delivery'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_person_id = intval($_POST['delivery_person_id']);
    if ($delivery_person_id <= 0) {
        $error = 'Please select a delivery person.';
    } else {
        $dres = mysqli_query($connection, "SELECT * FROM delivery WHERE order_id = $order_id");
        $d = $dres ? mysqli_fetch_assoc($dres) : null;
        if (!$d) {
            $error = 'No delivery record for this order — assign first.';
        } elseif ($d['status'] === 'Delivered') {
            $error = 'Cannot reassign a completed delivery.';
        } else {
            $busy_sql = "SELECT COUNT(*) AS cnt FROM delivery 
                         WHERE delivery_person_id = $delivery_person_id 
                         AND status IN ('Assigned','Picked Up','On the way')
                         AND order_id != $order_id";
            $busy_res = mysqli_query($connection, $busy_sql);
            $busy_row = $busy_res ? mysqli_fetch_assoc($busy_res) : ['cnt' => 0];
            if (intval($busy_row['cnt']) > 0) {
                $error = 'Selected delivery person is currently busy with another order.';
            } else {
                $did = intval($d['delivery_id']);
                $sql = "UPDATE delivery SET delivery_person_id = $delivery_person_id, status = 'Assigned', 
                        assigned_at = NOW(), delivered_at = NULL WHERE delivery_id = $did";
                if (mysqli_query($connection, $sql)) {
                    kaah_prg_redirect('admin_dashboard.php', 'Delivery reassigned.');
                } else {
                    $error = mysqli_error($connection);
                }
            }
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

// Active menu items (available)
$sql = "SELECT COUNT(*) as menu_cnt FROM menuitem WHERE is_available = TRUE";
$result = mysqli_query($connection, $sql);
if ($result) {
    $stats['menu_items'] = mysqli_fetch_assoc($result)['menu_cnt'];
} else {
    $stats['menu_items'] = 0;
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
                      JOIN `user` u ON o.user_id = u.user_id 
                      WHERE o.status = 'Pending' 
                      ORDER BY o.order_date DESC";
$pending_orders_result = mysqli_query($connection, $pending_orders_sql);

// Approved, waiting for a driver (no delivery row yet — order must stay visible here)
$preparing_orders_sql = "SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         JOIN `user` u ON o.user_id = u.user_id 
                         WHERE o.status = 'Approved' 
                         AND NOT EXISTS (SELECT 1 FROM delivery d WHERE d.order_id = o.order_id)
                         ORDER BY o.order_date DESC";
$preparing_orders_result = mysqli_query($connection, $preparing_orders_sql);

// Get active delivery personnel with availability
$delivery_personnel_sql = "SELECT u.*, 
                           (SELECT COUNT(*) FROM delivery d 
                            WHERE d.delivery_person_id = u.user_id 
                            AND d.status IN ('Assigned','Picked Up','On the way')) AS active_deliveries 
                           FROM `user` u 
                           WHERE u.user_type = 'Delivery' AND u.is_active = TRUE";
$delivery_personnel_result = mysqli_query($connection, $delivery_personnel_sql);

// Get currently assigned deliveries for admin view
$assigned_orders_admin_sql = "SELECT o.order_id, o.total_amount, o.status as order_status, u.name as customer_name, 
                              u2.name as delivery_person_name, d.status as delivery_status
                              FROM orders o 
                              JOIN `user` u ON o.user_id = u.user_id 
                              INNER JOIN delivery d ON d.order_id = o.order_id 
                              LEFT JOIN `user` u2 ON d.delivery_person_id = u2.user_id 
                              WHERE d.status IN ('Assigned','Picked Up','On the way')
                              ORDER BY o.order_date DESC";
$assigned_orders_admin_result = mysqli_query($connection, $assigned_orders_admin_sql);

// Get categories
$categories_sql = "SELECT DISTINCT category FROM menuitem WHERE category IS NOT NULL AND category != ''";
$categories_result = mysqli_query($connection, $categories_sql);

// Get all orders with delivery info
$all_orders_sql = "SELECT o.*, 
                          u.name as customer_name, 
                          d.delivery_id,
                          d.status as delivery_status, 
                          u2.user_id AS delivery_person_id,
                          u2.name as delivery_person_name 
                   FROM orders o 
                   JOIN `user` u ON o.user_id = u.user_id 
                   LEFT JOIN delivery d ON d.order_id = o.order_id 
                   LEFT JOIN `user` u2 ON d.delivery_person_id = u2.user_id 
                   ORDER BY o.order_date DESC";
$all_orders_result = mysqli_query($connection, $all_orders_sql);

// History: completed or rejected only (no “disappearing” orders — they land here when done)
$history_today_sql = "SELECT o.*, u.name as customer_name, u2.name as delivery_person_name 
                      FROM orders o 
                      JOIN `user` u ON o.user_id = u.user_id 
                      LEFT JOIN delivery d ON d.order_id = o.order_id 
                      LEFT JOIN `user` u2 ON d.delivery_person_id = u2.user_id 
                      WHERE DATE(o.order_date) = CURDATE() 
                      AND o.status IN ('Delivered','Rejected') 
                      ORDER BY o.order_date DESC";
$history_today_result = mysqli_query($connection, $history_today_sql);

$history_yesterday_sql = "SELECT o.*, u.name as customer_name, u2.name as delivery_person_name 
                          FROM orders o 
                          JOIN `user` u ON o.user_id = u.user_id 
                          LEFT JOIN delivery d ON d.order_id = o.order_id 
                          LEFT JOIN `user` u2 ON d.delivery_person_id = u2.user_id 
                          WHERE DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                          AND o.status IN ('Delivered','Rejected') 
                          ORDER BY o.order_date DESC";
$history_yesterday_result = mysqli_query($connection, $history_yesterday_sql);

$history_older_sql = "SELECT o.*, u.name as customer_name, u2.name as delivery_person_name 
                      FROM orders o 
                      JOIN `user` u ON o.user_id = u.user_id 
                      LEFT JOIN delivery d ON d.order_id = o.order_id 
                      LEFT JOIN `user` u2 ON d.delivery_person_id = u2.user_id 
                      WHERE DATE(o.order_date) < DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                      AND o.status IN ('Delivered','Rejected') 
                      ORDER BY o.order_date DESC";
$history_older_result = mysqli_query($connection, $history_older_sql);

// Root-relative CSS URL (fixes broken ../css when SCRIPT_NAME / URL path varies)
$__sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
if ($__sn === '' || $__sn[0] !== '/') {
    $__sn = '/' . ltrim($__sn, '/');
}
$__app_root = str_replace('\\', '/', dirname(dirname($__sn)));
if ($__app_root === '/' || $__app_root === '.' || $__app_root === '\\') {
    $admin_css_href = '/css/kaah-admin-v2.css';
} else {
    $admin_css_href = rtrim($__app_root, '/') . '/css/kaah-admin-v2.css';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kaah Fast Food</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($admin_css_href, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="kaav2-admin">
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
    <button type="button" class="mobile-menu-toggle" onclick="toggleSidebar()" aria-label="Open menu"><i class="fas fa-bars"></i></button>
    <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar" aria-label="Collapse sidebar"><i class="fas fa-angles-left"></i></button>

    <aside class="sidebar" id="appSidebar">
        <div class="kaah-brand">
            <div class="kaah-brand__logo"><i class="fas fa-utensils"></i></div>
            <div class="kaah-brand__text">
                <strong>Kaah Fast Food</strong>
                <span><i class="fas fa-location-dot"></i> New Hargeisa</span>
            </div>
        </div>
        <div class="sidebar-header">
            <h2>Navigation</h2>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p>Administrator</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="#menu-display" onclick="showSection('menu-display'); return false;">
                        <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#menu-management" onclick="showSection('menu-management'); return false;">
                        <i class="fas fa-utensils"></i><span>Menu</span>
                    </a>
                </li>
                <li>
                    <a href="#user-management" onclick="showSection('user-management'); return false;">
                        <i class="fas fa-users"></i><span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="#order-approval" onclick="showSection('order-approval'); return false;">
                        <i class="fas fa-inbox"></i><span>New orders</span>
                    </a>
                </li>
                <li>
                    <a href="#delivery-assignment" onclick="showSection('delivery-assignment'); return false;">
                        <i class="fas fa-user-clock"></i><span>Waiting assignment</span>
                    </a>
                </li>
                <li>
                    <a href="#assigned-deliveries" onclick="showSection('assigned-deliveries'); return false;">
                        <i class="fas fa-truck"></i><span>Active deliveries</span>
                    </a>
                </li>
                <li>
                    <a href="#orders-list" onclick="showSection('orders-list'); return false;">
                        <i class="fas fa-receipt"></i><span>All orders</span>
                    </a>
                </li>
                <li>
                    <a href="#order-history" onclick="showSection('order-history'); return false;">
                        <i class="fas fa-history"></i><span>History</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top bar -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Operations console</h1>
                <p>Kaah Fast Food · <strong>New Hargeisa</strong> — orders, menu, users &amp; delivery</p>
            </div>
            <div class="header-right">
                <span class="kaah-pill"><i class="fas fa-shield-halved"></i> Admin</span>
                <span class="current-date"><?php echo date('F d, Y'); ?></span>
                <div class="kaah-top-links">
                    <a href="admin_profile.php"><i class="fas fa-user-gear"></i> Profile</a>
                    <a href="../index.php" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Site</a>
                </div>
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

            <div class="stat-card">
                <div class="stat-icon" style="background: #fff4e6;">
                    <i class="fas fa-bowl-food" style="color: #ea580c;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo (int)($stats['menu_items'] ?? 0); ?></h3>
                    <p>Active menu items</p>
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

        <!-- Section 4: New orders (Pending) -->
        <div id="order-approval" class="section" style="display: none;">
            <div class="section-header">
                <h2>New orders <span style="font-weight:600;font-size:0.85em;color:var(--kaah-muted);">(Pending)</span></h2>
                <span class="badge" style="background: var(--warning-color); color: white; padding: 5px 10px; border-radius: 10px;">
                    <?php echo $pending_orders_result ? mysqli_num_rows($pending_orders_result) : 0; ?> pending
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_orders_result && mysqli_num_rows($pending_orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($pending_orders_result)): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$order['order_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-pending">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openAdminOrderDetail(<?php echo (int)$order['order_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
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
                                <td colspan="6" class="text-center">No pending orders</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 5: Waiting for delivery assignment (Approved) -->
        <div id="delivery-assignment" class="section" style="display: none;">
            <div class="section-header">
                <h2>Waiting for assignment <span style="font-weight:600;font-size:0.85em;color:var(--kaah-muted);">(Approved)</span></h2>
                <span class="badge" style="background: var(--secondary-color); color: white; padding: 5px 10px; border-radius: 10px;">
                    <?php echo $preparing_orders_result ? mysqli_num_rows($preparing_orders_result) : 0; ?> waiting
                </span>
            </div>
            <p class="text-muted" style="margin-bottom:12px;font-size:0.9rem;">
                After you <strong>approve</strong> a new order, it appears here until you assign a driver. Orders stay in this list — they do not disappear.
            </p>
            
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Assign driver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($preparing_orders_result && mysqli_num_rows($preparing_orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($preparing_orders_result)): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$order['order_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-approved">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openAdminOrderDetail(<?php echo (int)$order['order_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
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
                                <td colspan="6" class="text-center">No orders waiting for driver assignment. Approve a new order first.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 6: Active deliveries (driver assigned) -->
        <div id="assigned-deliveries" class="section" style="display: none;">
            <div class="section-header">
                <h2>Active deliveries <span style="font-weight:600;font-size:0.85em;color:var(--kaah-muted);">(Assigned / in progress)</span></h2>
            </div>
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Order status</th>
                            <th>Driver</th>
                            <th>Delivery progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($assigned_orders_admin_result && mysqli_num_rows($assigned_orders_admin_result) > 0): ?>
                            <?php while($ao = mysqli_fetch_assoc($assigned_orders_admin_result)): ?>
                            <tr>
                                <td><?php echo $ao['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($ao['customer_name']); ?></td>
                                <td><strong>$<?php echo number_format($ao['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $ao['order_status'] ?? '')); ?>">
                                        <?php echo htmlspecialchars($ao['order_status'] ?? ''); ?>
                                    </span>
                                </td>
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

        <!-- Section 7: All Orders -->
        <div id="orders-list" class="section" style="display: none;">
            <div class="section-header">
                <h2>All Customer Orders</h2>
                <span class="badge" style="background: var(--primary-color); color: white; padding: 5px 10px; border-radius: 10px;">
                    <?php echo $all_orders_result ? mysqli_num_rows($all_orders_result) : 0; ?> total
                </span>
            </div>
            <div class="table-responsive">
                <p class="text-muted" style="margin-bottom:12px;font-size:0.9rem;">
                    Approve in <strong>New orders</strong> → assign in <strong>Waiting assignment</strong> (Approved). Use this list to change status or reassign drivers.
                </p>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Order</th>
                            <th>Pay</th>
                            <th>Driver / delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($all_orders_result && mysqli_num_rows($all_orders_result) > 0): ?>
                            <?php 
                            mysqli_data_seek($all_orders_result, 0);
                            while($order = mysqli_fetch_assoc($all_orders_result)): 
                                $oid = (int)$order['order_id'];
                                $dst = $order['delivery_status'] ?? '';
                                $dpid = isset($order['delivery_person_id']) ? (int)$order['delivery_person_id'] : 0;
                                $del_done = ($dst === 'Delivered');
                            ?>
                            <tr>
                                <td><strong>#<?php echo $oid; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo htmlspecialchars($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td style="font-size:0.88rem;">
                                    <div><strong><?php echo htmlspecialchars($order['delivery_person_name'] ?: '—'); ?></strong></div>
                                    <?php if ($dst): ?>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $dst)); ?>">
                                            <?php echo htmlspecialchars($dst); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No driver</span>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width:200px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openAdminOrderDetail(<?php echo $oid; ?>)" style="margin-bottom:6px;">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <form method="POST" style="margin-bottom:8px;">
                                        <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                        <select name="new_status" class="form-control" style="padding:6px;font-size:0.85rem;margin-bottom:4px;">
                                            <?php foreach (['Pending','Approved','Assigned','Preparing','On the way','Delivered','Rejected'] as $st): ?>
                                            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($order['status'] === $st) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="admin_update_order_status" class="btn btn-primary btn-sm" style="width:100%;">
                                            <i class="fas fa-sync"></i> Update status
                                        </button>
                                    </form>
                                    <?php if ($order['status'] === 'Approved' && !$dst): ?>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                        <select name="delivery_person_id" required class="form-control" style="padding:6px;font-size:0.85rem;margin-bottom:4px;">
                                            <option value="">Assign driver…</option>
                                            <?php
                                            if ($delivery_personnel_result) {
                                                mysqli_data_seek($delivery_personnel_result, 0);
                                                while ($dp = mysqli_fetch_assoc($delivery_personnel_result)):
                                                    $busy = (int)($dp['active_deliveries'] ?? 0) > 0;
                                            ?>
                                            <option value="<?php echo (int)$dp['user_id']; ?>" <?php echo $busy ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($dp['name']); ?><?php echo $busy ? ' (busy)' : ''; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" name="assign_delivery" class="btn btn-success btn-sm" style="width:100%;">
                                            <i class="fas fa-motorcycle"></i> Assign
                                        </button>
                                    </form>
                                    <?php elseif ($dst && !$del_done): ?>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                        <select name="delivery_person_id" required class="form-control" style="padding:6px;font-size:0.85rem;margin-bottom:4px;">
                                            <?php
                                            if ($delivery_personnel_result) {
                                                mysqli_data_seek($delivery_personnel_result, 0);
                                                while ($dp = mysqli_fetch_assoc($delivery_personnel_result)):
                                                    $uid = (int)$dp['user_id'];
                                                    $busy = (int)($dp['active_deliveries'] ?? 0) > 0;
                                                    $is_here = ($dpid > 0 && $uid === $dpid);
                                            ?>
                                            <option value="<?php echo $uid; ?>" <?php echo ($dpid === $uid) ? 'selected' : ''; ?> <?php echo ($busy && !$is_here) ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($dp['name']); ?><?php echo ($busy && !$is_here) ? ' (busy)' : ''; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" name="reassign_delivery" class="btn btn-warning btn-sm" style="width:100%;">
                                            <i class="fas fa-user-friends"></i> Reassign
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 8: Order History -->
        <div id="order-history" class="section" style="display: none;">
            <div class="section-header">
                <h2>Order history</h2>
            </div>
            <p class="text-muted" style="margin-bottom:16px;font-size:0.9rem;">
                Completed and rejected orders only. Active work stays in <strong>New orders</strong>, <strong>Waiting assignment</strong>, or <strong>Active deliveries</strong>.
            </p>
            <div class="table-responsive">
                <h3>Today — Delivered / Rejected</h3>
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

                <h3 style="margin-top: 30px;">Yesterday — Delivered / Rejected</h3>
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

                <h3 style="margin-top: 30px;">Older completed orders</h3>
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
    <!-- Admin: full order detail (AJAX) -->
    <div id="adminOrderDetailModal" class="modal">
        <div class="modal-content" style="max-width:720px;">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Order details</h2>
                <button type="button" class="modal-close" onclick="closeModal('adminOrderDetailModal')">&times;</button>
            </div>
            <div class="modal-body" id="adminOrderDetailBody">
                <p class="text-muted">Loading…</p>
            </div>
        </div>
    </div>

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
                        <label>Image URL or data</label>
                        <textarea name="image_url" class="form-control" rows="3" placeholder="https://example.com/image.jpg — or paste data:image/jpeg;base64,... (long)"></textarea>
                        <small class="text-muted">Prefer an image link. For pasted base64, run <code>migrate_menu_image_url.php</code> once if MySQL reports “Data too long”.</small>
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
                        <label>Image URL or data</label>
                        <textarea name="image_url" id="edit_image_url" class="form-control" rows="3" placeholder="HTTPS link or data URI"></textarea>
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
                        <input type="tel" name="phone" class="form-control" required placeholder="6-10 digits" pattern="[0-9]{6,10}" minlength="6" maxlength="10">
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
                        <input type="tel" name="phone" id="edit_user_phone" class="form-control" required pattern="[0-9]{6,10}" minlength="6" maxlength="10">
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

    <script src="../js/session_idle.js" defer></script>
    <script>
        // Mobile drawer: body.sidebar-open + overlay (see kaah-admin-v2.css)
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        document.getElementById('sidebarOverlay')?.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });

        document.getElementById('sidebarCollapseBtn')?.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-collapsed');
        });

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

            document.querySelectorAll('.sidebar-nav li').forEach(item => {
                item.classList.remove('active');
            });

            document.querySelectorAll('.sidebar-nav a').forEach(item => {
                if (item.getAttribute('href') === '#' + sectionId) {
                    item.parentElement.classList.add('active');
                }
            });

            document.body.classList.remove('sidebar-open');
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        /** Load full order HTML for admin (items, customer, address). */
        function openAdminOrderDetail(orderId) {
            const body = document.getElementById('adminOrderDetailBody');
            if (!body) return;
            body.innerHTML = '<p class="text-muted">Loading…</p>';
            openModal('adminOrderDetailModal');
            fetch('get_order_details_admin.php?order_id=' + encodeURIComponent(orderId), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(async r => {
                    if (r.status === 401) {
                        try {
                            const j = await r.json();
                            window.location.href = (j && j.redirect) ? j.redirect : 'login.php';
                        } catch (e) {
                            window.location.href = 'login.php';
                        }
                        return;
                    }
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(html => {
                    if (html === undefined) return;
                    body.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    body.innerHTML = '<p class="text-danger">Could not load order details.</p>';
                });
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
            fetch(`get_menu_item.php?id=${itemId}`, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(async response => {
                    if (response.status === 401) {
                        try {
                            const j = await response.json();
                            window.location.href = (j && j.redirect) ? j.redirect : 'login.php';
                        } catch (e) {
                            window.location.href = 'login.php';
                        }
                        return null;
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data) return;
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
                    <input type="hidden" name="activate_user" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function submitDeactivate() {
            if (userToDeactivate) {
                if (confirm('Are you sure you want to deactivate this user?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const userIdInput = document.createElement('input');
                    userIdInput.type = 'hidden';
                    userIdInput.name = 'delete_id';
                    userIdInput.value = userToDeactivate;
                    form.appendChild(userIdInput);

                    const flag = document.createElement('input');
                    flag.type = 'hidden';
                    flag.name = 'delete_user';
                    flag.value = '1';
                    form.appendChild(flag);

                    document.body.appendChild(form);
                    form.submit();
                }
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
        
        // Show menu display by default, or deep-link section from URL (?section=delivery-assignment)
        document.addEventListener('DOMContentLoaded', function() {
            var p = new URLSearchParams(window.location.search);
            var sec = p.get('section');
            if (sec && document.getElementById(sec)) {
                showSection(sec);
            } else {
                showSection('menu-display');
            }
        });
    </script>
</body>
</html>