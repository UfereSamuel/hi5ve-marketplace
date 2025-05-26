-- Hi5ve MarketPlace Phase 3 - Update Existing Data
-- Update existing records with new column values

-- Update products with sample meta data and slugs
UPDATE `products` SET 
    `slug` = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '(', ''), ')', '')),
    `meta_title` = CONCAT(name, ' - Hi5ve MarketPlace'),
    `meta_description` = CONCAT('Buy fresh ', name, ' at the best prices. Quality guaranteed with fast delivery across Nigeria.'),
    `view_count` = FLOOR(RAND() * 100) + 10
WHERE `slug` IS NULL OR `slug` = '';

-- Update users with default verification status
UPDATE `users` SET 
    `phone_verified` = 0,
    `email_verified` = 1,
    `login_count` = 0
WHERE `phone_verified` IS NULL; 