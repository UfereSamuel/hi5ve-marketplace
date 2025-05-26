<?php
require_once 'config/config.php';
require_once 'classes/Order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$order = new Order();

// Get parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$limit = 10;

// Get user orders
$orders = $order->getUserOrders($_SESSION['user_id'], $limit, ($page - 1) * $limit, $status);
$total_orders = $order->getUserOrdersCount($_SESSION['user_id'], $status);
$total_pages = ceil($total_orders / $limit);

$page_title = "My Orders";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">My Orders</h1>
                <p class="text-gray-600">
                    <?php if ($total_orders > 0): ?>
                        Showing <?= min($limit, $total_orders - (($page - 1) * $limit)) ?> of <?= $total_orders ?> orders
                    <?php else: ?>
                        No orders found
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="mt-4 md:mt-0 flex space-x-4">
                <a href="products.php" 
                   class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-shopping-cart mr-2"></i>Continue Shopping
                </a>
            </div>
        </div>

        <!-- Filter Options -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold mb-4">Filter Orders</h3>
            <div class="flex flex-wrap gap-2">
                <a href="orders.php" 
                   class="px-4 py-2 rounded-lg <?= empty($status) ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> transition duration-300">
                    All Orders
                </a>
                <a href="orders.php?status=pending" 
                   class="px-4 py-2 rounded-lg <?= $status === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> transition duration-300">
                    Pending
                </a>
                <a href="orders.php?status=confirmed" 
                   class="px-4 py-2 rounded-lg <?= $status === 'confirmed' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> transition duration-300">
                    Confirmed
                </a>
                <a href="orders.php?status=delivered" 
                   class="px-4 py-2 rounded-lg <?= $status === 'delivered' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> transition duration-300">
                    Delivered
                </a>
                <a href="orders.php?status=cancelled" 
                   class="px-4 py-2 rounded-lg <?= $status === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> transition duration-300">
                    Cancelled
                </a>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="mb-8">
                <i class="fas fa-shopping-bag text-gray-300 text-6xl"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-600 mb-4">
                <?php if ($status): ?>
                    No <?= htmlspecialchars($status) ?> orders found
                <?php else: ?>
                    No orders found
                <?php endif; ?>
            </h2>
            <p class="text-gray-500 mb-8">
                <?php if ($status): ?>
                    You don't have any <?= htmlspecialchars($status) ?> orders yet.
                <?php else: ?>
                    You haven't placed any orders yet. Start shopping to see your orders here.
                <?php endif; ?>
            </p>
            <div class="space-x-4">
                <a href="products.php" 
                   class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-shopping-cart mr-2"></i>Start Shopping
                </a>
                <a href="<?= getWhatsAppLink('Hello! I would like to place an order.') ?>" 
                   target="_blank" 
                   class="inline-block bg-green-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300">
                    <i class="fab fa-whatsapp mr-2"></i>Order via WhatsApp
                </a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="space-y-6">
            <?php foreach ($orders as $order_item): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Order Header -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                Order #<?= htmlspecialchars($order_item['order_id']) ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                Placed on <?= date('M j, Y g:i A', strtotime($order_item['created_at'])) ?>
                            </p>
                        </div>
                        <div class="mt-2 md:mt-0 flex items-center space-x-4">
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                <?php
                                switch ($order_item['status']) {
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
                                <?= ucfirst($order_item['status']) ?>
                            </span>
                            <span class="text-lg font-bold text-green-600">
                                <?= formatCurrency($order_item['total_amount']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="px-6 py-4">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Order Information</h4>
                            <div class="space-y-1 text-sm">
                                <p><span class="text-gray-600">Payment Method:</span> <?= ucfirst($order_item['payment_method']) ?></p>
                                <p><span class="text-gray-600">Delivery Fee:</span> <?= formatCurrency($order_item['delivery_fee']) ?></p>
                                <?php if ($order_item['notes']): ?>
                                <p><span class="text-gray-600">Notes:</span> <?= htmlspecialchars($order_item['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Delivery Information</h4>
                            <div class="space-y-1 text-sm">
                                <p><span class="text-gray-600">Name:</span> <?= htmlspecialchars($order_item['customer_name']) ?></p>
                                <p><span class="text-gray-600">Phone:</span> <?= htmlspecialchars($order_item['customer_phone']) ?></p>
                                <p><span class="text-gray-600">Address:</span> <?= htmlspecialchars($order_item['customer_address']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Actions -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-2 md:space-y-0">
                        <div class="flex space-x-4">
                            <a href="order-details.php?id=<?= $order_item['id'] ?>" 
                               class="text-green-600 hover:text-green-700 font-medium">
                                <i class="fas fa-eye mr-1"></i>View Details
                            </a>
                            
                            <?php if ($order_item['status'] === 'pending'): ?>
                            <a href="<?= getWhatsAppLink('Hello! I would like to modify my order #' . $order_item['order_id']) ?>" 
                               target="_blank" 
                               class="text-blue-600 hover:text-blue-700 font-medium">
                                <i class="fab fa-whatsapp mr-1"></i>Modify Order
                            </a>
                            <?php endif; ?>
                            
                            <a href="<?= getWhatsAppLink('Hello! I need help with my order #' . $order_item['order_id']) ?>" 
                               target="_blank" 
                               class="text-gray-600 hover:text-gray-700 font-medium">
                                <i class="fas fa-headset mr-1"></i>Get Help
                            </a>
                        </div>
                        
                        <?php if ($order_item['status'] === 'delivered'): ?>
                        <a href="products.php" 
                           class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            <i class="fas fa-redo mr-2"></i>Reorder
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-8">
            <nav class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                   class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-300">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="px-3 py-2 <?= $i == $page ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' ?> rounded-lg transition duration-300">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                   class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-300">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 