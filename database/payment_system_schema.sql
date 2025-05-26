-- Payment System Database Schema for Hi5ve MarketPlace
-- Supports Paystack, Flutterwave, and other payment methods

-- Main payments table for transaction tracking
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    user_id INT,
    gateway ENUM('paystack', 'flutterwave', 'whatsapp', 'manual') NOT NULL,
    gateway_reference VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NGN',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    gateway_response JSON,
    transaction_fee DECIMAL(10,2) DEFAULT 0.00,
    net_amount DECIMAL(10,2) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    payment_method VARCHAR(50), -- card, bank_transfer, ussd, etc.
    authorization_code VARCHAR(255), -- For recurring payments
    ip_address VARCHAR(45),
    user_agent TEXT,
    webhook_verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_gateway_reference (gateway_reference),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    UNIQUE KEY unique_gateway_ref (gateway, gateway_reference),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Payment methods configuration
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    gateway ENUM('paystack', 'flutterwave', 'whatsapp', 'manual') NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    min_amount DECIMAL(10,2) DEFAULT 0.00,
    max_amount DECIMAL(10,2) DEFAULT 999999.99,
    transaction_fee_type ENUM('fixed', 'percentage') DEFAULT 'percentage',
    transaction_fee_value DECIMAL(5,2) DEFAULT 0.00,
    settings JSON, -- Gateway-specific settings
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_gateway (gateway),
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order)
);

-- Payment webhooks log for debugging
CREATE TABLE IF NOT EXISTS payment_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway ENUM('paystack', 'flutterwave') NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    reference VARCHAR(255),
    payload JSON NOT NULL,
    headers JSON,
    ip_address VARCHAR(45),
    processed BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_gateway (gateway),
    INDEX idx_reference (reference),
    INDEX idx_processed (processed),
    INDEX idx_created_at (created_at)
);

-- Refunds tracking
CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    refund_reference VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    gateway_response JSON,
    processed_by INT, -- Admin user ID
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Saved payment methods for customers (cards, bank accounts)
CREATE TABLE IF NOT EXISTS customer_payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gateway ENUM('paystack', 'flutterwave') NOT NULL,
    authorization_code VARCHAR(255) NOT NULL,
    card_type VARCHAR(20), -- visa, mastercard, verve
    last_four VARCHAR(4),
    exp_month VARCHAR(2),
    exp_year VARCHAR(4),
    bank VARCHAR(100),
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_gateway (gateway),
    INDEX idx_authorization_code (authorization_code),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert payment methods
INSERT INTO payment_methods (name, display_name, description, gateway, is_active, transaction_fee_type, transaction_fee_value, min_amount, max_amount, sort_order) VALUES
('paystack_card', 'Paystack - Card Payment', 'Pay with your debit/credit card via Paystack', 'paystack', 1, 'percentage', 1.5, 100, 1000000, 1),
('paystack_bank', 'Paystack - Bank Transfer', 'Pay via bank transfer through Paystack', 'paystack', 1, 'fixed', 50, 100, 1000000, 2),
('paystack_ussd', 'Paystack - USSD', 'Pay using USSD code via Paystack', 'paystack', 1, 'fixed', 50, 100, 50000, 3),
('flutterwave_card', 'Flutterwave - Card Payment', 'Pay with your debit/credit card via Flutterwave', 'flutterwave', 1, 'percentage', 1.4, 100, 1000000, 4),
('flutterwave_bank', 'Flutterwave - Bank Transfer', 'Pay via bank transfer through Flutterwave', 'flutterwave', 1, 'fixed', 50, 100, 1000000, 5),
('whatsapp_payment', 'WhatsApp Payment', 'Pay via WhatsApp transfer - Admin will confirm payment', 'whatsapp', 1, 'fixed', 0, 100, 1000000, 6),
('bank_transfer', 'Direct Bank Transfer', 'Transfer directly to our bank account', 'manual', 1, 'fixed', 0, 100, 1000000, 7),
('cod', 'Cash on Delivery', 'Pay with cash when your order is delivered', 'manual', 1, 'fixed', 0, 100, 100000, 8);

-- Add payment_reference to orders table if not exists
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(255) AFTER payment_status;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS gateway_fee DECIMAL(10,2) DEFAULT 0.00 AFTER payment_reference;

-- Add indexes for better performance
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_payment_reference (payment_reference);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_payment_method (payment_method); 