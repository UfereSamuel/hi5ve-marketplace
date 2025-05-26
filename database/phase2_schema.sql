-- Hi5ve MarketPlace Phase 2 Database Schema Updates
-- Run this after Phase 1 is complete

-- Payment system tables
CREATE TABLE IF NOT EXISTS `payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `gateway` enum('paystack','flutterwave','bank_transfer','ussd','cash_on_delivery','wallet') NOT NULL,
    `reference` varchar(255) NOT NULL UNIQUE,
    `status` enum('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
    `amount` decimal(10,2) NOT NULL,
    `gateway_response` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `reference` (`reference`),
    KEY `status` (`status`),
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bank transfer details
CREATE TABLE IF NOT EXISTS `bank_transfers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `payment_reference` varchar(255) NOT NULL,
    `account_name` varchar(255) NOT NULL,
    `account_number` varchar(50) NOT NULL,
    `bank_name` varchar(255) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `proof_of_payment` varchar(255),
    `verified_at` timestamp NULL,
    `verified_by` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `payment_reference` (`payment_reference`),
    FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer wallet system
CREATE TABLE IF NOT EXISTS `customer_wallets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL UNIQUE,
    `balance` decimal(10,2) DEFAULT 0.00,
    `total_credited` decimal(10,2) DEFAULT 0.00,
    `total_debited` decimal(10,2) DEFAULT 0.00,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wallet transactions
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `wallet_id` int(11) NOT NULL,
    `type` enum('credit','debit') NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `description` varchar(255) NOT NULL,
    `reference` varchar(255),
    `order_id` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `wallet_id` (`wallet_id`),
    KEY `order_id` (`order_id`),
    FOREIGN KEY (`wallet_id`) REFERENCES `customer_wallets` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory management
CREATE TABLE IF NOT EXISTS `inventory_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `type` enum('stock_in','stock_out','adjustment','sale','return') NOT NULL,
    `quantity` int(11) NOT NULL,
    `previous_stock` int(11) NOT NULL,
    `new_stock` int(11) NOT NULL,
    `reason` varchar(255),
    `reference` varchar(255),
    `created_by` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `type` (`type`),
    KEY `created_by` (`created_by`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock alerts
CREATE TABLE IF NOT EXISTS `stock_alerts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `alert_type` enum('low_stock','out_of_stock','overstock') NOT NULL,
    `threshold` int(11),
    `current_stock` int(11) NOT NULL,
    `status` enum('active','resolved','dismissed') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` timestamp NULL,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `status` (`status`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suppliers
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `contact_person` varchar(255),
    `email` varchar(255),
    `phone` varchar(20),
    `address` text,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase orders
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `supplier_id` int(11) NOT NULL,
    `po_number` varchar(50) NOT NULL UNIQUE,
    `status` enum('draft','sent','confirmed','received','cancelled') DEFAULT 'draft',
    `total_amount` decimal(10,2) DEFAULT 0.00,
    `expected_delivery` date,
    `notes` text,
    `created_by` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `supplier_id` (`supplier_id`),
    KEY `created_by` (`created_by`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase order items
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `po_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `unit_cost` decimal(10,2) NOT NULL,
    `total_cost` decimal(10,2) NOT NULL,
    `received_quantity` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `po_id` (`po_id`),
    KEY `product_id` (`product_id`),
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email marketing system
CREATE TABLE IF NOT EXISTS `email_subscribers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL UNIQUE,
    `first_name` varchar(100),
    `last_name` varchar(100),
    `status` enum('active','unsubscribed','bounced') DEFAULT 'active',
    `source` varchar(100),
    `subscribed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `unsubscribed_at` timestamp NULL,
    PRIMARY KEY (`id`),
    KEY `email` (`email`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email campaigns
CREATE TABLE IF NOT EXISTS `email_campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `content` longtext NOT NULL,
    `template` varchar(100),
    `status` enum('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
    `scheduled_at` timestamp NULL,
    `sent_at` timestamp NULL,
    `total_recipients` int(11) DEFAULT 0,
    `total_sent` int(11) DEFAULT 0,
    `total_opened` int(11) DEFAULT 0,
    `total_clicked` int(11) DEFAULT 0,
    `created_by` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `created_by` (`created_by`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email logs
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11),
    `subscriber_id` int(11),
    `email` varchar(255) NOT NULL,
    `status` enum('sent','delivered','opened','clicked','bounced','failed') NOT NULL,
    `opened_at` timestamp NULL,
    `clicked_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `campaign_id` (`campaign_id`),
    KEY `subscriber_id` (`subscriber_id`),
    KEY `email` (`email`),
    KEY `status` (`status`),
    FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subscriber_id`) REFERENCES `email_subscribers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotions and coupons
CREATE TABLE IF NOT EXISTS `promotions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `type` enum('percentage','fixed_amount','buy_x_get_y','free_shipping') NOT NULL,
    `value` decimal(10,2) NOT NULL,
    `minimum_amount` decimal(10,2) DEFAULT 0.00,
    `maximum_discount` decimal(10,2),
    `usage_limit` int(11),
    `usage_count` int(11) DEFAULT 0,
    `start_date` timestamp NOT NULL,
    `end_date` timestamp NOT NULL,
    `status` enum('active','inactive','expired') DEFAULT 'active',
    `created_by` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `start_date` (`start_date`),
    KEY `end_date` (`end_date`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupon codes
CREATE TABLE IF NOT EXISTS `coupon_codes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `promotion_id` int(11) NOT NULL,
    `code` varchar(50) NOT NULL UNIQUE,
    `usage_limit` int(11),
    `usage_count` int(11) DEFAULT 0,
    `status` enum('active','inactive','expired') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `promotion_id` (`promotion_id`),
    KEY `code` (`code`),
    KEY `status` (`status`),
    FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotion usage tracking
CREATE TABLE IF NOT EXISTS `promotion_usage` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `promotion_id` int(11) NOT NULL,
    `coupon_code_id` int(11),
    `order_id` int(11) NOT NULL,
    `user_id` int(11),
    `discount_amount` decimal(10,2) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `promotion_id` (`promotion_id`),
    KEY `coupon_code_id` (`coupon_code_id`),
    KEY `order_id` (`order_id`),
    KEY `user_id` (`user_id`),
    FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`coupon_code_id`) REFERENCES `coupon_codes` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer support system
CREATE TABLE IF NOT EXISTS `support_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support tickets
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_number` varchar(50) NOT NULL UNIQUE,
    `user_id` int(11),
    `category_id` int(11),
    `subject` varchar(255) NOT NULL,
    `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
    `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
    `assigned_to` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` timestamp NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `category_id` (`category_id`),
    KEY `assigned_to` (`assigned_to`),
    KEY `status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `support_categories` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket messages
CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_id` int(11) NOT NULL,
    `user_id` int(11),
    `message` text NOT NULL,
    `is_internal` boolean DEFAULT FALSE,
    `attachments` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_id` (`ticket_id`),
    KEY `user_id` (`user_id`),
    FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shipping zones and rates
CREATE TABLE IF NOT EXISTS `shipping_zones` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `states` text NOT NULL,
    `base_rate` decimal(10,2) NOT NULL,
    `per_kg_rate` decimal(10,2) DEFAULT 0.00,
    `free_shipping_threshold` decimal(10,2),
    `estimated_days` varchar(50),
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Delivery partners
CREATE TABLE IF NOT EXISTS `delivery_partners` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `contact_person` varchar(255),
    `email` varchar(255),
    `phone` varchar(20),
    `api_endpoint` varchar(255),
    `api_key` varchar(255),
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracking information
CREATE TABLE IF NOT EXISTS `tracking_info` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `delivery_partner_id` int(11),
    `tracking_number` varchar(255),
    `status` varchar(100),
    `location` varchar(255),
    `estimated_delivery` timestamp NULL,
    `delivered_at` timestamp NULL,
    `notes` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `delivery_partner_id` (`delivery_partner_id`),
    KEY `tracking_number` (`tracking_number`),
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`delivery_partner_id`) REFERENCES `delivery_partners` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Search analytics
CREATE TABLE IF NOT EXISTS `search_analytics` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `search_term` varchar(255) NOT NULL,
    `results_count` int(11) NOT NULL,
    `user_id` int(11),
    `ip_address` varchar(45),
    `user_agent` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `search_term` (`search_term`),
    KEY `user_id` (`user_id`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product views tracking
CREATE TABLE IF NOT EXISTS `product_views` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `user_id` int(11),
    `ip_address` varchar(45),
    `user_agent` text,
    `referrer` varchar(255),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `user_id` (`user_id`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wishlist
CREATE TABLE IF NOT EXISTS `wishlists` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_product` (`user_id`, `product_id`),
    KEY `user_id` (`user_id`),
    KEY `product_id` (`product_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Loyalty program
CREATE TABLE IF NOT EXISTS `loyalty_points` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `points` int(11) NOT NULL,
    `type` enum('earned','redeemed','expired','bonus') NOT NULL,
    `description` varchar(255) NOT NULL,
    `order_id` int(11),
    `expires_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `order_id` (`order_id`),
    KEY `expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default support categories
INSERT INTO `support_categories` (`name`, `description`) VALUES
('General Inquiry', 'General questions and information'),
('Order Issues', 'Problems with orders, delivery, or products'),
('Payment Issues', 'Payment and billing related questions'),
('Technical Support', 'Website or app technical issues'),
('Returns & Refunds', 'Return and refund requests'),
('Account Issues', 'Account access and profile problems');

-- Insert default shipping zones for Nigeria
INSERT INTO `shipping_zones` (`name`, `states`, `base_rate`, `per_kg_rate`, `free_shipping_threshold`, `estimated_days`) VALUES
('Lagos Zone', 'Lagos', 500.00, 100.00, 10000.00, '1-2 days'),
('South West', 'Ogun,Oyo,Osun,Ondo,Ekiti', 800.00, 150.00, 15000.00, '2-3 days'),
('South East', 'Abia,Anambra,Ebonyi,Enugu,Imo', 1000.00, 200.00, 15000.00, '3-4 days'),
('South South', 'Akwa Ibom,Bayelsa,Cross River,Delta,Edo,Rivers', 1200.00, 250.00, 20000.00, '3-5 days'),
('North Central', 'Benue,FCT,Kogi,Kwara,Nasarawa,Niger,Plateau', 1500.00, 300.00, 25000.00, '4-6 days'),
('North East', 'Adamawa,Bauchi,Borno,Gombe,Taraba,Yobe', 2000.00, 400.00, 30000.00, '5-7 days'),
('North West', 'Jigawa,Kaduna,Kano,Katsina,Kebbi,Sokoto,Zamfara', 1800.00, 350.00, 25000.00, '4-6 days');

-- Add indexes for better performance
CREATE INDEX idx_payments_created_at ON payments(created_at);
CREATE INDEX idx_inventory_logs_created_at ON inventory_logs(created_at);
CREATE INDEX idx_email_logs_created_at ON email_logs(created_at);
CREATE INDEX idx_support_tickets_created_at ON support_tickets(created_at);
CREATE INDEX idx_product_views_created_at ON product_views(created_at);
CREATE INDEX idx_search_analytics_created_at ON search_analytics(created_at); 