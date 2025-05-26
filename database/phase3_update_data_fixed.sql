-- Hi5ve MarketPlace Phase 3 - Update Existing Data (Fixed)
-- Update existing records with new column values

-- Update products with view count (slug and meta fields already exist)
UPDATE `products` SET 
    `view_count` = FLOOR(RAND() * 100) + 10
WHERE `view_count` IS NULL OR `view_count` = 0;

-- Update users with phone verification status
UPDATE `users` SET 
    `phone_verified` = 0
WHERE `phone_verified` IS NULL; 