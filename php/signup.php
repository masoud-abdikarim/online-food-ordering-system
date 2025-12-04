<?php
session_start();
require_once('config.php');

$errors = [];
$old_data = [];

// Check for previous errors from redirect
if(isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']);
}
if(isset($_SESSION['old_data'])) {
    $old_data = $_SESSION['old_data'];
    unset($_SESSION['old_data']);
}

// Process form submission
if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($connection, $_POST['user_type']);

    // Validation
    if(empty($name) || empty($phone) || empty($password)){
        $errors[] = "All fields are required";
    }
    
    if(!preg_match('/^[0-9]{10,15}$/', $phone)){
        $errors[] = "Please enter a valid phone number (10-15 digits)";
    }
    
    if($password !== $confirm_password){
        $errors[] = "Passwords do not match";
    }
    
    if(strlen($password) < 6){
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // Check if phone already exists
    $check_sql = "SELECT user_id FROM User WHERE phone = '$phone'";
    $check_result = mysqli_query($connection, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0){
        $errors[] = "Phone number already registered";
    }
    
    // If no errors, insert data
    if(empty($errors)){
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database
        $sql = "INSERT INTO User (name, phone, password, user_type) 
                VALUES ('$name', '$phone', '$hashed_password', '$user_type')";
        
        if(mysqli_query($connection, $sql)){
            $user_id = mysqli_insert_id($connection);
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['phone'] = $phone;
            $_SESSION['logged_in'] = true;
            
            // Redirect based on user type
            switch($user_type){
                case 'Admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'Delivery':
                    header("Location: delivery_dashboard.php");
                    break;
                case 'Customer':
                default:
                    header("Location: customer_dashboard.php");
                    break;
            }
            exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($connection);
            $old_data = $_POST;
        }
    } else {
        $old_data = $_POST;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
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

            <form action="signup.php" method="POST" class="auth-form">
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
    </script>
</body>
</html>