<?php
/**
 * Safe schema alignment for existing databases (no DROP).
 * Run once in the browser: .../php/ensure_database_schema.php
 * Creates missing tables/columns so the DB matches schema.sql behaviour.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h1>ensure_database_schema</h1><pre style="font-family:monospace;">';

function table_exists(mysqli $c, $name) {
    $name = mysqli_real_escape_string($c, $name);
    $r = mysqli_query($c, "SHOW TABLES LIKE '$name'");
    return $r && mysqli_num_rows($r) > 0;
}

function column_exists(mysqli $c, $table, $col) {
    $t = mysqli_real_escape_string($c, $table);
    $col = mysqli_real_escape_string($c, $col);
    $r = mysqli_query($c, "SHOW COLUMNS FROM `$t` LIKE '$col'");
    return $r && mysqli_num_rows($r) > 0;
}

mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=0');

// --- address (required by place_order.php) ---
if (!table_exists($connection, 'address')) {
    $sql = "CREATE TABLE `address` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (mysqli_query($connection, $sql)) {
        echo "OK: created table `address`\n";
    } else {
        $e1 = mysqli_error($connection);
        // Retry without FK if types mismatch on older DBs
        $sql2 = "CREATE TABLE `address` (
          `address_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `order_id` INT UNSIGNED NOT NULL,
          `address` TEXT NOT NULL,
          `city` VARCHAR(100) NULL,
          `postal_code` VARCHAR(30) NULL,
          PRIMARY KEY (`address_id`),
          UNIQUE KEY `uq_address_order` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (mysqli_query($connection, $sql2)) {
            echo "OK: created table `address` (without FK — run ALTER when orders.order_id type matches)\n";
        } else {
            echo "ERROR address: $e1\n";
            echo "FALLBACK: " . mysqli_error($connection) . "\n";
        }
    }
} else {
    echo "SKIP: table `address` already exists\n";
}

// --- delivery: status + delivered_at (schema.sql) ---
if (table_exists($connection, 'delivery')) {
    if (!column_exists($connection, 'delivery', 'status')) {
        $sql = "ALTER TABLE `delivery` ADD COLUMN `status` ENUM('Assigned','Picked Up','On the way','Delivered') NOT NULL DEFAULT 'Assigned' AFTER `delivery_person_id`";
        if (mysqli_query($connection, $sql)) {
            echo "OK: delivery.status added\n";
        } else {
            echo "ERROR delivery.status: " . mysqli_error($connection) . "\n";
        }
    }
    if (!column_exists($connection, 'delivery', 'delivered_at')) {
        $sql = "ALTER TABLE `delivery` ADD COLUMN `delivered_at` DATETIME NULL DEFAULT NULL AFTER `assigned_at`";
        if (mysqli_query($connection, $sql)) {
            echo "OK: delivery.delivered_at added\n";
        } else {
            echo "ERROR delivery.delivered_at: " . mysqli_error($connection) . "\n";
        }
    }
    // Normalize assigned_at to DATETIME if still TIMESTAMP (optional)
    if (column_exists($connection, 'delivery', 'assigned_at')) {
        @mysqli_query($connection, "ALTER TABLE `delivery` MODIFY COLUMN `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
}

// --- menuitem.category ---
if (table_exists($connection, 'menuitem') && !column_exists($connection, 'menuitem', 'category')) {
    if (@mysqli_query($connection, "ALTER TABLE `menuitem` ADD COLUMN `category` VARCHAR(100) NULL AFTER `image_url`")) {
        echo "OK: menuitem.category added\n";
    }
}

// --- user.is_active ---
if (table_exists($connection, 'user') && !column_exists($connection, 'user', 'is_active')) {
    if (@mysqli_query($connection, "ALTER TABLE `user` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1")) {
        echo "OK: user.is_active added\n";
    }
}

mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=1');

echo "\nDone. <a href=\"../docs/SYSTEM_REVIEW.md\">docs</a> | <a href=\"login.php\">Login</a>\n";
echo '</pre>';
