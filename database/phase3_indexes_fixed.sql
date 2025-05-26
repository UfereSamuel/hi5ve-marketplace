-- Hi5ve MarketPlace Phase 3 - Indexes and Foreign Keys (Fixed)
-- Add indexes for new columns (after columns are created)

-- Add indexes for products table (only for columns that exist or will be added)
ALTER TABLE `products` ADD INDEX `idx_view_count` (`view_count`);

-- Add indexes for users table (only for columns that exist or will be added)  
ALTER TABLE `users` ADD INDEX `idx_phone_verified` (`phone_verified`);

-- Add indexes for orders table (only for columns that exist or will be added)
-- estimated_delivery and tracking_number already exist, coupon_id will be added
ALTER TABLE `orders` ADD INDEX `idx_coupon_id` (`coupon_id`);

-- Add foreign key for coupon in orders table
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL; 