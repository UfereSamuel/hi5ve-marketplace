<?php
require_once '../config/config.php';
require_once '../classes/Banner.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';
require_once '../classes/FileUpload.php';

$banner = new Banner();
$product = new Product();
$category = new Category();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = sanitizeInput($_POST['title']);
                $description = sanitizeInput($_POST['description']);
                $link_type = sanitizeInput($_POST['link_type']);
                $link_value = sanitizeInput($_POST['link_value']);
                $position = sanitizeInput($_POST['position']);
                $display_order = (int)$_POST['display_order'];
                $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                
                // Handle file upload
                if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                    // Create banners directory if it doesn't exist
                    $banner_dir = '../uploads/banners/';
                    if (!is_dir($banner_dir)) {
                        mkdir($banner_dir, 0755, true);
                    }
                    
                    $fileUpload = new FileUpload();
                    $upload_result = $fileUpload->upload($_FILES['banner_image'], 'banners', $_SESSION['user_id']);
                    
                    if ($upload_result['success']) {
                        $banner_data = [
                            'title' => $title,
                            'description' => $description,
                            'image_path' => $upload_result['file_path'],
                            'link_type' => $link_type,
                            'link_value' => $link_value,
                            'position' => $position,
                            'display_order' => $display_order,
                            'start_date' => $start_date,
                            'end_date' => $end_date
                        ];
                        
                        $result = $banner->create($banner_data);
                        
                        if ($result['success']) {
                            $success = 'Banner created successfully!';
                        } else {
                            $error = $result['message'];
                        }
                    } else {
                        $error = 'Failed to upload banner image: ' . $upload_result['message'];
                    }
                } else {
                    $error = 'Please select a banner image';
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                if ($banner->toggleStatus($id)) {
                    $success = 'Banner status updated successfully!';
                } else {
                    $error = 'Failed to update banner status';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if ($banner->delete($id)) {
                    $success = 'Banner deleted successfully!';
                } else {
                    $error = 'Failed to delete banner';
                }
                break;
        }
    }
}

// Get banners with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$banners = $banner->getAllForAdmin($limit, $offset);
$total_banners = $banner->getTotalCount();
$total_pages = ceil($total_banners / $limit);

// Get banner statistics
$banner_stats = $banner->getStats();

// Get products and categories for dropdown
$products = $product->getAll();
$categories = $category->getAll();

$page_title = "Banner Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Banner Management</h1>
            <p class="text-gray-600">Manage promotional banners and featured product displays</p>
        </div>
        <div>
            <button onclick="showCreateModal()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                <i class="fas fa-plus mr-2"></i>Create Banner
            </button>
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

    <!-- Banner Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-images text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Banners</h3>
                    <p class="text-3xl font-bold text-blue-600"><?= $banner_stats['total_banners'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-eye text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Active Banners</h3>
                    <p class="text-3xl font-bold text-green-600"><?= $banner_stats['active_banners'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Expired</h3>
                    <p class="text-3xl font-bold text-red-600"><?= $banner_stats['expired_banners'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-mouse-pointer text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Clicks</h3>
                    <p class="text-3xl font-bold text-purple-600"><?= number_format($banner_stats['total_clicks']) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Views</h3>
                    <p class="text-3xl font-bold text-orange-600"><?= number_format($banner_stats['total_views']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Banners List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($banners)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No banners yet. Create your first promotional banner to get started.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($banners as $banner_item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-16 w-24">
                                    <img class="h-16 w-24 object-cover rounded-lg" 
                                         src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($banner_item['title']) ?>">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($banner_item['title']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars(substr($banner_item['description'], 0, 50)) ?>...
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?= ucfirst($banner_item['link_type']) ?>
                                <?php if ($banner_item['link_name']): ?>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($banner_item['link_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?= ucfirst($banner_item['position']) ?>
                            </span>
                            <div class="text-xs text-gray-500">Order: <?= $banner_item['display_order'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($banner_item['start_date']): ?>
                            <div class="text-xs">Start: <?= date('M j, Y', strtotime($banner_item['start_date'])) ?></div>
                            <?php endif; ?>
                            <?php if ($banner_item['end_date']): ?>
                            <div class="text-xs">End: <?= date('M j, Y', strtotime($banner_item['end_date'])) ?></div>
                            <?php endif; ?>
                            <?php if (!$banner_item['start_date'] && !$banner_item['end_date']): ?>
                            <span class="text-gray-500">Always active</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="text-xs">Views: <?= number_format($banner_item['view_count']) ?></div>
                            <div class="text-xs">Clicks: <?= number_format($banner_item['click_count']) ?></div>
                            <?php if ($banner_item['view_count'] > 0): ?>
                            <div class="text-xs text-green-600">
                                CTR: <?= number_format(($banner_item['click_count'] / $banner_item['view_count']) * 100, 1) ?>%
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch ($banner_item['status']) {
                                    case 'active':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'expired':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    case 'scheduled':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= ucfirst($banner_item['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="previewBanner(<?= $banner_item['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900" title="Preview">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $banner_item['id'] ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                                        <i class="fas fa-toggle-<?= $banner_item['is_active'] ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this banner?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $banner_item['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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
                    <a href="?page=<?= $page - 1 ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= min($limit, $total_banners - $offset) ?></span> of 
                            <span class="font-medium"><?= $total_banners ?></span> banners
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
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

<!-- Create Banner Modal -->
<div id="create-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Banner</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Banner Title</label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="banner_image" class="block text-sm font-medium text-gray-700 mb-1">Banner Image</label>
                        <input type="file" id="banner_image" name="banner_image" accept="image/*" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-1">Recommended size: 1200x400px for hero banners, 300x250px for sidebar</p>
                    </div>
                    
                    <div>
                        <label for="link_type" class="block text-sm font-medium text-gray-700 mb-1">Link Type</label>
                        <select id="link_type" name="link_type" onchange="updateLinkOptions()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="none">No Link</option>
                            <option value="product">Link to Product</option>
                            <option value="category">Link to Category</option>
                            <option value="url">Custom URL</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="link_value" class="block text-sm font-medium text-gray-700 mb-1">Link Target</label>
                        <select id="link_value" name="link_value" style="display: none;"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </select>
                        <input type="text" id="link_value_text" name="link_value_text" style="display: none;" placeholder="Enter URL"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <select id="position" name="position"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="hero">Hero Banner</option>
                            <option value="sidebar">Sidebar</option>
                            <option value="footer">Footer</option>
                            <option value="category_top">Category Top</option>
                            <option value="popup">Popup</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date (Optional)</label>
                        <input type="datetime-local" id="start_date" name="start_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                        <input type="datetime-local" id="end_date" name="end_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeCreateModal()" 
                            class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Create Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Products and categories data for JavaScript
const products = <?= json_encode($products) ?>;
const categories = <?= json_encode($categories) ?>;

function showCreateModal() {
    document.getElementById('create-modal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('create-modal').classList.add('hidden');
}

function updateLinkOptions() {
    const linkType = document.getElementById('link_type').value;
    const linkValueSelect = document.getElementById('link_value');
    const linkValueText = document.getElementById('link_value_text');
    
    // Hide both initially
    linkValueSelect.style.display = 'none';
    linkValueText.style.display = 'none';
    linkValueSelect.name = '';
    linkValueText.name = '';
    
    if (linkType === 'product') {
        linkValueSelect.innerHTML = '<option value="">Select Product</option>';
        products.forEach(product => {
            linkValueSelect.innerHTML += `<option value="${product.id}">${product.name}</option>`;
        });
        linkValueSelect.style.display = 'block';
        linkValueSelect.name = 'link_value';
    } else if (linkType === 'category') {
        linkValueSelect.innerHTML = '<option value="">Select Category</option>';
        categories.forEach(category => {
            linkValueSelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
        linkValueSelect.style.display = 'block';
        linkValueSelect.name = 'link_value';
    } else if (linkType === 'url') {
        linkValueText.style.display = 'block';
        linkValueText.name = 'link_value';
    }
}

function previewBanner(bannerId) {
    // You can implement banner preview functionality here
    alert('Banner preview functionality can be implemented here');
}

// Close modal when clicking outside
document.getElementById('create-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 