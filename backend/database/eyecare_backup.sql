-- Optical Management System SQL backup
-- Compatible with MySQL 8+ / MariaDB 10.5+ (XAMPP phpMyAdmin)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `eyecare` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `eyecare`;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `product_categories`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'customer',
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `lens_type` varchar(255) DEFAULT NULL,
  `frame_material` varchar(255) DEFAULT NULL,
  `ar_model_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_brand_index` (`brand`),
  KEY `products_is_active_index` (`is_active`),
  FULLTEXT KEY `products_fulltext_search` (`name`,`description`,`brand`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  KEY `product_images_sort_order_index` (`sort_order`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT 0,
  `reorder_level` int unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_product_id_unique` (`product_id`),
  CONSTRAINT `inventory_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `walk_in_name` varchar(255) DEFAULT NULL,
  `walk_in_phone` varchar(255) DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_status_index` (`status`),
  KEY `orders_user_id_index` (`user_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_index` (`order_id`),
  KEY `order_items_product_id_index` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_invoice_number_unique` (`invoice_number`),
  KEY `bills_payment_status_index` (`payment_status`),
  KEY `bills_order_id_index` (`order_id`),
  CONSTRAINT `bills_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feedbacks_user_id_product_id_unique` (`user_id`,`product_id`),
  KEY `feedbacks_product_id_index` (`product_id`),
  CONSTRAINT `feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedbacks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample seed data
-- Login password for seeded users: password
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `name`, `role`, `email`, `phone`, `avatar_url`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Admin User', 'admin', 'admin@eyecare.test', '09171234567', NULL, '2026-04-06 08:00:00', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 'admintoken1', '2026-04-06 08:00:00', '2026-04-06 08:00:00', NULL),
(2, 'Staff User', 'staff', 'staff@eyecare.test', '09179876543', NULL, '2026-04-06 08:00:00', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 'stafftoken1', '2026-04-06 08:00:00', '2026-04-06 08:00:00', NULL),
(3, 'Juan Dela Cruz', 'customer', 'customer@eyecare.test', '09181234567', NULL, '2026-04-06 08:00:00', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 'customtokn1', '2026-04-06 08:00:00', '2026-04-06 08:00:00', NULL);

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Eyeglass Frames', 'eyeglass-frames', 'Prescription eyeglass frames in various styles and materials', '2026-04-06 08:05:00', '2026-04-06 08:05:00', NULL),
(2, 'Prescription Lenses', 'prescription-lenses', 'Single vision, bifocal, and progressive lenses with various coatings', '2026-04-06 08:05:00', '2026-04-06 08:05:00', NULL),
(3, 'Contact Lenses', 'contact-lenses', 'Daily, monthly, and colored contact lenses', '2026-04-06 08:05:00', '2026-04-06 08:05:00', NULL),
(4, 'Sunglasses', 'sunglasses', 'Prescription and non-prescription sunglasses', '2026-04-06 08:05:00', '2026-04-06 08:05:00', NULL),
(5, 'Accessories', 'accessories', 'Cases, cleaning solutions, cloths, and other accessories', '2026-04-06 08:05:00', '2026-04-06 08:05:00', NULL);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `sku`, `brand`, `lens_type`, `frame_material`, `ar_model_url`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Classic Full-Rim Frame', 'Durable acetate full-rim frame suitable for everyday wear.', 1500.00, 'FRM-001', 'Bolon', NULL, 'acetate', NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(2, 1, 'Titanium Semi-Rimless Frame', 'Lightweight titanium semi-rimless frame for a sleek look.', 2800.00, 'FRM-002', 'Hangten', NULL, 'titanium', NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(3, 1, 'TR90 Flexible Frame', 'Super flexible TR90 frame, ideal for active lifestyles.', 1200.00, 'FRM-003', 'Peculiar', NULL, 'tr90', NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(4, 2, 'Single Vision Anti-Radiation Lens', 'Single vision lens with anti-radiation and blue-light blocking coating.', 800.00, 'LNS-001', NULL, 'single_vision', NULL, NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(5, 2, 'Progressive Photochromic Lens', 'Progressive lens with photochromic (Transitions) coating.', 3500.00, 'LNS-002', NULL, 'progressive', NULL, NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(6, 3, 'Daily Disposable Clear Contacts', 'Pack of 30 daily disposable clear contact lenses.', 1200.00, 'CTL-001', 'Acuvue', 'daily', NULL, NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(7, 4, 'Polarized UV Protection Sunglasses', 'Stylish polarized sunglasses with full UV protection.', 2000.00, 'SUN-001', 'Bolon', NULL, 'acetate', NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(8, 5, 'Premium Microfiber Cleaning Cloth', 'Soft microfiber cloth for lens cleaning.', 50.00, 'ACC-001', NULL, NULL, NULL, NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(9, 5, 'Hard Shell Eyeglass Case', 'Durable hard shell case for eyeglass protection.', 250.00, 'ACC-002', NULL, NULL, NULL, NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL),
(10, 5, 'Lens Cleaning Solution 120ml', 'Anti-fog lens cleaning spray solution.', 150.00, 'ACC-003', NULL, NULL, NULL, NULL, 1, '2026-04-06 08:10:00', '2026-04-06 08:10:00', NULL);

INSERT INTO `inventory` (`id`, `product_id`, `quantity`, `reorder_level`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 28, 8, 'Popular frame; restock monthly.', '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(2, 2, 14, 6, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(3, 3, 22, 7, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(4, 4, 60, 15, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(5, 5, 18, 5, 'Long lead time from lab.', '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(6, 6, 80, 20, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(7, 7, 10, 4, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(8, 8, 3, 10, 'Low stock - reorder cloths.', '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(9, 9, 35, 12, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00'),
(10, 10, 48, 15, NULL, '2026-04-06 08:15:00', '2026-04-06 08:15:00');

INSERT INTO `orders` (`id`, `user_id`, `walk_in_name`, `walk_in_phone`, `order_number`, `status`, `total_amount`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, NULL, NULL, 'ORD-20260406-00001', 'completed', 7100.00, 'First test order - completed.', '2026-04-06 08:20:00', '2026-04-06 08:20:00', NULL),
(2, 3, NULL, NULL, 'ORD-20260406-00002', 'pending', 1200.00, NULL, '2026-04-06 08:20:00', '2026-04-06 08:20:00', NULL),
(3, 3, NULL, NULL, 'ORD-20260406-00003', 'confirmed', 2300.00, 'Rush order please.', '2026-04-06 08:20:00', '2026-04-06 08:20:00', NULL),
(4, NULL, 'Maria Santos', '09171234567', 'ORD-20260406-00004', 'ready_for_pickup', 3500.00, 'Walk-in customer.', '2026-04-06 08:20:00', '2026-04-06 08:20:00', NULL);

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1500.00, 1500.00, '2026-04-06 08:20:30', '2026-04-06 08:20:30'),
(2, 1, 2, 2, 2800.00, 5600.00, '2026-04-06 08:20:30', '2026-04-06 08:20:30'),
(3, 2, 3, 1, 1200.00, 1200.00, '2026-04-06 08:20:30', '2026-04-06 08:20:30'),
(4, 3, 1, 1, 1500.00, 1500.00, '2026-04-06 08:20:30', '2026-04-06 08:20:30'),
(5, 3, 4, 1, 800.00, 800.00, '2026-04-06 08:20:30', '2026-04-06 08:20:30'),
(6, 4, 5, 1, 3500.00, 3500.00, '2026-04-06 08:20:30', '2026-04-06 08:20:30');

INSERT INTO `bills` (`id`, `order_id`, `appointment_id`, `invoice_number`, `amount`, `payment_status`, `payment_method`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'INV-20260406-00001', 7100.00, 'paid', 'cash', '2026-04-06 08:22:00', '2026-04-06 08:22:00', '2026-04-06 08:22:00'),
(2, 2, NULL, 'INV-20260406-00002', 1200.00, 'unpaid', NULL, NULL, '2026-04-06 08:22:00', '2026-04-06 08:22:00'),
(3, 3, NULL, 'INV-20260406-00003', 2300.00, 'unpaid', NULL, NULL, '2026-04-06 08:22:00', '2026-04-06 08:22:00'),
(4, 4, NULL, 'INV-20260406-00004', 3500.00, 'paid', 'GCash', '2026-04-06 08:22:00', '2026-04-06 08:22:00', '2026-04-06 08:22:00');

INSERT INTO `feedbacks` (`id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 5, 'Excellent quality frames! Very comfortable to wear all day.', '2026-04-06 08:25:00', '2026-04-06 08:25:00'),
(2, 3, 2, 4, 'Good product, fast service. Would buy again.', '2026-04-06 08:25:00', '2026-04-06 08:25:00'),
(3, 3, 3, 3, 'Decent but expected better for the price.', '2026-04-06 08:25:00', '2026-04-06 08:25:00'),
(4, 3, 4, 5, NULL, '2026-04-06 08:25:00', '2026-04-06 08:25:00'),
(5, 3, 5, 4, 'Great value for money!', '2026-04-06 08:25:00', '2026-04-06 08:25:00');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
