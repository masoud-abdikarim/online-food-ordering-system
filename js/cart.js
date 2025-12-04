// Cart Management System
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
            
            // Remove from cart
            if (e.target.classList.contains('remove-from-cart')) {
                const itemId = e.target.dataset.itemId;
                this.removeItem(itemId);
            }
            
            // Update quantity
            if (e.target.classList.contains('quantity-update')) {
                const input = e.target;
                const itemId = input.dataset.itemId;
                const quantity = parseInt(input.value);
                
                if (quantity > 0) {
                    this.updateQuantity(itemId, quantity);
                } else {
                    this.removeItem(itemId);
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
    
    removeItem(itemId) {
        this.cart = this.cart.filter(item => item.id !== itemId);
        this.saveCart();
        this.updateCartCount();
        this.showNotification('Item removed from cart', 'info');
    }
    
    updateQuantity(itemId, quantity) {
        const item = this.cart.find(item => item.id === itemId);
        if (item) {
            item.quantity = quantity;
            this.saveCart();
            this.updateCartCount();
        }
    }
    
    clearCart() {
        this.cart = [];
        this.saveCart();
        this.updateCartCount();
    }
    
    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }
    
    updateCartCount() {
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Update all cart badges
        document.querySelectorAll('.cart-badge').forEach(badge => {
            badge.textContent = totalItems;
        });
        
        // Update cart total
        const cartTotal = document.getElementById('cartTotal');
        if (cartTotal) {
            cartTotal.textContent = `$${totalPrice.toFixed(2)}`;
        }
        
        return { totalItems, totalPrice };
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
}

// Initialize cart
let cart = new ShoppingCart();

// Export for use in other files
window.ShoppingCart = ShoppingCart;