<?php
session_start();
require_once '../config/config.php';
require_once '../classes/PaymentGateway.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$paymentGateway = new PaymentGateway();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_payment_methods'])) {
            // Update payment method statuses
            if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
                foreach ($_POST['payment_methods'] as $method_id => $data) {
                    $is_active = isset($data['is_active']) ? 1 : 0;
                    $min_amount = floatval($data['min_amount'] ?? 0);
                    $max_amount = floatval($data['max_amount'] ?? 999999.99);
                    $transaction_fee_value = floatval($data['transaction_fee_value'] ?? 0);
                    $sort_order = intval($data['sort_order'] ?? 0);
                    
                    $stmt = $conn->prepare("
                        UPDATE payment_methods 
                        SET is_active = ?, min_amount = ?, max_amount = ?, 
                            transaction_fee_value = ?, sort_order = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$is_active, $min_amount, $max_amount, $transaction_fee_value, $sort_order, $method_id]);
                }
                $success_message = "Payment method settings updated successfully!";
            } else {
                $error_message = "No payment methods data received. Please try again.";
            }
        }
        
        if (isset($_POST['update_api_settings'])) {
            // Update API settings
            $settings = [
                'payment_test_mode' => isset($_POST['payment_test_mode']) ? 1 : 0,
                'payment_paystack_test_public' => trim($_POST['payment_paystack_test_public'] ?? ''),
                'payment_paystack_test_secret' => trim($_POST['payment_paystack_test_secret'] ?? ''),
                'payment_paystack_live_public' => trim($_POST['payment_paystack_live_public'] ?? ''),
                'payment_paystack_live_secret' => trim($_POST['payment_paystack_live_secret'] ?? ''),
                'payment_flutterwave_test_public' => trim($_POST['payment_flutterwave_test_public'] ?? ''),
                'payment_flutterwave_test_secret' => trim($_POST['payment_flutterwave_test_secret'] ?? ''),
                'payment_flutterwave_live_public' => trim($_POST['payment_flutterwave_live_public'] ?? ''),
                'payment_flutterwave_live_secret' => trim($_POST['payment_flutterwave_live_secret'] ?? ''),
                'whatsapp_business_number' => trim($_POST['whatsapp_business_number'] ?? '+2348123456789'),
                'bank_account_name' => trim($_POST['bank_account_name'] ?? 'Hi5ve MarketPlace'),
                'bank_account_number' => trim($_POST['bank_account_number'] ?? ''),
                'bank_name' => trim($_POST['bank_name'] ?? '')
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("
                    INSERT INTO site_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value]);
            }
            $success_message = "API settings updated successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Get current payment methods
$stmt = $conn->prepare("SELECT * FROM payment_methods ORDER BY sort_order ASC, id ASC");
$stmt->execute();
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current API settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'payment_%' OR setting_key IN ('whatsapp_business_number', 'bank_account_name', 'bank_account_number', 'bank_name')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = "Payment Settings";
include 'includes/admin_header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Payment Settings</h1>
                    <p class="mt-2 text-gray-600">Manage payment methods and gateway configurations</p>
                </div>
                <div class="flex space-x-3">
                    <a href="payments.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-list mr-2"></i>View Payments
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Payment Methods Management -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                            Payment Methods
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Enable/disable payment options and configure their settings</p>
                    </div>

                    <form method="POST" class="p-6">
                        <div class="space-y-6">
                            <?php foreach ($payment_methods as $method): ?>
                                <div class="border border-gray-200 rounded-lg p-4 <?= $method['is_active'] ? 'bg-green-50 border-green-200' : 'bg-gray-50' ?>">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" 
                                                       name="payment_methods[<?= $method['id'] ?>][is_active]" 
                                                       value="1"
                                                       <?= $method['is_active'] ? 'checked' : '' ?>
                                                       class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                                <div class="ml-3">
                                                    <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($method['display_name']) ?></h3>
                                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($method['description']) ?></p>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <?php
                                            $gateway_colors = [
                                                'paystack' => 'bg-blue-100 text-blue-800',
                                                'flutterwave' => 'bg-orange-100 text-orange-800',
                                                'whatsapp' => 'bg-green-100 text-green-800',
                                                'manual' => 'bg-purple-100 text-purple-800'
                                            ];
                                            $gateway_color = $gateway_colors[$method['gateway']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $gateway_color ?>">
                                                <?= ucfirst($method['gateway']) ?>
                                            </span>
                                            <?php if ($method['is_active']): ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    <i class="fas fa-times-circle mr-1"></i>Inactive
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Min Amount (₦)</label>
                                            <input type="number" 
                                                   name="payment_methods[<?= $method['id'] ?>][min_amount]" 
                                                   value="<?= $method['min_amount'] ?>"
                                                   step="0.01"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Amount (₦)</label>
                                            <input type="number" 
                                                   name="payment_methods[<?= $method['id'] ?>][max_amount]" 
                                                   value="<?= $method['max_amount'] ?>"
                                                   step="0.01"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Fee (<?= $method['transaction_fee_type'] === 'percentage' ? '%' : '₦' ?>)
                                            </label>
                                            <input type="number" 
                                                   name="payment_methods[<?= $method['id'] ?>][transaction_fee_value]" 
                                                   value="<?= $method['transaction_fee_value'] ?>"
                                                   step="0.01"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                                            <input type="number" 
                                                   name="payment_methods[<?= $method['id'] ?>][sort_order]" 
                                                   value="<?= $method['sort_order'] ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    name="update_payment_methods"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                <i class="fas fa-save mr-2"></i>Update Payment Methods
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- API Settings Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-cog mr-2 text-gray-600"></i>
                            API Settings
                        </h2>
                    </div>

                    <form method="POST" class="p-6 space-y-6">
                        <!-- Test Mode Toggle -->
                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       name="payment_test_mode" 
                                       value="1"
                                       <?= ($settings['payment_test_mode'] ?? 1) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-700">Test Mode</span>
                                    <p class="text-xs text-gray-500">Use test API keys for development</p>
                                </div>
                            </label>
                        </div>

                        <!-- Paystack Settings -->
                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                                <i class="fas fa-credit-card mr-2 text-blue-600"></i>Paystack
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Test Public Key</label>
                                    <input type="text" 
                                           name="payment_paystack_test_public" 
                                           value="<?= htmlspecialchars($settings['payment_paystack_test_public'] ?? '') ?>"
                                           placeholder="pk_test_..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Test Secret Key</label>
                                    <input type="password" 
                                           name="payment_paystack_test_secret" 
                                           value="<?= htmlspecialchars($settings['payment_paystack_test_secret'] ?? '') ?>"
                                           placeholder="sk_test_..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Live Public Key</label>
                                    <input type="text" 
                                           name="payment_paystack_live_public" 
                                           value="<?= htmlspecialchars($settings['payment_paystack_live_public'] ?? '') ?>"
                                           placeholder="pk_live_..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Live Secret Key</label>
                                    <input type="password" 
                                           name="payment_paystack_live_secret" 
                                           value="<?= htmlspecialchars($settings['payment_paystack_live_secret'] ?? '') ?>"
                                           placeholder="sk_live_..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Flutterwave Settings -->
                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                                <i class="fas fa-credit-card mr-2 text-orange-600"></i>Flutterwave
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Test Public Key</label>
                                    <input type="text" 
                                           name="payment_flutterwave_test_public" 
                                           value="<?= htmlspecialchars($settings['payment_flutterwave_test_public'] ?? '') ?>"
                                           placeholder="FLWPUBK_TEST-..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Test Secret Key</label>
                                    <input type="password" 
                                           name="payment_flutterwave_test_secret" 
                                           value="<?= htmlspecialchars($settings['payment_flutterwave_test_secret'] ?? '') ?>"
                                           placeholder="FLWSECK_TEST-..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Live Public Key</label>
                                    <input type="text" 
                                           name="payment_flutterwave_live_public" 
                                           value="<?= htmlspecialchars($settings['payment_flutterwave_live_public'] ?? '') ?>"
                                           placeholder="FLWPUBK-..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Live Secret Key</label>
                                    <input type="password" 
                                           name="payment_flutterwave_live_secret" 
                                           value="<?= htmlspecialchars($settings['payment_flutterwave_live_secret'] ?? '') ?>"
                                           placeholder="FLWSECK-..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp & Bank Settings -->
                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                                <i class="fab fa-whatsapp mr-2 text-green-600"></i>WhatsApp & Bank
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Business Number</label>
                                    <input type="text" 
                                           name="whatsapp_business_number" 
                                           value="<?= htmlspecialchars($settings['whatsapp_business_number'] ?? '+2348123456789') ?>"
                                           placeholder="+234..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account Name</label>
                                    <input type="text" 
                                           name="bank_account_name" 
                                           value="<?= htmlspecialchars($settings['bank_account_name'] ?? 'Hi5ve MarketPlace') ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account Number</label>
                                    <input type="text" 
                                           name="bank_account_number" 
                                           value="<?= htmlspecialchars($settings['bank_account_number'] ?? '') ?>"
                                           placeholder="1234567890"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                                    <input type="text" 
                                           name="bank_name" 
                                           value="<?= htmlspecialchars($settings['bank_name'] ?? '') ?>"
                                           placeholder="First Bank Nigeria"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <button type="submit" 
                                    name="update_api_settings"
                                    class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300">
                                <i class="fas fa-save mr-2"></i>Update API Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Status Card -->
                <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                        Payment Status
                    </h3>
                    <div class="space-y-3">
                        <?php
                        $active_methods = array_filter($payment_methods, function($method) {
                            return $method['is_active'];
                        });
                        ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Active Methods:</span>
                            <span class="font-semibold text-green-600"><?= count($active_methods) ?>/<?= count($payment_methods) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Test Mode:</span>
                            <span class="font-semibold <?= ($settings['payment_test_mode'] ?? 1) ? 'text-yellow-600' : 'text-green-600' ?>">
                                <?= ($settings['payment_test_mode'] ?? 1) ? 'Enabled' : 'Live' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Paystack:</span>
                            <span class="font-semibold <?= !empty($settings['payment_paystack_test_public']) ? 'text-green-600' : 'text-red-600' ?>">
                                <?= !empty($settings['payment_paystack_test_public']) ? 'Configured' : 'Not Set' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Flutterwave:</span>
                            <span class="font-semibold <?= !empty($settings['payment_flutterwave_test_public']) ? 'text-green-600' : 'text-red-600' ?>">
                                <?= !empty($settings['payment_flutterwave_test_public']) ? 'Configured' : 'Not Set' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-hide success/error messages (only target actual alert messages, not payment method cards)
setTimeout(function() {
    const alerts = document.querySelectorAll('.mb-6.bg-green-50, .mb-6.bg-red-50');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Toggle payment method details based on active status
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name*="is_active"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const container = this.closest('.border');
            if (this.checked) {
                container.classList.remove('bg-gray-50');
                container.classList.add('bg-green-50', 'border-green-200');
            } else {
                container.classList.remove('bg-green-50', 'border-green-200');
                container.classList.add('bg-gray-50');
            }
        });
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?> 