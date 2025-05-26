<?php
require_once __DIR__ . '/../config/config.php';

class PaymentGateway {
    private $conn;
    private $paystack_secret_key;
    private $flutterwave_secret_key;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Set API keys (these should be in environment variables in production)
        $this->paystack_secret_key = 'sk_test_your_paystack_secret_key'; // Replace with actual key
        $this->flutterwave_secret_key = 'FLWSECK_TEST-your_flutterwave_secret_key'; // Replace with actual key
    }

    // Initialize payment with Paystack
    public function initializePaystackPayment($order_id, $amount, $email, $callback_url = null) {
        try {
            $url = "https://api.paystack.co/transaction/initialize";
            
            $fields = [
                'email' => $email,
                'amount' => $amount * 100, // Convert to kobo
                'reference' => 'hi5ve_' . $order_id . '_' . time(),
                'callback_url' => $callback_url ?: SITE_URL . '/payment/callback.php',
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

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response['status']) {
                    // Save payment record
                    $this->savePaymentRecord($order_id, 'paystack', $response['data']['reference'], 'pending', $amount);
                    
                    return [
                        'success' => true,
                        'authorization_url' => $response['data']['authorization_url'],
                        'access_code' => $response['data']['access_code'],
                        'reference' => $response['data']['reference']
                    ];
                }
            }

            return ['success' => false, 'message' => 'Failed to initialize payment'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Initialize payment with Flutterwave
    public function initializeFlutterwavePayment($order_id, $amount, $email, $phone, $name) {
        try {
            $url = "https://api.flutterwave.com/v3/payments";
            
            $fields = [
                'tx_ref' => 'hi5ve_' . $order_id . '_' . time(),
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => SITE_URL . '/payment/flutterwave-callback.php',
                'payment_options' => 'card,banktransfer,ussd',
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

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response['status'] === 'success') {
                    // Save payment record
                    $this->savePaymentRecord($order_id, 'flutterwave', $response['data']['tx_ref'], 'pending', $amount);
                    
                    return [
                        'success' => true,
                        'payment_link' => $response['data']['link'],
                        'tx_ref' => $response['data']['tx_ref']
                    ];
                }
            }

            return ['success' => false, 'message' => 'Failed to initialize payment'];
        } catch (Exception $e) {
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

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response['status'] && $response['data']['status'] === 'success') {
                    // Update payment record
                    $this->updatePaymentStatus($reference, 'completed', $response['data']);
                    
                    return [
                        'success' => true,
                        'data' => $response['data']
                    ];
                }
            }

            return ['success' => false, 'message' => 'Payment verification failed'];
        } catch (Exception $e) {
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

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response['status'] === 'success' && $response['data']['status'] === 'successful') {
                    // Update payment record
                    $this->updatePaymentStatus($response['data']['tx_ref'], 'completed', $response['data']);
                    
                    return [
                        'success' => true,
                        'data' => $response['data']
                    ];
                }
            }

            return ['success' => false, 'message' => 'Payment verification failed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Save payment record to database
    private function savePaymentRecord($order_id, $gateway, $reference, $status, $amount) {
        try {
            $query = "INSERT INTO payments (order_id, gateway, reference, status, amount, created_at) 
                     VALUES (:order_id, :gateway, :reference, :status, :amount, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':gateway', $gateway);
            $stmt->bindParam(':reference', $reference);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':amount', $amount);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update payment status
    private function updatePaymentStatus($reference, $status, $gateway_response = null) {
        try {
            $query = "UPDATE payments 
                     SET status = :status, 
                         gateway_response = :gateway_response, 
                         updated_at = NOW() 
                     WHERE reference = :reference";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':gateway_response', json_encode($gateway_response));
            $stmt->bindParam(':reference', $reference);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get payment by reference
    public function getPaymentByReference($reference) {
        try {
            $query = "SELECT * FROM payments WHERE reference = :reference";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':reference', $reference);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get payments for an order
    public function getOrderPayments($order_id) {
        try {
            $query = "SELECT * FROM payments WHERE order_id = :order_id ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Process bank transfer payment
    public function processBankTransfer($order_id, $amount, $account_details) {
        try {
            $reference = 'bank_transfer_' . $order_id . '_' . time();
            
            // Save payment record with pending status
            $this->savePaymentRecord($order_id, 'bank_transfer', $reference, 'pending', $amount);
            
            // Save bank transfer details
            $query = "INSERT INTO bank_transfers (payment_reference, account_name, account_number, bank_name, amount, created_at) 
                     VALUES (:reference, :account_name, :account_number, :bank_name, :amount, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':reference', $reference);
            $stmt->bindParam(':account_name', $account_details['account_name']);
            $stmt->bindParam(':account_number', $account_details['account_number']);
            $stmt->bindParam(':bank_name', $account_details['bank_name']);
            $stmt->bindParam(':amount', $amount);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'reference' => $reference,
                    'message' => 'Bank transfer details saved. Please make payment and upload proof.'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to save bank transfer details'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Generate USSD payment code
    public function generateUSSDCode($order_id, $amount, $bank_code) {
        try {
            $reference = 'ussd_' . $order_id . '_' . time();
            
            // Save payment record
            $this->savePaymentRecord($order_id, 'ussd', $reference, 'pending', $amount);
            
            // Generate USSD codes for different banks
            $ussd_codes = [
                'gtb' => '*737*1*' . $amount . '#',
                'access' => '*901*1*' . $amount . '#',
                'zenith' => '*966*1*' . $amount . '#',
                'uba' => '*919*1*' . $amount . '#',
                'first_bank' => '*894*1*' . $amount . '#',
                'sterling' => '*822*1*' . $amount . '#'
            ];
            
            $ussd_code = $ussd_codes[$bank_code] ?? '*737*1*' . $amount . '#';
            
            return [
                'success' => true,
                'reference' => $reference,
                'ussd_code' => $ussd_code,
                'instructions' => 'Dial ' . $ussd_code . ' on your phone to complete payment'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Process cash on delivery
    public function processCashOnDelivery($order_id, $amount) {
        try {
            $reference = 'cod_' . $order_id . '_' . time();
            
            // Save payment record with pending status
            $this->savePaymentRecord($order_id, 'cash_on_delivery', $reference, 'pending', $amount);
            
            return [
                'success' => true,
                'reference' => $reference,
                'message' => 'Cash on delivery selected. Pay when your order is delivered.'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get payment statistics
    public function getPaymentStats($period = 'month') {
        try {
            $date_condition = $this->getDateCondition($period);
            
            $stats = [];
            
            // Total payments by gateway
            $query = "SELECT gateway, COUNT(*) as count, SUM(amount) as total_amount 
                     FROM payments 
                     WHERE status = 'completed' AND " . $date_condition . "
                     GROUP BY gateway";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['by_gateway'] = $stmt->fetchAll();
            
            // Payment success rate
            $query = "SELECT 
                        COUNT(*) as total_attempts,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_payments,
                        ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
                     FROM payments 
                     WHERE " . $date_condition;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['success_rate'] = $stmt->fetch();
            
            // Failed payments
            $query = "SELECT gateway, COUNT(*) as failed_count 
                     FROM payments 
                     WHERE status = 'failed' AND " . $date_condition . "
                     GROUP BY gateway";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['failed_payments'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Helper method for date conditions
    private function getDateCondition($period) {
        switch ($period) {
            case 'today':
                return 'DATE(created_at) = CURDATE()';
            case 'week':
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            case 'month':
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            case 'quarter':
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
            case 'year':
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)';
            default:
                return '1=1';
        }
    }

    // Refund payment (for supported gateways)
    public function refundPayment($payment_id, $amount = null) {
        try {
            // Get payment details
            $query = "SELECT * FROM payments WHERE id = :payment_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->execute();
            $payment = $stmt->fetch();
            
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
            $refund_amount = $amount ?: $payment['amount'];
            
            if ($payment['gateway'] === 'paystack') {
                return $this->refundPaystackPayment($payment['reference'], $refund_amount);
            } elseif ($payment['gateway'] === 'flutterwave') {
                return $this->refundFlutterwavePayment($payment['reference'], $refund_amount);
            } else {
                return ['success' => false, 'message' => 'Refund not supported for this payment method'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Refund Paystack payment
    private function refundPaystackPayment($reference, $amount) {
        try {
            $url = "https://api.paystack.co/refund";
            
            $fields = [
                'transaction' => $reference,
                'amount' => $amount * 100 // Convert to kobo
            ];

            $fields_string = json_encode($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->paystack_secret_key,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode === 200) {
                $response = json_decode($result, true);
                if ($response['status']) {
                    return [
                        'success' => true,
                        'message' => 'Refund processed successfully',
                        'data' => $response['data']
                    ];
                }
            }

            return ['success' => false, 'message' => 'Refund failed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Refund Flutterwave payment
    private function refundFlutterwavePayment($reference, $amount) {
        try {
            // Get transaction ID first
            $url = "https://api.flutterwave.com/v3/transactions?tx_ref=" . $reference;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->flutterwave_secret_key
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result, true);
            if ($response['status'] === 'success' && !empty($response['data'])) {
                $transaction_id = $response['data'][0]['id'];
                
                // Process refund
                $refund_url = "https://api.flutterwave.com/v3/transactions/" . $transaction_id . "/refund";
                
                $refund_data = ['amount' => $amount];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $refund_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($refund_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $this->flutterwave_secret_key,
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $refund_result = curl_exec($ch);
                curl_close($ch);

                $refund_response = json_decode($refund_result, true);
                if ($refund_response['status'] === 'success') {
                    return [
                        'success' => true,
                        'message' => 'Refund processed successfully',
                        'data' => $refund_response['data']
                    ];
                }
            }

            return ['success' => false, 'message' => 'Refund failed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?> 