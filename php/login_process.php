<?php
require_once('config.php');
session_start();

if(isset($_POST['submit'])){
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $errors = [];
    
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
                
                // Update last login time (optional - add column to User table)
                // $update_sql = "UPDATE User SET last_login = NOW() WHERE user_id = " . $user['user_id'];
                // mysqli_query($connection, $update_sql);
                
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
                        header("Location: index.php");
                        break;
                }
                exit();
            } else {
                $errors[] = "Invalid password";
            }
        } else {
            $errors[] = "Phone number not registered";
        }
    }
    
    // If there are errors, show them
    if(!empty($errors)){
        $_SESSION['login_errors'] = $errors;
        $_SESSION['old_phone'] = $phone;
        header("Location: login.html");
        exit();
    }
} else {
    header("Location: login.html");
    exit();
}
?>