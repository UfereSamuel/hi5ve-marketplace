-- Sample Products for Hi5ve MarketPlace
-- Run this after the main setup to add sample products

-- Insert sample products for Fruits & Vegetables
INSERT INTO products (name, description, category_id, price, discount_price, stock_quantity, unit, featured) VALUES
('Fresh Tomatoes', 'Fresh red tomatoes perfect for cooking and salads', 1, 800.00, 700.00, 50, 'kg', 1),
('Green Bell Peppers', 'Crisp green bell peppers rich in vitamins', 1, 600.00, NULL, 30, 'kg', 0),
('Fresh Onions', 'Quality onions for all your cooking needs', 1, 400.00, 350.00, 40, 'kg', 1),
('Carrots', 'Fresh orange carrots packed with nutrients', 1, 500.00, NULL, 25, 'kg', 0),
('Cucumber', 'Fresh cucumbers perfect for salads', 1, 300.00, NULL, 35, 'kg', 0),
('Spinach (Ugu)', 'Fresh Nigerian spinach leaves', 1, 200.00, NULL, 20, 'bunch', 1),
('Plantain (Unripe)', 'Green plantains for cooking', 1, 150.00, NULL, 60, 'piece', 0),
('Banana', 'Sweet ripe bananas', 1, 100.00, 80.00, 80, 'piece', 1);

-- Insert sample products for Dairy & Eggs
INSERT INTO products (name, description, category_id, price, discount_price, stock_quantity, unit, featured) VALUES
('Fresh Milk (1L)', 'Pure fresh cow milk', 2, 800.00, NULL, 25, 'liter', 1),
('Eggs (Crate)', 'Fresh chicken eggs - 30 pieces', 2, 1800.00, 1650.00, 15, 'crate', 1),
('Butter', 'Premium quality butter for cooking and baking', 2, 1200.00, NULL, 20, 'pack', 0),
('Cheese (Processed)', 'Delicious processed cheese', 2, 1500.00, NULL, 18, 'pack', 0),
('Yogurt', 'Natural yogurt with probiotics', 2, 600.00, 550.00, 30, 'cup', 0);

-- Insert sample products for Meat & Poultry
INSERT INTO products (name, description, category_id, price, discount_price, stock_quantity, unit, featured) VALUES
('Chicken (Whole)', 'Fresh whole chicken', 3, 3500.00, 3200.00, 12, 'kg', 1),
('Beef (Fresh)', 'Premium fresh beef cuts', 3, 4500.00, NULL, 8, 'kg', 1),
('Fish (Tilapia)', 'Fresh tilapia fish', 3, 2800.00, 2500.00, 15, 'kg', 0),
('Turkey', 'Fresh turkey meat', 3, 4000.00, NULL, 6, 'kg', 0);

-- Insert sample products for Pantry Staples
INSERT INTO products (name, description, category_id, price, discount_price, stock_quantity, unit, featured) VALUES
('Rice (Local)', 'Quality Nigerian rice - 50kg bag', 4, 35000.00, 32000.00, 20, 'bag', 1),
('Beans (Brown)', 'Nutritious brown beans - 10kg', 4, 8500.00, NULL, 25, 'bag', 1),
('Garri (White)', 'Premium white garri', 4, 1200.00, 1000.00, 40, 'kg', 0),
('Palm Oil', 'Pure red palm oil - 4 liters', 4, 3200.00, NULL, 30, 'gallon', 1),
('Groundnut Oil', 'Pure groundnut cooking oil - 4 liters', 4, 4500.00, 4200.00, 22, 'gallon', 0),
('Salt', 'Refined table salt', 4, 200.00, NULL, 50, 'kg', 0),
('Sugar', 'White granulated sugar', 4, 800.00, 750.00, 35, 'kg', 0),
('Flour (Wheat)', 'All-purpose wheat flour', 4, 1500.00, NULL, 28, 'kg', 0);

-- Insert sample products for Beverages
INSERT INTO products (name, description, category_id, price, discount_price, stock_quantity, unit, featured) VALUES
('Coca-Cola (50cl)', 'Refreshing Coca-Cola soft drink', 5, 200.00, NULL, 100, 'bottle', 0),
('Fanta (50cl)', 'Orange flavored soft drink', 5, 200.00, NULL, 80, 'bottle', 0),
('Bottled Water (75cl)', 'Pure drinking water', 5, 150.00, 120.00, 120, 'bottle', 1),
('Fruit Juice (1L)', 'Mixed fruit juice', 5, 800.00, 700.00, 40, 'pack', 0),
('Malt Drink', 'Nutritious malt beverage', 5, 300.00, NULL, 60, 'bottle', 0);

-- Insert sample products for Snacks
INSERT INTO products (name, description, category_id, price, discount_price, stock_quantity, unit, featured) VALUES
('Biscuits (Digestive)', 'Healthy digestive biscuits', 6, 500.00, 450.00, 45, 'pack', 0),
('Chin Chin', 'Crunchy Nigerian snack', 6, 800.00, NULL, 30, 'pack', 1),
('Plantain Chips', 'Crispy plantain chips', 6, 600.00, 550.00, 35, 'pack', 0),
('Groundnuts (Roasted)', 'Roasted groundnuts', 6, 400.00, NULL, 40, 'pack', 0),
('Popcorn', 'Sweet popcorn snack', 6, 300.00, 250.00, 50, 'pack', 0); 