<?php
require_once '../config/config.php';
require_once '../classes/User.php';
require_once '../classes/Order.php';

$user = new User();
$order = new Order();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $id = (int)$_POST['id'];
                if ($user->toggleStatus($id)) {
                    $success = 'Customer status updated successfully!';
                } else {
                    $error = 'Failed to update customer status';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if ($user->delete($id)) {
                    $success = 'Customer deleted successfully!';
                } else {
                    $error = 'Failed to delete customer';
                }
                break;
        }
    }
}

// Get customers with pagination and filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

$customers = $user->getAllCustomers($limit, $offset, $search, $status_filter);
$total_customers = $user->getTotalCustomersCount($search, $status_filter);
$total_pages = ceil($total_customers / $limit);

// Get customer statistics
$customer_stats = $user->getCustomerStats();

$page_title = "Customers Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Customers Management</h1>
            <p class="text-gray-600">Manage your customer base</p>
        </div>
        <div class="flex space-x-4">
            <a href="../register.php" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                <i class="fas fa-user-plus mr-2"></i>Add Customer
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

    <!-- Customer Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Customers</h3>
                    <p class="text-3xl font-bold text-blue-600"><?= $customer_stats['total_customers'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-user-check text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Active Customers</h3>
                    <p class="text-3xl font-bold text-green-600"><?= $customer_stats['active_customers'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Customers with Orders</h3>
                    <p class="text-3xl font-bold text-purple-600"><?= $customer_stats['customers_with_orders'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-calendar-plus text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">New This Month</h3>
                    <p class="text-3xl font-bold text-orange-600"><?= $customer_stats['new_this_month'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="" class="grid md:grid-cols-3 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Customers</label>
                <input type="text" id="search" name="search" 
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Name, email, username, phone..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select id="status" name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="customers.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Customers List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Customers List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            <?php if ($search || $status_filter): ?>
                                No customers found matching your criteria.
                            <?php else: ?>
                                No customers yet. Customers will appear here once they register.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center">
                                        <span class="text-white font-semibold">
                                            <?= strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">@<?= htmlspecialchars($customer['username']) ?></div>
                                    <?php if ($customer['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-crown mr-1"></i>Admin
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= htmlspecialchars($customer['email']) ?></div>
                            <?php if ($customer['phone']): ?>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-semibold"><?= $customer['total_orders'] ?> orders</div>
                            <?php if ($customer['last_order_date']): ?>
                            <div class="text-xs text-gray-500">
                                Last: <?= date('M j, Y', strtotime($customer['last_order_date'])) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-semibold"><?= formatCurrency($customer['total_spent']) ?></div>
                            <?php if ($customer['total_orders'] > 0): ?>
                            <div class="text-xs text-gray-500">
                                Avg: <?= formatCurrency($customer['total_spent'] / $customer['total_orders']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?= $customer['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($customer['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?= date('M j, Y', strtotime($customer['created_at'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($customer['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="showCustomerDetails(<?= $customer['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($customer['phone']): ?>
                                <a href="<?= getWhatsAppLink('Hello ' . $customer['first_name'] . '! Thank you for being a valued customer at Hi5ve MarketPlace.', $customer['phone']) ?>" 
                                   target="_blank" class="text-green-600 hover:text-green-900" title="Contact via WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($customer['role'] !== 'admin'): ?>
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to toggle this customer status?')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                                        <i class="fas fa-toggle-<?= $customer['status'] === 'active' ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                                
                                <?php if ($customer['total_orders'] == 0): ?>
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this customer?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete Customer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-400" title="Cannot delete customer with orders">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-gray-400" title="Cannot modify admin account">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
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
                            Showing <span class="font-medium"><?= min($limit, $total_customers - $offset) ?></span> of 
                            <span class="font-medium"><?= $total_customers ?></span> customers
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

<!-- Customer Details Modal -->
<div id="customer-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Customer Details</h3>
                <button onclick="closeCustomerModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="customer-details-content">
                <!-- Customer details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showCustomerDetails(customerId) {
    const modal = document.getElementById('customer-modal');
    const content = document.getElementById('customer-details-content');
    
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>';
    modal.classList.remove('hidden');
    
    fetch(`customer-details.php?id=${customerId}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="text-center py-4 text-red-600">Error loading customer details</div>';
        });
}

function closeCustomerModal() {
    document.getElementById('customer-modal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('customer-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomerModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 