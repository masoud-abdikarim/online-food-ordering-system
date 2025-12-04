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

// Get user's recent orders
$orders_sql = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC LIMIT 5";
$orders_result = mysqli_query($connection, $orders_sql);

// Get total spent (all paid orders)
$total_sql = "SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = $user_id AND payment_status = 'Paid'";
$total_result = mysqli_query($connection, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_spent = $total_row['total_spent'] ? $total_row['total_spent'] : 0;

// Get total order count
$count_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = $user_id";
$count_result = mysqli_query($connection, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$order_count = $count_row['order_count'];

// Get active orders (On the way)
$active_sql = "SELECT COUNT(*) as active_orders FROM orders WHERE user_id = $user_id AND status = 'On the way'";
$active_result = mysqli_query($connection, $active_sql);
$active_row = mysqli_fetch_assoc($active_result);
$active_orders = $active_row['active_orders'];

// Get pending orders for approval status
$pending_sql = "SELECT COUNT(*) as pending_orders FROM orders WHERE user_id = $user_id AND status = 'Pending'";
$pending_result = mysqli_query($connection, $pending_sql);
$pending_row = mysqli_fetch_assoc($pending_result);
$pending_orders = $pending_row['pending_orders'];

// Get menu items for display
$menu_sql = "SELECT * FROM MenuItem WHERE is_available = TRUE ORDER BY item_id DESC LIMIT 6";
$menu_result = mysqli_query($connection, $menu_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Fixed sidebar styles */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f5f5f5;
        }
        
        .sidebar {
            width: 280px;
            background: #2c2c2c;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }
        
        /* Cart badge */
        .cart-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-left: 5px;
            padding: 0 5px;
        }
        
        /* Notification popup */
        .notification-popup {
            position: fixed;
            top: 100px;
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
            border-left: 4px solid #27ae60;
        }
        
        .notification-popup.success {
            border-left-color: #27ae60;
        }
        
        .notification-popup.error {
            border-left-color: #e74c3c;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Make header fixed */
        .dashboard-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 99;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="light-mode">
    <!-- Dashboard Sidebar -->
    <div class="dashboard-container">
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
                <p>Customer</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
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
                            <span class="cart-badge" id="cartCount">0</span>
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
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p>Order delicious food from our menu</p>
                </div>
                <div class="header-right">
                    <a href="#menu" class="btn btn-primary">
                        <i class="fas fa-utensils"></i> Browse Menu
                    </a>
                    <a href="customer_cart.php" class="btn btn-secondary">
                        <i class="fas fa-shopping-cart"></i> View Cart
                        <span class="cart-badge" id="headerCartCount">0</span>
                    </a>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd;">
                        <i class="fas fa-shopping-bag" style="color: #1976d2;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $order_count; ?></h3>
                        <p>Total Orders</p>
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
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">
                        <i class="fas fa-clock" style="color: #388e3c;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Awaiting Approval</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">
                        <i class="fas fa-shipping-fast" style="color: #f57c00;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $active_orders; ?></h3>
                        <p>On The Way</p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="section">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="customer_orders.php" class="view-all">View All Orders</a>
                </div>
                
                <div class="orders-table">
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
                            <?php if(mysqli_num_rows($orders_result) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $items_sql = "SELECT COUNT(*) as item_count FROM OrderItem WHERE order_id = " . $order['order_id'];
                                        $items_result = mysqli_query($connection, $items_sql);
                                        if ($items_result) {
                                            $items_row = mysqli_fetch_assoc($items_result);
                                            echo $items_row['item_count'] . " items";
                                        } else {
                                            echo "0 items";
                                        }
                                        ?>
                                    </td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                        <?php if($order['status'] == 'Pending'): ?>
                                            <br><small class="status-info">Awaiting admin approval</small>
                                        <?php elseif($order['status'] == 'Preparing'): ?>
                                            <br><small class="status-info">Being prepared</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="customer_order_view.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if($order['status'] == 'Pending'): ?>
                                            <a href="cancel_order.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn-sm btn-danger" 
                                               onclick="return confirm('Cancel this order?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders yet. <a href="#menu">Start ordering!</a></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Menu Items Section -->
            <div id="menu" class="section">
                <div class="section-header">
                    <h2>Our Menu</h2>
                    <a href="menu.php" class="view-all">View Full Menu</a>
                </div>
                
                <div class="menu-grid">
                    <?php if(mysqli_num_rows($menu_result) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($menu_result)): ?>
                        <div class="menu-card">
                            <div class="menu-card-image">
                                <img src="<?php echo $item['image_url'] ? $item['image_url'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php if($item['is_available']): ?>
                                    <span class="available-badge">Available</span>
                                <?php else: ?>
                                    <span class="unavailable-badge">Unavailable</span>
                                <?php endif; ?>
                            </div>
                            <div class="menu-card-content">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($item['description'], 0, 80)); ?>...</p>
                                <div class="menu-card-details">
                                    <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                                    <?php if($item['is_available']): ?>
                                        <button class="btn-add-to-cart" 
                                                data-item-id="<?php echo $item['item_id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-item-price="<?php echo $item['price']; ?>"
                                                data-item-image="<?php echo $item['image_url']; ?>">
                                            <i class="fas fa-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-add-to-cart" disabled>
                                            <i class="fas fa-times"></i> Unavailable
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-menu">
                            <i class="fas fa-utensils"></i>
                            <h3>No Menu Items Available</h3>
                            <p>Check back later for delicious food options!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="actions-grid">
                    <a href="#menu" class="action-card">
                        <i class="fas fa-utensils"></i>
                        <span>Browse Menu</span>
                    </a>
                    <a href="customer_orders.php" class="action-card">
                        <i class="fas fa-history"></i>
                        <span>Order History</span>
                    </a>
                    <a href="customer_cart.php" class="action-card">
                        <i class="fas fa-shopping-cart"></i>
                        <span>View Cart</span>
                    </a>
                    <a href="support.php" class="action-card">
                        <i class="fas fa-headset"></i>
                        <span>Customer Support</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Shopping Cart Class
        class ShoppingCart {
            constructor() {
                this.cart = JSON.parse(localStorage.getItem('cart')) || [];
                this.init();
            }
            
            init() {
                this.updateCartCount();
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                // Add to cart buttons
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('btn-add-to-cart') || 
                        e.target.closest('.btn-add-to-cart')) {
                        const button = e.target.classList.contains('btn-add-to-cart') 
                            ? e.target 
                            : e.target.closest('.btn-add-to-cart');
                        
                        if (!button.disabled) {
                            this.addItem(
                                button.dataset.itemId,
                                button.dataset.itemName,
                                parseFloat(button.dataset.itemPrice),
                                button.dataset.itemImage
                            );
                        }
                    }
                });
            }
            
            addItem(id, name, price, image) {
                // Check if item already exists
                const existingItem = this.cart.find(item => item.id === id);
                
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    this.cart.push({
                        id: id,
                        name: name,
                        price: price,
                        image: image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                        quantity: 1
                    });
                }
                
                this.saveCart();
                this.updateCartCount();
                this.showNotification(`${name} added to cart!`, 'success');
            }
            
            saveCart() {
                localStorage.setItem('cart', JSON.stringify(this.cart));
            }
            
            updateCartCount() {
                const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
                
                // Update all cart badges
                document.querySelectorAll('.cart-badge').forEach(badge => {
                    badge.textContent = totalItems;
                });
                
                return totalItems;
            }
            
            showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification-popup ${type}`;
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                `;
                
                document.body.appendChild(notification);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
            
            getCartItems() {
                return this.cart;
            }
            
            getCartTotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            }
            
            getCartCount() {
                return this.cart.reduce((sum, item) => sum + item.quantity, 0);
            }
            
            clearCart() {
                this.cart = [];
                this.saveCart();
                this.updateCartCount();
            }
        }
        
        // Initialize cart
        let cart = new ShoppingCart();
        
        // Smooth scroll for menu link
        document.querySelector('a[href="#menu"]').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('#menu').scrollIntoView({ 
                behavior: 'smooth' 
            });
        });
    </script>
</body>
</html>