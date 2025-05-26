<?php
require_once '../config/config.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';
require_once '../classes/FileUpload.php';

$product = new Product();
$category = new Category();
$fileUpload = new FileUpload();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Handle image upload
                $image_filename = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $user_id = $_SESSION['user_id'] ?? 1; // Use session user_id or default to 1
                    $upload_result = $fileUpload->upload($_FILES['image'], 'product', $user_id);
                    if ($upload_result['success']) {
                        $image_filename = $upload_result['filename'];
                    } else {
                        $error = $upload_result['message'];
                        break;
                    }
                }
                
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'category_id' => (int)$_POST['category_id'],
                    'price' => (float)$_POST['price'],
                    'discount_price' => !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null,
                    'stock_quantity' => (int)$_POST['stock_quantity'],
                    'unit' => sanitizeInput($_POST['unit']),
                    'image' => $image_filename,
                    'gallery' => '', // TODO: Handle multiple images in future
                    'featured' => isset($_POST['featured']) ? 1 : 0
                ];
                
                $result = $product->create($data);
                if ($result['success']) {
                    $success = 'Product added successfully!';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                
                // Get current product data
                $current_product = $product->getById($id);
                $image_filename = $current_product['image']; // Keep existing image by default
                
                // Handle image upload if new image is provided
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $user_id = $_SESSION['user_id'] ?? 1; // Use session user_id or default to 1
                    $upload_result = $fileUpload->upload($_FILES['image'], 'product', $user_id);
                    if ($upload_result['success']) {
                        // Delete old image if it exists
                        if ($current_product['image'] && file_exists('../uploads/products/' . $current_product['image'])) {
                            unlink('../uploads/products/' . $current_product['image']);
                        }
                        $image_filename = $upload_result['filename'];
                    } else {
                        $error = $upload_result['message'];
                        break;
                    }
                }
                
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'category_id' => (int)$_POST['category_id'],
                    'price' => (float)$_POST['price'],
                    'discount_price' => !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null,
                    'stock_quantity' => (int)$_POST['stock_quantity'],
                    'unit' => sanitizeInput($_POST['unit']),
                    'image' => $image_filename,
                    'gallery' => $current_product['gallery'], // Keep existing gallery
                    'featured' => isset($_POST['featured']) ? 1 : 0
                ];
                
                if ($product->update($id, $data)) {
                    $success = 'Product updated successfully!';
                } else {
                    $error = 'Failed to update product';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Get product data to delete associated image
                $product_data = $product->getById($id);
                if ($product_data && $product_data['image'] && file_exists('../uploads/products/' . $product_data['image'])) {
                    unlink('../uploads/products/' . $product_data['image']);
                }
                
                if ($product->delete($id)) {
                    $success = 'Product deleted successfully!';
                } else {
                    $error = 'Failed to delete product';
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                if ($product->toggleStatus($id)) {
                    $success = 'Product status updated successfully!';
                } else {
                    $error = 'Failed to update product status';
                }
                break;
        }
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$products = $product->getAll($limit, $offset);
$total_products = $product->getTotalCount();
$total_pages = ceil($total_products / $limit);

// Get categories for dropdown
$categories = $category->getAll();

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_product = $product->getById((int)$_GET['edit']);
}

$page_title = "Products Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Products Management</h1>
            <p class="text-gray-600">Manage your product catalog</p>
        </div>
        <button onclick="toggleAddForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Add New Product
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

    <!-- Add/Edit Product Form -->
    <div id="product-form" class="bg-white rounded-lg shadow-md p-6 mb-8 <?= $edit_product ? '' : 'hidden' ?>">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">
            <?= $edit_product ? 'Edit Product' : 'Add New Product' ?>
        </h2>
        
        <form method="POST" action="" class="grid md:grid-cols-2 gap-6" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $edit_product ? 'edit' : 'add' ?>">
            <?php if ($edit_product): ?>
            <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
            <?php endif; ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                <input type="text" id="name" name="name" required
                       value="<?= $edit_product ? htmlspecialchars($edit_product['name']) : '' ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select id="category_id" name="category_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $edit_product && $edit_product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= $edit_product ? htmlspecialchars($edit_product['description']) : '' ?></textarea>
            </div>
            
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (₦) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required
                       value="<?= $edit_product ? $edit_product['price'] : '' ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="discount_price" class="block text-sm font-medium text-gray-700 mb-1">Discount Price (₦)</label>
                <input type="number" id="discount_price" name="discount_price" step="0.01" min="0"
                       value="<?= $edit_product ? $edit_product['discount_price'] : '' ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required
                       value="<?= $edit_product ? $edit_product['stock_quantity'] : '' ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                <select id="unit" name="unit" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Select Unit</option>
                    <option value="kg" <?= $edit_product && $edit_product['unit'] == 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                    <option value="g" <?= $edit_product && $edit_product['unit'] == 'g' ? 'selected' : '' ?>>Gram (g)</option>
                    <option value="liter" <?= $edit_product && $edit_product['unit'] == 'liter' ? 'selected' : '' ?>>Liter</option>
                    <option value="piece" <?= $edit_product && $edit_product['unit'] == 'piece' ? 'selected' : '' ?>>Piece</option>
                    <option value="pack" <?= $edit_product && $edit_product['unit'] == 'pack' ? 'selected' : '' ?>>Pack</option>
                    <option value="bag" <?= $edit_product && $edit_product['unit'] == 'bag' ? 'selected' : '' ?>>Bag</option>
                    <option value="crate" <?= $edit_product && $edit_product['unit'] == 'crate' ? 'selected' : '' ?>>Crate</option>
                    <option value="gallon" <?= $edit_product && $edit_product['unit'] == 'gallon' ? 'selected' : '' ?>>Gallon</option>
                    <option value="bottle" <?= $edit_product && $edit_product['unit'] == 'bottle' ? 'selected' : '' ?>>Bottle</option>
                    <option value="cup" <?= $edit_product && $edit_product['unit'] == 'cup' ? 'selected' : '' ?>>Cup</option>
                    <option value="bunch" <?= $edit_product && $edit_product['unit'] == 'bunch' ? 'selected' : '' ?>>Bunch</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="featured" value="1" 
                           <?= $edit_product && $edit_product['featured'] ? 'checked' : '' ?>
                           class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Featured Product</span>
                </label>
            </div>
            
            <div class="md:col-span-2">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                
                <?php if ($edit_product && $edit_product['image']): ?>
                <div class="mb-3">
                    <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                    <img src="../uploads/products/<?= $edit_product['image'] ?>" 
                         alt="Current product image" 
                         class="h-20 w-20 rounded-lg object-cover border border-gray-300">
                </div>
                <?php endif; ?>
                
                <div class="flex items-center space-x-4">
                    <input type="file" id="image" name="image" 
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                           onchange="previewImage(this)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <div id="image-preview" class="hidden">
                        <img id="preview-img" src="" alt="Preview" class="h-20 w-20 rounded-lg object-cover border border-gray-300">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Supported formats: JPEG, PNG, GIF, WebP. Max size: 5MB</p>
            </div>
            
            <div class="md:col-span-2 flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i><?= $edit_product ? 'Update Product' : 'Add Product' ?>
                </button>
                <button type="button" onclick="cancelForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Products List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Products List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No products found. <a href="#" onclick="toggleAddForm()" class="text-green-600 hover:text-green-700">Add your first product</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $prod): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="<?= $prod['image'] ? '../uploads/products/' . $prod['image'] : '../get_placeholder_image.php?w=60&h=60&text=Product' ?>" 
                                     alt="<?= htmlspecialchars($prod['name']) ?>" 
                                     class="h-12 w-12 rounded-lg object-cover mr-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($prod['name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($prod['description'], 0, 50)) ?>...</div>
                                    <?php if ($prod['featured']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-star mr-1"></i>Featured
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($prod['category_name'] ?? 'No Category') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div>
                                <?php if ($prod['discount_price']): ?>
                                <span class="font-semibold text-green-600"><?= formatCurrency($prod['discount_price']) ?></span>
                                <span class="text-gray-500 line-through text-xs"><?= formatCurrency($prod['price']) ?></span>
                                <?php else: ?>
                                <span class="font-semibold"><?= formatCurrency($prod['price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500">per <?= htmlspecialchars($prod['unit']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="<?= $prod['stock_quantity'] <= 10 ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $prod['stock_quantity'] ?> <?= htmlspecialchars($prod['unit']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch ($prod['status']) {
                                    case 'active':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'inactive':
                                        echo 'bg-gray-100 text-gray-800';
                                        break;
                                    case 'out_of_stock':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                }
                                ?>">
                                <?= ucfirst(str_replace('_', ' ', $prod['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="?edit=<?= $prod['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to toggle this product status?')">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this product?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
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
                    <a href="?page=<?= $page - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= min($limit, $total_products - $offset) ?></span> of 
                            <span class="font-medium"><?= $total_products ?></span> products
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" 
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

<script>
function toggleAddForm() {
    const form = document.getElementById('product-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('name').focus();
    }
}

function cancelForm() {
    window.location.href = 'products.php';
}

function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}

function confirmDelete(message) {
    return confirm(message);
}
</script>

<?php include 'includes/admin_footer.php'; ?> 