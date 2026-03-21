<?php
/**
 * One-time migration: widen menuitem.image_url so long HTTPS URLs and
 * data:image/...;base64,... strings can be stored (VARCHAR(255) is too small).
 *
 * Run once in the browser: .../kaah-online/php/migrate_menu_image_url.php
 * Or: mysql -u USER -p DB < sql/migrate_menu_image_url.sql
 */
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h1>migrate_menu_image_url</h1><pre>';

$sql = 'ALTER TABLE menuitem MODIFY COLUMN image_url MEDIUMTEXT NULL';
if (mysqli_query($connection, $sql)) {
    echo "SUCCESS: menuitem.image_url is now MEDIUMTEXT.\n";
    echo "You can add menu items with long base64 image data.\n";
} else {
    echo 'ERROR: ' . htmlspecialchars(mysqli_error($connection), ENT_QUOTES, 'UTF-8') . "\n";
}

echo '</pre><p><a href="admin_dashboard.php">Back to admin</a></p>';
