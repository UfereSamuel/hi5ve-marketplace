<?php
require_once '../config/config.php';
require_once '../classes/Order.php';
require_once '../classes/User.php';

$order = new Order();
$user = new User();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = sanitizeInput($_POST['status']);
                
                if ($order->updateStatus($id, $status)) {
                    $success = 'Order status updated successfully!';
                } else {
                    $error = 'Failed to update order status';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if ($order->delete($id)) {
                    $success = 'Order deleted successfully!';
                } else {
                    $error = 'Failed to delete order';
                }
                break;
        }
    }
}

// Get orders with pagination and filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$orders = $order->getAllOrders($limit, $offset, $status_filter, $search);
$total_orders = $order->getTotalOrdersCount($status_filter, $search);
$total_pages = ceil($total_orders / $limit);

// Get order statistics
$order_stats = $order->getStats();

$page_title = "Orders Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Orders Management</h1>
            <p class="text-gray-600">Track and manage customer orders</p>
        </div>
        <div class="flex space-x-4">
            <a href="../orders.php" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                <i class="fas fa-external-link-alt mr-2"></i>View Customer Orders
            </a>
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

    <!-- Order Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Orders</h3>
                    <p class="text-3xl font-bold text-blue-600"><?= $order_stats['total_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Pending Orders</h3>
                    <p class="text-3xl font-bold text-yellow-600"><?= $order_stats['pending_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Completed Orders</h3>
                    <p class="text-3xl font-bold text-green-600"><?= $order_stats['delivered_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-naira-sign text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Revenue</h3>
                    <p class="text-2xl font-bold text-purple-600"><?= formatCurrency($order_stats['total_revenue'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="" class="grid md:grid-cols-3 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Orders</label>
                <input type="text" id="search" name="search" 
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Order ID, customer name, email..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select id="status" name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="orders.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Orders List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Orders List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            <?php if ($status_filter || $search): ?>
                                No orders found matching your criteria.
                            <?php else: ?>
                                No orders yet. Orders will appear here once customers start placing them.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order_item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Order #<?= htmlspecialchars($order_item['order_id']) ?></div>
                                <div class="text-sm text-gray-500"><?= $order_item['total_items'] ?> items</div>
                                <div class="text-xs text-gray-400">
                                    Payment: <?= ucfirst(str_replace('_', ' ', $order_item['payment_method'])) ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($order_item['customer_name']) ?>
                                </div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($order_item['customer_email']) ?></div>
                                <?php if ($order_item['customer_phone']): ?>
                                <div class="text-xs text-gray-400"><?= htmlspecialchars($order_item['customer_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= formatCurrency($order_item['total_amount']) ?></div>
                            <?php if ($order_item['delivery_fee'] > 0): ?>
                            <div class="text-xs text-gray-500">+ <?= formatCurrency($order_item['delivery_fee']) ?> delivery</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch ($order_item['status']) {
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
                                <?= ucfirst($order_item['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?= date('M j, Y', strtotime($order_item['created_at'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($order_item['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="showOrderDetails(<?= $order_item['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <div class="relative group">
                                    <button class="text-green-600 hover:text-green-900" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-10">
                                        <div class="py-1">
                                            <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                                            <?php if ($status !== $order_item['status']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?= $order_item['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $status ?>">
                                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <?= ucfirst($status) ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <a href="<?= getWhatsAppLink('Hello! Regarding your order #' . $order_item['order_id'] . ', we wanted to update you on the status.', $order_item['customer_phone']) ?>" 
                                   target="_blank" class="text-green-600 hover:text-green-900" title="Contact via WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                
                                <?php if ($order_item['status'] === 'cancelled'): ?>
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this order?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $order_item['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete Order">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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
                    <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= min($limit, $total_orders - $offset) ?></span> of 
                            <span class="font-medium"><?= $total_orders ?></span> orders
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                            <a href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                      <?= $i == $page ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Order Details</h3>
                <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="order-details-content">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showOrderDetails(orderId) {
    const modal = document.getElementById('order-modal');
    const content = document.getElementById('order-details-content');
    
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>';
    modal.classList.remove('hidden');
    
    fetch(`order-details.php?id=${orderId}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="text-center py-4 text-red-600">Error loading order details</div>';
        });
}

function closeOrderModal() {
    document.getElementById('order-modal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('order-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 