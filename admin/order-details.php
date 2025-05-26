<?php
require_once '../config/config.php';
require_once '../classes/Order.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo '<div class="text-center py-4 text-red-600">Access denied</div>';
    exit;
}

$order = new Order();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    echo '<div class="text-center py-4 text-red-600">Invalid order ID</div>';
    exit;
}

$order_data = $order->getById($order_id);

if (!$order_data) {
    echo '<div class="text-center py-4 text-red-600">Order not found</div>';
    exit;
}
?>

<div class="space-y-6">
    <!-- Order Header -->
    <div class="border-b border-gray-200 pb-4">
        <div class="flex justify-between items-start">
            <div>
                <h4 class="text-lg font-semibold text-gray-800">Order #<?= htmlspecialchars($order_data['order_id']) ?></h4>
                <p class="text-sm text-gray-600">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order_data['created_at'])) ?></p>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-semibold
                <?php
                switch ($order_data['order_status']) {
                    case 'pending':
                        echo 'bg-yellow-100 text-yellow-800';
                        break;
                    case 'confirmed':
                        echo 'bg-blue-100 text-blue-800';
                        break;
                    case 'processing':
                        echo 'bg-purple-100 text-purple-800';
                        break;
                    case 'shipped':
                        echo 'bg-indigo-100 text-indigo-800';
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
                <?= ucfirst($order_data['order_status']) ?>
            </span>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <h5 class="font-semibold text-gray-800 mb-3">Customer Information</h5>
            <div class="space-y-2 text-sm">
                <div><span class="font-medium">Name:</span> <?= htmlspecialchars($order_data['customer_name']) ?></div>
                <div><span class="font-medium">Email:</span> <?= htmlspecialchars($order_data['customer_email']) ?></div>
                <?php if ($order_data['customer_phone']): ?>
                <div><span class="font-medium">Phone:</span> <?= htmlspecialchars($order_data['customer_phone']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h5 class="font-semibold text-gray-800 mb-3">Delivery Information</h5>
            <div class="space-y-2 text-sm">
                <div><span class="font-medium">Address:</span> <?= htmlspecialchars($order_data['delivery_address']) ?></div>
                <div><span class="font-medium">Payment Method:</span> <?= ucfirst(str_replace('_', ' ', $order_data['payment_method'])) ?></div>
                <?php if ($order_data['delivery_fee'] > 0): ?>
                <div><span class="font-medium">Delivery Fee:</span> <?= formatCurrency($order_data['delivery_fee']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div>
        <h5 class="font-semibold text-gray-800 mb-3">Order Items</h5>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($order_data['items'] as $item): ?>
                    <tr>
                        <td class="px-4 py-2">
                            <div class="flex items-center">
                                <img src="<?= $item['image'] ? '../uploads/products/' . $item['image'] : '../get_placeholder_image.php?w=40&h=40&text=Product' ?>" 
                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                     class="h-10 w-10 rounded object-cover mr-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900"><?= formatCurrency($item['price']) ?></td>
                        <td class="px-4 py-2 text-sm text-gray-900"><?= $item['quantity'] ?> <?= htmlspecialchars($item['unit'] ?? 'pcs') ?></td>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900"><?= formatCurrency($item['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="border-t border-gray-200 pt-4">
        <div class="flex justify-end">
            <div class="w-64 space-y-2">
                <div class="flex justify-between text-sm">
                    <span>Subtotal:</span>
                    <span><?= formatCurrency($order_data['total_amount'] - $order_data['delivery_fee']) ?></span>
                </div>
                <?php if ($order_data['delivery_fee'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span>Delivery Fee:</span>
                    <span><?= formatCurrency($order_data['delivery_fee']) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-2">
                    <span>Total:</span>
                    <span><?= formatCurrency($order_data['total_amount']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <?php if ($order_data['notes']): ?>
    <div>
        <h5 class="font-semibold text-gray-800 mb-2">Order Notes</h5>
        <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded"><?= htmlspecialchars($order_data['notes']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <?php if ($order_data['customer_phone']): ?>
        <a href="<?= getWhatsAppLink('Hello! Regarding your order #' . $order_data['order_id'] . ', we wanted to update you on the status.', $order_data['customer_phone']) ?>" 
           target="_blank" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition duration-300">
            <i class="fab fa-whatsapp mr-2"></i>Contact Customer
        </a>
        <?php endif; ?>
        <button onclick="window.print()" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition duration-300">
            <i class="fas fa-print mr-2"></i>Print Order
        </button>
    </div>
</div> 