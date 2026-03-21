-- Run in phpMyAdmin on database `kaah` if `address` is missing (matches schema.sql).
-- If you get FK errors, your `orders.order_id` type may differ; run php/ensure_database_schema.php instead.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `address` (
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

SET FOREIGN_KEY_CHECKS = 1;
