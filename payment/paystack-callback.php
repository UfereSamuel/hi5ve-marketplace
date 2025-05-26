<?php
session_start();
require_once '../config/config.php';
require_once '../classes/PaymentGateway.php';
require_once '../classes/Order.php';

$paymentGateway = new PaymentGateway();
$order = new Order();

// Get payment reference from URL
$reference = $_GET['reference'] ?? '';
$trxref = $_GET['trxref'] ?? '';

// Use reference or trxref
$payment_reference = $reference ?: $trxref;

if (empty($payment_reference)) {
    header('Location: ../checkout.php?error=invalid_reference');
    exit();
}

try {
    // Verify payment with Paystack
    $verification_result = $paymentGateway->verifyPaystackPayment($payment_reference);
    
    if ($verification_result['success']) {
        // Payment successful
        $payment_data = $verification_result['data'];
        
        // Get payment record from database
        $payment_record = $paymentGateway->getPaymentByReference($payment_reference);
        
        if ($payment_record) {
            // Get order details
            $order_details = $order->getByOrderId($payment_record['order_id']);
            
            if ($order_details) {
                // Update order status to confirmed
                $order->updateStatus($order_details['id'], 'confirmed');
                
                // Set success message
                $_SESSION['payment_success'] = [
                    'order_id' => $payment_record['order_id'],
                    'amount' => $payment_data['amount'] / 100, // Convert from kobo
                    'reference' => $payment_reference,
                    'gateway' => 'Paystack'
                ];
                
                // Redirect to success page
                header('Location: ../order-confirmation.php?order_id=' . $payment_record['order_id'] . '&payment=success');
                exit();
            }
        }
        
        // If we get here, something went wrong with order lookup
        header('Location: ../order-confirmation.php?payment=success&reference=' . $payment_reference);
        exit();
        
    } else {
        // Payment failed
        $_SESSION['payment_error'] = [
            'message' => $verification_result['message'] ?? 'Payment verification failed',
            'reference' => $payment_reference
        ];
        
        header('Location: ../checkout.php?error=payment_failed');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Paystack callback error: " . $e->getMessage());
    
    $_SESSION['payment_error'] = [
        'message' => 'An error occurred while processing your payment',
        'reference' => $payment_reference
    ];
    
    header('Location: ../checkout.php?error=processing_error');
    exit();
}
?> 