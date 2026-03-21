-- Clean schema aligned with current PHP codebase.
-- Target: MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `delivery`;
DROP TABLE IF EXISTS `address`;
DROP TABLE IF EXISTS `orderitem`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `menuitem`;
DROP TABLE IF EXISTS `user`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `user` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menuitem` (
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
  KEY `idx_menuitem_category` (`category`),
  CONSTRAINT `chk_menuitem_price_nonnegative` CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
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
    ON DELETE RESTRICT,
  CONSTRAINT `chk_orders_total_nonnegative` CHECK (`total_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orderitem` (
  `order_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `menu_item_id` INT UNSIGNED NOT NULL,
  `item_id` INT AS (`menu_item_id`) STORED,
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
    ON DELETE RESTRICT,
  CONSTRAINT `chk_orderitem_quantity_positive` CHECK (`quantity` > 0),
  CONSTRAINT `chk_orderitem_price_nonnegative` CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `address` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
