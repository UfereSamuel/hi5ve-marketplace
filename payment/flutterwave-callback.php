<?php
session_start();
require_once '../config/config.php';
require_once '../classes/PaymentGateway.php';
require_once '../classes/Order.php';

$paymentGateway = new PaymentGateway();
$order = new Order();

// Get payment parameters from URL
$status = $_GET['status'] ?? '';
$tx_ref = $_GET['tx_ref'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';

if (empty($tx_ref) || empty($transaction_id)) {
    header('Location: ../checkout.php?error=invalid_reference');
    exit();
}

try {
    // Only proceed if status is successful
    if ($status === 'successful') {
        // Verify payment with Flutterwave
        $verification_result = $paymentGateway->verifyFlutterwavePayment($transaction_id);
        
        if ($verification_result['success']) {
            // Payment successful
            $payment_data = $verification_result['data'];
            
            // Get payment record from database
            $payment_record = $paymentGateway->getPaymentByReference($tx_ref);
            
            if ($payment_record) {
                // Get order details
                $order_details = $order->getByOrderId($payment_record['order_id']);
                
                if ($order_details) {
                    // Update order status to confirmed
                    $order->updateStatus($order_details['id'], 'confirmed');
                    
                    // Set success message
                    $_SESSION['payment_success'] = [
                        'order_id' => $payment_record['order_id'],
                        'amount' => $payment_data['amount'],
                        'reference' => $tx_ref,
                        'gateway' => 'Flutterwave'
                    ];
                    
                    // Redirect to success page
                    header('Location: ../order-confirmation.php?order_id=' . $payment_record['order_id'] . '&payment=success');
                    exit();
                }
            }
            
            // If we get here, something went wrong with order lookup
            header('Location: ../order-confirmation.php?payment=success&reference=' . $tx_ref);
            exit();
            
        } else {
            // Payment verification failed
            $_SESSION['payment_error'] = [
                'message' => $verification_result['message'] ?? 'Payment verification failed',
                'reference' => $tx_ref
            ];
            
            header('Location: ../checkout.php?error=payment_failed');
            exit();
        }
    } else {
        // Payment was not successful
        $_SESSION['payment_error'] = [
            'message' => 'Payment was cancelled or failed',
            'reference' => $tx_ref
        ];
        
        header('Location: ../checkout.php?error=payment_cancelled');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Flutterwave callback error: " . $e->getMessage());
    
    $_SESSION['payment_error'] = [
        'message' => 'An error occurred while processing your payment',
        'reference' => $tx_ref
    ];
    
    header('Location: ../checkout.php?error=processing_error');
    exit();
}
?> 