<?php
require_once '../config/config.php';
require_once '../classes/Category.php';

$category = new Category();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'image' => '' // TODO: Handle file upload
                ];
                
                $result = $category->create($data);
                if ($result['success']) {
                    $success = 'Category added successfully!';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'image' => '' // TODO: Handle file upload
                ];
                
                if ($category->update($id, $data)) {
                    $success = 'Category updated successfully!';
                } else {
                    $error = 'Failed to update category';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $result = $category->delete($id);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                if ($category->toggleStatus($id)) {
                    $success = 'Category status updated successfully!';
                } else {
                    $error = 'Failed to update category status';
                }
                break;
        }
    }
}

// Get categories
$categories = $category->getAll(false); // Get all categories including inactive

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_category = $category->getById((int)$_GET['edit']);
}

$page_title = "Categories Management";
include 'includes/admin_header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Categories Management</h1>
            <p class="text-gray-600">Organize your products into categories</p>
        </div>
        <button onclick="toggleAddForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Add New Category
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

    <!-- Add/Edit Category Form -->
    <div id="category-form" class="bg-white rounded-lg shadow-md p-6 mb-8 <?= $edit_category ? '' : 'hidden' ?>">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">
            <?= $edit_category ? 'Edit Category' : 'Add New Category' ?>
        </h2>
        
        <form method="POST" action="" class="grid md:grid-cols-2 gap-6">
            <input type="hidden" name="action" value="<?= $edit_category ? 'edit' : 'add' ?>">
            <?php if ($edit_category): ?>
            <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
            <?php endif; ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                <input type="text" id="name" name="name" required
                       value="<?= $edit_category ? htmlspecialchars($edit_category['name']) : '' ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Brief description of this category"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= $edit_category ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
            </div>
            
            <div class="md:col-span-2 flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i><?= $edit_category ? 'Update Category' : 'Add Category' ?>
                </button>
                <button type="button" onclick="cancelForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Categories Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($categories)): ?>
        <div class="md:col-span-2 lg:col-span-3 text-center py-12">
            <i class="fas fa-tags text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No categories yet</h3>
            <p class="text-gray-500 mb-6">Create your first category to organize your products</p>
            <button onclick="toggleAddForm()" class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                <i class="fas fa-plus mr-2"></i>Add First Category
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($categories as $cat): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-tag text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($cat['name']) ?></h3>
                            <p class="text-sm text-gray-500"><?= $cat['product_count'] ?> products</p>
                        </div>
                    </div>
                    
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        <?= $cat['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                        <?= ucfirst($cat['status']) ?>
                    </span>
                </div>
                
                <?php if ($cat['description']): ?>
                <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($cat['description']) ?></p>
                <?php endif; ?>
                
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="flex space-x-2">
                        <a href="?edit=<?= $cat['id'] ?>" 
                           class="text-blue-600 hover:text-blue-800 transition duration-300" 
                           title="Edit Category">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to toggle this category status?')">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" 
                                    class="text-yellow-600 hover:text-yellow-800 transition duration-300" 
                                    title="Toggle Status">
                                <i class="fas fa-toggle-<?= $cat['status'] === 'active' ? 'on' : 'off' ?>"></i>
                            </button>
                        </form>
                        
                        <?php if ($cat['product_count'] == 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this category?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" 
                                    class="text-red-600 hover:text-red-800 transition duration-300" 
                                    title="Delete Category">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-gray-400" title="Cannot delete category with products">
                            <i class="fas fa-trash"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="../products.php?category=<?= $cat['id'] ?>" 
                       target="_blank"
                       class="text-green-600 hover:text-green-800 text-sm font-medium transition duration-300">
                        View Products <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Categories Statistics -->
    <?php if (!empty($categories)): ?>
    <div class="mt-12 bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Categories Overview</h2>
        
        <div class="grid md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600"><?= count($categories) ?></div>
                <div class="text-sm text-gray-600">Total Categories</div>
            </div>
            
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">
                    <?= count(array_filter($categories, function($cat) { return $cat['status'] === 'active'; })) ?>
                </div>
                <div class="text-sm text-gray-600">Active Categories</div>
            </div>
            
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">
                    <?= array_sum(array_column($categories, 'product_count')) ?>
                </div>
                <div class="text-sm text-gray-600">Total Products</div>
            </div>
            
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">
                    <?= count(array_filter($categories, function($cat) { return $cat['product_count'] > 0; })) ?>
                </div>
                <div class="text-sm text-gray-600">Categories with Products</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleAddForm() {
    const form = document.getElementById('category-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('name').focus();
    }
}

function cancelForm() {
    window.location.href = 'categories.php';
}
</script>

<?php include 'includes/admin_footer.php'; ?> 