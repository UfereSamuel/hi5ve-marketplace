-- Banner/Promotion Management System Schema
-- This allows admins to create promotional banners with product links

CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(500) NOT NULL,
    link_type ENUM('product', 'category', 'url', 'none') DEFAULT 'none',
    link_value VARCHAR(500), -- product_id, category_id, or custom URL
    position ENUM('hero', 'sidebar', 'footer', 'popup', 'category_top') DEFAULT 'hero',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATETIME,
    end_date DATETIME,
    click_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for better performance
CREATE INDEX idx_banners_active ON banners(is_active);
CREATE INDEX idx_banners_position ON banners(position);
CREATE INDEX idx_banners_dates ON banners(start_date, end_date);
CREATE INDEX idx_banners_order ON banners(display_order);

-- Insert sample promotional banners
INSERT INTO banners (title, description, image_path, link_type, link_value, position, display_order, start_date, end_date) VALUES
('Summer Sale - 50% Off Fresh Fruits', 'Get the best deals on fresh seasonal fruits', 'uploads/banners/summer-fruits-banner.jpg', 'category', '1', 'hero', 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('Featured Product: Premium Rice', 'High quality premium rice now available', 'uploads/banners/premium-rice-banner.jpg', 'product', '1', 'hero', 2, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY)),
('Free Delivery on Orders Above ₦5000', 'Shop now and enjoy free delivery to your doorstep', 'uploads/banners/free-delivery-banner.jpg', 'url', '/products.php', 'sidebar', 1, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY)); 