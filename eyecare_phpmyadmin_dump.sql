-- EyeCare MySQL dump for phpMyAdmin import
-- Generated from the current Laravel migration structure and project seed data shape.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `eyecare`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `eyecare`;

DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `billing_payment_histories`;
DROP TABLE IF EXISTS `order_status_histories`;
DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `inventory_adjustments`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `product_categories`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'customer',
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text,
  `customer_notes` text,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text,
  `two_factor_recovery_codes` text,
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
  `user_agent` text,
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
  `options` mediumtext,
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

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `has_ar_support` tinyint(1) NOT NULL DEFAULT '0',
  `requires_expiry_tracking` tinyint(1) NOT NULL DEFAULT '0',
  `requires_prescription` tinyint(1) NOT NULL DEFAULT '0',
  `stock_unit` enum('units','pairs','boxes') NOT NULL DEFAULT 'units',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `has_frame_size` tinyint(1) NOT NULL DEFAULT '0',
  `has_color` tinyint(1) NOT NULL DEFAULT '0',
  `has_material` tinyint(1) NOT NULL DEFAULT '0',
  `has_lens_type` tinyint(1) NOT NULL DEFAULT '0',
  `has_power_field` tinyint(1) NOT NULL DEFAULT '0',
  `has_duration` tinyint(1) NOT NULL DEFAULT '0',
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
  `description` text,
  `brand` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_brand_index` (`brand`),
  KEY `products_is_active_index` (`is_active`),
  FULLTEXT KEY `products_fulltext_search` (`name`,`description`,`brand`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `frame_size` varchar(255) DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `lens_type` varchar(255) DEFAULT NULL,
  `power` varchar(255) DEFAULT NULL,
  `duration` enum('Daily','Bi-weekly','Monthly','Quarterly','Yearly') DEFAULT NULL,
  `base_curve` varchar(255) DEFAULT NULL,
  `diameter` varchar(255) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `ar_model_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_product_id_index` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  KEY `product_images_product_variant_id_foreign` (`product_variant_id`),
  KEY `product_images_sort_order_index` (`sort_order`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_images_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '0',
  `reorder_level` int unsigned NOT NULL DEFAULT '0',
  `reorder_quantity` int unsigned NOT NULL DEFAULT '0',
  `batch_number` varchar(255) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_product_variant_id_unique` (`product_variant_id`),
  CONSTRAINT `inventory_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `quantity_before` int NOT NULL,
  `quantity_after` int NOT NULL,
  `delta` int NOT NULL,
  `adjustment_type` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `adjusted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_adjustments_inventory_created_index` (`inventory_id`,`created_at`),
  KEY `inventory_adjustments_adjustment_type_index` (`adjustment_type`),
  KEY `inventory_adjustments_adjusted_by_foreign` (`adjusted_by`),
  CONSTRAINT `inventory_adjustments_adjusted_by_foreign` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_adjustments_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `appointment_type` varchar(255) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_user_status_index` (`user_id`,`status`),
  CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `walk_in_name` varchar(255) DEFAULT NULL,
  `walk_in_phone` varchar(255) DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `ready_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_user_id_index` (`user_id`),
  KEY `orders_appointment_id_index` (`appointment_id`),
  KEY `orders_processed_by_index` (`processed_by`),
  KEY `orders_status_index` (`status`),
  KEY `orders_ready_at_index` (`ready_at`),
  KEY `orders_completed_at_index` (`completed_at`),
  CONSTRAINT `orders_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_index` (`order_id`),
  KEY `order_items_product_variant_id_index` (`product_variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `official_receipt_number` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(255) DEFAULT NULL,
  `collected_by` bigint unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_invoice_number_unique` (`invoice_number`),
  KEY `bills_order_id_index` (`order_id`),
  KEY `bills_payment_status_index` (`payment_status`),
  KEY `bills_payment_method_index` (`payment_method`),
  KEY `bills_collected_by_index` (`collected_by`),
  CONSTRAINT `bills_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `admin_reply` text,
  `moderated_by` bigint unsigned DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `feedback_type` varchar(32) NOT NULL DEFAULT 'product',
  `appointment_id` bigint unsigned DEFAULT NULL,
  `approval_status` varchar(32) NOT NULL DEFAULT 'pending',
  `approval_reviewed_at` timestamp NULL DEFAULT NULL,
  `approval_reviewed_by` bigint unsigned DEFAULT NULL,
  `rejection_reason` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feedbacks_user_product_unique` (`user_id`,`product_id`),
  UNIQUE KEY `feedbacks_user_appointment_unique` (`user_id`,`appointment_id`),
  KEY `feedbacks_product_id_index` (`product_id`),
  KEY `feedbacks_is_visible_index` (`is_visible`),
  KEY `feedbacks_is_verified_purchase_index` (`is_verified_purchase`),
  KEY `feedbacks_moderated_by_index` (`moderated_by`),
  KEY `feedbacks_feedback_type_approval_status_index` (`feedback_type`,`approval_status`),
  KEY `feedbacks_appointment_id_foreign` (`appointment_id`),
  KEY `feedbacks_approval_reviewed_by_foreign` (`approval_reviewed_by`),
  CONSTRAINT `feedbacks_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedbacks_approval_reviewed_by_foreign` FOREIGN KEY (`approval_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedbacks_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedbacks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_status_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `from_status` varchar(32) DEFAULT NULL,
  `to_status` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_status_histories_created_at_index` (`created_at`),
  KEY `order_status_histories_order_created_index` (`order_id`,`created_at`),
  KEY `order_status_histories_actor_user_id_foreign` (`actor_user_id`),
  CONSTRAINT `order_status_histories_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `billing_payment_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(48) NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(32) DEFAULT NULL,
  `from_payment_status` varchar(32) DEFAULT NULL,
  `to_payment_status` varchar(32) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `billing_payment_histories_created_at_index` (`created_at`),
  KEY `billing_payment_histories_bill_created_index` (`bill_id`,`created_at`),
  KEY `billing_payment_histories_actor_user_id_foreign` (`actor_user_id`),
  CONSTRAINT `billing_payment_histories_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_payment_histories_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversations_user_id_unique` (`user_id`),
  KEY `conversations_user_id_index` (`user_id`),
  CONSTRAINT `conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_index` (`conversation_id`),
  KEY `messages_sender_id_index` (`sender_id`),
  KEY `messages_is_read_index` (`is_read`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`name`,`role`,`email`,`email_verified_at`,`phone`,`avatar_url`,`date_of_birth`,`address`,`customer_notes`,`password`,`two_factor_secret`,`two_factor_recovery_codes`,`two_factor_confirmed_at`,`remember_token`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'Admin User','admin','admin@eyecare.test','2026-04-10 08:00:00','09171234567',NULL,NULL,NULL,NULL,'$2y$12$vQdXcpXnzbjjv5gLAadYzu5fM7p7HG0I5ZEtVxkAoHX96SpRoRgrO',NULL,NULL,NULL,'admintok01','2026-04-10 08:00:00','2026-04-10 08:00:00',NULL),
(2,'Staff User','staff','staff@eyecare.test','2026-04-10 08:05:00','09179876543',NULL,NULL,NULL,NULL,'$2y$12$vQdXcpXnzbjjv5gLAadYzu5fM7p7HG0I5ZEtVxkAoHX96SpRoRgrO',NULL,NULL,NULL,'stafftok02','2026-04-10 08:05:00','2026-04-10 08:05:00',NULL),
(3,'Juan Dela Cruz','customer','customer@eyecare.test','2026-04-10 08:10:00','09181234567',NULL,'1990-05-12','123 Timog Avenue, Brgy. South Triangle\nQuezon City, Metro Manila','Prefers lightweight frames. SC/PWD discount verified on file (manual). Reminder: sensitive to tight nose pads.','$2y$12$vQdXcpXnzbjjv5gLAadYzu5fM7p7HG0I5ZEtVxkAoHX96SpRoRgrO',NULL,NULL,NULL,'customtok03','2026-04-10 08:10:00','2026-04-10 08:10:00',NULL),
(4,'Maria Santos','customer','maria@eyecare.test','2026-04-10 08:15:00','09189876543',NULL,'1988-03-22','456 Session Road, Baguio City, Benguet',NULL,'$2y$12$vQdXcpXnzbjjv5gLAadYzu5fM7p7HG0I5ZEtVxkAoHX96SpRoRgrO',NULL,NULL,NULL,'mariatok04','2026-04-10 08:15:00','2026-04-10 08:15:00',NULL);

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),
(5,'2026_03_10_000001_add_role_and_profile_columns_to_users_table',1),
(6,'2026_03_10_000002_create_product_categories_table',1),
(7,'2026_03_10_000003_create_products_table',1),
(8,'2026_03_10_000004_create_product_images_table',1),
(9,'2026_03_10_000005_create_inventory_table',1),
(10,'2026_03_10_080127_create_personal_access_tokens_table',1),
(11,'2026_03_27_000001_create_orders_table',1),
(12,'2026_03_27_000002_create_order_items_table',1),
(13,'2026_03_27_000003_create_bills_table',1),
(14,'2026_03_27_000004_create_feedbacks_table',1),
(15,'2026_04_06_000001_fix_product_category_fk_and_add_fulltext_index',1),
(16,'2026_04_08_000001_add_partial_payment_fields_to_bills_table',1),
(17,'2026_04_08_000001_add_product_variants_refactor_inventory',1),
(18,'2026_04_08_000002_add_discount_amount_to_orders_table',1),
(19,'2026_04_08_000002_move_sku_to_product_variants',1),
(20,'2026_04_08_000003_add_pricing_fields_to_products_table',1),
(21,'2026_04_08_000004_add_gender_to_products_table',1),
(22,'2026_04_08_000005_drop_compare_at_price_from_products_table',1),
(23,'2026_04_08_000006_create_suppliers_table',1),
(24,'2026_04_08_000007_add_supplier_id_to_products_table',1),
(25,'2026_04_08_000008_add_optical_fields_to_variants_and_inventory',1),
(26,'2026_04_08_000009_create_inventory_adjustments_table',1),
(27,'2026_04_08_000010_add_requires_expiry_tracking_to_product_categories_table',1),
(28,'2026_04_08_000011_drop_gender_from_products_table',1),
(29,'2026_04_08_000012_add_operational_columns_to_orders_table',1),
(30,'2026_04_08_000013_add_item_notes_to_order_items_table',1),
(31,'2026_04_08_000014_add_practical_fields_to_bills_table',1),
(32,'2026_04_08_000015_add_moderation_fields_to_feedbacks_table',1),
(33,'2026_04_08_000016_add_storage_location_to_inventory_table',1),
(34,'2026_04_09_000001_add_behavior_flags_to_product_categories_table',1),
(35,'2026_04_09_000001_add_customer_profile_fields_to_users_table',1),
(36,'2026_04_09_120000_migrate_product_images_to_variants',1),
(37,'2026_04_09_160000_move_ar_model_url_to_product_variants',1),
(38,'2026_04_09_180000_create_order_status_histories_table',1),
(39,'2026_04_09_190000_create_billing_payment_histories_table',1),
(40,'2026_04_10_100000_drop_suppliers_add_cost_to_variants_and_product_images_product_id',1),
(41,'2026_04_10_120000_drop_storage_location_and_bill_extra_columns',1),
(42,'2026_04_10_130000_move_product_price_to_product_variants_table',1),
(43,'2026_04_10_140000_add_appointment_id_to_orders_table',1),
(44,'2026_04_10_150000_add_power_and_duration_to_product_variants_table',1),
(45,'2026_04_10_160000_change_product_variants_duration_to_enum',1),
(46,'2026_04_10_170000_create_conversations_table',1),
(47,'2026_04_10_170001_create_messages_table',1),
(48,'2026_04_11_120000_add_is_active_to_product_variants_table',1),
(49,'2026_04_11_130000_create_appointments_and_extend_feedbacks_table',1),
(50,'2026_04_12_100000_add_official_receipt_number_to_bills_table',1),
(51,'2026_04_12_120000_drop_subject_and_status_from_conversations',1),
(52,'2026_04_12_200000_add_authorized_by_to_billing_payment_histories_table',1),
(53,'2026_04_12_220000_drop_authorized_by_user_id_from_billing_payment_histories_table',1);

INSERT INTO `product_categories` (`id`,`name`,`slug`,`description`,`has_ar_support`,`requires_expiry_tracking`,`requires_prescription`,`stock_unit`,`is_system`,`has_frame_size`,`has_color`,`has_material`,`has_lens_type`,`has_power_field`,`has_duration`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'Eyeglass Frames','eyeglass-frames','Prescription eyeglass frames in various styles and materials.',1,0,0,'units',1,1,1,1,1,0,0,'2026-04-10 09:00:00','2026-04-10 09:00:00',NULL),
(2,'Contact Lenses','contact-lenses','Daily, monthly, and colored contact lenses. Expiry date required.',0,1,1,'boxes',1,0,0,0,0,1,1,'2026-04-10 09:00:00','2026-04-10 09:00:00',NULL),
(3,'Sunglasses','sunglasses','Prescription and non-prescription sunglasses with UV protection.',1,0,0,'units',1,1,1,1,1,0,0,'2026-04-10 09:00:00','2026-04-10 09:00:00',NULL),
(4,'Accessories','accessories','Cases, cleaning solutions, cloths, and other eyewear accessories.',0,0,0,'units',0,0,1,0,0,0,0,'2026-04-10 09:00:00','2026-04-10 09:00:00',NULL);

INSERT INTO `products` (`id`,`category_id`,`name`,`description`,`brand`,`is_active`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,1,'Classic Full-Rim Frame','Durable acetate full-rim frame suitable for everyday wear. Available in multiple colours and well-suited for high prescriptions.','Bolon',1,'2026-04-10 09:10:00','2026-04-10 09:10:00',NULL),
(2,2,'Daily Disposable Clear Contacts','Pack of 30 daily disposable clear contact lenses. Recommended for first-time wearers and occasional use.','Acuvue',1,'2026-04-10 09:12:00','2026-04-10 09:12:00',NULL),
(3,3,'Polarized UV Protection Sunglasses','Stylish polarized sunglasses with UV400 protection. Reduces glare and suitable for driving and outdoor activities.','Bolon',1,'2026-04-10 09:14:00','2026-04-10 09:14:00',NULL),
(4,4,'Premium Microfiber Cleaning Cloth','Ultra-soft microfiber cloth for streak-free lens cleaning. Safe for all coatings including anti-reflective.','Eyecare Essentials',1,'2026-04-10 09:16:00','2026-04-10 09:16:00',NULL);

INSERT INTO `product_variants` (`id`,`product_id`,`sku`,`color`,`frame_size`,`material`,`lens_type`,`power`,`duration`,`base_curve`,`diameter`,`cost_per_unit`,`price`,`is_default`,`ar_model_url`,`is_active`,`created_at`,`updated_at`) VALUES
(1,1,'FRAME-CLASSIC-BLK-54','Black','Medium (54mm)','Acetate','Clear',NULL,NULL,NULL,NULL,600.00,1500.00,1,'https://models.eyecare.test/frames/classic-full-rim.glb',1,'2026-04-10 09:10:00','2026-04-10 09:10:00'),
(2,2,'CONTACT-DAILY-200','',NULL,NULL,NULL,'-2.00','Daily',NULL,NULL,480.00,1200.00,1,NULL,1,'2026-04-10 09:12:00','2026-04-10 09:12:00'),
(3,3,'SUN-POLARIZED-BLK-56','Black','Large (56mm)','Acetate','Polarized',NULL,NULL,NULL,NULL,750.00,2000.00,1,'https://models.eyecare.test/sunglasses/polarized-uv.glb',1,'2026-04-10 09:14:00','2026-04-10 09:14:00'),
(4,4,'ACC-CLOTH-GRAY-01','Gray',NULL,NULL,NULL,NULL,NULL,NULL,NULL,15.00,50.00,1,NULL,1,'2026-04-10 09:16:00','2026-04-10 09:16:00');

INSERT INTO `product_images` (`id`,`product_id`,`product_variant_id`,`image_url`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,1,1,'/images/products/classic_full_rim_frame_black.webp',0,'2026-04-10 09:10:00','2026-04-10 09:10:00'),
(2,2,2,'/images/products/contact_lens.jpg',0,'2026-04-10 09:12:00','2026-04-10 09:12:00'),
(3,3,3,'/images/products/polarized_sunglasses.jpg',0,'2026-04-10 09:14:00','2026-04-10 09:14:00'),
(4,4,4,'/images/products/microfiber_cloth.jpg',0,'2026-04-10 09:16:00','2026-04-10 09:16:00');

INSERT INTO `inventory` (`id`,`product_variant_id`,`quantity`,`reorder_level`,`reorder_quantity`,`batch_number`,`expires_at`,`notes`,`created_at`,`updated_at`) VALUES
(1,1,28,8,20,NULL,NULL,'Popular frame; restock monthly.','2026-04-10 10:00:00','2026-04-10 10:20:00'),
(2,2,80,20,50,'ACU-2026-03-D','2027-03-31','Keep sealed until dispensed.','2026-04-10 10:00:00','2026-04-10 10:10:00'),
(3,3,10,4,12,NULL,NULL,NULL,'2026-04-10 10:00:00','2026-04-10 10:10:00'),
(4,4,120,24,60,NULL,NULL,'Fast-moving accessory.','2026-04-10 10:00:00','2026-04-10 10:10:00');

INSERT INTO `inventory_adjustments` (`id`,`inventory_id`,`quantity_before`,`quantity_after`,`delta`,`adjustment_type`,`reason`,`adjusted_by`,`created_at`,`updated_at`) VALUES
(1,1,0,30,30,'add','data_correction',2,'2026-04-10 10:05:00','2026-04-10 10:05:00'),
(2,1,30,28,-2,'subtract','damaged_or_defective',2,'2026-04-10 10:20:00','2026-04-10 10:20:00'),
(3,2,0,80,80,'add','data_correction',2,'2026-04-10 10:10:00','2026-04-10 10:10:00'),
(4,3,0,10,10,'add','data_correction',2,'2026-04-10 10:10:00','2026-04-10 10:10:00'),
(5,4,0,120,120,'add','data_correction',2,'2026-04-10 10:10:00','2026-04-10 10:10:00');

INSERT INTO `orders` (`id`,`user_id`,`appointment_id`,`processed_by`,`walk_in_name`,`walk_in_phone`,`order_number`,`status`,`total_amount`,`discount_amount`,`notes`,`ready_at`,`completed_at`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,4,NULL,2,NULL,NULL,'ORD-20260417-00001','completed',3900.00,0.00,'First test order - completed.','2026-04-11 09:00:00','2026-04-12 09:00:00','2026-04-11 08:30:00','2026-04-12 09:00:00',NULL),
(2,4,NULL,NULL,NULL,NULL,'ORD-20260417-00002','pending',1200.00,0.00,NULL,NULL,NULL,'2026-04-13 10:00:00','2026-04-13 10:00:00',NULL),
(3,4,NULL,2,NULL,NULL,'ORD-20260417-00003','confirmed',3500.00,150.00,'SC discount applied - 20% on frame.',NULL,NULL,'2026-04-14 11:00:00','2026-04-14 11:00:00',NULL),
(4,NULL,NULL,2,'Maria Santos','09171234567','ORD-20260417-00004','ready_for_pickup',50.00,0.00,'Walk-in customer - accessory ready for pickup.','2026-04-16 15:00:00',NULL,'2026-04-16 14:00:00','2026-04-16 15:00:00',NULL),
(5,4,NULL,2,NULL,NULL,'ORD-20260417-00005','completed',2000.00,0.00,NULL,'2026-04-14 16:00:00','2026-04-15 16:00:00','2026-04-14 12:00:00','2026-04-15 16:00:00',NULL);

INSERT INTO `order_items` (`id`,`order_id`,`product_variant_id`,`quantity`,`unit_price`,`subtotal`,`notes`,`created_at`,`updated_at`) VALUES
(1,1,1,1,1500.00,1500.00,NULL,'2026-04-11 08:30:00','2026-04-11 08:30:00'),
(2,1,2,2,1200.00,2400.00,NULL,'2026-04-11 08:30:00','2026-04-11 08:30:00'),
(3,2,2,1,1200.00,1200.00,NULL,'2026-04-13 10:00:00','2026-04-13 10:00:00'),
(4,3,1,1,1500.00,1500.00,NULL,'2026-04-14 11:00:00','2026-04-14 11:00:00'),
(5,3,3,1,2000.00,2000.00,NULL,'2026-04-14 11:00:00','2026-04-14 11:00:00'),
(6,4,4,1,50.00,50.00,NULL,'2026-04-16 14:00:00','2026-04-16 14:00:00'),
(7,5,3,1,2000.00,2000.00,NULL,'2026-04-14 12:00:00','2026-04-14 12:00:00');

INSERT INTO `bills` (`id`,`order_id`,`appointment_id`,`invoice_number`,`official_receipt_number`,`amount`,`amount_paid`,`balance_due`,`payment_status`,`payment_method`,`collected_by`,`paid_at`,`created_at`,`updated_at`) VALUES
(1,1,NULL,'INV-20260417-00001',NULL,3900.00,3900.00,0.00,'paid','cash',2,'2026-04-12 09:05:00','2026-04-11 08:35:00','2026-04-12 09:05:00'),
(2,2,NULL,'INV-20260417-00002',NULL,1200.00,0.00,1200.00,'unpaid',NULL,NULL,NULL,'2026-04-13 10:05:00','2026-04-13 10:05:00'),
(3,3,NULL,'INV-20260417-00003',NULL,3350.00,0.00,3350.00,'unpaid',NULL,NULL,NULL,'2026-04-14 11:05:00','2026-04-14 11:05:00'),
(4,4,NULL,'INV-20260417-00004',NULL,50.00,50.00,0.00,'paid','gcash',2,'2026-04-16 15:05:00','2026-04-16 14:05:00','2026-04-16 15:05:00'),
(5,5,NULL,'INV-20260417-00005',NULL,2000.00,2000.00,0.00,'paid','maya',2,'2026-04-15 16:05:00','2026-04-14 12:05:00','2026-04-15 16:05:00');

INSERT INTO `billing_payment_histories` (`id`,`bill_id`,`actor_user_id`,`action`,`amount`,`payment_method`,`from_payment_status`,`to_payment_status`,`note`,`created_at`) VALUES
(1,1,2,'payment_recorded',3900.00,'cash','unpaid','paid',NULL,'2026-04-12 09:05:00'),
(2,4,2,'payment_recorded',50.00,'gcash','unpaid','paid',NULL,'2026-04-16 15:05:00'),
(3,5,2,'payment_recorded',800.00,'maya','unpaid','partially_paid',NULL,'2026-04-15 15:30:00'),
(4,5,2,'payment_recorded',1200.00,'maya','partially_paid','paid',NULL,'2026-04-15 16:05:00');

INSERT INTO `feedbacks` (`id`,`user_id`,`product_id`,`rating`,`comment`,`is_verified_purchase`,`is_visible`,`admin_reply`,`moderated_by`,`moderated_at`,`created_at`,`updated_at`,`feedback_type`,`appointment_id`,`approval_status`,`approval_reviewed_at`,`approval_reviewed_by`,`rejection_reason`) VALUES
(1,4,1,5,'Excellent quality frames! Very comfortable to wear all day. The acetate feels premium.',1,1,'Thank you for the kind words, Maria! We''re glad the Classic Full-Rim is working out well for you. Visit us anytime for adjustments.',1,'2026-04-13 12:00:00','2026-04-13 11:30:00','2026-04-13 12:00:00','product',NULL,'approved',NULL,NULL,NULL),
(2,4,2,3,'Comfortable dailies; took a few days to get used to insertion. Stock was fresh.',1,1,NULL,NULL,NULL,'2026-04-13 11:35:00','2026-04-13 11:35:00','product',NULL,'approved',NULL,NULL,NULL),
(3,4,3,5,NULL,0,1,NULL,NULL,NULL,'2026-04-13 11:40:00','2026-04-13 11:40:00','product',NULL,'approved',NULL,NULL,NULL),
(4,4,4,4,'Soft cloth, no streaks on my lenses. Good size for my bag.',0,1,NULL,NULL,NULL,'2026-04-13 11:45:00','2026-04-13 11:45:00','product',NULL,'approved',NULL,NULL,NULL);

INSERT INTO `conversations` (`id`,`user_id`,`last_message_at`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,4,'2026-04-17 07:55:00','2026-04-17 05:00:00','2026-04-17 07:55:00',NULL);

INSERT INTO `messages` (`id`,`conversation_id`,`sender_id`,`body`,`is_read`,`read_at`,`created_at`,`updated_at`) VALUES
(1,1,4,'Hi, I wanted to ask about my lens prescription. My doctor gave me a new one - do I need to come in for a fitting?',1,'2026-04-17 05:05:00','2026-04-17 05:00:00','2026-04-17 05:00:00'),
(2,1,2,'Hello Maria! Yes, if you have a new prescription we recommend dropping by so we can verify the measurements and check your current frames are still suitable.',1,'2026-04-17 05:22:00','2026-04-17 05:15:00','2026-04-17 05:15:00'),
(3,1,4,'Great, can I walk in or do I need an appointment?',1,'2026-04-17 05:47:00','2026-04-17 05:40:00','2026-04-17 05:40:00'),
(4,1,2,'Walk-ins are welcome during business hours (Mon-Sat, 9AM-6PM). We usually process lens replacements on the spot if the frames are with us.',1,'2026-04-17 06:20:00','2026-04-17 06:10:00','2026-04-17 06:10:00'),
(5,1,4,'Perfect, I''ll come by this Saturday. Thank you!',0,NULL,'2026-04-17 07:55:00','2026-04-17 07:55:00');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
