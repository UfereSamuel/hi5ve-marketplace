-- Hi5ve MarketPlace Phase 3 Database Schema
-- Advanced Features: Wishlist, Product Variants, Gallery, Analytics, etc.

-- Wishlist table
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_wishlist` (`user_id`, `product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_product_id` (`product_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product variants table
CREATE TABLE IF NOT EXISTS `product_variants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `variant_type` varchar(50) NOT NULL COMMENT 'size, color, weight, etc.',
    `variant_value` varchar(100) NOT NULL,
    `price_modifier` decimal(10,2) DEFAULT 0.00 COMMENT 'Additional price for this variant',
    `stock_quantity` int(11) DEFAULT 0,
    `sku` varchar(100) DEFAULT NULL,
    `is_default` tinyint(1) DEFAULT 0,
    `sort_order` int(11) DEFAULT 0,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_variant_type` (`variant_type`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product gallery table
CREATE TABLE IF NOT EXISTS `product_gallery` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `image_path` varchar(255) NOT NULL,
    `alt_text` varchar(255) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_primary` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_is_primary` (`is_primary`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product views tracking
CREATE TABLE IF NOT EXISTS `product_views` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(128) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `referrer` varchar(255) DEFAULT NULL,
    `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_viewed_at` (`viewed_at`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recently viewed products
CREATE TABLE IF NOT EXISTS `recently_viewed` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(128) DEFAULT NULL,
    `product_id` int(11) NOT NULL,
    `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
    UNIQUE KEY `unique_session_product` (`session_id`, `product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_viewed_at` (`viewed_at`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist sharing
CREATE TABLE IF NOT EXISTS `wishlist_shares` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `share_token` varchar(64) NOT NULL,
    `expires_at` timestamp NOT NULL,
    `view_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_share_token` (`share_token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product search history
CREATE TABLE IF NOT EXISTS `search_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(128) DEFAULT NULL,
    `search_query` varchar(255) NOT NULL,
    `results_count` int(11) DEFAULT 0,
    `ip_address` varchar(45) DEFAULT NULL,
    `searched_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_search_query` (`search_query`),
    KEY `idx_searched_at` (`searched_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product recommendations
CREATE TABLE IF NOT EXISTS `product_recommendations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(128) DEFAULT NULL,
    `product_id` int(11) NOT NULL,
    `recommended_product_id` int(11) NOT NULL,
    `recommendation_type` enum('viewed_together','bought_together','similar','category') NOT NULL,
    `score` decimal(5,4) DEFAULT 0.0000,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_recommended_product_id` (`recommended_product_id`),
    KEY `idx_recommendation_type` (`recommendation_type`),
    KEY `idx_score` (`score`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recommended_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User preferences
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `preference_key` varchar(100) NOT NULL,
    `preference_value` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_preference` (`user_id`, `preference_key`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_preference_key` (`preference_key`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter subscriptions
CREATE TABLE IF NOT EXISTS `newsletter_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `status` enum('active','inactive','unsubscribed') DEFAULT 'active',
    `subscription_token` varchar(64) NOT NULL,
    `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unsubscribed_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_email` (`email`),
    UNIQUE KEY `unique_subscription_token` (`subscription_token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupons and discounts
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `type` enum('percentage','fixed_amount','free_shipping') NOT NULL,
    `value` decimal(10,2) NOT NULL,
    `minimum_amount` decimal(10,2) DEFAULT 0.00,
    `maximum_discount` decimal(10,2) DEFAULT NULL,
    `usage_limit` int(11) DEFAULT NULL,
    `used_count` int(11) DEFAULT 0,
    `user_limit` int(11) DEFAULT 1 COMMENT 'How many times a user can use this coupon',
    `valid_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `valid_until` timestamp NULL DEFAULT NULL,
    `status` enum('active','inactive','expired') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_code` (`code`),
    KEY `idx_status` (`status`),
    KEY `idx_valid_from` (`valid_from`),
    KEY `idx_valid_until` (`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupon usage tracking
CREATE TABLE IF NOT EXISTS `coupon_usage` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `coupon_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `order_id` int(11) DEFAULT NULL,
    `discount_amount` decimal(10,2) NOT NULL,
    `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_coupon_id` (`coupon_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_used_at` (`used_at`),
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new columns to existing tables
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `view_count` int(11) DEFAULT 0 AFTER `featured`,
ADD COLUMN IF NOT EXISTS `meta_title` varchar(255) DEFAULT NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `meta_description` text DEFAULT NULL AFTER `meta_title`,
ADD COLUMN IF NOT EXISTS `meta_keywords` text DEFAULT NULL AFTER `meta_description`,
ADD COLUMN IF NOT EXISTS `slug` varchar(255) DEFAULT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `weight` decimal(8,2) DEFAULT NULL AFTER `unit`,
ADD COLUMN IF NOT EXISTS `dimensions` varchar(100) DEFAULT NULL AFTER `weight`,
ADD COLUMN IF NOT EXISTS `brand` varchar(100) DEFAULT NULL AFTER `category_id`,
ADD COLUMN IF NOT EXISTS `tags` text DEFAULT NULL AFTER `meta_keywords`;

-- Add indexes for new columns
ALTER TABLE `products` 
ADD INDEX IF NOT EXISTS `idx_view_count` (`view_count`),
ADD INDEX IF NOT EXISTS `idx_slug` (`slug`),
ADD INDEX IF NOT EXISTS `idx_brand` (`brand`);

-- Add new columns to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `phone_verified` tinyint(1) DEFAULT 0 AFTER `phone`,
ADD COLUMN IF NOT EXISTS `email_verified` tinyint(1) DEFAULT 0 AFTER `email`,
ADD COLUMN IF NOT EXISTS `birth_date` date DEFAULT NULL AFTER `last_name`,
ADD COLUMN IF NOT EXISTS `gender` enum('male','female','other') DEFAULT NULL AFTER `birth_date`,
ADD COLUMN IF NOT EXISTS `avatar` varchar(255) DEFAULT NULL AFTER `gender`,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0 AFTER `last_login`;

-- Add indexes for new user columns
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_phone_verified` (`phone_verified`),
ADD INDEX IF NOT EXISTS `idx_email_verified` (`email_verified`),
ADD INDEX IF NOT EXISTS `idx_last_login` (`last_login`);

-- Add new columns to orders table
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `coupon_id` int(11) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) DEFAULT 0.00 AFTER `coupon_id`,
ADD COLUMN IF NOT EXISTS `tax_amount` decimal(10,2) DEFAULT 0.00 AFTER `discount_amount`,
ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL AFTER `whatsapp_message`,
ADD COLUMN IF NOT EXISTS `estimated_delivery` date DEFAULT NULL AFTER `notes`,
ADD COLUMN IF NOT EXISTS `tracking_number` varchar(100) DEFAULT NULL AFTER `estimated_delivery`;

-- Add foreign key for coupon
ALTER TABLE `orders` 
ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL;

-- Sample data for product variants
INSERT IGNORE INTO `product_variants` (`product_id`, `variant_type`, `variant_value`, `price_modifier`, `stock_quantity`, `is_default`) VALUES
(1, 'size', 'Small (1kg)', 0.00, 50, 1),
(1, 'size', 'Medium (2kg)', 500.00, 30, 0),
(1, 'size', 'Large (5kg)', 1000.00, 20, 0),
(2, 'size', 'Small (500g)', 0.00, 40, 1),
(2, 'size', 'Large (1kg)', 300.00, 25, 0),
(3, 'color', 'Green', 0.00, 100, 1),
(3, 'color', 'Red', 0.00, 80, 0),
(4, 'size', '1 Liter', 0.00, 60, 1),
(4, 'size', '2 Liters', 200.00, 40, 0),
(5, 'weight', '1kg', 0.00, 30, 1),
(5, 'weight', '2kg', 800.00, 20, 0);

-- Sample coupons
INSERT IGNORE INTO `coupons` (`code`, `name`, `description`, `type`, `value`, `minimum_amount`, `usage_limit`, `valid_from`, `valid_until`) VALUES
('WELCOME10', 'Welcome Discount', 'Get 10% off your first order', 'percentage', 10.00, 1000.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('SAVE500', 'Save ₦500', 'Get ₦500 off orders above ₦5000', 'fixed_amount', 500.00, 5000.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY)),
('FREESHIP', 'Free Shipping', 'Free shipping on all orders', 'free_shipping', 0.00, 2000.00, 200, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY));

-- Update products with sample meta data and slugs
UPDATE `products` SET 
    `slug` = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '(', ''), ')', '')),
    `meta_title` = CONCAT(name, ' - Hi5ve MarketPlace'),
    `meta_description` = CONCAT('Buy fresh ', name, ' at the best prices. Quality guaranteed with fast delivery across Nigeria.'),
    `view_count` = FLOOR(RAND() * 100) + 10
WHERE `slug` IS NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_products_slug` ON `products` (`slug`);
CREATE INDEX IF NOT EXISTS `idx_products_brand` ON `products` (`brand`);
CREATE INDEX IF NOT EXISTS `idx_products_view_count` ON `products` (`view_count`);
CREATE INDEX IF NOT EXISTS `idx_users_last_login` ON `users` (`last_login`);
CREATE INDEX IF NOT EXISTS `idx_orders_estimated_delivery` ON `orders` (`estimated_delivery`);
CREATE INDEX IF NOT EXISTS `idx_orders_tracking_number` ON `orders` (`tracking_number`); 