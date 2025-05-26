<?php
require_once '../config/config.php';
require_once '../classes/PaymentGateway.php';

// Get the raw POST data
$input = file_get_contents('php://input');
$headers = getallheaders();

// Log the webhook for debugging
error_log("Flutterwave Webhook Received: " . $input);

try {
    $paymentGateway = new PaymentGateway();
    
    // Handle the webhook
    $result = $paymentGateway->handleWebhook('flutterwave', $input, $headers);
    
    if ($result) {
        // Respond with 200 OK
        http_response_code(200);
        echo "OK";
    } else {
        // Respond with 400 Bad Request
        http_response_code(400);
        echo "Bad Request";
    }
    
} catch (Exception $e) {
    error_log("Flutterwave webhook error: " . $e->getMessage());
    http_response_code(500);
    echo "Internal Server Error";
}
?> 