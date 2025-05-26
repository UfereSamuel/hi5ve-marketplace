<?php
require_once '../config/config.php';
require_once '../classes/Blog.php';

$blog = new Blog();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_post':
                $published_at = null;
                if ($_POST['status'] === 'published') {
                    $published_at = $_POST['published_at'] ?: date('Y-m-d H:i:s');
                }
                
                $data = [
                    'title' => sanitizeInput($_POST['title']),
                    'slug' => $_POST['slug'] ? sanitizeInput($_POST['slug']) : $blog->generateSlug($_POST['title']),
                    'excerpt' => sanitizeInput($_POST['excerpt']),
                    'content' => $_POST['content'], // Allow HTML content
                    'featured_image' => sanitizeInput($_POST['featured_image']),
                    'meta_title' => sanitizeInput($_POST['meta_title']),
                    'meta_description' => sanitizeInput($_POST['meta_description']),
                    'status' => sanitizeInput($_POST['status']),
                    'author_id' => $_SESSION['user_id'],
                    'published_at' => $published_at
                ];
                
                $result = $blog->create($data);
                if ($result['success']) {
                    $success = 'Blog post created successfully!';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_post':
                $post_id = (int)$_POST['post_id'];
                
                $published_at = null;
                if ($_POST['status'] === 'published') {
                    $published_at = $_POST['published_at'] ?: date('Y-m-d H:i:s');
                }
                
                $data = [
                    'title' => sanitizeInput($_POST['title']),
                    'slug' => $_POST['slug'] ? sanitizeInput($_POST['slug']) : $blog->generateSlug($_POST['title'], $post_id),
                    'excerpt' => sanitizeInput($_POST['excerpt']),
                    'content' => $_POST['content'], // Allow HTML content
                    'featured_image' => sanitizeInput($_POST['featured_image']),
                    'meta_title' => sanitizeInput($_POST['meta_title']),
                    'meta_description' => sanitizeInput($_POST['meta_description']),
                    'status' => sanitizeInput($_POST['status']),
                    'published_at' => $published_at
                ];
                
                if ($blog->update($post_id, $data)) {
                    $success = 'Blog post updated successfully!';
                } else {
                    $error = 'Failed to update blog post';
                }
                break;
                
            case 'toggle_status':
                $post_id = (int)$_POST['post_id'];
                if ($blog->toggleStatus($post_id)) {
                    $success = 'Blog post status updated successfully!';
                } else {
                    $error = 'Failed to update blog post status';
                }
                break;
                
            case 'delete_post':
                $post_id = (int)$_POST['post_id'];
                $result = $blog->delete($post_id);
                
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

// Get blog posts
$posts = $blog->getAll($status_filter ?: null, $limit, $offset);
$total_posts = $blog->getTotalCount($status_filter ?: null);
$total_pages_count = ceil($total_posts / $limit);

// Get statistics
$stats = $blog->getStats();

$page_title = "Blog Management";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Blog Management</h1>
            <p class="text-gray-600">Create and manage blog posts</p>
        </div>
        <button onclick="toggleCreateForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Create Post
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-blog text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Posts</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_posts'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Published</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['published_posts'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-edit text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Drafts</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['draft_posts'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                    <i class="fas fa-archive text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Archived</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['archived_posts'] ?></p>
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
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['recent_posts'] ?></p>
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

    <!-- Create Post Form -->
    <div id="create-post-form" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Blog Post</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_post">
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Post Title *</label>
                    <input type="text" id="title" name="title" required
                           placeholder="e.g., Latest News and Updates"
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
                <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="3"
                          placeholder="Brief summary of the blog post..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="mb-6">
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Post Content *</label>
                <textarea id="content" name="content" rows="12" required
                          placeholder="Enter your blog post content here..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-1">Featured Image URL</label>
                    <input type="url" id="featured_image" name="featured_image"
                           placeholder="https://example.com/image.jpg"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select id="status" name="status" required onchange="togglePublishDate()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            
            <div id="publish-date-field" class="mb-6 hidden">
                <label for="published_at" class="block text-sm font-medium text-gray-700 mb-1">Publish Date & Time</label>
                <input type="datetime-local" id="published_at" name="published_at"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-500 mt-1">Leave empty to publish immediately</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title"
                           placeholder="SEO title for search engines"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                    <input type="text" id="meta_description" name="meta_description"
                           placeholder="Brief description for search engines"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Create Post
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
                <select id="status_filter" onchange="filterPosts()" 
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Posts</option>
                    <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="archived" <?= $status_filter === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Posts List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Blog Posts</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No blog posts found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <?php if ($post['featured_image']): ?>
                                <div class="flex-shrink-0 h-12 w-12">
                                    <img class="h-12 w-12 rounded-lg object-cover" src="<?= htmlspecialchars($post['featured_image']) ?>" alt="">
                                </div>
                                <div class="ml-4">
                                <?php else: ?>
                                <div>
                                <?php endif; ?>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($post['title']) ?></div>
                                    <?php if ($post['excerpt']): ?>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($post['excerpt'], 0, 60)) ?>...</div>
                                    <?php endif; ?>
                                    <div class="text-xs text-blue-600">
                                        <a href="/mart3/blog/<?= htmlspecialchars($post['slug']) ?>" target="_blank" class="hover:text-blue-800">
                                            /blog/<?= htmlspecialchars($post['slug']) ?>
                                            <i class="fas fa-external-link-alt ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch($post['status']) {
                                    case 'published': echo 'bg-green-100 text-green-800'; break;
                                    case 'draft': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'archived': echo 'bg-gray-100 text-gray-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= ucfirst($post['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($post['published_at']): ?>
                            <div><?= date('M j, Y', strtotime($post['published_at'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($post['published_at'])) ?></div>
                            <?php else: ?>
                            <span class="text-gray-400">Not published</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?= date('M j, Y', strtotime($post['created_at'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($post['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editPost(<?= $post['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Edit Post">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to change this post status?')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this blog post?')">
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete Post">
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
                        Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $limit, $total_posts) ?></span> of <span class="font-medium"><?= $total_posts ?></span> results
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

<!-- Edit Post Modal -->
<div id="edit-post-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Edit Blog Post</h2>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="edit-post-form" method="POST" action="">
                    <input type="hidden" name="action" value="update_post">
                    <input type="hidden" id="edit_post_id" name="post_id">
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-1">Post Title *</label>
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
                        <label for="edit_excerpt" class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                        <textarea id="edit_excerpt" name="excerpt" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label for="edit_content" class="block text-sm font-medium text-gray-700 mb-1">Post Content *</label>
                        <textarea id="edit_content" name="content" rows="12" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_featured_image" class="block text-sm font-medium text-gray-700 mb-1">Featured Image URL</label>
                            <input type="url" id="edit_featured_image" name="featured_image"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select id="edit_status" name="status" required onchange="toggleEditPublishDate()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="edit-publish-date-field" class="mb-6 hidden">
                        <label for="edit_published_at" class="block text-sm font-medium text-gray-700 mb-1">Publish Date & Time</label>
                        <input type="datetime-local" id="edit_published_at" name="published_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" id="edit_meta_title" name="meta_title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label for="edit_meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <input type="text" id="edit_meta_description" name="meta_description"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            <i class="fas fa-save mr-2"></i>Update Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const posts = <?= json_encode($posts) ?>;

function toggleCreateForm() {
    const form = document.getElementById('create-post-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('title').focus();
    }
}

function editPost(postId) {
    const post = posts.find(p => p.id == postId);
    if (!post) return;
    
    document.getElementById('edit_post_id').value = post.id;
    document.getElementById('edit_title').value = post.title;
    document.getElementById('edit_slug').value = post.slug;
    document.getElementById('edit_excerpt').value = post.excerpt || '';
    document.getElementById('edit_content').value = post.content;
    document.getElementById('edit_featured_image').value = post.featured_image || '';
    document.getElementById('edit_meta_title').value = post.meta_title || '';
    document.getElementById('edit_meta_description').value = post.meta_description || '';
    document.getElementById('edit_status').value = post.status;
    
    if (post.published_at) {
        const date = new Date(post.published_at);
        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        document.getElementById('edit_published_at').value = localDate.toISOString().slice(0, 16);
    }
    
    toggleEditPublishDate();
    document.getElementById('edit-post-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-post-modal').classList.add('hidden');
}

function filterPosts() {
    const status = document.getElementById('status_filter').value;
    window.location.href = '?status=' + status;
}

function togglePublishDate() {
    const status = document.getElementById('status').value;
    const field = document.getElementById('publish-date-field');
    if (status === 'published') {
        field.classList.remove('hidden');
    } else {
        field.classList.add('hidden');
    }
}

function toggleEditPublishDate() {
    const status = document.getElementById('edit_status').value;
    const field = document.getElementById('edit-publish-date-field');
    if (status === 'published') {
        field.classList.remove('hidden');
    } else {
        field.classList.add('hidden');
    }
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
document.getElementById('edit-post-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 