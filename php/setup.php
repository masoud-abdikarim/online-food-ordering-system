<?php
// Setup script to initialize database tables
// Run this once to create tables

require_once('config.php');

echo "<h1>Database Setup</h1>";

// Array of table creation SQL statements
$tables = [
    'user' => "CREATE TABLE IF NOT EXISTS user (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('Admin', 'Delivery', 'Customer') NOT NULL DEFAULT 'Customer',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'menuitem' => "CREATE TABLE IF NOT EXISTS menuitem (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        image_url MEDIUMTEXT,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'orders' => "CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('Pending', 'Preparing', 'On the way', 'Delivered', 'Rejected') NOT NULL DEFAULT 'Pending',
        payment_status ENUM('Pending', 'Paid', 'Failed') NOT NULL DEFAULT 'Pending',
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user(user_id)
    )",
    
    'orderitem' => "CREATE TABLE IF NOT EXISTS orderitem (
        order_item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (menu_item_id) REFERENCES menuitem(item_id)
    )",
    
    'delivery' => "CREATE TABLE IF NOT EXISTS delivery (
        delivery_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        delivery_person_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (delivery_person_id) REFERENCES user(user_id)
    )",
    
    'address' => "CREATE TABLE IF NOT EXISTS address (
        address_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        postal_code VARCHAR(20),
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
    )"
];

// Create tables
foreach ($tables as $name => $sql) {
    if (mysqli_query($connection, $sql)) {
        echo "<p style='color: green;'>Table '$name' created successfully or already exists.</p>";
    } else {
        echo "<p style='color: red;'>Error creating table '$name': " . mysqli_error($connection) . "</p>";
    }
}

// Widen image_url for long HTTPS URLs or data:image/...;base64,... strings (VARCHAR(255) is too small)
if (mysqli_query($connection, "ALTER TABLE menuitem MODIFY COLUMN image_url MEDIUMTEXT NULL")) {
    echo "<p style='color: green;'>Column <code>menuitem.image_url</code> is set to MEDIUMTEXT (long image URLs / base64 OK).</p>";
} else {
    echo "<p style='color: orange;'>Could not run ALTER on menuitem.image_url: " . htmlspecialchars(mysqli_error($connection)) . " — run <code>php/migrate_menu_image_url.php</code> manually.</p>";
}

// Ensure user.is_active exists (older installs)
if (@mysqli_query($connection, "ALTER TABLE user ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1")) {
    echo "<p style='color: green;'>Column <code>user.is_active</code> added.</p>";
}

// Check if default admin exists
$admin_phone = "0000000000";
$check_admin = mysqli_query($connection, "SELECT * FROM user WHERE phone = '$admin_phone'");
if (mysqli_num_rows($check_admin) == 0) {
    $admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_sql = "INSERT INTO user (name, phone, password, user_type, is_active) VALUES ('Kaah Fast Food Admin', '$admin_phone', '$admin_pass', 'Admin', 1)";
    if (mysqli_query($connection, $admin_sql)) {
        echo "<p style='color: green;'>Default admin created (Phone: $admin_phone, Pass: admin123)</p>";
    } else {
        echo "<p style='color: red;'>Error creating admin: " . mysqli_error($connection) . "</p>";
    }
} else {
    echo "<p>Admin account already exists.</p>";
}

// Add sample menu items if empty
$check_menu = mysqli_query($connection, "SELECT * FROM menuitem");
if (mysqli_num_rows($check_menu) == 0) {
    $items = [
        ['Traditional Chicken Platter', 'Succulent chicken marinated in traditional spices', 5.99, 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
        ['Suqaar Special', 'Signature suqaar from New Hargeisa - tender beef cubes', 4.99, 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
        ['Grilled Red Snapper', 'Fresh red snapper marinated in lemon herb sauce', 4.99, 'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80']
    ];
    
    foreach ($items as $item) {
        $name = mysqli_real_escape_string($connection, $item[0]);
        $desc = mysqli_real_escape_string($connection, $item[1]);
        $price = $item[2];
        $img = mysqli_real_escape_string($connection, $item[3]);
        
        $sql = "INSERT INTO menuitem (name, description, price, image_url) VALUES ('$name', '$desc', $price, '$img')";
        mysqli_query($connection, $sql);
    }
    echo "<p style='color: green;'>Sample menu items added.</p>";
}

echo "<h3>Setup Complete! You can now delete this file and login.</h3>";
echo "<a href='login.php'>Go to Login</a>";
?>
