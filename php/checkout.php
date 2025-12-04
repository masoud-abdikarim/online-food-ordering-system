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

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Get cart data from session storage (will be sent via AJAX in real implementation)
    // For now, we'll use a simplified version
    
    $delivery_type = mysqli_real_escape_string($connection, $_POST['delivery_type']);
    $delivery_address = mysqli_real_escape_string($connection, $_POST['delivery_address']);
    $special_instructions = mysqli_real_escape_string($connection, $_POST['special_instructions']);
    
    // Calculate totals (in real app, get from cart)
    $subtotal = 50.00; // Example
    $delivery_fee = $delivery_type == 'express' ? 5.99 : 2.99;
    $tax = $subtotal * 0.10;
    $total_amount = $subtotal + $delivery_fee + $tax;
    
    // Insert order
    $order_sql = "INSERT INTO orders (user_id, total_amount, status, payment_status) 
                  VALUES ($user_id, $total_amount, 'Pending', 'Pending')";
    
    if (mysqli_query($connection, $order_sql)) {
        $order_id = mysqli_insert_id($connection);
        
        // In real app, insert order items from cart
        // For now, insert example items
        $example_items = [
            ['item_id' => 1, 'quantity' => 2, 'price' => 12.99],
            ['item_id' => 2, 'quantity' => 1, 'price' => 18.99]
        ];
        
        foreach ($example_items as $item) {
            $item_sql = "INSERT INTO OrderItem (order_id, menu_item_id, quantity, price) 
                         VALUES ($order_id, {$item['item_id']}, {$item['quantity']}, {$item['price']})";
            mysqli_query($connection, $item_sql);
        }
        
        // Clear cart
        echo '<script>localStorage.removeItem("cart");</script>';
        
        $success = "Order placed successfully! Order #$order_id";
    } else {
        $error = "Error placing order: " . mysqli_error($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="light-mode">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-utensils"></i> Ateye albailk</h2>
                <span class="user-role">Checkout</span>
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
                        <a href="checkout.php">
                            <i class="fas fa-shopping-bag"></i> Checkout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Checkout</h1>
                    <p>Complete your order</p>
                </div>
                <div class="header-right">
                    <a href="customer_cart.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </header>

            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <p>Your order is awaiting admin approval. You can track it in My Orders.</p>
                    <div class="alert-actions">
                        <a href="customer_orders.php" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Order
                        </a>
                        <a href="customer_dashboard.php#menu" class="btn btn-secondary">
                            <i class="fas fa-utensils"></i> Order More
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(!isset($success)): ?>
            <div class="checkout-container">
                <div class="checkout-steps">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-info">
                            <span class="step-title">Cart Review</span>
                            <span class="step-status">Completed</span>
                        </div>
                    </div>
                    <div class="step active">
                        <div class="step-number">2</div>
                        <div class="step-info">
                            <span class="step-title">Delivery Details</span>
                            <span class="step-status">Current Step</span>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-info">
                            <span class="step-title">Payment</span>
                            <span class="step-status">Upcoming</span>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-info">
                            <span class="step-title">Confirmation</span>
                            <span class="step-status">Upcoming</span>
                        </div>
                    </div>
                </div>

                <form method="POST" class="checkout-form">
                    <div class="checkout-sections">
                        <!-- Delivery Details -->
                        <div class="checkout-section">
                            <h3><i class="fas fa-truck"></i> Delivery Details</h3>
                            
                            <div class="form-group">
                                <label>Delivery Address *</label>
                                <input type="text" name="delivery_address" required 
                                       placeholder="Enter your full delivery address">
                            </div>
                            
                            <div class="form-group">
                                <label>Delivery Type *</label>
                                <div class="delivery-options">
                                    <label class="delivery-option">
                                        <input type="radio" name="delivery_type" value="standard" checked>
                                        <div class="option-content">
                                            <i class="fas fa-motorcycle"></i>
                                            <div>
                                                <strong>Standard Delivery</strong>
                                                <span>30-45 minutes • $2.99</span>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="delivery-option">
                                        <input type="radio" name="delivery_type" value="express">
                                        <div class="option-content">
                                            <i class="fas fa-bolt"></i>
                                            <div>
                                                <strong>Express Delivery</strong>
                                                <span>15-25 minutes • $5.99</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Special Instructions (Optional)</label>
                                <textarea name="special_instructions" 
                                          placeholder="Any special instructions for delivery..." 
                                          rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="checkout-section">
                            <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                            
                            <div class="order-summary">
                                <div class="summary-items">
                                    <div class="summary-item">
                                        <span>Subtotal (2 items)</span>
                                        <span>$50.00</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Delivery Fee</span>
                                        <span>$2.99</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Tax (10%)</span>
                                        <span>$5.00</span>
                                    </div>
                                    <div class="summary-item total">
                                        <strong>Total</strong>
                                        <strong>$57.99</strong>
                                    </div>
                                </div>
                                
                                <div class="payment-methods">
                                    <h4>Payment Method</h4>
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" value="cash" id="cash" checked>
                                        <label for="cash">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Cash on Delivery</span>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" value="card" id="card">
                                        <label for="card">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Credit/Debit Card</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="terms-agreement">
                                    <input type="checkbox" id="terms" required>
                                    <label for="terms">
                                        I agree to the <a href="#">Terms of Service</a> and understand that my order needs admin approval before preparation.
                                    </label>
                                </div>
                                
                                <button type="submit" name="place_order" class="btn-place-order">
                                    <i class="fas fa-lock"></i> Place Order & Await Approval
                                </button>
                                
                                <p class="order-note">
                                    <i class="fas fa-info-circle"></i>
                                    Your order will be reviewed by admin. You'll be notified once approved.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Update delivery fee based on selection
        document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const deliveryFee = this.value === 'express' ? '$5.99' : '$2.99';
                document.querySelector('.summary-item:nth-child(2) span:last-child').textContent = deliveryFee;
                
                // Recalculate total
                const subtotal = 50.00;
                const fee = this.value === 'express' ? 5.99 : 2.99;
                const tax = subtotal * 0.10;
                const total = subtotal + fee + tax;
                document.querySelector('.total strong:last-child').textContent = `$${total.toFixed(2)}`;
            });
        });
        
        // Form validation
        document.querySelector('.checkout-form').addEventListener('submit', function(e) {
            const address = document.querySelector('input[name="delivery_address"]').value;
            const terms = document.querySelector('#terms').checked;
            
            if (!address.trim()) {
                e.preventDefault();
                alert('Please enter your delivery address.');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Please agree to the terms of service.');
                return false;
            }
            
            // Confirm order placement
            if (!confirm('Place this order? It will be sent for admin approval.')) {
                e.preventDefault();
                return false;
            }
            
            // Clear cart after successful order
            localStorage.removeItem('cart');
        });
    </script>
</body>
</html>