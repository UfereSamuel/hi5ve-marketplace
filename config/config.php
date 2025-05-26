<?php
// Site Configuration
define('SITE_NAME', 'Hi5ve MarketPlace');
define('SITE_URL', 'http://localhost/mart3');
define('CURRENCY', '₦');
define('CURRENCY_CODE', 'NGN');

// WhatsApp Configuration
define('WHATSAPP_NUMBER', '+2348123456789'); // Replace with your business WhatsApp number
define('WHATSAPP_API_URL', 'https://wa.me/');

// Payment Configuration
define('PAYMENT_METHODS', [
    'online' => 'Online Payment',
    'cod' => 'Cash on Delivery'
]);

// User Roles
define('USER_ROLES', [
    'admin' => 'Administrator',
    'customer' => 'Customer',
    'guest' => 'Guest'
]);

// Session Configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/database.php';

// Utility Functions
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateOrderId() {
    return 'HI5-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function redirectTo($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getWhatsAppLink($message, $phone = null) {
    $phone = $phone ?: WHATSAPP_NUMBER;
    return WHATSAPP_API_URL . $phone . '?text=' . urlencode($message);
}
?> 