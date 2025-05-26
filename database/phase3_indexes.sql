-- Hi5ve MarketPlace Phase 3 - Indexes and Foreign Keys
-- Add indexes for new columns (after columns are created)

-- Add indexes for products table
ALTER TABLE `products` ADD INDEX `idx_view_count` (`view_count`);
ALTER TABLE `products` ADD INDEX `idx_slug` (`slug`);
ALTER TABLE `products` ADD INDEX `idx_brand` (`brand`);

-- Add indexes for users table
ALTER TABLE `users` ADD INDEX `idx_phone_verified` (`phone_verified`);
ALTER TABLE `users` ADD INDEX `idx_email_verified` (`email_verified`);
ALTER TABLE `users` ADD INDEX `idx_last_login` (`last_login`);

-- Add indexes for orders table
ALTER TABLE `orders` ADD INDEX `idx_estimated_delivery` (`estimated_delivery`);
ALTER TABLE `orders` ADD INDEX `idx_tracking_number` (`tracking_number`);

-- Add foreign key for coupon in orders table
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL; 