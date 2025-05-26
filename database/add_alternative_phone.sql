-- Add alternative phone number field to users table
ALTER TABLE users ADD COLUMN alternative_phone VARCHAR(20) DEFAULT NULL AFTER phone;

-- Update the table comment
ALTER TABLE users COMMENT = 'Users table with profile completion support'; 