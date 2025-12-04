<?php
session_start();
require_once('config.php');

$errors = [];
$old_phone = '';

// Check for previous errors
if(isset($_SESSION['login_errors'])) {
    $errors = $_SESSION['login_errors'];
    unset($_SESSION['login_errors']);
}
if(isset($_SESSION['old_phone'])) {
    $old_phone = $_SESSION['old_phone'];
    unset($_SESSION['old_phone']);
}

// Process login
if(isset($_POST['submit'])){
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    if(empty($phone) || empty($password)){
        $errors[] = "Phone number and password are required";
    }
    
    if(empty($errors)){
        // Check user exists
        $sql = "SELECT * FROM User WHERE phone = '$phone'";
        $result = mysqli_query($connection, $sql);
        
        if(mysqli_num_rows($result) == 1){
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if(password_verify($password, $user['password'])){
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['logged_in'] = true;
                
                // Set cookie if remember me is checked
                if($remember){
                    $cookie_value = base64_encode($user['user_id'] . ':' . $user['phone']);
                    setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30 days
                }
                
                // Redirect based on user type
                switch($user['user_type']){
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
                $errors[] = "Invalid password";
                $old_phone = $phone;
            }
        } else {
            $errors[] = "Phone number not registered";
            $old_phone = $phone;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ateye albailk</title>
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
                <h2>Welcome Back!</h2>
                <p>Login to your account to continue</p>
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

            <form action="login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Enter your phone number" 
                           value="<?php echo htmlspecialchars($old_phone); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group remember-forgot">
                    <div class="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot">Forgot Password?</a>
                </div>

                <button type="submit" name="submit" class="btn-auth">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
            </form>

            <div class="auth-image">
                <img src="https://images.unsplash.com/photo-1565299507177-b0ac66763828?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Food Delivery">
                <div class="image-overlay">
                    <h3>Fast Food Delivery</h3>
                    <p>Login to track your orders and get recommendations</p>
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
    </script>
</body>
</html>