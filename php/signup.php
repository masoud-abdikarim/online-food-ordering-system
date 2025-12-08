<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add this CSS if you don't have it in auth.css */
        .auth-card {
            display: flex;
            flex-direction: row;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 1000px;
            width: 100%;
            min-height: 600px;
        }
        
        .auth-content {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 350px;
        }
        
        .auth-image {
            flex: 1;
            position: relative;
            min-width: 300px;
        }
        
        .auth-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .auth-card {
                flex-direction: column;
                min-height: auto;
            }
            
            .auth-content, .auth-image {
                min-width: 100%;
            }
            
            .auth-image {
                height: 250px;
                order: -1;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Left side: Form content -->
            <div class="auth-content">
                <div class="auth-header">
                    <a href="../index.html" class="back-home">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                    <div class="logo">
                        <i class="fas fa-utensils"></i>
                        <h1>Ateye albailk</h1>
                    </div>
                    <h2>Create Your Account</h2>
                    <p>Join our food community today</p>
                </div>

                <!-- Display Errors -->
                <?php if(!empty($errors)): ?>
                    <div class="error-alert">
                        <ul>
                            <?php foreach($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- CHANGED FORM ACTION TO EMPTY STRING -->
                <form method="POST" class="auth-form" action="">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name" 
                               placeholder="Enter your full name" 
                               value="<?php echo isset($old_data['name']) ? htmlspecialchars($old_data['name']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="Enter your phone number" 
                               value="<?php echo isset($old_data['phone']) ? htmlspecialchars($old_data['phone']) : ''; ?>" 
                               required>
                        <small class="hint">We'll use this for login and notifications</small>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" 
                                   placeholder="Create a strong password" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="hint">At least 6 characters with letters and numbers</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter your password" required>
                    </div>

                    <div class="form-group">
                        <label for="user_type">
                            <i class="fas fa-user-tag"></i> Account Type
                        </label>
                        <select id="user_type" name="user_type">
                            <option value="Customer" <?php echo (isset($old_data['user_type']) && $old_data['user_type'] == 'Customer') ? 'selected' : ''; ?>>
                                Customer - Order Food
                            </option>
                            <option value="Delivery" <?php echo (isset($old_data['user_type']) && $old_data['user_type'] == 'Delivery') ? 'selected' : ''; ?>>
                                Delivery Partner - Deliver Orders
                            </option>
                        </select>
                        <small class="hint">Admin accounts require verification</small>
                    </div>

                    <div class="form-group terms">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" name="submit" class="btn-auth">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>

            <!-- Right side: Image -->
            <div class="auth-image">
                <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Delicious Food">
                <div class="image-overlay">
                    <h3>Join Thousands of Happy Customers</h3>
                    <p>Get exclusive offers and fast delivery</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        
        function checkPasswordMatch() {
            if (passwordInput.value && confirmInput.value) {
                if (passwordInput.value !== confirmInput.value) {
                    confirmInput.style.borderColor = '#e74c3c';
                } else {
                    confirmInput.style.borderColor = '#27ae60';
                }
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if(!terms) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy');
                return false;
            }
            
            if(password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if(password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
        });
    </script>
</body>
</html>