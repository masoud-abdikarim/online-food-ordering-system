<?php
require_once('config.php');

// ONE-TIME USE: Create additional admin
// Delete this file after use for security

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, preg_replace('/\D+/', '', $_POST['phone'] ?? ''));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!preg_match('/^[0-9]{6,10}$/', $phone)) {
        $error = "Please enter a valid phone number (6-10 digits)!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO user (name, phone, password, user_type) 
                VALUES ('$name', '$phone', '$hashed_password', 'Admin')";
        
        if (mysqli_query($connection, $sql)) {
            $success = "Admin account created successfully!";
            // Auto-delete this file after successful creation (optional)
            // unlink(__FILE__);
        } else {
            $error = "Error: " . mysqli_error($connection);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Additional Admin - ONE TIME USE</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth.css">
    <style>
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #856404;
        }
        .warning-box h3 {
            color: #856404;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="warning-box">
                <h3>⚠️ ONE TIME USE ONLY</h3>
                <p>This page is for creating additional admin accounts. Delete this file after use for security.</p>
            </div>
            
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-user-shield"></i>
                    <h1>Create Admin Account</h1>
                </div>
                <p>Create additional administrator account</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="warning-box">
                    <p><strong>IMPORTANT:</strong> Please delete this file (create_admin_again.php) now for security!</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Enter admin name">
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" required placeholder="Enter phone number (6-10 digits)" pattern="[0-9]{6,10}" minlength="6" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password">
                </div>
                
                <button type="submit" class="btn-auth">
                    Create Admin Account
                </button>
                
                <div class="auth-footer">
                    <p>Already have admin account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
