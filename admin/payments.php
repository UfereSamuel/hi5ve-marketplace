<?php
session_start();
require_once '../config/config.php';
require_once '../classes/PaymentGateway.php';
require_once '../classes/Order.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$paymentGateway = new PaymentGateway();
$order = new Order();

$error = '';
$success = '';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$gateway_filter = $_GET['gateway'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query conditions
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($gateway_filter) {
    $where_conditions[] = "p.gateway = ?";
    $params[] = $gateway_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(p.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

if ($search) {
    $where_conditions[] = "(p.gateway_reference LIKE ? OR p.customer_email LIKE ? OR o.order_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get payments
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Count total payments
    $count_query = "SELECT COUNT(*) as total FROM payments p 
                   LEFT JOIN orders o ON p.order_id = o.order_id 
                   $where_clause";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_payments = $count_stmt->fetch()['total'];
    
    // Get payments with pagination
    $query = "SELECT p.*, o.customer_name, o.customer_phone 
              FROM payments p 
              LEFT JOIN orders o ON p.order_id = o.order_id 
              $where_clause 
              ORDER BY p.created_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
    
    // Get payment statistics
    $stats = $paymentGateway->getPaymentStats('month');
    
} catch (Exception $e) {
    $payments = [];
    $total_payments = 0;
    $stats = [];
}

$total_pages = ceil($total_payments / $limit);

// Calculate statistics
$total_revenue = 0;
$total_transactions = 0;
$successful_transactions = 0;
$total_fees = 0;

foreach ($stats as $stat) {
    $total_revenue += $stat['total_revenue'];
    $total_transactions += $stat['total_transactions'];
    $successful_transactions += $stat['successful_transactions'];
    $total_fees += $stat['total_fees'];
}

$success_rate = $total_transactions > 0 ? ($successful_transactions / $total_transactions) * 100 : 0;

$page_title = "Payment Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Payment Management</h1>
            <p class="text-gray-600">Monitor and manage payment transactions</p>
        </div>
        <div class="flex space-x-4">
            <button onclick="exportPayments()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                <i class="fas fa-download mr-2"></i>Export
            </button>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        <i class="fas fa-check-circle mr-2"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Payment Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Revenue</h3>
                    <p class="text-2xl font-bold text-blue-600">₦<?= number_format($total_revenue, 2) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Successful Payments</h3>
                    <p class="text-3xl font-bold text-green-600"><?= number_format($successful_transactions) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Success Rate</h3>
                    <p class="text-3xl font-bold text-purple-600"><?= number_format($success_rate, 1) ?>%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-percentage text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Fees</h3>
                    <p class="text-2xl font-bold text-orange-600">₦<?= number_format($total_fees, 2) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="" class="grid md:grid-cols-5 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Payments</label>
                <input type="text" id="search" name="search" 
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Reference, Email, Order ID..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="refunded" <?= $status_filter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                </select>
            </div>

            <div>
                <label for="gateway" class="block text-sm font-medium text-gray-700 mb-1">Gateway</label>
                <select id="gateway" name="gateway"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Gateways</option>
                    <option value="paystack" <?= $gateway_filter === 'paystack' ? 'selected' : '' ?>>Paystack</option>
                    <option value="flutterwave" <?= $gateway_filter === 'flutterwave' ? 'selected' : '' ?>>Flutterwave</option>
                    <option value="whatsapp" <?= $gateway_filter === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="bank_transfer" <?= $gateway_filter === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="cod" <?= $gateway_filter === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
                </select>
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <select id="date" name="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Time</option>
                    <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>This Month</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="payments.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Payments List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">
                Payment Transactions 
                <span class="text-sm font-normal text-gray-500">(<?= number_format($total_payments) ?> total)</span>
            </h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium text-gray-400">No payments found</p>
                                <?php if ($status_filter || $gateway_filter || $date_filter || $search): ?>
                                    <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-400">Payments will appear here once customers start making transactions</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($payment['gateway_reference']) ?></code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="orders.php?search=<?= $payment['order_id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                <?= htmlspecialchars($payment['order_id']) ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($payment['customer_name'] ?? 'N/A') ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($payment['customer_email']) ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $gateway_colors = [
                                'paystack' => 'bg-blue-100 text-blue-800',
                                'flutterwave' => 'bg-orange-100 text-orange-800',
                                'whatsapp' => 'bg-green-100 text-green-800',
                                'bank_transfer' => 'bg-purple-100 text-purple-800',
                                'cod' => 'bg-yellow-100 text-yellow-800'
                            ];
                            $gateway_color = $gateway_colors[$payment['gateway']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $gateway_color ?>">
                                <?= ucfirst($payment['gateway']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">₦<?= number_format($payment['amount'], 2) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">₦<?= number_format($payment['transaction_fee'], 2) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= date('M d, Y', strtotime($payment['created_at'])) ?></div>
                            <div class="text-sm text-gray-500"><?= date('g:i A', strtotime($payment['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="viewPayment(<?= $payment['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded transition duration-300">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($payment['status'] === 'completed' && in_array($payment['gateway'], ['paystack', 'flutterwave'])): ?>
                                    <button onclick="refundPayment(<?= $payment['id'] ?>)" 
                                            class="text-orange-600 hover:text-orange-900 bg-orange-50 hover:bg-orange-100 px-2 py-1 rounded transition duration-300">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                            <span class="font-medium"><?= min($offset + $limit, $total_payments) ?></span> of 
                            <span class="font-medium"><?= $total_payments ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $page ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Details Modal -->
<div id="paymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Payment Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="paymentDetails">
            <!-- Payment details will be loaded here -->
        </div>
    </div>
</div>

<script>
function viewPayment(paymentId) {
    // Load payment details via AJAX
    fetch(`ajax/get_payment_details.php?id=${paymentId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('paymentDetails').innerHTML = html;
            document.getElementById('paymentModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading payment details');
        });
}

function closeModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function refundPayment(paymentId) {
    if (confirm('Are you sure you want to refund this payment?')) {
        // Implement refund functionality
        fetch(`ajax/refund_payment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({payment_id: paymentId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Refund initiated successfully');
                location.reload();
            } else {
                alert('Refund failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing refund');
        });
    }
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open(`ajax/export_payments.php?${params.toString()}`, '_blank');
}

// WhatsApp Payment Functions
function confirmWhatsAppPayment(paymentId) {
    const adminNotes = document.getElementById('admin_notes').value;
    
    if (confirm('Are you sure you want to confirm this WhatsApp payment?')) {
        const formData = new FormData();
        formData.append('action', 'confirm_whatsapp');
        formData.append('payment_id', paymentId);
        formData.append('admin_notes', adminNotes);
        
        fetch('ajax/refund_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Payment confirmed successfully!', 'success');
                closeModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error confirming payment', 'error');
        });
    }
}

function rejectWhatsAppPayment(paymentId) {
    const reason = prompt('Please enter the reason for rejecting this payment:');
    
    if (reason && confirm('Are you sure you want to reject this WhatsApp payment?')) {
        const formData = new FormData();
        formData.append('action', 'reject_whatsapp');
        formData.append('payment_id', paymentId);
        formData.append('rejection_reason', reason);
        
        fetch('ajax/refund_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Payment rejected successfully!', 'warning');
                closeModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error rejecting payment', 'error');
        });
    }
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg text-white font-medium ${
        type === 'success' ? 'bg-green-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        'bg-red-500'
    }`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 