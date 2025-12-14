<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Delivery') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$success = '';
$error = '';

function get_user($connection, $user_id) {
    $res = mysqli_query($connection, "SELECT * FROM user WHERE user_id = $user_id");
    return $res ? mysqli_fetch_assoc($res) : null;
}

$user = get_user($connection, $user_id);

if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);

    $errors = [];
    if (empty($name) || empty($phone)) { $errors[] = 'Name and phone are required'; }
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) { $errors[] = 'Enter a valid phone number (10-15 digits)'; }

    if (empty($errors)) {
        $check_sql = "SELECT user_id FROM user WHERE phone = '$phone' AND user_id != $user_id";
        $check_res = mysqli_query($connection, $check_sql);
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $error = 'Phone number already in use';
        } else {
            $upd_sql = "UPDATE user SET name = '$name', phone = '$phone' WHERE user_id = $user_id";
            if (mysqli_query($connection, $upd_sql)) {
                $_SESSION['name'] = $name;
                $_SESSION['phone'] = $phone;
                $success = 'Profile updated successfully';
                $user = get_user($connection, $user_id);
            } else {
                $error = 'Failed to update profile';
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) { $errors[] = 'All password fields are required'; }
    if ($new_password !== $confirm_password) { $errors[] = 'New passwords do not match'; }
    if (strlen($new_password) < 6) { $errors[] = 'New password must be at least 6 characters'; }

    $user = get_user($connection, $user_id);
    if (!$user) { $errors[] = 'User not found'; }
    if (empty($errors) && !password_verify($current_password, $user['password'])) { $errors[] = 'Current password is incorrect'; }

    if (empty($errors)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $upd_sql = "UPDATE user SET password = '$hashed' WHERE user_id = $user_id";
        if (mysqli_query($connection, $upd_sql)) {
            $success = 'Password changed successfully';
        } else {
            $error = 'Failed to change password';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #2c3e50; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .card-header h2 { font-size: 1.3rem; margin: 0; }
        .card-body { padding: 24px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .btn { padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #3498db; color: white; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    </head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i>
                <h2>My Profile</h2>
            </div>
            <div class="card-body">
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input class="form-control" type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input class="form-control" type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                    <button class="btn btn-primary" type="submit" name="update_profile"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i>
                <h2>Change Password</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input class="form-control" type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input class="form-control" type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button class="btn btn-primary" type="submit" name="change_password"><i class="fas fa-key"></i> Update Password</button>
                </form>
            </div>
        </div>

        <div style="text-align:center; margin-top: 10px;">
            <a class="btn" href="delivery_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

