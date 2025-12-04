// Theme Toggle
const themeToggle = document.getElementById('themeToggle');
const body = document.body;

// Check for saved theme or prefer-color-scheme
const savedTheme = localStorage.getItem('theme') || 
                  (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
body.classList.toggle('dark-mode', savedTheme === 'dark');

themeToggle.addEventListener('click', () => {
  body.classList.toggle('dark-mode');
  const isDark = body.classList.contains('dark-mode');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
  updateThemeIcon(isDark);
});

function updateThemeIcon(isDark) {
  const moonIcon = themeToggle.querySelector('.fa-moon');
  const sunIcon = themeToggle.querySelector('.fa-sun');
  moonIcon.style.display = isDark ? 'none' : 'block';
  sunIcon.style.display = isDark ? 'block' : 'none';
}

// Initialize theme icon
updateThemeIcon(body.classList.contains('dark-mode'));

// Cart Functionality
const cartIcon = document.querySelector('.cart-icon');
const cartSidebar = document.querySelector('.cart-sidebar');
const closeCart = document.querySelector('.close-cart');
const cartItems = document.querySelector('.cart-items');
const totalPrice = document.querySelector('.total-price');
const cartCount = document.querySelector('.cart-count');

let cart = JSON.parse(localStorage.getItem('cart')) || [];
let total = 0;

function updateCart() {
  cartItems.innerHTML = '';
  total = 0;
  
  cart.forEach((item, index) => {
    total += item.price * item.quantity;
    
    const cartItem = document.createElement('div');
    cartItem.className = 'cart-item';
    cartItem.innerHTML = `
      <div class="cart-item-img">
        <img src="${item.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'}" alt="${item.name}">
      </div>
      <div class="cart-item-info">
        <h4>${item.name}</h4>
        <div class="price">$${(item.price * item.quantity).toFixed(2)}</div>
      </div>
      <div class="cart-item-actions">
        <button onclick="updateQuantity(${index}, -1)">-</button>
        <span>${item.quantity}</span>
        <button onclick="updateQuantity(${index}, 1)">+</button>
        <button onclick="removeFromCart(${index})" class="remove-btn"><i class="fas fa-trash"></i></button>
      </div>
    `;
    cartItems.appendChild(cartItem);
  });
  
  totalPrice.textContent = `$${total.toFixed(2)}`;
  cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
  localStorage.setItem('cart', JSON.stringify(cart));
}

function addToCart(name, price, image = '') {
  const existingItem = cart.find(item => item.name === name);
  
  if (existingItem) {
    existingItem.quantity++;
  } else {
    cart.push({ name, price, quantity: 1, image });
  }
  
  updateCart();
  showNotification(`${name} added to cart!`);
}

function updateQuantity(index, change) {
  cart[index].quantity += change;
  
  if (cart[index].quantity <= 0) {
    cart.splice(index, 1);
  }
  
  updateCart();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  updateCart();
  showNotification('Item removed from cart');
}

// Add to cart buttons
document.querySelectorAll('.btn-add').forEach(button => {
  button.addEventListener('click', function() {
    const item = this.dataset.item;
    const price = parseFloat(this.dataset.price);
    const card = this.closest('.menu-card');
    const image = card.querySelector('img')?.src || '';
    
    addToCart(item, price, image);
  });
});

// Cart sidebar controls
cartIcon.addEventListener('click', () => {
  cartSidebar.classList.add('active');
});

closeCart.addEventListener('click', () => {
  cartSidebar.classList.remove('active');
});

// Close cart when clicking outside
document.addEventListener('click', (e) => {
  if (!cartSidebar.contains(e.target) && !cartIcon.contains(e.target)) {
    cartSidebar.classList.remove('active');
  }
});

// Menu Filtering
const filterButtons = document.querySelectorAll('.filter-btn');
const menuCards = document.querySelectorAll('.menu-card');

filterButtons.forEach(button => {
  button.addEventListener('click', function() {
    // Remove active class from all buttons
    filterButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    this.classList.add('active');
    
    const filter = this.dataset.filter;
    
    menuCards.forEach(card => {
      if (filter === 'all' || card.dataset.category === filter) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  });
});

// Notification System
function showNotification(message) {
  const notification = document.createElement('div');
  notification.className = 'notification';
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 100px;
    right: 20px;
    background: var(--primary);
    color: white;
    padding: 15px 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    z-index: 10000;
    animation: slideIn 0.3s ease;
  `;
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 2000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  @keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
  }
`;
document.head.appendChild(style);

// Form Submission
document.getElementById('orderForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  
  if (cart.length === 0) {
    showNotification('Please add items to cart before ordering');
    return;
  }
  
  const formData = new FormData(this);
  const orderData = {
    name: formData.get('name'),
    phone: formData.get('phone'),
    instructions: formData.get('instructions'),
    items: cart,
    total: total
  };
  
  // In a real app, you would send this to your backend
  console.log('Order placed:', orderData);
  
  // Show success message
  showNotification('Order placed successfully! We\'ll contact you shortly.');
  
  // Clear cart
  cart = [];
  updateCart();
  cartSidebar.classList.remove('active');
  this.reset();
});

// Mobile Navigation
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

hamburger.addEventListener('click', () => {
  navMenu.style.display = navMenu.style.display === 'flex' ? 'none' : 'flex';
  hamburger.classList.toggle('active');
});

// Close mobile menu when clicking a link
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
      navMenu.style.display = 'none';
      hamburger.classList.remove('active');
    }
  });
});

// Initialize cart on load
updateCart();