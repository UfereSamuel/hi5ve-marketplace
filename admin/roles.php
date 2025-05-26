<?php
require_once '../config/config.php';
require_once '../classes/AdminRole.php';

$adminRole = new AdminRole();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_role':
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'permissions' => $_POST['permissions'] ?? []
                ];
                
                $result = $adminRole->create($data);
                if ($result['success']) {
                    $success = 'Role created successfully!';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_role':
                $role_id = (int)$_POST['role_id'];
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'permissions' => $_POST['permissions'] ?? []
                ];
                
                if ($adminRole->update($role_id, $data)) {
                    $success = 'Role updated successfully!';
                } else {
                    $error = 'Failed to update role';
                }
                break;
                
            case 'delete_role':
                $role_id = (int)$_POST['role_id'];
                $result = $adminRole->delete($role_id);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get all roles
$roles = $adminRole->getAll();
$available_permissions = $adminRole->getAvailablePermissions();

$page_title = "Roles & Permissions";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Roles & Permissions</h1>
            <p class="text-gray-600">Manage admin roles and their permissions</p>
        </div>
        <button onclick="toggleCreateForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Create Role
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

    <!-- Create Role Form -->
    <div id="create-role-form" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Role</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_role">
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                    <input type="text" id="name" name="name" required
                           placeholder="e.g., Content Manager"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" id="description" name="description"
                           placeholder="Brief description of this role"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Permissions *</label>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($available_permissions as $key => $label): ?>
                    <div class="flex items-center">
                        <input type="checkbox" id="perm_<?= $key ?>" name="permissions[]" value="<?= $key ?>"
                               class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <label for="perm_<?= $key ?>" class="ml-2 text-sm text-gray-700">
                            <?= htmlspecialchars($label) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Select the permissions this role should have</p>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Create Role
                </button>
                <button type="button" onclick="toggleCreateForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Roles List -->
    <div class="grid lg:grid-cols-2 gap-6">
        <?php foreach ($roles as $role): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($role['name']) ?></h3>
                        <?php if ($role['description']): ?>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($role['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="editRole(<?= $role['id'] ?>)" class="text-blue-600 hover:text-blue-800" title="Edit Role">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <?php if ($role['name'] !== 'Super Admin'): ?>
                        <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this role?')">
                            <input type="hidden" name="action" value="delete_role">
                            <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete Role">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Permissions:</h4>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $permissions = json_decode($role['permissions'], true);
                    if (in_array('all', $permissions)): ?>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                            <i class="fas fa-crown mr-1"></i>All Permissions
                        </span>
                    <?php else: ?>
                        <?php foreach ($permissions as $permission): ?>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            <?= htmlspecialchars($available_permissions[$permission] ?? $permission) ?>
                        </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 text-xs text-gray-500">
                    Created: <?= date('M j, Y', strtotime($role['created_at'])) ?>
                    <?php if ($role['updated_at'] !== $role['created_at']): ?>
                    | Updated: <?= date('M j, Y', strtotime($role['updated_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Role Modal -->
<div id="edit-role-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Edit Role</h2>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="edit-role-form" method="POST" action="">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" id="edit_role_id" name="role_id">
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                            <input type="text" id="edit_name" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" id="edit_description" name="description"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Permissions *</label>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4" id="edit-permissions">
                            <?php foreach ($available_permissions as $key => $label): ?>
                            <div class="flex items-center">
                                <input type="checkbox" id="edit_perm_<?= $key ?>" name="permissions[]" value="<?= $key ?>"
                                       class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                <label for="edit_perm_<?= $key ?>" class="ml-2 text-sm text-gray-700">
                                    <?= htmlspecialchars($label) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            <i class="fas fa-save mr-2"></i>Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const roles = <?= json_encode($roles) ?>;

function toggleCreateForm() {
    const form = document.getElementById('create-role-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('name').focus();
    }
}

function editRole(roleId) {
    const role = roles.find(r => r.id == roleId);
    if (!role) return;
    
    document.getElementById('edit_role_id').value = role.id;
    document.getElementById('edit_name').value = role.name;
    document.getElementById('edit_description').value = role.description || '';
    
    // Clear all checkboxes
    document.querySelectorAll('#edit-permissions input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Check permissions
    const permissions = JSON.parse(role.permissions);
    permissions.forEach(permission => {
        const checkbox = document.getElementById('edit_perm_' + permission);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    document.getElementById('edit-role-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-role-modal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('edit-role-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 