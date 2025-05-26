<?php
require_once 'config/config.php';

echo "<h2>Setting up Initial Payment Methods</h2>\n";
echo "<p>Configuring payment methods as requested: WhatsApp Payment and Cash on Delivery only</p>\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Disable Paystack and Flutterwave methods
    $stmt = $conn->prepare("UPDATE payment_methods SET is_active = 0 WHERE gateway IN ('paystack', 'flutterwave')");
    $stmt->execute();
    $disabled_count = $stmt->rowCount();
    
    echo "<p>✅ Disabled {$disabled_count} Paystack and Flutterwave payment methods</p>\n";
    
    // Enable WhatsApp and Cash on Delivery
    $stmt = $conn->prepare("UPDATE payment_methods SET is_active = 1 WHERE name IN ('whatsapp_payment', 'cod')");
    $stmt->execute();
    $enabled_count = $stmt->rowCount();
    
    echo "<p>✅ Enabled {$enabled_count} payment methods (WhatsApp and Cash on Delivery)</p>\n";
    
    // Optionally disable bank transfer as well (you can enable it later if needed)
    $stmt = $conn->prepare("UPDATE payment_methods SET is_active = 0 WHERE name = 'bank_transfer'");
    $stmt->execute();
    
    echo "<p>✅ Disabled direct bank transfer (can be re-enabled later)</p>\n";
    
    // Set WhatsApp business number and basic bank details
    $settings = [
        'whatsapp_business_number' => '+2348123456789', // Replace with your actual WhatsApp number
        'bank_account_name' => 'Hi5ve MarketPlace',
        'bank_account_number' => '1234567890', // Replace with your actual account number
        'bank_name' => 'First Bank Nigeria' // Replace with your actual bank
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$key, $value]);
    }
    
    echo "<p>✅ Configured basic WhatsApp and bank settings</p>\n";
    
    // Show current active payment methods
    echo "<h3>Current Active Payment Methods:</h3>\n";
    $stmt = $conn->prepare("SELECT name, display_name, gateway FROM payment_methods WHERE is_active = 1 ORDER BY sort_order");
    $stmt->execute();
    $active_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>\n";
    foreach ($active_methods as $method) {
        echo "<li><strong>{$method['display_name']}</strong> ({$method['gateway']})</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3>✅ Setup Complete!</h3>\n";
    echo "<p><strong>What's been configured:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ WhatsApp Payment - Enabled (customers can pay via WhatsApp)</li>\n";
    echo "<li>✅ Cash on Delivery - Enabled (customers can pay on delivery)</li>\n";
    echo "<li>❌ Paystack methods - Disabled (can be enabled when you get API keys)</li>\n";
    echo "<li>❌ Flutterwave methods - Disabled (can be enabled when you get API keys)</li>\n";
    echo "<li>❌ Direct Bank Transfer - Disabled (can be enabled if needed)</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Update the WhatsApp business number in admin/payment-settings.php</li>\n";
    echo "<li>Update bank account details if you want to enable bank transfers</li>\n";
    echo "<li>When ready, get Paystack/Flutterwave API keys and enable those methods</li>\n";
    echo "<li>Test the checkout process with the enabled payment methods</li>\n";
    echo "</ol>\n";
    
    echo "<p><strong>Admin Access:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Go to <a href='admin/payment-settings.php'>admin/payment-settings.php</a> to manage payment methods</li>\n";
    echo "<li>Go to <a href='admin/payments.php'>admin/payments.php</a> to view and manage payments</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Error during setup:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?> 