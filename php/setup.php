<?php
/**
 * Database setup — aligned with ../schema.sql (same tables, columns, ENUMs, FKs).
 * Run once, then you may delete this file in production.
 */
require_once('config.php');

echo "<h1>Database Setup</h1>";

// Order matters: user → menuitem → orders → orderitem → address → delivery
$tables = [
    'user' => "CREATE TABLE IF NOT EXISTS `user` (
        `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `user_type` ENUM('Customer', 'Admin', 'Delivery') NOT NULL DEFAULT 'Customer',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `uq_user_phone` (`phone`),
        KEY `idx_user_type_active` (`user_type`, `is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'menuitem' => "CREATE TABLE IF NOT EXISTS `menuitem` (
        `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `image_url` MEDIUMTEXT NULL,
        `category` VARCHAR(100) NULL,
        `is_available` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`item_id`),
        KEY `idx_menuitem_available` (`is_available`),
        KEY `idx_menuitem_category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
        `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `order_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `status` ENUM('Pending', 'Preparing', 'On the way', 'Delivered', 'Rejected') NOT NULL DEFAULT 'Pending',
        `payment_status` ENUM('Pending', 'Paid', 'Failed') NOT NULL DEFAULT 'Pending',
        PRIMARY KEY (`order_id`),
        KEY `idx_orders_user_date` (`user_id`, `order_date`),
        KEY `idx_orders_status_date` (`status`, `order_date`),
        KEY `idx_orders_payment_status` (`payment_status`),
        CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`)
            REFERENCES `user` (`user_id`)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'orderitem' => "CREATE TABLE IF NOT EXISTS `orderitem` (
        `order_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_id` INT UNSIGNED NOT NULL,
        `menu_item_id` INT UNSIGNED NOT NULL,
        `quantity` INT UNSIGNED NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (`order_item_id`),
        KEY `idx_orderitem_order` (`order_id`),
        KEY `idx_orderitem_menu_item` (`menu_item_id`),
        CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`)
            REFERENCES `orders` (`order_id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        CONSTRAINT `fk_orderitem_menuitem` FOREIGN KEY (`menu_item_id`)
            REFERENCES `menuitem` (`item_id`)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'address' => "CREATE TABLE IF NOT EXISTS `address` (
        `address_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_id` INT UNSIGNED NOT NULL,
        `address` TEXT NOT NULL,
        `city` VARCHAR(100) NULL,
        `postal_code` VARCHAR(30) NULL,
        PRIMARY KEY (`address_id`),
        UNIQUE KEY `uq_address_order` (`order_id`),
        CONSTRAINT `fk_address_order` FOREIGN KEY (`order_id`)
            REFERENCES `orders` (`order_id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'delivery' => "CREATE TABLE IF NOT EXISTS `delivery` (
        `delivery_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_id` INT UNSIGNED NOT NULL,
        `delivery_person_id` INT UNSIGNED NOT NULL,
        `status` ENUM('Assigned', 'Picked Up', 'On the way', 'Delivered') NOT NULL DEFAULT 'Assigned',
        `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `delivered_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`delivery_id`),
        UNIQUE KEY `uq_delivery_order` (`order_id`),
        KEY `idx_delivery_person_status` (`delivery_person_id`, `status`),
        KEY `idx_delivery_status` (`status`),
        CONSTRAINT `fk_delivery_order` FOREIGN KEY (`order_id`)
            REFERENCES `orders` (`order_id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        CONSTRAINT `fk_delivery_user` FOREIGN KEY (`delivery_person_id`)
            REFERENCES `user` (`user_id`)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $name => $sql) {
    if (mysqli_query($connection, $sql)) {
        echo "<p style='color: green;'>Table <code>$name</code> OK (created or already exists).</p>";
    } else {
        echo "<p style='color: red;'>Error <code>$name</code>: " . htmlspecialchars(mysqli_error($connection)) . "</p>";
    }
}

mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=1');

// Widen image_url for legacy VARCHAR installs
if (@mysqli_query($connection, "ALTER TABLE menuitem MODIFY COLUMN image_url MEDIUMTEXT NULL")) {
    echo "<p style='color: green;'><code>menuitem.image_url</code> → MEDIUMTEXT.</p>";
}

// Legacy: add missing columns if table existed from older setup
if (@mysqli_query($connection, "ALTER TABLE user ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1")) {
    echo "<p style='color: green;'><code>user.is_active</code> added.</p>";
}
if (@mysqli_query($connection, "ALTER TABLE menuitem ADD COLUMN category VARCHAR(100) NULL")) {
    echo "<p style='color: green;'><code>menuitem.category</code> added.</p>";
}

// Check if default admin exists
$admin_phone = "0000000000";
$check_admin = mysqli_query($connection, "SELECT * FROM user WHERE phone = '$admin_phone'");
if ($check_admin && mysqli_num_rows($check_admin) == 0) {
    $admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_sql = "INSERT INTO user (name, phone, password, user_type, is_active) VALUES ('Kaah Fast Food Admin', '$admin_phone', '$admin_pass', 'Admin', 1)";
    if (mysqli_query($connection, $admin_sql)) {
        echo "<p style='color: green;'>Default admin created (Phone: $admin_phone, Pass: admin123)</p>";
    } else {
        echo "<p style='color: red;'>Error creating admin: " . htmlspecialchars(mysqli_error($connection)) . "</p>";
    }
} else {
    echo "<p>Admin account already exists or DB check skipped.</p>";
}

// Add sample menu items if empty
$check_menu = mysqli_query($connection, "SELECT * FROM menuitem LIMIT 1");
if ($check_menu && mysqli_num_rows($check_menu) == 0) {
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

echo "<h3>Setup Complete! You can delete this file in production.</h3>";
echo "<p>Already have data? Also run <a href=\"ensure_database_schema.php\"><code>ensure_database_schema.php</code></a> to patch missing tables.</p>";
echo "<a href='login.php'>Go to Login</a>";
?>
