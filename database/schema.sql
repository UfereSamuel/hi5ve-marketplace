-- Hi5ve MarketPlace Database Schema
-- Database: mart3

-- Users table (Admin and Customers)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'piece',
    image VARCHAR(255),
    gallery TEXT, -- JSON array of additional images
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0
);

-- Cart table (for registered users)
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Guest cart table (for unregistered users using session)
CREATE TABLE IF NOT EXISTS guest_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_product (session_id, product_id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NULL, -- NULL for guest orders
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('online', 'cod') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    whatsapp_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- WhatsApp messages log
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('order_confirmation', 'inquiry', 'support') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Admin roles and permissions
CREATE TABLE admin_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin roles
INSERT INTO admin_roles (name, description, permissions) VALUES
('Super Admin', 'Full system access', '["all"]'),
('Admin', 'General admin access', '["products", "orders", "customers", "categories", "reports"]'),
('Manager', 'Limited admin access', '["products", "orders", "categories"]'),
('Support', 'Customer support access', '["orders", "customers"]');

-- Update users table to include role_id
ALTER TABLE users ADD COLUMN role_id INT DEFAULT NULL AFTER role;
ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES admin_roles(id);

-- Site settings table
CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'number', 'boolean', 'json', 'file') DEFAULT 'text',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, category, description) VALUES
('site_name', 'Hi5ve MarketPlace', 'text', 'general', 'Website name'),
('site_description', 'Your trusted online grocery marketplace', 'textarea', 'general', 'Website description'),
('site_logo', '', 'file', 'general', 'Website logo'),
('site_favicon', '', 'file', 'general', 'Website favicon'),
('contact_email', 'info@hi5ve.com', 'text', 'contact', 'Contact email address'),
('contact_phone', '+2348123456789', 'text', 'contact', 'Contact phone number'),
('contact_address', 'Lagos, Nigeria', 'textarea', 'contact', 'Business address'),
('whatsapp_number', '+2348123456789', 'text', 'contact', 'WhatsApp business number'),
('currency_symbol', '₦', 'text', 'general', 'Currency symbol'),
('currency_code', 'NGN', 'text', 'general', 'Currency code'),
('delivery_fee', '500', 'number', 'shipping', 'Default delivery fee'),
('free_delivery_threshold', '5000', 'number', 'shipping', 'Minimum order for free delivery'),
('low_stock_threshold', '10', 'number', 'inventory', 'Low stock alert threshold'),
('enable_reviews', '1', 'boolean', 'features', 'Enable product reviews'),
('enable_wishlist', '1', 'boolean', 'features', 'Enable wishlist feature'),
('maintenance_mode', '0', 'boolean', 'general', 'Enable maintenance mode'),
('google_analytics', '', 'textarea', 'tracking', 'Google Analytics code'),
('facebook_pixel', '', 'textarea', 'tracking', 'Facebook Pixel code'),
('social_facebook', '', 'text', 'social', 'Facebook page URL'),
('social_twitter', '', 'text', 'social', 'Twitter profile URL'),
('social_instagram', '', 'text', 'social', 'Instagram profile URL'),
('social_youtube', '', 'text', 'social', 'YouTube channel URL');

-- Content management tables
CREATE TABLE pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert default pages
INSERT INTO pages (title, slug, content, meta_title, meta_description, created_by) VALUES
('About Us', 'about-us', '<h2>About Hi5ve MarketPlace</h2><p>Welcome to Hi5ve MarketPlace, your trusted online grocery destination...</p>', 'About Us - Hi5ve MarketPlace', 'Learn more about Hi5ve MarketPlace and our mission', 1),
('Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>Your privacy is important to us...</p>', 'Privacy Policy - Hi5ve MarketPlace', 'Hi5ve MarketPlace privacy policy and data protection', 1),
('Terms of Service', 'terms-of-service', '<h2>Terms of Service</h2><p>By using our service, you agree to these terms...</p>', 'Terms of Service - Hi5ve MarketPlace', 'Hi5ve MarketPlace terms of service and conditions', 1),
('FAQ', 'faq', '<h2>Frequently Asked Questions</h2><p>Find answers to common questions...</p>', 'FAQ - Hi5ve MarketPlace', 'Frequently asked questions about Hi5ve MarketPlace', 1);

-- Blog/News system
CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(255),
    meta_title VARCHAR(255),
    meta_description TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    author_id INT,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- File uploads table
CREATE TABLE uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    upload_type ENUM('product', 'category', 'blog', 'page', 'setting', 'other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Product images table (for multiple images per product)
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product reviews table
CREATE TABLE product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_review (product_id, user_id)
);

-- Insert default admin user
INSERT INTO users (username, email, password, first_name, last_name, role) 
VALUES ('admin', 'admin@hi5ve.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Fruits & Vegetables', 'Fresh fruits and vegetables'),
('Dairy & Eggs', 'Milk, cheese, eggs and dairy products'),
('Meat & Poultry', 'Fresh meat and poultry'),
('Pantry Staples', 'Rice, beans, flour and cooking essentials'),
('Beverages', 'Soft drinks, juices and water'),
('Snacks', 'Chips, biscuits and snack items')
ON DUPLICATE KEY UPDATE name = name;

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES 
('site_name', 'Hi5ve MarketPlace'),
('whatsapp_number', '+2348123456789'),
('delivery_fee', '500'),
('min_order_amount', '1000')
ON DUPLICATE KEY UPDATE setting_key = setting_key; 