<?php
require_once '../config/config.php';
require_once '../classes/FileUpload.php';

$fileUpload = new FileUpload();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_file':
                $file_id = (int)$_POST['file_id'];
                $result = $fileUpload->delete($file_id);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Pagination and filtering
$limit = 20;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $limit;
$type_filter = $_GET['type'] ?? '';

// Get files
$files = $fileUpload->getAll($type_filter ?: null, $limit, $offset);

// Add URLs and format file sizes
foreach ($files as &$file) {
    $file['url'] = $fileUpload->getFileUrl($file['file_path']);
    $file['size_formatted'] = formatFileSize($file['file_size']);
}

// Calculate total for pagination (simplified)
$total_files = count($fileUpload->getAll($type_filter ?: null, 1000, 0)); // Get large number for count
$total_pages_count = ceil($total_files / $limit);

$page_title = "File Manager";
include 'includes/admin_header.php';

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getFileIcon($mime_type) {
    if (strpos($mime_type, 'image/') === 0) {
        return 'fas fa-image text-green-600';
    } elseif (strpos($mime_type, 'video/') === 0) {
        return 'fas fa-video text-blue-600';
    } elseif (strpos($mime_type, 'audio/') === 0) {
        return 'fas fa-music text-purple-600';
    } elseif (strpos($mime_type, 'application/pdf') === 0) {
        return 'fas fa-file-pdf text-red-600';
    } elseif (strpos($mime_type, 'text/') === 0) {
        return 'fas fa-file-alt text-gray-600';
    } else {
        return 'fas fa-file text-gray-600';
    }
}
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">File Manager</h1>
            <p class="text-gray-600">Manage uploaded files and media</p>
        </div>
        <button onclick="toggleUploadForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-upload mr-2"></i>Upload Files
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

    <!-- Upload Form -->
    <div id="upload-form" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Upload Files</h2>
        
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="upload_type" class="block text-sm font-medium text-gray-700 mb-1">Upload Type</label>
                <select id="upload_type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="other">General</option>
                    <option value="product">Product Images</option>
                    <option value="category">Category Images</option>
                    <option value="blog">Blog Images</option>
                    <option value="page">Page Images</option>
                    <option value="setting">Setting Files</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload Method</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="upload_method" value="single" checked class="mr-2">
                        Single File
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="upload_method" value="multiple" class="mr-2">
                        Multiple Files
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Single File Upload -->
        <div id="single-upload" class="mb-6">
            <label for="single_file" class="block text-sm font-medium text-gray-700 mb-1">Select File</label>
            <input type="file" id="single_file" accept="image/*"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        
        <!-- Multiple Files Upload -->
        <div id="multiple-upload" class="mb-6 hidden">
            <label for="multiple_files" class="block text-sm font-medium text-gray-700 mb-1">Select Files</label>
            <input type="file" id="multiple_files" multiple accept="image/*"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        
        <!-- Upload Progress -->
        <div id="upload-progress" class="mb-6 hidden">
            <div class="bg-gray-200 rounded-full h-2">
                <div id="progress-bar" class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <p id="progress-text" class="text-sm text-gray-600 mt-2">Uploading...</p>
        </div>
        
        <div class="flex space-x-4">
            <button onclick="uploadFiles()" id="upload-btn" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                <i class="fas fa-upload mr-2"></i>Upload
            </button>
            <button type="button" onclick="toggleUploadForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                <i class="fas fa-times mr-2"></i>Cancel
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <label for="type_filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Type</label>
                <select id="type_filter" onchange="filterFiles()" 
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Files</option>
                    <option value="product" <?= $type_filter === 'product' ? 'selected' : '' ?>>Product Images</option>
                    <option value="category" <?= $type_filter === 'category' ? 'selected' : '' ?>>Category Images</option>
                    <option value="blog" <?= $type_filter === 'blog' ? 'selected' : '' ?>>Blog Images</option>
                    <option value="page" <?= $type_filter === 'page' ? 'selected' : '' ?>>Page Images</option>
                    <option value="setting" <?= $type_filter === 'setting' ? 'selected' : '' ?>>Setting Files</option>
                    <option value="other" <?= $type_filter === 'other' ? 'selected' : '' ?>>Other Files</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button onclick="refreshFiles()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Files Grid -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Files (<?= count($files) ?>)</h2>
        </div>
        
        <?php if (empty($files)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No files found</p>
            <p class="text-gray-400">Upload some files to get started</p>
        </div>
        <?php else: ?>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                <?php foreach ($files as $file): ?>
                <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition duration-300 group">
                    <div class="aspect-square mb-3 relative">
                        <?php if (strpos($file['mime_type'], 'image/') === 0): ?>
                        <img src="<?= htmlspecialchars($file['url']) ?>" 
                             alt="<?= htmlspecialchars($file['original_name']) ?>"
                             class="w-full h-full object-cover rounded-lg cursor-pointer"
                             onclick="viewFile('<?= htmlspecialchars($file['url']) ?>', '<?= htmlspecialchars($file['original_name']) ?>')">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-white rounded-lg border-2 border-dashed border-gray-300">
                            <i class="<?= getFileIcon($file['mime_type']) ?> text-4xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <!-- File Actions -->
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="flex space-x-1">
                                <button onclick="copyFileUrl('<?= htmlspecialchars($file['url']) ?>')" 
                                        class="bg-blue-600 text-white p-1 rounded text-xs hover:bg-blue-700" 
                                        title="Copy URL">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this file?')">
                                    <input type="hidden" name="action" value="delete_file">
                                    <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                    <button type="submit" 
                                            class="bg-red-600 text-white p-1 rounded text-xs hover:bg-red-700" 
                                            title="Delete File">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-900 truncate" title="<?= htmlspecialchars($file['original_name']) ?>">
                            <?= htmlspecialchars($file['original_name']) ?>
                        </p>
                        <p class="text-xs text-gray-500"><?= $file['size_formatted'] ?></p>
                        <p class="text-xs text-gray-400 capitalize"><?= htmlspecialchars($file['upload_type']) ?></p>
                        <p class="text-xs text-gray-400"><?= date('M j, Y', strtotime($file['created_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages_count > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($current_page > 1): ?>
                <a href="?page=<?= $current_page - 1 ?>&type=<?= $type_filter ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                <?php endif; ?>
                <?php if ($current_page < $total_pages_count): ?>
                <a href="?page=<?= $current_page + 1 ?>&type=<?= $type_filter ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $limit, $total_files) ?></span> of <span class="font-medium"><?= $total_files ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php for ($i = 1; $i <= $total_pages_count; $i++): ?>
                        <a href="?page=<?= $i ?>&type=<?= $type_filter ?>" 
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
        <?php endif; ?>
    </div>
</div>

<!-- File Viewer Modal -->
<div id="file-viewer-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 id="file-viewer-title" class="text-xl font-semibold text-gray-800">File Preview</h2>
                    <button onclick="closeFileViewer()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="text-center">
                    <img id="file-viewer-image" src="" alt="" class="max-w-full max-h-96 mx-auto rounded-lg">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleUploadForm() {
    const form = document.getElementById('upload-form');
    form.classList.toggle('hidden');
}

function filterFiles() {
    const type = document.getElementById('type_filter').value;
    window.location.href = '?type=' + type;
}

function refreshFiles() {
    window.location.reload();
}

function viewFile(url, name) {
    document.getElementById('file-viewer-title').textContent = name;
    document.getElementById('file-viewer-image').src = url;
    document.getElementById('file-viewer-modal').classList.remove('hidden');
}

function closeFileViewer() {
    document.getElementById('file-viewer-modal').classList.add('hidden');
}

function copyFileUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        // Show temporary success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        btn.classList.add('bg-green-600');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 1000);
    });
}

// Upload method toggle
document.querySelectorAll('input[name="upload_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const singleUpload = document.getElementById('single-upload');
        const multipleUpload = document.getElementById('multiple-upload');
        
        if (this.value === 'single') {
            singleUpload.classList.remove('hidden');
            multipleUpload.classList.add('hidden');
        } else {
            singleUpload.classList.add('hidden');
            multipleUpload.classList.remove('hidden');
        }
    });
});

function uploadFiles() {
    const uploadMethod = document.querySelector('input[name="upload_method"]:checked').value;
    const uploadType = document.getElementById('upload_type').value;
    const progressDiv = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const uploadBtn = document.getElementById('upload-btn');
    
    let files;
    if (uploadMethod === 'single') {
        const fileInput = document.getElementById('single_file');
        files = fileInput.files;
    } else {
        const fileInput = document.getElementById('multiple_files');
        files = fileInput.files;
    }
    
    if (files.length === 0) {
        alert('Please select at least one file');
        return;
    }
    
    // Show progress
    progressDiv.classList.remove('hidden');
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...';
    
    const formData = new FormData();
    formData.append('action', uploadMethod === 'single' ? 'upload_single' : 'upload_multiple');
    formData.append('type', uploadType);
    
    if (uploadMethod === 'single') {
        formData.append('file', files[0]);
    } else {
        for (let i = 0; i < files.length; i++) {
            formData.append('files[name][]', files[i].name);
            formData.append('files[type][]', files[i].type);
            formData.append('files[size][]', files[i].size);
            formData.append('files[tmp_name][]', files[i]);
        }
    }
    
    fetch('../ajax/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        progressBar.style.width = '100%';
        
        if (data.success) {
            progressText.textContent = 'Upload completed successfully!';
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            progressText.textContent = 'Upload failed: ' + data.message;
            progressBar.classList.remove('bg-green-600');
            progressBar.classList.add('bg-red-600');
        }
    })
    .catch(error => {
        progressText.textContent = 'Upload failed: ' + error.message;
        progressBar.classList.remove('bg-green-600');
        progressBar.classList.add('bg-red-600');
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Upload';
    });
}

// Close modal when clicking outside
document.getElementById('file-viewer-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFileViewer();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 