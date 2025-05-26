<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test File Upload - Hi5ve MarketPlace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Test File Upload - Debug Mode</h1>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
                require_once 'classes/SimpleFileUpload.php';
                
                $fileUpload = new SimpleFileUpload();
                $result = $fileUpload->upload($_FILES['test_image'], 'product');
                
                if ($result['success']) {
                    echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">';
                    echo '<h3 class="font-semibold">✓ Upload Successful!</h3>';
                    echo '<p>File: ' . htmlspecialchars($result['filename']) . '</p>';
                    echo '<p>Path: ' . htmlspecialchars($result['file_path']) . '</p>';
                    echo '<p>URL: <a href="' . htmlspecialchars($result['url']) . '" target="_blank" class="text-blue-600 underline">' . htmlspecialchars($result['url']) . '</a></p>';
                    echo '<img src="' . htmlspecialchars($result['url']) . '" alt="Uploaded image" class="mt-4 max-w-xs rounded-lg shadow-md">';
                    echo '</div>';
                } else {
                    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">';
                    echo '<h3 class="font-semibold">✗ Upload Failed!</h3>';
                    echo '<p>Error: ' . htmlspecialchars($result['message']) . '</p>';
                    echo '</div>';
                }
                
                // Show debug information
                if (isset($result['debug'])) {
                    echo '<div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-6">';
                    echo '<h3 class="font-semibold">🔍 Debug Information</h3>';
                    echo '<pre class="text-xs mt-2 overflow-auto">' . htmlspecialchars(print_r($result['debug'], true)) . '</pre>';
                    echo '</div>';
                }
                
                if (isset($result['last_error'])) {
                    echo '<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-6">';
                    echo '<h3 class="font-semibold">⚠ Last PHP Error</h3>';
                    echo '<pre class="text-xs mt-2">' . htmlspecialchars(print_r($result['last_error'], true)) . '</pre>';
                    echo '</div>';
                }
            }
            ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="test_image" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Image File
                    </label>
                    <input type="file" 
                           id="test_image" 
                           name="test_image" 
                           accept="image/*" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="text-sm text-gray-500 mt-1">
                        Allowed: JPEG, PNG, GIF, WebP (max 5MB)
                    </p>
                </div>
                
                <button type="submit" 
                        class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-upload mr-2"></i>Test Upload
                </button>
            </form>
            
            <div class="grid md:grid-cols-2 gap-6 mt-8">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold text-gray-800 mb-2">System Information:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li><strong>Upload Directory:</strong> <?= __DIR__ . '/uploads/' ?></li>
                        <li><strong>Products Directory:</strong> <?= __DIR__ . '/uploads/products/' ?></li>
                        <li><strong>Directory Writable:</strong> <?= is_writable(__DIR__ . '/uploads/') ? 'Yes' : 'No' ?></li>
                        <li><strong>Products Writable:</strong> <?= is_writable(__DIR__ . '/uploads/products/') ? 'Yes' : 'No' ?></li>
                        <li><strong>Max Upload Size:</strong> <?= ini_get('upload_max_filesize') ?></li>
                        <li><strong>Max Post Size:</strong> <?= ini_get('post_max_size') ?></li>
                    </ul>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold text-gray-800 mb-2">Directory Test:</h3>
                    <?php
                    $upload_dir = __DIR__ . '/uploads/';
                    $products_dir = $upload_dir . 'products/';
                    
                    echo '<ul class="text-sm text-gray-600 space-y-1">';
                    echo '<li><strong>Upload dir exists:</strong> ' . (is_dir($upload_dir) ? 'Yes' : 'No') . '</li>';
                    echo '<li><strong>Products dir exists:</strong> ' . (is_dir($products_dir) ? 'Yes' : 'No') . '</li>';
                    
                    if (is_dir($upload_dir)) {
                        $perms = fileperms($upload_dir);
                        echo '<li><strong>Upload permissions:</strong> ' . substr(sprintf('%o', $perms), -4) . '</li>';
                    }
                    
                    if (is_dir($products_dir)) {
                        $perms = fileperms($products_dir);
                        echo '<li><strong>Products permissions:</strong> ' . substr(sprintf('%o', $perms), -4) . '</li>';
                    }
                    
                    // Test file creation
                    $test_file = $products_dir . 'test_' . time() . '.txt';
                    if (file_put_contents($test_file, 'test')) {
                        echo '<li><strong>File creation test:</strong> ✓ Success</li>';
                        unlink($test_file);
                    } else {
                        echo '<li><strong>File creation test:</strong> ✗ Failed</li>';
                    }
                    echo '</ul>';
                    ?>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="admin/products.php" class="text-blue-600 hover:underline">
                    ← Back to Products Admin
                </a>
            </div>
        </div>
    </div>
</body>
</html> 