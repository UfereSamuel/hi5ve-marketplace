<?php
require_once 'config/config.php';
require_once 'classes/PaymentGateway.php';

echo "<h2>Payment Settings Test</h2>\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    $paymentGateway = new PaymentGateway();
    
    echo "<h3>1. Testing Payment Methods Retrieval</h3>\n";
    
    // Get all payment methods
    $stmt = $conn->prepare("SELECT * FROM payment_methods ORDER BY sort_order ASC");
    $stmt->execute();
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Display Name</th><th>Gateway</th><th>Active</th><th>Min Amount</th><th>Max Amount</th><th>Fee</th></tr>\n";
    
    foreach ($payment_methods as $method) {
        $active_status = $method['is_active'] ? '✅ Yes' : '❌ No';
        $fee_display = $method['transaction_fee_type'] === 'percentage' 
            ? $method['transaction_fee_value'] . '%' 
            : '₦' . number_format($method['transaction_fee_value'], 2);
            
        echo "<tr>";
        echo "<td>{$method['id']}</td>";
        echo "<td>{$method['name']}</td>";
        echo "<td>{$method['display_name']}</td>";
        echo "<td>{$method['gateway']}</td>";
        echo "<td>{$active_status}</td>";
        echo "<td>₦" . number_format($method['min_amount'], 2) . "</td>";
        echo "<td>₦" . number_format($method['max_amount'], 2) . "</td>";
        echo "<td>{$fee_display}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>2. Testing Available Payment Methods for ₦5,000</h3>\n";
    $test_amount = 5000;
    $available_methods = $paymentGateway->getAvailablePaymentMethods($test_amount);
    
    echo "<p><strong>Available methods for ₦" . number_format($test_amount, 2) . ":</strong></p>\n";
    echo "<ul>\n";
    foreach ($available_methods as $method) {
        $fee = $paymentGateway->calculateTransactionFee($test_amount, $method['name']);
        echo "<li>{$method['display_name']} - Fee: ₦" . number_format($fee, 2) . "</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3>3. Testing API Settings</h3>\n";
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'payment_%' OR setting_key IN ('whatsapp_business_number', 'bank_account_name', 'bank_account_number', 'bank_name')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Setting Key</th><th>Value</th><th>Status</th></tr>\n";
    
    $important_settings = [
        'payment_test_mode' => 'Test Mode',
        'payment_paystack_test_public' => 'Paystack Test Public Key',
        'payment_paystack_test_secret' => 'Paystack Test Secret Key',
        'payment_flutterwave_test_public' => 'Flutterwave Test Public Key',
        'payment_flutterwave_test_secret' => 'Flutterwave Test Secret Key',
        'whatsapp_business_number' => 'WhatsApp Business Number',
        'bank_account_name' => 'Bank Account Name',
        'bank_account_number' => 'Bank Account Number',
        'bank_name' => 'Bank Name'
    ];
    
    foreach ($important_settings as $key => $label) {
        $value = $settings[$key] ?? 'Not Set';
        $status = !empty($settings[$key]) ? '✅ Configured' : '❌ Not Set';
        
        // Mask sensitive data
        if (strpos($key, 'secret') !== false && !empty($settings[$key])) {
            $value = substr($settings[$key], 0, 10) . '...';
        }
        
        echo "<tr>";
        echo "<td>{$label}</td>";
        echo "<td>{$value}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>4. Testing WhatsApp Payment Processing</h3>\n";
    
    // Test WhatsApp payment processing
    $test_order_id = 'TEST_' . time();
    $test_amount = 2500;
    $test_email = 'test@example.com';
    $test_phone = '+2348123456789';
    $test_name = 'Test Customer';
    
    echo "<p><strong>Testing WhatsApp payment for:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Order ID: {$test_order_id}</li>\n";
    echo "<li>Amount: ₦" . number_format($test_amount, 2) . "</li>\n";
    echo "<li>Customer: {$test_name}</li>\n";
    echo "<li>Email: {$test_email}</li>\n";
    echo "<li>Phone: {$test_phone}</li>\n";
    echo "</ul>\n";
    
    // Note: We won't actually process the payment in the test
    echo "<p><em>Note: WhatsApp payment processing test skipped to avoid creating test records.</em></p>\n";
    
    echo "<h3>5. Payment Gateway Status Summary</h3>\n";
    
    $active_methods = array_filter($payment_methods, function($method) {
        return $method['is_active'];
    });
    
    $gateway_counts = [];
    foreach ($active_methods as $method) {
        $gateway_counts[$method['gateway']] = ($gateway_counts[$method['gateway']] ?? 0) + 1;
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Gateway</th><th>Active Methods</th><th>Status</th></tr>\n";
    
    $gateway_status = [
        'paystack' => !empty($settings['payment_paystack_test_public']) ? '✅ Configured' : '❌ Not Configured',
        'flutterwave' => !empty($settings['payment_flutterwave_test_public']) ? '✅ Configured' : '❌ Not Configured',
        'whatsapp' => !empty($settings['whatsapp_business_number']) ? '✅ Configured' : '❌ Not Configured',
        'manual' => '✅ Always Available'
    ];
    
    foreach ($gateway_status as $gateway => $status) {
        $count = $gateway_counts[$gateway] ?? 0;
        echo "<tr>";
        echo "<td>" . ucfirst($gateway) . "</td>";
        echo "<td>{$count}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>✅ Payment Settings Test Completed Successfully!</h3>\n";
    echo "<p><strong>Summary:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Total Payment Methods: " . count($payment_methods) . "</li>\n";
    echo "<li>Active Payment Methods: " . count($active_methods) . "</li>\n";
    echo "<li>Test Mode: " . (($settings['payment_test_mode'] ?? 1) ? 'Enabled' : 'Disabled') . "</li>\n";
    echo "<li>Available for ₦5,000: " . count($available_methods) . " methods</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Recommended Actions:</strong></p>\n";
    echo "<ul>\n";
    
    if (empty($settings['payment_paystack_test_public'])) {
        echo "<li>❌ Configure Paystack API keys in Payment Settings</li>\n";
    } else {
        echo "<li>✅ Paystack is configured</li>\n";
    }
    
    if (empty($settings['payment_flutterwave_test_public'])) {
        echo "<li>❌ Configure Flutterwave API keys in Payment Settings</li>\n";
    } else {
        echo "<li>✅ Flutterwave is configured</li>\n";
    }
    
    if (empty($settings['whatsapp_business_number'])) {
        echo "<li>❌ Set WhatsApp business number in Payment Settings</li>\n";
    } else {
        echo "<li>✅ WhatsApp is configured</li>\n";
    }
    
    if (empty($settings['bank_account_number'])) {
        echo "<li>❌ Set bank account details for manual transfers</li>\n";
    } else {
        echo "<li>✅ Bank details are configured</li>\n";
    }
    
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Error during testing:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Stack trace:</strong></p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?> 