<?php
require_once('config.php');

if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($connection, $_POST['user_type']);

    // Validation
    $errors = [];
    
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
            
            // Start session and redirect
            session_start();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['phone'] = $phone;
            
            // Redirect based on user type
            if($user_type == 'Customer'){
                header("Location: index.php");
            } elseif($user_type == 'Delivery'){
                header("Location: delivery_dashboard.php");
            }
            exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($connection);
        }
    }
    
    // If there are errors, show them
    if(!empty($errors)){
        session_start();
        $_SESSION['errors'] = $errors;
        $_SESSION['old_data'] = $_POST;
        header("Location: signup.html");
        exit();
    }
} else {
    header("Location: signup.html");
    exit();
}
?>