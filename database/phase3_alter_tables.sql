-- Hi5ve MarketPlace Phase 3 - ALTER TABLE statements
-- Add new columns to existing tables

-- Add new columns to products table
ALTER TABLE `products` ADD COLUMN `view_count` int(11) DEFAULT 0 AFTER `featured`;
ALTER TABLE `products` ADD COLUMN `meta_title` varchar(255) DEFAULT NULL AFTER `description`;
ALTER TABLE `products` ADD COLUMN `meta_description` text DEFAULT NULL AFTER `meta_title`;
ALTER TABLE `products` ADD COLUMN `meta_keywords` text DEFAULT NULL AFTER `meta_description`;
ALTER TABLE `products` ADD COLUMN `slug` varchar(255) DEFAULT NULL AFTER `name`;
ALTER TABLE `products` ADD COLUMN `weight` decimal(8,2) DEFAULT NULL AFTER `unit`;
ALTER TABLE `products` ADD COLUMN `dimensions` varchar(100) DEFAULT NULL AFTER `weight`;
ALTER TABLE `products` ADD COLUMN `brand` varchar(100) DEFAULT NULL AFTER `category_id`;
ALTER TABLE `products` ADD COLUMN `tags` text DEFAULT NULL AFTER `meta_keywords`;

-- Add new columns to users table
ALTER TABLE `users` ADD COLUMN `phone_verified` tinyint(1) DEFAULT 0 AFTER `phone`;
ALTER TABLE `users` ADD COLUMN `email_verified` tinyint(1) DEFAULT 0 AFTER `email`;
ALTER TABLE `users` ADD COLUMN `birth_date` date DEFAULT NULL AFTER `last_name`;
ALTER TABLE `users` ADD COLUMN `gender` enum('male','female','other') DEFAULT NULL AFTER `birth_date`;
ALTER TABLE `users` ADD COLUMN `avatar` varchar(255) DEFAULT NULL AFTER `gender`;
ALTER TABLE `users` ADD COLUMN `last_login` timestamp NULL DEFAULT NULL AFTER `created_at`;
ALTER TABLE `users` ADD COLUMN `login_count` int(11) DEFAULT 0 AFTER `last_login`;

-- Add new columns to orders table
ALTER TABLE `orders` ADD COLUMN `coupon_id` int(11) DEFAULT NULL AFTER `payment_method`;
ALTER TABLE `orders` ADD COLUMN `discount_amount` decimal(10,2) DEFAULT 0.00 AFTER `coupon_id`;
ALTER TABLE `orders` ADD COLUMN `tax_amount` decimal(10,2) DEFAULT 0.00 AFTER `discount_amount`;
ALTER TABLE `orders` ADD COLUMN `notes` text DEFAULT NULL AFTER `whatsapp_message`;
ALTER TABLE `orders` ADD COLUMN `estimated_delivery` date DEFAULT NULL AFTER `notes`;
ALTER TABLE `orders` ADD COLUMN `tracking_number` varchar(100) DEFAULT NULL AFTER `estimated_delivery`; 