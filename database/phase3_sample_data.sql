-- Hi5ve MarketPlace Phase 3 - Sample Data
-- Insert sample data for new features

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