<?php
require_once 'config/config.php';
require_once 'classes/User.php';
require_once 'classes/Order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user = new User();
$order = new Order();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $data = [
                    'first_name' => sanitizeInput($_POST['first_name']),
                    'last_name' => sanitizeInput($_POST['last_name']),
                    'phone' => sanitizeInput($_POST['phone']),
                    'address' => sanitizeInput($_POST['address'])
                ];
                
                if (empty($data['first_name']) || empty($data['last_name'])) {
                    $error = 'First name and last name are required';
                } else {
                    if ($user->updateProfile($_SESSION['user_id'], $data)) {
                        // Update session data
                        $_SESSION['first_name'] = $data['first_name'];
                        $_SESSION['last_name'] = $data['last_name'];
                        $success = 'Profile updated successfully';
                    } else {
                        $error = 'Failed to update profile';
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long';
                } else {
                    $result = $user->changePassword($_SESSION['user_id'], $current_password, $new_password);
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}

// Get user data
$user_data = $user->getUserById($_SESSION['user_id']);

// Get recent orders
$recent_orders = $order->getUserOrders($_SESSION['user_id'], 5);

$page_title = "My Profile";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">My Profile</h1>
            <p class="text-gray-600">Manage your account information and view your order history</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) ?>
                        </h3>
                        <p class="text-gray-600"><?= htmlspecialchars($user_data['email']) ?></p>
                        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-sm mt-2">
                            <?= ucfirst($user_data['role']) ?>
                        </span>
                    </div>
                    
                    <nav class="space-y-2">
                        <a href="#profile-info" 
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded transition duration-300">
                            <i class="fas fa-user mr-2"></i>Profile Information
                        </a>
                        <a href="#change-password" 
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded transition duration-300">
                            <i class="fas fa-lock mr-2"></i>Change Password
                        </a>
                        <a href="#order-history" 
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded transition duration-300">
                            <i class="fas fa-shopping-bag mr-2"></i>Order History
                        </a>
                        <a href="orders.php" 
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded transition duration-300">
                            <i class="fas fa-list mr-2"></i>All Orders
                        </a>
                        <?php if (isAdmin()): ?>
                        <a href="admin/" 
                           class="block px-4 py-2 text-blue-700 hover:bg-blue-100 rounded transition duration-300">
                            <i class="fas fa-cog mr-2"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="md:col-span-2 space-y-8">
                <!-- Profile Information -->
                <div id="profile-info" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Profile Information</h2>
                    
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    First Name *
                                </label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?= htmlspecialchars($user_data['first_name']) ?>" 
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Last Name *
                                </label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?= htmlspecialchars($user_data['last_name']) ?>" 
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input type="email" 
                                   id="email" 
                                   value="<?= htmlspecialchars($user_data['email']) ?>" 
                                   disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                            <p class="text-sm text-gray-500 mt-1">Email cannot be changed. Contact support if needed.</p>
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Phone Number
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" 
                                   placeholder="e.g., +234 801 234 5678"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                Address
                            </label>
                            <textarea id="address" 
                                      name="address" 
                                      rows="3" 
                                      placeholder="Your delivery address"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div id="change-password" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Change Password</h2>
                    
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                Current Password *
                            </label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                New Password *
                            </label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   required
                                   minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-sm text-gray-500 mt-1">Must be at least 6 characters long</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm New Password *
                            </label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                                <i class="fas fa-key mr-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Orders -->
                <div id="order-history" class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Orders</h2>
                        <a href="orders.php" class="text-green-600 hover:text-green-700 font-medium">
                            View All Orders <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-shopping-bag text-gray-300 text-4xl mb-4"></i>
                        <p class="text-gray-500 mb-4">You haven't placed any orders yet</p>
                        <a href="products.php" 
                           class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            Start Shopping
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_orders as $order_item): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Order #<?= htmlspecialchars($order_item['order_id']) ?></h4>
                                    <p class="text-sm text-gray-600">
                                        <?= date('M j, Y g:i A', strtotime($order_item['created_at'])) ?>
                                    </p>
                                </div>
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
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-gray-600">
                                    Total: <span class="font-semibold text-green-600"><?= formatCurrency($order_item['total_amount']) ?></span>
                                </p>
                                <a href="order-details.php?id=<?= $order_item['id'] ?>" 
                                   class="text-green-600 hover:text-green-700 font-medium">
                                    View Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 