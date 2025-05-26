<?php
require_once __DIR__ . '/../config/config.php';

class PaymentGateway {
    private $conn;
    private $paystack_secret_key;
    private $paystack_public_key;
    private $flutterwave_secret_key;
    private $flutterwave_public_key;
    private $test_mode;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Load payment configuration from database or environment
        $this->loadConfiguration();
    }

    private function loadConfiguration() {
        // Try to load from database settings first
        $stmt = $this->conn->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'payment_%'");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Set API keys with fallback to test keys
        $this->test_mode = $settings['payment_test_mode'] ?? true;
        
        if ($this->test_mode) {
            // Test keys - replace with your actual test keys
            $this->paystack_secret_key = $settings['payment_paystack_test_secret'] ?? 'sk_test_your_paystack_test_secret_key';
            $this->paystack_public_key = $settings['payment_paystack_test_public'] ?? 'pk_test_your_paystack_test_public_key';
            $this->flutterwave_secret_key = $settings['payment_flutterwave_test_secret'] ?? 'FLWSECK_TEST-your_flutterwave_test_secret_key';
            $this->flutterwave_public_key = $settings['payment_flutterwave_test_public'] ?? 'FLWPUBK_TEST-your_flutterwave_test_public_key';
        } else {
            // Live keys
            $this->paystack_secret_key = $settings['payment_paystack_live_secret'] ?? '';
            $this->paystack_public_key = $settings['payment_paystack_live_public'] ?? '';
            $this->flutterwave_secret_key = $settings['payment_flutterwave_live_secret'] ?? '';
            $this->flutterwave_public_key = $settings['payment_flutterwave_live_public'] ?? '';
        }
    }

    // Get available payment methods for an amount
    public function getAvailablePaymentMethods($amount) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM payment_methods 
                WHERE is_active = 1 
                AND min_amount <= ? 
                AND max_amount >= ? 
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$amount, $amount]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payment methods: " . $e->getMessage());
            return [];
        }
    }

    // Calculate transaction fee
    public function calculateTransactionFee($amount, $payment_method) {
        try {
            $stmt = $this->conn->prepare("SELECT transaction_fee_type, transaction_fee_value FROM payment_methods WHERE name = ?");
            $stmt->execute([$payment_method]);
            $method = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$method) return 0;

            if ($method['transaction_fee_type'] === 'percentage') {
                return ($amount * $method['transaction_fee_value']) / 100;
            } else {
                return $method['transaction_fee_value'];
            }
        } catch (Exception $e) {
            error_log("Error calculating transaction fee: " . $e->getMessage());
            return 0;
        }
    }

    // Initialize payment with Paystack
    public function initializePaystackPayment($order_id, $amount, $email, $phone = null, $callback_url = null) {
        try {
            $url = "https://api.paystack.co/transaction/initialize";
            $reference = 'hi5ve_' . $order_id . '_' . time() . '_' . rand(1000, 9999);
            
            $fields = [
                'email' => $email,
                'amount' => $amount * 100, // Convert to kobo
                'reference' => $reference,
                'callback_url' => $callback_url ?: SITE_URL . '/payment/paystack-callback.php',
                'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
                'metadata' => [
                    'order_id' => $order_id,
                    'custom_fields' => [
                        [
                            'display_name' => 'Order ID',
                            'variable_name' => 'order_id',
                            'value' => $order_id
                        ]
                    ]
                ]
            ];

            if ($phone) {
                $fields['metadata']['phone'] = $phone;
            }

            $fields_string = json_encode($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->paystack_secret_key,
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response && $response['status']) {
                    // Calculate transaction fee
                    $fee = $this->calculateTransactionFee($amount, 'paystack_card');
                    
                    // Save payment record
                    $this->savePaymentRecord($order_id, 'paystack', $reference, 'pending', $amount, $fee, $email, $phone);
                    
                    return [
                        'success' => true,
                        'authorization_url' => $response['data']['authorization_url'],
                        'access_code' => $response['data']['access_code'],
                        'reference' => $reference
                    ];
                } else {
                    throw new Exception($response['message'] ?? 'Unknown error from Paystack');
                }
            } else {
                throw new Exception("HTTP Error: $httpcode - $result");
            }
        } catch (Exception $e) {
            error_log("Paystack initialization error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Initialize payment with Flutterwave
    public function initializeFlutterwavePayment($order_id, $amount, $email, $phone, $name) {
        try {
            $url = "https://api.flutterwave.com/v3/payments";
            $tx_ref = 'hi5ve_fw_' . $order_id . '_' . time() . '_' . rand(1000, 9999);
            
            $fields = [
                'tx_ref' => $tx_ref,
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => SITE_URL . '/payment/flutterwave-callback.php',
                'payment_options' => 'card,banktransfer,ussd,mobilemoney',
                'customer' => [
                    'email' => $email,
                    'phonenumber' => $phone,
                    'name' => $name
                ],
                'customizations' => [
                    'title' => 'Hi5ve MarketPlace',
                    'description' => 'Payment for Order #' . $order_id,
                    'logo' => SITE_URL . '/assets/images/logo.png'
                ],
                'meta' => [
                    'order_id' => $order_id
                ]
            ];

            $fields_string = json_encode($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->flutterwave_secret_key,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response && $response['status'] === 'success') {
                    // Calculate transaction fee
                    $fee = $this->calculateTransactionFee($amount, 'flutterwave_card');
                    
                    // Save payment record
                    $this->savePaymentRecord($order_id, 'flutterwave', $tx_ref, 'pending', $amount, $fee, $email, $phone);
                    
                    return [
                        'success' => true,
                        'payment_link' => $response['data']['link'],
                        'tx_ref' => $tx_ref
                    ];
                } else {
                    throw new Exception($response['message'] ?? 'Unknown error from Flutterwave');
                }
            } else {
                throw new Exception("HTTP Error: $httpcode - $result");
            }
        } catch (Exception $e) {
            error_log("Flutterwave initialization error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Verify Paystack payment
    public function verifyPaystackPayment($reference) {
        try {
            $url = "https://api.paystack.co/transaction/verify/" . $reference;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->paystack_secret_key,
                "Cache-Control: no-cache"
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response && $response['status'] && $response['data']['status'] === 'success') {
                    // Update payment record
                    $this->updatePaymentStatus($reference, 'completed', $response['data']);
                    
                    // Update order status
                    $this->updateOrderPaymentStatus($reference, 'paid');
                    
                    return [
                        'success' => true,
                        'data' => $response['data']
                    ];
                } else {
                    $this->updatePaymentStatus($reference, 'failed', $response['data'] ?? null);
                    return ['success' => false, 'message' => 'Payment was not successful'];
                }
            } else {
                throw new Exception("HTTP Error: $httpcode - $result");
            }
        } catch (Exception $e) {
            error_log("Paystack verification error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Verify Flutterwave payment
    public function verifyFlutterwavePayment($transaction_id) {
        try {
            $url = "https://api.flutterwave.com/v3/transactions/" . $transaction_id . "/verify";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->flutterwave_secret_key,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response && $response['status'] === 'success' && $response['data']['status'] === 'successful') {
                    // Update payment record
                    $this->updatePaymentStatus($response['data']['tx_ref'], 'completed', $response['data']);
                    
                    // Update order status
                    $this->updateOrderPaymentStatus($response['data']['tx_ref'], 'paid');
                    
                    return [
                        'success' => true,
                        'data' => $response['data']
                    ];
                } else {
                    $this->updatePaymentStatus($response['data']['tx_ref'] ?? '', 'failed', $response['data'] ?? null);
                    return ['success' => false, 'message' => 'Payment was not successful'];
                }
            } else {
                throw new Exception("HTTP Error: $httpcode - $result");
            }
        } catch (Exception $e) {
            error_log("Flutterwave verification error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Save payment record to database
    private function savePaymentRecord($order_id, $gateway, $reference, $status, $amount, $fee = 0, $email = null, $phone = null) {
        try {
            $net_amount = $amount - $fee;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Get user_id from order
            $stmt = $this->conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $order['user_id'] ?? null;

            $stmt = $this->conn->prepare("
                INSERT INTO payments (
                    order_id, user_id, gateway, gateway_reference, amount, status, 
                    transaction_fee, net_amount, customer_email, customer_phone, 
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $order_id, $user_id, $gateway, $reference, $amount, $status,
                $fee, $net_amount, $email, $phone, $ip_address, $user_agent
            ]);
        } catch (Exception $e) {
            error_log("Error saving payment record: " . $e->getMessage());
            return false;
        }
    }

    // Update payment status
    private function updatePaymentStatus($reference, $status, $gateway_response = null) {
        try {
            $sql = "UPDATE payments SET status = ?, updated_at = CURRENT_TIMESTAMP";
            $params = [$status];

            if ($gateway_response) {
                $sql .= ", gateway_response = ?";
                $params[] = json_encode($gateway_response);
            }

            if ($status === 'completed') {
                $sql .= ", verified_at = CURRENT_TIMESTAMP, webhook_verified = 1";
            }

            $sql .= " WHERE gateway_reference = ?";
            $params[] = $reference;

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }

    // Update order payment status
    private function updateOrderPaymentStatus($reference, $payment_status) {
        try {
            // Get order_id from payment
            $stmt = $this->conn->prepare("SELECT order_id FROM payments WHERE gateway_reference = ?");
            $stmt->execute([$reference]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                $stmt = $this->conn->prepare("
                    UPDATE orders 
                    SET payment_status = ?, payment_reference = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE order_id = ?
                ");
                return $stmt->execute([$payment_status, $reference, $payment['order_id']]);
            }
            return false;
        } catch (Exception $e) {
            error_log("Error updating order payment status: " . $e->getMessage());
            return false;
        }
    }

    // Handle webhook from payment gateway
    public function handleWebhook($gateway, $payload, $headers = []) {
        try {
            // Log webhook for debugging
            $this->logWebhook($gateway, $payload, $headers);

            if ($gateway === 'paystack') {
                return $this->handlePaystackWebhook($payload, $headers);
            } elseif ($gateway === 'flutterwave') {
                return $this->handleFlutterwaveWebhook($payload, $headers);
            }

            return false;
        } catch (Exception $e) {
            error_log("Webhook handling error: " . $e->getMessage());
            return false;
        }
    }

    // Handle Paystack webhook
    private function handlePaystackWebhook($payload, $headers) {
        // Verify webhook signature
        $signature = $headers['x-paystack-signature'] ?? '';
        $computed_signature = hash_hmac('sha512', $payload, $this->paystack_secret_key);

        if (!hash_equals($signature, $computed_signature)) {
            error_log("Invalid Paystack webhook signature");
            return false;
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';
        $reference = $data['data']['reference'] ?? '';

        switch ($event) {
            case 'charge.success':
                return $this->verifyPaystackPayment($reference);
            case 'charge.failed':
                $this->updatePaymentStatus($reference, 'failed', $data['data']);
                return true;
            default:
                return true; // Acknowledge other events
        }
    }

    // Handle Flutterwave webhook
    private function handleFlutterwaveWebhook($payload, $headers) {
        // Verify webhook signature
        $signature = $headers['verif-hash'] ?? '';
        $secret_hash = hash('sha256', $this->flutterwave_secret_key);

        if ($signature !== $secret_hash) {
            error_log("Invalid Flutterwave webhook signature");
            return false;
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';
        $tx_ref = $data['data']['tx_ref'] ?? '';

        switch ($event) {
            case 'charge.completed':
                return $this->verifyFlutterwavePayment($data['data']['id']);
            case 'charge.failed':
                $this->updatePaymentStatus($tx_ref, 'failed', $data['data']);
                return true;
            default:
                return true; // Acknowledge other events
        }
    }

    // Log webhook for debugging
    private function logWebhook($gateway, $payload, $headers) {
        try {
            $data = json_decode($payload, true);
            $event_type = '';
            $reference = '';

            if ($gateway === 'paystack') {
                $event_type = $data['event'] ?? '';
                $reference = $data['data']['reference'] ?? '';
            } elseif ($gateway === 'flutterwave') {
                $event_type = $data['event'] ?? '';
                $reference = $data['data']['tx_ref'] ?? '';
            }

            $stmt = $this->conn->prepare("
                INSERT INTO payment_webhooks (gateway, event_type, reference, payload, headers, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $gateway,
                $event_type,
                $reference,
                $payload,
                json_encode($headers),
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error logging webhook: " . $e->getMessage());
        }
    }

    // Get payment by reference
    public function getPaymentByReference($reference) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM payments WHERE gateway_reference = ?");
            $stmt->execute([$reference]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payment by reference: " . $e->getMessage());
            return false;
        }
    }

    // Get order payments
    public function getOrderPayments($order_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting order payments: " . $e->getMessage());
            return [];
        }
    }

    // Get payment statistics
    public function getPaymentStats($period = 'month') {
        try {
            $date_condition = $this->getDateCondition($period);
            
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'completed' THEN transaction_fee ELSE 0 END) as total_fees,
                    AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_transaction,
                    gateway,
                    COUNT(*) as gateway_count
                FROM payments 
                WHERE $date_condition
                GROUP BY gateway
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payment stats: " . $e->getMessage());
            return [];
        }
    }

    // Get date condition for statistics
    private function getDateCondition($period) {
        switch ($period) {
            case 'today':
                return "DATE(created_at) = CURDATE()";
            case 'week':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'month':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'year':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
    }

    // Process WhatsApp payment
    public function processWhatsAppPayment($order_id, $amount, $customer_email, $customer_phone, $customer_name) {
        try {
            $reference = 'hi5ve_whatsapp_' . $order_id . '_' . time();
            
            // Save payment record as pending (awaiting admin confirmation)
            $this->savePaymentRecord($order_id, 'whatsapp', $reference, 'pending', $amount, 0, $customer_email, $customer_phone);
            
            // Generate WhatsApp message for payment
            $message = $this->generateWhatsAppPaymentMessage($order_id, $amount, $customer_name, $reference);
            
            // Update order status to pending payment
            $this->updateOrderPaymentStatus($reference, 'pending');
            
            return [
                'success' => true,
                'reference' => $reference,
                'whatsapp_link' => getWhatsAppLink($message),
                'message' => 'WhatsApp payment initiated. Please complete payment via WhatsApp and admin will confirm.'
            ];
        } catch (Exception $e) {
            error_log("WhatsApp payment processing error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Generate WhatsApp payment message
    private function generateWhatsAppPaymentMessage($order_id, $amount, $customer_name, $reference) {
        $message = "🛒 *Hi5ve MarketPlace - Payment Request*\n\n";
        $message .= "Hello! I would like to complete payment for my order.\n\n";
        $message .= "*Order Details:*\n";
        $message .= "Order ID: #$order_id\n";
        $message .= "Customer: $customer_name\n";
        $message .= "Amount: ₦" . number_format($amount, 2) . "\n";
        $message .= "Payment Reference: $reference\n\n";
        $message .= "*Payment Options:*\n";
        $message .= "• Bank Transfer\n";
        $message .= "• Mobile Money Transfer\n";
        $message .= "• Cash Deposit\n\n";
        $message .= "Please provide your bank details or preferred payment method.\n\n";
        $message .= "After payment, I will send proof of payment for confirmation.";
        
        return $message;
    }

    // Admin confirm WhatsApp payment
    public function confirmWhatsAppPayment($payment_id, $admin_notes = '', $proof_of_payment = null) {
        try {
            // Get payment details
            $stmt = $this->conn->prepare("SELECT * FROM payments WHERE id = ? AND gateway = 'whatsapp'");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
            if ($payment['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Payment already processed'];
            }
            
            // Update payment status to completed
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET status = 'completed', 
                    gateway_response = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $gateway_response = json_encode([
                'admin_confirmed' => true,
                'admin_notes' => $admin_notes,
                'proof_of_payment' => $proof_of_payment,
                'confirmed_at' => date('Y-m-d H:i:s'),
                'confirmed_by' => $_SESSION['user_id'] ?? 'admin'
            ]);
            
            $stmt->execute([$gateway_response, $payment_id]);
            
            // Update order payment status
            $this->updateOrderPaymentStatus($payment['gateway_reference'], 'paid');
            
            // Update order status to confirmed
            $order_stmt = $this->conn->prepare("UPDATE orders SET order_status = 'confirmed' WHERE order_id = ?");
            $order_stmt->execute([$payment['order_id']]);
            
            return [
                'success' => true,
                'message' => 'WhatsApp payment confirmed successfully'
            ];
        } catch (Exception $e) {
            error_log("Error confirming WhatsApp payment: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Reject WhatsApp payment
    public function rejectWhatsAppPayment($payment_id, $reason = '') {
        try {
            // Get payment details
            $stmt = $this->conn->prepare("SELECT * FROM payments WHERE id = ? AND gateway = 'whatsapp'");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
            // Update payment status to failed
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET status = 'failed', 
                    gateway_response = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $gateway_response = json_encode([
                'admin_rejected' => true,
                'rejection_reason' => $reason,
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejected_by' => $_SESSION['user_id'] ?? 'admin'
            ]);
            
            $stmt->execute([$gateway_response, $payment_id]);
            
            // Update order payment status
            $this->updateOrderPaymentStatus($payment['gateway_reference'], 'failed');
            
            return [
                'success' => true,
                'message' => 'WhatsApp payment rejected'
            ];
        } catch (Exception $e) {
            error_log("Error rejecting WhatsApp payment: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get pending WhatsApp payments for admin
    public function getPendingWhatsAppPayments() {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, o.customer_name, o.customer_phone, o.order_id as order_number
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.order_id
                WHERE p.gateway = 'whatsapp' AND p.status = 'pending'
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting pending WhatsApp payments: " . $e->getMessage());
            return [];
        }
    }

    // Process bank transfer
    public function processBankTransfer($order_id, $amount, $account_details) {
        try {
            $reference = 'hi5ve_bank_' . $order_id . '_' . time();
            
            // Save payment record as pending
            $this->savePaymentRecord($order_id, 'manual', $reference, 'pending', $amount, 0);
            
            // You can add logic here to send bank details to customer
            // or create a pending payment that admin can verify manually
            
            return [
                'success' => true,
                'reference' => $reference,
                'message' => 'Bank transfer details sent. Please complete the transfer and upload proof of payment.'
            ];
        } catch (Exception $e) {
            error_log("Bank transfer processing error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Process cash on delivery
    public function processCashOnDelivery($order_id, $amount) {
        try {
            $reference = 'hi5ve_cod_' . $order_id . '_' . time();
            
            // Save payment record as pending
            $this->savePaymentRecord($order_id, 'manual', $reference, 'pending', $amount, 0);
            
            // Update order status
            $this->updateOrderPaymentStatus($reference, 'pending');
            
            return [
                'success' => true,
                'reference' => $reference,
                'message' => 'Order placed successfully. You will pay cash on delivery.'
            ];
        } catch (Exception $e) {
            error_log("COD processing error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Process Paystack refund
    public function processPaystackRefund($reference, $amount, $reason = '') {
        try {
            $url = "https://api.paystack.co/refund";
            
            $fields = [
                'transaction' => $reference,
                'amount' => $amount * 100, // Convert to kobo
                'currency' => 'NGN',
                'customer_note' => $reason ?: 'Refund processed by admin',
                'merchant_note' => 'Admin initiated refund'
            ];
            
            $fields_string = json_encode($fields);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->paystack_secret_key,
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response && $response['status']) {
                    return [
                        'success' => true,
                        'message' => 'Paystack refund initiated successfully',
                        'data' => $response['data']
                    ];
                } else {
                    throw new Exception($response['message'] ?? 'Unknown error from Paystack');
                }
            } else {
                throw new Exception("HTTP Error: $httpcode - $result");
            }
        } catch (Exception $e) {
            error_log("Paystack refund error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Process Flutterwave refund
    public function processFlutterwaveRefund($reference, $amount, $reason = '') {
        try {
            // First, get the transaction ID from Flutterwave using the tx_ref
            $verify_url = "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=" . $reference;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verify_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->flutterwave_secret_key,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $verify_result = curl_exec($ch);
            $verify_response = json_decode($verify_result, true);
            
            if (!$verify_response || $verify_response['status'] !== 'success') {
                curl_close($ch);
                throw new Exception('Could not verify transaction for refund');
            }
            
            $transaction_id = $verify_response['data']['id'];
            
            // Now process the refund
            $refund_url = "https://api.flutterwave.com/v3/transactions/$transaction_id/refund";
            
            $fields = [
                'amount' => $amount,
                'comments' => $reason ?: 'Admin initiated refund'
            ];
            
            $fields_string = json_encode($fields);
            
            curl_setopt($ch, CURLOPT_URL, $refund_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->flutterwave_secret_key,
                "Content-Type: application/json"
            ]);
            
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response && $response['status'] === 'success') {
                    return [
                        'success' => true,
                        'message' => 'Flutterwave refund initiated successfully',
                        'data' => $response['data']
                    ];
                } else {
                    throw new Exception($response['message'] ?? 'Unknown error from Flutterwave');
                }
            } else {
                throw new Exception("HTTP Error: $httpcode - $result");
            }
        } catch (Exception $e) {
            error_log("Flutterwave refund error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get public keys for frontend
    public function getPublicKeys() {
        return [
            'paystack_public_key' => $this->paystack_public_key,
            'flutterwave_public_key' => $this->flutterwave_public_key,
            'test_mode' => $this->test_mode
        ];
    }
}
?> 