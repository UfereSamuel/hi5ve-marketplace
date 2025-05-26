<?php
/**
 * Debug File Upload Issues
 */

echo "<h2>File Upload Debug Information</h2>\n";

// Test 1: Check upload directory paths
echo "<h3>1. Directory Paths</h3>\n";
$upload_dir = __DIR__ . '/uploads/';
$products_dir = $upload_dir . 'products/';

echo "Current directory: " . __DIR__ . "\n<br>";
echo "Upload directory: " . $upload_dir . "\n<br>";
echo "Products directory: " . $products_dir . "\n<br>";

// Test 2: Check if directories exist
echo "<h3>2. Directory Existence</h3>\n";
echo "Upload dir exists: " . (is_dir($upload_dir) ? 'YES' : 'NO') . "\n<br>";
echo "Products dir exists: " . (is_dir($products_dir) ? 'YES' : 'NO') . "\n<br>";

// Test 3: Check permissions
echo "<h3>3. Directory Permissions</h3>\n";
if (is_dir($upload_dir)) {
    $perms = fileperms($upload_dir);
    echo "Upload dir permissions: " . substr(sprintf('%o', $perms), -4) . "\n<br>";
    echo "Upload dir writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "\n<br>";
}

if (is_dir($products_dir)) {
    $perms = fileperms($products_dir);
    echo "Products dir permissions: " . substr(sprintf('%o', $perms), -4) . "\n<br>";
    echo "Products dir writable: " . (is_writable($products_dir) ? 'YES' : 'NO') . "\n<br>";
}

// Test 4: PHP Upload Settings
echo "<h3>4. PHP Upload Settings</h3>\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n<br>";
echo "post_max_size: " . ini_get('post_max_size') . "\n<br>";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'default') . "\n<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n<br>";

// Test 5: Try to create a test file
echo "<h3>5. File Creation Test</h3>\n";
$test_file = $products_dir . 'test_' . time() . '.txt';
$test_content = "Test file created at " . date('Y-m-d H:i:s');

if (file_put_contents($test_file, $test_content)) {
    echo "✓ Successfully created test file: " . basename($test_file) . "\n<br>";
    // Clean up
    unlink($test_file);
    echo "✓ Successfully deleted test file\n<br>";
} else {
    echo "✗ Failed to create test file\n<br>";
    echo "Error: " . error_get_last()['message'] . "\n<br>";
}

// Test 6: Simulate file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>6. File Upload Test Results</h3>\n";
    
    $file = $_FILES['test_file'];
    
    echo "File details:\n<br>";
    echo "- Name: " . $file['name'] . "\n<br>";
    echo "- Type: " . $file['type'] . "\n<br>";
    echo "- Size: " . $file['size'] . " bytes\n<br>";
    echo "- Error: " . $file['error'] . "\n<br>";
    echo "- Tmp name: " . $file['tmp_name'] . "\n<br>";
    
    // Check if tmp file exists
    echo "- Tmp file exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO') . "\n<br>";
    echo "- Tmp file readable: " . (is_readable($file['tmp_name']) ? 'YES' : 'NO') . "\n<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $target_file = $products_dir . 'debug_' . time() . '_' . $file['name'];
        echo "- Target file: " . $target_file . "\n<br>";
        
        // Try the move
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            echo "✓ File upload successful!\n<br>";
            echo "- File saved to: " . $target_file . "\n<br>";
            echo "- File size: " . filesize($target_file) . " bytes\n<br>";
            
            // Clean up
            unlink($target_file);
        } else {
            echo "✗ File upload failed!\n<br>";
            echo "- Last error: " . (error_get_last()['message'] ?? 'No error message') . "\n<br>";
            
            // Additional debugging
            echo "- Target directory writable: " . (is_writable(dirname($target_file)) ? 'YES' : 'NO') . "\n<br>";
            echo "- Target file path length: " . strlen($target_file) . "\n<br>";
        }
    } else {
        echo "✗ Upload error code: " . $file['error'] . "\n<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h3>Test File Upload</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="test_file" accept="image/*" required>
        <button type="submit">Upload Test File</button>
    </form>
    
    <hr>
    
    <h3>Manual Directory Creation</h3>
    <?php
    if (isset($_GET['create_dirs'])) {
        echo "Creating directories...\n<br>";
        
        if (!is_dir($upload_dir)) {
            if (mkdir($upload_dir, 0755, true)) {
                echo "✓ Created upload directory\n<br>";
            } else {
                echo "✗ Failed to create upload directory\n<br>";
            }
        }
        
        if (!is_dir($products_dir)) {
            if (mkdir($products_dir, 0755, true)) {
                echo "✓ Created products directory\n<br>";
            } else {
                echo "✗ Failed to create products directory\n<br>";
            }
        }
        
        // Set permissions
        chmod($upload_dir, 0755);
        chmod($products_dir, 0755);
        echo "✓ Set directory permissions\n<br>";
    }
    ?>
    <a href="?create_dirs=1">Create/Fix Directories</a>
</body>
</html> 