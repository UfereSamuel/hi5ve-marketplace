<?php
require_once '../config/config.php';
require_once '../classes/Page.php';

$page = new Page();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_page':
                $data = [
                    'title' => sanitizeInput($_POST['title']),
                    'slug' => $_POST['slug'] ? sanitizeInput($_POST['slug']) : $page->generateSlug($_POST['title']),
                    'content' => $_POST['content'], // Allow HTML content
                    'meta_title' => sanitizeInput($_POST['meta_title']),
                    'meta_description' => sanitizeInput($_POST['meta_description']),
                    'status' => sanitizeInput($_POST['status']),
                    'created_by' => $_SESSION['user_id']
                ];
                
                $result = $page->create($data);
                if ($result['success']) {
                    $success = 'Page created successfully!';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_page':
                $page_id = (int)$_POST['page_id'];
                $data = [
                    'title' => sanitizeInput($_POST['title']),
                    'slug' => $_POST['slug'] ? sanitizeInput($_POST['slug']) : $page->generateSlug($_POST['title'], $page_id),
                    'content' => $_POST['content'], // Allow HTML content
                    'meta_title' => sanitizeInput($_POST['meta_title']),
                    'meta_description' => sanitizeInput($_POST['meta_description']),
                    'status' => sanitizeInput($_POST['status'])
                ];
                
                if ($page->update($page_id, $data)) {
                    $success = 'Page updated successfully!';
                } else {
                    $error = 'Failed to update page';
                }
                break;
                
            case 'toggle_status':
                $page_id = (int)$_POST['page_id'];
                if ($page->toggleStatus($page_id)) {
                    $success = 'Page status updated successfully!';
                } else {
                    $error = 'Failed to update page status';
                }
                break;
                
            case 'delete_page':
                $page_id = (int)$_POST['page_id'];
                $result = $page->delete($page_id);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Pagination
$limit = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';

// Get pages
$pages = $page->getAll($status_filter ?: null, $limit, $offset);
$total_pages = $page->getTotalCount($status_filter ?: null);
$total_pages_count = ceil($total_pages / $limit);

// Get statistics
$stats = $page->getStats();

$page_title = "Pages Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Pages Management</h1>
            <p class="text-gray-600">Create and manage website pages</p>
        </div>
        <button onclick="toggleCreateForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Create Page
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-file-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Pages</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_pages'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Pages</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['active_pages'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                    <i class="fas fa-pause-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Inactive Pages</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['inactive_pages'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Recent (30d)</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['recent_pages'] ?></p>
                </div>
            </div>
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

    <!-- Create Page Form -->
    <div id="create-page-form" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Page</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_page">
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Page Title *</label>
                    <input type="text" id="title" name="title" required
                           placeholder="e.g., About Us"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                    <input type="text" id="slug" name="slug"
                           placeholder="auto-generated from title"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from title</p>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Page Content *</label>
                <textarea id="content" name="content" rows="10" required
                          placeholder="Enter your page content here..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title"
                           placeholder="SEO title for search engines"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select id="status" name="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                <textarea id="meta_description" name="meta_description" rows="3"
                          placeholder="Brief description for search engines (160 characters max)"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Create Page
                </button>
                <button type="button" onclick="toggleCreateForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select id="status_filter" onchange="filterPages()" 
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Pages</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Pages List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Pages List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No pages found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pages as $page_item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($page_item['title']) ?></div>
                                <?php if ($page_item['meta_description']): ?>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($page_item['meta_description'], 0, 60)) ?>...</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <a href="/mart3/page/<?= htmlspecialchars($page_item['slug']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    /<?= htmlspecialchars($page_item['slug']) ?>
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?= $page_item['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($page_item['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($page_item['first_name'] . ' ' . $page_item['last_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?= date('M j, Y', strtotime($page_item['created_at'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($page_item['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editPage(<?= $page_item['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Edit Page">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to toggle this page status?')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="page_id" value="<?= $page_item['id'] ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                                        <i class="fas fa-toggle-<?= $page_item['status'] === 'active' ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this page?')">
                                    <input type="hidden" name="action" value="delete_page">
                                    <input type="hidden" name="page_id" value="<?= $page_item['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete Page">
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
        <?php if ($total_pages_count > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($current_page > 1): ?>
                <a href="?page=<?= $current_page - 1 ?>&status=<?= $status_filter ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                <?php endif; ?>
                <?php if ($current_page < $total_pages_count): ?>
                <a href="?page=<?= $current_page + 1 ?>&status=<?= $status_filter ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $limit, $total_pages) ?></span> of <span class="font-medium"><?= $total_pages ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php for ($i = 1; $i <= $total_pages_count; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= $status_filter ?>" 
                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                           <?= $i === $current_page ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Page Modal -->
<div id="edit-page-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Edit Page</h2>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="edit-page-form" method="POST" action="">
                    <input type="hidden" name="action" value="update_page">
                    <input type="hidden" id="edit_page_id" name="page_id">
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-1">Page Title *</label>
                            <input type="text" id="edit_title" name="title" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="edit_slug" class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                            <input type="text" id="edit_slug" name="slug"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="edit_content" class="block text-sm font-medium text-gray-700 mb-1">Page Content *</label>
                        <textarea id="edit_content" name="content" rows="10" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" id="edit_meta_title" name="meta_title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select id="edit_status" name="status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="edit_meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                        <textarea id="edit_meta_description" name="meta_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            <i class="fas fa-save mr-2"></i>Update Page
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const pages = <?= json_encode($pages) ?>;

function toggleCreateForm() {
    const form = document.getElementById('create-page-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('title').focus();
    }
}

function editPage(pageId) {
    const pageData = pages.find(p => p.id == pageId);
    if (!pageData) return;
    
    document.getElementById('edit_page_id').value = pageData.id;
    document.getElementById('edit_title').value = pageData.title;
    document.getElementById('edit_slug').value = pageData.slug;
    document.getElementById('edit_content').value = pageData.content;
    document.getElementById('edit_meta_title').value = pageData.meta_title || '';
    document.getElementById('edit_meta_description').value = pageData.meta_description || '';
    document.getElementById('edit_status').value = pageData.status;
    
    document.getElementById('edit-page-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-page-modal').classList.add('hidden');
}

function filterPages() {
    const status = document.getElementById('status_filter').value;
    window.location.href = '?status=' + status;
}

// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
        .replace(/[^a-z0-9 -]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim('-');
    document.getElementById('slug').placeholder = slug || 'auto-generated from title';
});

// Close modal when clicking outside
document.getElementById('edit-page-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 