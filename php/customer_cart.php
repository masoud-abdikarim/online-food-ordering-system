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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="light-mode">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-utensils"></i> Ateye albailk</h2>
                <span class="user-role">Shopping Cart</span>
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
                    <li class="active">
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

        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Shopping Cart</h1>
                    <p>Review your items before checkout</p>
                </div>
                <div class="header-right">
                    <a href="customer_dashboard.php#menu" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add More Items
                    </a>
                </div>
            </header>

            <div class="cart-container">
                <div class="cart-items-section">
                    <div class="section-header">
                        <h2>Your Items (<span id="itemCount">0</span>)</h2>
                        <button class="btn-clear-cart" id="clearCartBtn">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                    
                    <div id="cartItemsList" class="cart-items-list">
                        <!-- Cart items will be loaded here via JavaScript -->
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Your cart is empty</h3>
                            <p>Add delicious food items from our menu</p>
                            <a href="customer_dashboard.php#menu" class="btn btn-primary">
                                <i class="fas fa-utensils"></i> Browse Menu
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="cart-summary-section">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="subtotal">$0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee</span>
                                <span id="deliveryFee">$2.99</span>
                            </div>
                            <div class="summary-row">
                                <span>Tax (10%)</span>
                                <span id="tax">$0.00</span>
                            </div>
                            <div class="summary-row total">
                                <strong>Total</strong>
                                <strong id="orderTotal">$0.00</strong>
                            </div>
                        </div>
                        
                        <div class="delivery-options">
                            <h4>Delivery Options</h4>
                            <div class="option">
                                <input type="radio" id="standard" name="delivery" checked>
                                <label for="standard">
                                    <i class="fas fa-motorcycle"></i>
                                    <div>
                                        <strong>Standard Delivery</strong>
                                        <span>30-45 minutes • $2.99</span>
                                    </div>
                                </label>
                            </div>
                            <div class="option">
                                <input type="radio" id="express" name="delivery">
                                <label for="express">
                                    <i class="fas fa-bolt"></i>
                                    <div>
                                        <strong>Express Delivery</strong>
                                        <span>15-25 minutes • $5.99</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button class="btn-checkout" id="checkoutBtn" disabled>
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </button>
                        
                        <p class="secure-checkout">
                            <i class="fas fa-shield-alt"></i>
                            Secure checkout guaranteed
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/cart.js"></script>
    <script>
        // Cart instance
        const cart = new ShoppingCart();
        
        // Load cart items
        function loadCartItems() {
            const cartItems = cart.getCartItems();
            const cartList = document.getElementById('cartItemsList');
            const itemCount = document.getElementById('itemCount');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (cartItems.length === 0) {
                cartList.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Add delicious food items from our menu</p>
                        <a href="customer_dashboard.php#menu" class="btn btn-primary">
                            <i class="fas fa-utensils"></i> Browse Menu
                        </a>
                    </div>
                `;
                itemCount.textContent = '0';
                checkoutBtn.disabled = true;
                return;
            }
            
            let html = '';
            let subtotal = 0;
            
            cartItems.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                html += `
                    <div class="cart-item" data-item-id="${item.id}">
                        <div class="cart-item-image">
                            <img src="${item.image}" alt="${item.name}">
                        </div>
                        <div class="cart-item-details">
                            <h4>${item.name}</h4>
                            <span class="item-price">$${item.price.toFixed(2)} each</span>
                            <div class="item-actions">
                                <button class="remove-from-cart" data-item-id="${item.id}">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn minus" data-item-id="${item.id}">-</button>
                            <input type="number" class="quantity-input" 
                                   value="${item.quantity}" min="1" 
                                   data-item-id="${item.id}">
                            <button class="quantity-btn plus" data-item-id="${item.id}">+</button>
                        </div>
                        <div class="cart-item-total">
                            <span>$${itemTotal.toFixed(2)}</span>
                        </div>
                    </div>
                `;
            });
            
            cartList.innerHTML = html;
            itemCount.textContent = cart.getCartCount();
            checkoutBtn.disabled = false;
            
            // Update summary
            updateOrderSummary(subtotal);
            
            // Add event listeners
            setupCartEventListeners();
        }
        
        // Update order summary
        function updateOrderSummary(subtotal) {
            const deliveryFee = 2.99;
            const tax = subtotal * 0.10;
            const total = subtotal + deliveryFee + tax;
            
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('orderTotal').textContent = `$${total.toFixed(2)}`;
        }
        
        // Setup cart event listeners
        function setupCartEventListeners() {
            // Quantity buttons
            document.querySelectorAll('.quantity-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    const input = this.parentElement.querySelector('.quantity-input');
                    let quantity = parseInt(input.value);
                    
                    if (this.classList.contains('plus')) {
                        quantity++;
                    } else if (this.classList.contains('minus')) {
                        quantity = Math.max(1, quantity - 1);
                    }
                    
                    input.value = quantity;
                    cart.updateQuantity(itemId, quantity);
                    loadCartItems();
                });
            });
            
            // Quantity input
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const itemId = this.dataset.itemId;
                    const quantity = parseInt(this.value);
                    
                    if (quantity > 0) {
                        cart.updateQuantity(itemId, quantity);
                        loadCartItems();
                    }
                });
            });
            
            // Remove buttons
            document.querySelectorAll('.remove-from-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    cart.removeItem(itemId);
                    loadCartItems();
                });
            });
        }
        
        // Clear cart
        document.getElementById('clearCartBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                cart.clearCart();
                loadCartItems();
                cart.showNotification('Cart cleared successfully', 'info');
            }
        });
        
        // Checkout button
        document.getElementById('checkoutBtn').addEventListener('click', function() {
            const cartItems = cart.getCartItems();
            if (cartItems.length === 0) {
                cart.showNotification('Your cart is empty', 'error');
                return;
            }
            
            // Get selected delivery option
            const deliveryOption = document.querySelector('input[name="delivery"]:checked');
            const deliveryType = deliveryOption.id;
            const deliveryFee = deliveryType === 'express' ? 5.99 : 2.99;
            
            // Calculate total
            const subtotal = cart.getCartTotal();
            const tax = subtotal * 0.10;
            const total = subtotal + deliveryFee + tax;
            
            // Prepare order data
            const orderData = {
                items: cartItems,
                subtotal: subtotal,
                delivery_fee: deliveryFee,
                tax: tax,
                total: total,
                delivery_type: deliveryType
            };
            
            // Save order data to session
            sessionStorage.setItem('pendingOrder', JSON.stringify(orderData));
            
            // Redirect to checkout page
            window.location.href = 'checkout.php';
        });
        
        // Delivery option change
        document.querySelectorAll('input[name="delivery"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const subtotal = cart.getCartTotal();
                updateOrderSummary(subtotal);
            });
        });
        
        // Load cart on page load
        document.addEventListener('DOMContentLoaded', loadCartItems);
    </script>
</body>
</html>