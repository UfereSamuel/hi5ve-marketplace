<?php
/**
 * Production Configuration for Shared Hosting
 * Hi5ve MarketPlace
 */

// Environment detection
define('ENVIRONMENT', 'production');

// Database Configuration (use environment variables or secure config)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'your_database_name');
define('DB_USER', $_ENV['DB_USER'] ?? 'your_db_username');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'your_db_password');

// Security Settings
define('SECURE_SSL', true);
define('FORCE_HTTPS', true);

// File Upload Settings for Shared Hosting
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2MB (shared hosting limits)
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('UPLOAD_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/');

// Error Reporting (disabled in production)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session Security
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Cache Settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour

// Email Configuration (use hosting provider's SMTP)
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'mail.yourdomain.com');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? 'noreply@yourdomain.com');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', 'tls');

// WhatsApp API (production credentials)
define('WHATSAPP_API_URL', $_ENV['WHATSAPP_API_URL'] ?? '');
define('WHATSAPP_API_TOKEN', $_ENV['WHATSAPP_API_TOKEN'] ?? '');

// Site URLs
define('SITE_URL', 'https://yourdomain.com');
define('ADMIN_URL', 'https://yourdomain.com/admin');

// Security Keys (generate unique keys for production)
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-32-character-secret-key-here');
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-key-here');

// Rate Limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100); // requests per hour per IP

// Backup Settings
define('BACKUP_ENABLED', true);
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);
?> 