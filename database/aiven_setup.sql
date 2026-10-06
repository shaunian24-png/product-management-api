-- =====================================================================
--  Product Management System  -  Aiven MySQL setup
--  Laboratory Exercise No. 6
--
--  How to use:
--    1. Open your Aiven MySQL service -> connect with DBeaver / MySQL
--       Workbench / mysql CLI (use the Host, Port, User, Password from the
--       Aiven "Overview" page; SSL is required).
--    2. Select your database (Aiven's default is `defaultdb`).
--    3. Run this whole file.
--
--  This is exactly what `php lava migration run` creates, so you can use
--  EITHER this file OR the migration command (do not do both on the same
--  database; if you do, nothing breaks - migrations are skipped when the
--  tables are already recorded in the `migrations` table).
-- =====================================================================

-- 1) migrations  (LavaLust tracks which migration files already ran)
CREATE TABLE IF NOT EXISTS `migrations` (
  `id`         INT      NOT NULL AUTO_INCREMENT,
  `migration`  INT      NOT NULL,
  `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_unique` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) users  (login accounts)
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(100) NOT NULL,
  `email`      VARCHAR(255) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
  `is_active`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username_unique` (`username`),
  KEY `email_idx` (`email`),
  KEY `role_idx` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) refresh_tokens  (hashed refresh tokens for the API login)
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      TEXT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `jti`        TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) products  (the table required by Laboratory Exercise No. 6)
CREATE TABLE IF NOT EXISTS `products` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(100) NOT NULL,
  `description`  TEXT DEFAULT NULL,
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity`     INT NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_name_idx` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Tell LavaLust these migrations are already applied
--    (000_initial_setup, 001_create_users_table,
--     002_create_refresh_tokens_table, 003_create_products_table)
INSERT IGNORE INTO `migrations` (`migration`) VALUES (0), (1), (2), (3);

-- Quick check
SHOW TABLES;
