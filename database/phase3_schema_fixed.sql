-- Hi5ve MarketPlace Phase 3 Database Schema (Fixed)
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