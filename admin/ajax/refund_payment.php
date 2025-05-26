<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/PaymentGateway.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$payment_id = intval($_POST['payment_id'] ?? 0);

if (!$payment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
    exit;
}

$paymentGateway = new PaymentGateway();

try {
    switch ($action) {
        case 'confirm_whatsapp':
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            $result = $paymentGateway->confirmWhatsAppPayment($payment_id, $admin_notes);
            echo json_encode($result);
            break;
            
        case 'reject_whatsapp':
            $rejection_reason = trim($_POST['rejection_reason'] ?? 'Payment rejected by admin');
            $result = $paymentGateway->rejectWhatsAppPayment($payment_id, $rejection_reason);
            echo json_encode($result);
            break;
            
        case 'refund':
            // Get payment details using the database connection from config
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }

            if ($payment['status'] !== 'completed') {
                echo json_encode(['success' => false, 'message' => 'Only completed payments can be refunded']);
                exit;
            }

            $refund_amount = floatval($_POST['refund_amount'] ?? $payment['amount']);
            $refund_reason = trim($_POST['refund_reason'] ?? '');

            if ($refund_amount <= 0 || $refund_amount > $payment['amount']) {
                echo json_encode(['success' => false, 'message' => 'Invalid refund amount']);
                exit;
            }

            // Process refund based on gateway
            if ($payment['gateway'] === 'paystack') {
                $result = $paymentGateway->processPaystackRefund($payment['gateway_reference'], $refund_amount, $refund_reason);
            } elseif ($payment['gateway'] === 'flutterwave') {
                $result = $paymentGateway->processFlutterwaveRefund($payment['gateway_reference'], $refund_amount, $refund_reason);
            } else {
                echo json_encode(['success' => false, 'message' => 'Refunds not supported for this payment method']);
                exit;
            }

            if ($result['success']) {
                // Log refund in database
                $stmt = $conn->prepare("
                    INSERT INTO payment_refunds (payment_id, refund_amount, refund_reason, gateway_response, status, created_by) 
                    VALUES (?, ?, ?, ?, 'completed', ?)
                ");
                $stmt->execute([
                    $payment_id,
                    $refund_amount,
                    $refund_reason,
                    json_encode($result['data'] ?? []),
                    $_SESSION['user_id']
                ]);

                // Update payment status if fully refunded
                if ($refund_amount >= $payment['amount']) {
                    $stmt = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
                    $stmt->execute([$payment_id]);
                }
            }

            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Payment operation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request']);
}
?> 