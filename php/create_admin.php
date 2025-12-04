<?php
require_once('config.php');

// This should be run only once to create the first admin
// After that, only existing admin can create new admin accounts from dashboard

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if admin already exists
    $check_sql = "SELECT COUNT(*) as admin_count FROM User WHERE user_type = 'Admin'";
    $check_result = mysqli_query($connection, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['admin_count'] > 0) {
        $error = "Admin already exists! Contact existing admin to create new admin accounts.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO User (name, phone, password, user_type) 
                VALUES ('$name', '$phone', '$hashed_password', 'Admin')";
        
        if (mysqli_query($connection, $sql)) {
            $success = "Admin account created successfully! You can now login.";
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
    <title>Create First Admin - Ateye albailk</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-user-shield"></i>
                    <h1>Create First Admin</h1>
                </div>
                <p>Create the first administrator account for the system</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Enter admin name">
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" required placeholder="Enter phone number">
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