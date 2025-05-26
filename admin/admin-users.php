<?php
require_once '../config/config.php';
require_once '../classes/User.php';
require_once '../classes/AdminRole.php';

$user = new User();
$adminRole = new AdminRole();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_admin':
                $data = [
                    'username' => sanitizeInput($_POST['username']),
                    'email' => sanitizeInput($_POST['email']),
                    'password' => $_POST['password'],
                    'first_name' => sanitizeInput($_POST['first_name']),
                    'last_name' => sanitizeInput($_POST['last_name']),
                    'phone' => sanitizeInput($_POST['phone']),
                    'address' => sanitizeInput($_POST['address'])
                ];
                
                $result = $user->register($data);
                if ($result['success']) {
                    // Set as admin and assign role
                    $user_id = $result['user_id'];
                    $role_id = (int)$_POST['role_id'];
                    
                    // Update user to admin role
                    $query = "UPDATE users SET role = 'admin', role_id = :role_id WHERE id = :user_id";
                    $stmt = $user->getConnection()->prepare($query);
                    $stmt->bindParam(':role_id', $role_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $success = 'Admin user created successfully!';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_role':
                $user_id = (int)$_POST['user_id'];
                $role_id = (int)$_POST['role_id'];
                
                if ($adminRole->assignRole($user_id, $role_id)) {
                    $success = 'User role updated successfully!';
                } else {
                    $error = 'Failed to update user role';
                }
                break;
                
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                if ($user->toggleStatus($user_id)) {
                    $success = 'User status updated successfully!';
                } else {
                    $error = 'Failed to update user status';
                }
                break;
                
            case 'delete_admin':
                $user_id = (int)$_POST['user_id'];
                
                // Prevent deleting current user
                if ($user_id == $_SESSION['user_id']) {
                    $error = 'You cannot delete your own account';
                    break;
                }
                
                if ($user->delete($user_id)) {
                    $success = 'Admin user deleted successfully!';
                } else {
                    $error = 'Failed to delete admin user';
                }
                break;
        }
    }
}

// Get admin users with their roles
$query = "SELECT u.*, ar.name as role_name, ar.description as role_description 
          FROM users u 
          LEFT JOIN admin_roles ar ON u.role_id = ar.id 
          WHERE u.role = 'admin' 
          ORDER BY u.created_at DESC";
$stmt = $user->getConnection()->prepare($query);
$stmt->execute();
$admin_users = $stmt->fetchAll();

// Get all roles for dropdown
$roles = $adminRole->getAll();

$page_title = "Admin Users";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Admin Users</h1>
            <p class="text-gray-600">Manage administrator accounts and permissions</p>
        </div>
        <button onclick="toggleAddForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-user-plus mr-2"></i>Add Admin User
        </button>
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

    <!-- Add Admin Form -->
    <div id="add-admin-form" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Admin User</h2>
        
        <form method="POST" action="" class="grid md:grid-cols-2 gap-6">
            <input type="hidden" name="action" value="add_admin">
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                <input type="text" id="username" name="username" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                <input type="text" id="first_name" name="first_name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="tel" id="phone" name="phone"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="role_id" class="block text-sm font-medium text-gray-700 mb-1">Admin Role *</label>
                <select id="role_id" name="role_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                <input type="password" id="password" name="password" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea id="address" name="address" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="md:col-span-2 flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Create Admin User
                </button>
                <button type="button" onclick="toggleAddForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Admin Users List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Admin Users List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($admin_users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No admin users found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($admin_users as $admin): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center">
                                        <span class="text-white font-semibold">
                                            <?= strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
                                        <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                        <span class="text-xs text-blue-600">(You)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">@<?= htmlspecialchars($admin['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= htmlspecialchars($admin['email']) ?></div>
                            <?php if ($admin['phone']): ?>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($admin['phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($admin['role_name'] ?? 'No Role') ?>
                                </span>
                                
                                <!-- Role Change Dropdown -->
                                <div class="ml-2 relative group">
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-10">
                                        <div class="py-1">
                                            <?php foreach ($roles as $role): ?>
                                            <?php if ($role['id'] != $admin['role_id']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <?= htmlspecialchars($role['name']) ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($admin['role_description']): ?>
                            <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($admin['role_description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?= $admin['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($admin['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?= date('M j, Y', strtotime($admin['created_at'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($admin['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to toggle this user status?')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                                        <i class="fas fa-toggle-<?= $admin['status'] === 'active' ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this admin user?')">
                                    <input type="hidden" name="action" value="delete_admin">
                                    <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-400" title="Cannot modify your own account">
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
    </div>
</div>

<script>
function toggleAddForm() {
    const form = document.getElementById('add-admin-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('username').focus();
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 