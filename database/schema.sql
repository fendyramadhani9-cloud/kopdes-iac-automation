-- =====================================================
-- KopDes - Database Schema (MariaDB / MySQL)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `memberships`;
DROP TABLE IF EXISTS `kopdes`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('HEAD_GOV', 'MANAGER', 'CITIZEN') NOT NULL DEFAULT 'CITIZEN',
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `kopdes`
-- -----------------------------------------------------
CREATE TABLE `kopdes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `village_name` VARCHAR(100) NULL,
  `district_name` VARCHAR(100) NULL,
  `regency_name` VARCHAR(100) NULL,
  `province_name` VARCHAR(100) NULL,
  `latitude` DECIMAL(10, 8) NULL,
  `longitude` DECIMAL(11, 8) NULL,
  `description` TEXT NULL,
  `manager_id` INT UNSIGNED NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_kopdes_manager` (`manager_id`),
  INDEX `idx_kopdes_status` (`status`),
  INDEX `idx_kopdes_coords` (`latitude`, `longitude`),
  CONSTRAINT `fk_kopdes_manager`
    FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `memberships`
-- -----------------------------------------------------
CREATE TABLE `memberships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kopdes_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `member_number` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('active', 'pending', 'inactive') NOT NULL DEFAULT 'active',
  `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_kopdes_user` (`kopdes_id`, `user_id`),
  INDEX `idx_memberships_status` (`status`),
  CONSTRAINT `fk_memberships_kopdes`
    FOREIGN KEY (`kopdes_id`) REFERENCES `kopdes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_memberships_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `products`
-- -----------------------------------------------------
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kopdes_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(50) NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'Umum',
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
  `status` ENUM('available', 'out_of_stock', 'archived') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_products_kopdes` (`kopdes_id`),
  INDEX `idx_products_category` (`category`),
  INDEX `idx_products_status` (`status`),
  CONSTRAINT `fk_products_kopdes`
    FOREIGN KEY (`kopdes_id`) REFERENCES `kopdes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `transactions`
-- -----------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_code` VARCHAR(50) NOT NULL UNIQUE,
  `kopdes_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `type` ENUM('purchase', 'savings', 'loan') NOT NULL DEFAULT 'purchase',
  `quantity` INT NOT NULL DEFAULT 1,
  `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('completed', 'pending', 'cancelled') NOT NULL DEFAULT 'completed',
  `notes` VARCHAR(255) NULL,
  `transaction_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tx_invoice` (`invoice_code`),
  INDEX `idx_tx_kopdes` (`kopdes_id`),
  INDEX `idx_tx_user` (`user_id`),
  INDEX `idx_tx_product` (`product_id`),
  INDEX `idx_tx_date` (`transaction_date`),
  CONSTRAINT `fk_tx_kopdes`
    FOREIGN KEY (`kopdes_id`) REFERENCES `kopdes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tx_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tx_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
