-- Update Payment Gateway ENUM Values
-- This script updates existing payment tables to use the new gateway values

-- Update payments table gateway ENUM
ALTER TABLE payments MODIFY COLUMN gateway ENUM('paystack', 'flutterwave', 'whatsapp', 'manual') NOT NULL;

-- Update payment_methods table gateway ENUM  
ALTER TABLE payment_methods MODIFY COLUMN gateway ENUM('paystack', 'flutterwave', 'whatsapp', 'manual') NOT NULL;

-- Update existing payment method records to use new gateway values
UPDATE payment_methods SET gateway = 'whatsapp' WHERE name = 'whatsapp_payment';
UPDATE payment_methods SET gateway = 'manual' WHERE name IN ('bank_transfer', 'cod');

-- Update existing payment records to use new gateway values (if any exist)
UPDATE payments SET gateway = 'whatsapp' WHERE gateway = 'whatsapp_payment';
UPDATE payments SET gateway = 'manual' WHERE gateway IN ('bank_transfer', 'cod');

-- Ensure all payment methods are properly configured
UPDATE payment_methods SET 
    is_active = 1,
    sort_order = CASE 
        WHEN name = 'whatsapp_payment' THEN 6
        WHEN name = 'bank_transfer' THEN 7
        WHEN name = 'cod' THEN 8
        ELSE sort_order
    END
WHERE name IN ('whatsapp_payment', 'bank_transfer', 'cod'); 