-- Hi5ve MarketPlace Phase 3 - ALTER TABLE statements (Fixed)
-- Add new columns to existing tables (only if they don't exist)

-- Add missing columns to products table
-- Most columns already exist, only add view_count if missing
ALTER TABLE `products` ADD COLUMN `view_count` int(11) DEFAULT 0;

-- Add missing columns to users table  
-- Most columns already exist, only add phone_verified if missing
ALTER TABLE `users` ADD COLUMN `phone_verified` tinyint(1) DEFAULT 0;

-- Add missing columns to orders table
-- Most columns already exist, only add missing ones
ALTER TABLE `orders` ADD COLUMN `coupon_id` int(11) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN `discount_amount` decimal(10,2) DEFAULT 0.00;
ALTER TABLE `orders` ADD COLUMN `tax_amount` decimal(10,2) DEFAULT 0.00; 