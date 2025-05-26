<?php
require_once '../config/config.php';
require_once '../classes/User.php';
require_once '../classes/Order.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo '<div class="text-center py-4 text-red-600">Access denied</div>';
    exit;
}

$user = new User();
$order = new Order();
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$customer_id) {
    echo '<div class="text-center py-4 text-red-600">Invalid customer ID</div>';
    exit;
}

$customer = $user->getUserById($customer_id);

if (!$customer) {
    echo '<div class="text-center py-4 text-red-600">Customer not found</div>';
    exit;
}

// Get customer orders
$customer_orders = $order->getUserOrders($customer_id, 10);
?>

<div class="space-y-6">
    <!-- Customer Header -->
    <div class="border-b border-gray-200 pb-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="h-16 w-16 rounded-full bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center mr-4">
                    <span class="text-white font-bold text-xl">
                        <?= strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)) ?>
                    </span>
                </div>
                <div>
                    <h4 class="text-xl font-semibold text-gray-800">
                        <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                    </h4>
                    <p class="text-sm text-gray-600">@<?= htmlspecialchars($customer['username']) ?></p>
                    <p class="text-xs text-gray-500">Member since <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>
                </div>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-sm font-semibold
                    <?= $customer['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                    <?= ucfirst($customer['status']) ?>
                </span>
                <?php if ($customer['role'] === 'admin'): ?>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-crown mr-1"></i>Admin
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <h5 class="font-semibold text-gray-800 mb-3">Contact Information</h5>
            <div class="space-y-3">
                <div class="flex items-center">
                    <i class="fas fa-envelope text-gray-400 w-5 mr-3"></i>
                    <div>
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['email']) ?></div>
                        <div class="text-xs text-gray-500">Email Address</div>
                    </div>
                </div>
                
                <?php if ($customer['phone']): ?>
                <div class="flex items-center">
                    <i class="fas fa-phone text-gray-400 w-5 mr-3"></i>
                    <div>
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['phone']) ?></div>
                        <div class="text-xs text-gray-500">Phone Number</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($customer['address']): ?>
                <div class="flex items-start">
                    <i class="fas fa-map-marker-alt text-gray-400 w-5 mr-3 mt-1"></i>
                    <div>
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['address']) ?></div>
                        <div class="text-xs text-gray-500">Address</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h5 class="font-semibold text-gray-800 mb-3">Account Statistics</h5>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-shopping-cart text-blue-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700">Total Orders</span>
                    </div>
                    <span class="text-lg font-bold text-blue-600"><?= count($customer_orders) ?></span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-naira-sign text-green-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700">Total Spent</span>
                    </div>
                    <span class="text-lg font-bold text-green-600">
                        <?= formatCurrency(array_sum(array_column($customer_orders, 'total_amount'))) ?>
                    </span>
                </div>
                
                <?php if (!empty($customer_orders)): ?>
                <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-calendar text-purple-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700">Last Order</span>
                    </div>
                    <span class="text-sm font-medium text-purple-600">
                        <?= date('M j, Y', strtotime($customer_orders[0]['created_at'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <?php if (!empty($customer_orders)): ?>
    <div>
        <h5 class="font-semibold text-gray-800 mb-3">Recent Orders</h5>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach (array_slice($customer_orders, 0, 5) as $order_item): ?>
                    <tr>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">
                            #<?= htmlspecialchars($order_item['order_id']) ?>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            <?= date('M j, Y', strtotime($order_item['created_at'])) ?>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            <?= formatCurrency($order_item['total_amount']) ?>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch ($order_item['order_status']) {
                                    case 'pending':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'confirmed':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'delivered':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'cancelled':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= ucfirst($order_item['order_status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($customer_orders) > 5): ?>
        <div class="mt-3 text-center">
            <a href="../orders.php?customer=<?= $customer['id'] ?>" target="_blank" 
               class="text-green-600 hover:text-green-700 text-sm font-medium">
                View All Orders (<?= count($customer_orders) ?> total)
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-8">
        <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-3"></i>
        <h6 class="text-lg font-medium text-gray-600 mb-2">No Orders Yet</h6>
        <p class="text-gray-500">This customer hasn't placed any orders yet.</p>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <?php if ($customer['phone']): ?>
        <a href="<?= getWhatsAppLink('Hello ' . $customer['first_name'] . '! Thank you for being a valued customer at Hi5ve MarketPlace.', $customer['phone']) ?>" 
           target="_blank" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition duration-300">
            <i class="fab fa-whatsapp mr-2"></i>Contact via WhatsApp
        </a>
        <?php endif; ?>
        
        <a href="mailto:<?= htmlspecialchars($customer['email']) ?>" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition duration-300">
            <i class="fas fa-envelope mr-2"></i>Send Email
        </a>
        
        <a href="../orders.php?customer=<?= $customer['id'] ?>" target="_blank"
           class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition duration-300">
            <i class="fas fa-history mr-2"></i>View Order History
        </a>
    </div>
</div> 