<?php
require_once '../config/config.php';
require_once '../classes/User.php';
require_once '../classes/Product.php';
require_once '../classes/Order.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$product = new Product();
$order = new Order();

// Get statistics
$product_stats = $product->getStats();
$order_stats = $order->getStats();
$recent_orders = $order->getRecentOrders(10);

$page_title = "Admin Dashboard";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Dashboard</h1>
        <p class="text-gray-600">Manage your Hi5ve MarketPlace</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Products -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-box text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Products</h3>
                    <p class="text-3xl font-bold text-blue-600"><?= $product_stats['total_products'] ?></p>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Orders</h3>
                    <p class="text-3xl font-bold text-green-600"><?= $order_stats['total_orders'] ?></p>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Pending Orders</h3>
                    <p class="text-3xl font-bold text-yellow-600"><?= $order_stats['pending_orders'] ?></p>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-naira-sign text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Revenue</h3>
                    <p class="text-2xl font-bold text-purple-600"><?= formatCurrency($order_stats['total_revenue']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <a href="products.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <div class="text-center">
                <i class="fas fa-plus-circle text-4xl text-blue-600 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800">Add Product</h3>
                <p class="text-gray-600">Add new products to your store</p>
            </div>
        </a>

        <a href="orders.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <div class="text-center">
                <i class="fas fa-list-alt text-4xl text-green-600 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800">Manage Orders</h3>
                <p class="text-gray-600">View and process customer orders</p>
            </div>
        </a>

        <a href="categories.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <div class="text-center">
                <i class="fas fa-tags text-4xl text-purple-600 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800">Categories</h3>
                <p class="text-gray-600">Manage product categories</p>
            </div>
        </a>

        <a href="customers.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <div class="text-center">
                <i class="fas fa-users text-4xl text-orange-600 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800">Customers</h3>
                <p class="text-gray-600">View customer information</p>
            </div>
        </a>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Recent Orders</h2>
                <a href="orders.php" class="text-green-600 hover:text-green-700 font-medium">
                    View All Orders <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No orders yet. Orders will appear here once customers start placing them.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recent_orders as $order_item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #<?= htmlspecialchars($order_item['order_id']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($order_item['customer_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= formatCurrency($order_item['total_amount']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= date('M j, Y', strtotime($order_item['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="orders.php?view=<?= $order_item['id'] ?>" 
                               class="text-green-600 hover:text-green-900">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 