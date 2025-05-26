<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/PaymentGateway.php';
require_once '../../classes/Order.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

$payment_id = intval($_GET['id'] ?? 0);

if (!$payment_id) {
    http_response_code(400);
    exit('Invalid payment ID');
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get payment details with order information
    $stmt = $conn->prepare("
        SELECT p.*, o.customer_name, o.customer_phone, o.delivery_address, o.notes,
               u.first_name, u.last_name, u.email as user_email
        FROM payments p 
        LEFT JOIN orders o ON p.order_id = o.order_id 
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        exit('Payment not found');
    }
    
    // Parse gateway response if available
    $gateway_response = null;
    if ($payment['gateway_response']) {
        $gateway_response = json_decode($payment['gateway_response'], true);
    }
    
    // Format payment method display name
    $payment_method_names = [
        'paystack_card' => 'Debit/Credit Card (Paystack)',
        'paystack_bank' => 'Bank Transfer (Paystack)',
        'paystack_ussd' => 'USSD Payment',
        'flutterwave_card' => 'Debit/Credit Card (Flutterwave)',
        'flutterwave_bank' => 'Bank Transfer (Flutterwave)',
        'bank_transfer' => 'Direct Bank Transfer',
        'cod' => 'Cash on Delivery'
    ];
    
    $gateway_display = ucfirst($payment['gateway']);
    if (isset($payment_method_names[$payment['payment_method']])) {
        $gateway_display = $payment_method_names[$payment['payment_method']];
    }
    
    ?>
    <div class="space-y-6">
        <!-- Payment Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-3">Payment Information</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payment ID:</span>
                        <span class="font-medium">#<?= $payment['id'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Reference:</span>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($payment['gateway_reference']) ?></code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order ID:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['order_id']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gateway:</span>
                        <span class="font-medium"><?= $gateway_display ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Amount:</span>
                        <span class="font-bold text-green-600">₦<?= number_format($payment['amount'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Transaction Fee:</span>
                        <span class="font-medium">₦<?= number_format($payment['transaction_fee'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Net Amount:</span>
                        <span class="font-medium">₦<?= number_format($payment['net_amount'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Currency:</span>
                        <span class="font-medium"><?= $payment['currency'] ?></span>
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-3">Status & Timing</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <?php
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'failed' => 'bg-red-100 text-red-800',
                            'cancelled' => 'bg-gray-100 text-gray-800',
                            'refunded' => 'bg-purple-100 text-purple-800'
                        ];
                        $status_color = $status_colors[$payment['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                            <?= ucfirst($payment['status']) ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium"><?= date('M d, Y g:i A', strtotime($payment['created_at'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-medium"><?= date('M d, Y g:i A', strtotime($payment['updated_at'])) ?></span>
                    </div>
                    <?php if ($payment['verified_at']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Verified:</span>
                        <span class="font-medium"><?= date('M d, Y g:i A', strtotime($payment['verified_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($payment['expires_at']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Expires:</span>
                        <span class="font-medium"><?= date('M d, Y g:i A', strtotime($payment['expires_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Webhook Verified:</span>
                        <span class="font-medium">
                            <?php if ($payment['webhook_verified']): ?>
                                <i class="fas fa-check-circle text-green-500"></i> Yes
                            <?php else: ?>
                                <i class="fas fa-times-circle text-red-500"></i> No
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Customer Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['customer_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['customer_email']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['customer_phone'] ?? 'N/A') ?></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <?php if ($payment['user_id']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">User Account:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">User Email:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['user_email']) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Account Type:</span>
                        <span class="font-medium text-orange-600">Guest Order</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Technical Information -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Technical Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">IP Address:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['ip_address'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payment Method:</span>
                        <span class="font-medium"><?= htmlspecialchars($payment['payment_method'] ?? 'N/A') ?></span>
                    </div>
                    <?php if ($payment['authorization_code']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Auth Code:</span>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($payment['authorization_code']) ?></code>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="space-y-2">
                    <?php if ($payment['user_agent']): ?>
                    <div>
                        <span class="text-gray-600">User Agent:</span>
                        <p class="text-sm text-gray-800 mt-1 break-all"><?= htmlspecialchars($payment['user_agent']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Gateway Response -->
        <?php if ($gateway_response): ?>
        <div>
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Gateway Response</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
                <pre class="text-sm text-gray-800 whitespace-pre-wrap overflow-x-auto"><?= htmlspecialchars(json_encode($gateway_response, JSON_PRETTY_PRINT)) ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Order Notes -->
        <?php if ($payment['notes']): ?>
        <div>
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Order Notes</h4>
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-gray-800"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- WhatsApp Payment Actions -->
        <?php if ($payment['gateway'] === 'whatsapp' && $payment['status'] === 'pending'): ?>
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-3">
                    <i class="fab fa-whatsapp mr-2"></i>WhatsApp Payment Confirmation
                </h3>
                <p class="text-sm text-blue-700 mb-4">
                    This payment is awaiting admin confirmation. Please verify the payment with the customer via WhatsApp before confirming.
                </p>
                
                <div class="space-y-3">
                    <div>
                        <label for="admin_notes" class="block text-sm font-medium text-blue-700 mb-1">Admin Notes (Optional)</label>
                        <textarea id="admin_notes" 
                                  class="w-full px-3 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                  rows="2" 
                                  placeholder="Add any notes about the payment verification..."></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button onclick="confirmWhatsAppPayment(<?= $payment['id'] ?>)" 
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition duration-300">
                            <i class="fas fa-check mr-2"></i>Confirm Payment
                        </button>
                        <button onclick="rejectWhatsAppPayment(<?= $payment['id'] ?>)" 
                                class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition duration-300">
                            <i class="fas fa-times mr-2"></i>Reject Payment
                        </button>
                    </div>
                    
                    <?php if ($payment['customer_phone']): ?>
                        <div class="pt-2 border-t border-blue-200">
                            <a href="<?= getWhatsAppLink('Hello! Regarding your payment for order #' . $payment['order_id'] . ', we need to verify your payment details.', $payment['customer_phone']) ?>" 
                               target="_blank"
                               class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                                <i class="fab fa-whatsapp mr-2"></i>Contact Customer via WhatsApp
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-300">
                Close
            </button>
            <?php if ($payment['status'] === 'completed' && in_array($payment['gateway'], ['paystack', 'flutterwave'])): ?>
            <button onclick="refundPayment(<?= $payment['id'] ?>)" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition duration-300">
                <i class="fas fa-undo mr-2"></i>Initiate Refund
            </button>
            <?php endif; ?>
            <a href="orders.php?search=<?= $payment['order_id'] ?>" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-300">
                <i class="fas fa-eye mr-2"></i>View Order
            </a>
        </div>
    </div>
    
    <?php
    
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="text-center py-8">';
    echo '<i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>';
    echo '<p class="text-red-600">Error loading payment details</p>';
    echo '<p class="text-sm text-gray-500">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?> 