<?php
require_once __DIR__ . '/session_auth.php';
require_authenticated_session(null, 'html');

// Route based on user type
switch ($_SESSION['user_type']) {
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
?>
