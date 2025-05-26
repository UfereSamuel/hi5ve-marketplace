<?php
/**
 * Hi5ve MarketPlace Phase 3 Setup Script
 * Applies advanced database schema and features
 */

// Security check
$setup_password = 'hi5ve_phase3_2024';
$provided_password = $_POST['password'] ?? $_GET['password'] ?? '';

if ($provided_password !== $setup_password) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Hi5ve MarketPlace - Phase 3 Setup</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
            <div class="text-center mb-6">
                <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-16 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Phase 3 Setup</h1>
                <p class="text-gray-600 mt-2">Enter the setup password to continue</p>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Setup Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Enter setup password">
                </div>
                
                <button type="submit" 
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-300">
                    <i class="fas fa-unlock mr-2"></i>Continue Setup
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-500">
                <p><i class="fas fa-info-circle mr-1"></i>This will upgrade your database to Phase 3</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Include database configuration
require_once 'config/database.php';

// Initialize variables
$success_messages = [];
$error_messages = [];
$warnings = [];

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
    
    // Define SQL files to execute in order
    $sql_files = [
        'database/phase3_schema_fixed.sql' => 'Create new tables',
        'database/phase3_alter_tables_fixed.sql' => 'Add new columns to existing tables',
        'database/phase3_indexes_fixed.sql' => 'Add indexes and foreign keys',
        'database/phase3_sample_data.sql' => 'Insert sample data',
        'database/phase3_update_data_fixed.sql' => 'Update existing data'
    ];
    
    $total_success = 0;
    $total_errors = 0;
    
    // Execute each SQL file
    foreach ($sql_files as $file_path => $description) {
        if (!file_exists($file_path)) {
            $warnings[] = "File not found: {$file_path}";
            continue;
        }
        
        $success_messages[] = "Processing: {$description}";
        
        $sql_content = file_get_contents($file_path);
        if ($sql_content === false) {
            $error_messages[] = "Failed to read file: {$file_path}";
            continue;
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
            }
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            try {
                $conn->exec($statement);
                $total_success++;
                
                // Log specific successful operations
                if (preg_match('/CREATE TABLE.*`(\w+)`/', $statement, $matches)) {
                    $success_messages[] = "✓ Created table: {$matches[1]}";
                } elseif (preg_match('/ALTER TABLE.*`(\w+)`.*ADD COLUMN.*`(\w+)`/', $statement, $matches)) {
                    $success_messages[] = "✓ Added column {$matches[2]} to table {$matches[1]}";
                } elseif (preg_match('/ALTER TABLE.*`(\w+)`.*ADD INDEX.*`(\w+)`/', $statement, $matches)) {
                    $success_messages[] = "✓ Added index {$matches[2]} to table {$matches[1]}";
                } elseif (preg_match('/INSERT.*INTO.*`(\w+)`/', $statement, $matches)) {
                    $success_messages[] = "✓ Inserted sample data into: {$matches[1]}";
                } elseif (preg_match('/UPDATE.*`(\w+)`/', $statement, $matches)) {
                    $success_messages[] = "✓ Updated existing data in: {$matches[1]}";
                }
                
            } catch (PDOException $e) {
                $total_errors++;
                
                // Check if it's a harmless error
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate') !== false ||
                    strpos($e->getMessage(), 'duplicate column name') !== false) {
                    $warnings[] = "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...";
                } else {
                    $error_messages[] = "✗ Error: " . substr($statement, 0, 50) . "... - " . $e->getMessage();
                }
            }
        }
    }
    
    // Verify critical tables exist
    $critical_tables = [
        'wishlist', 'product_variants', 'product_gallery', 'product_views', 
        'recently_viewed', 'wishlist_shares', 'coupons', 'coupon_usage'
    ];
    
    $missing_tables = [];
    $existing_tables = [];
    
    foreach ($critical_tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        } else {
            $existing_tables[] = $table;
        }
    }
    
    if (!empty($existing_tables)) {
        $success_messages[] = "✓ Critical tables verified: " . implode(', ', $existing_tables);
    }
    
    if (!empty($missing_tables)) {
        $error_messages[] = "✗ Critical tables missing: " . implode(', ', $missing_tables);
    }
    
    // Check if new columns were added
    $column_checks = [
        'products' => ['view_count', 'slug', 'meta_title', 'brand'],
        'users' => ['phone_verified', 'email_verified', 'last_login'],
        'orders' => ['coupon_id', 'discount_amount', 'tracking_number']
    ];
    
    foreach ($column_checks as $table => $columns) {
        try {
            $stmt = $conn->prepare("DESCRIBE {$table}");
            $stmt->execute();
            $existing_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            foreach ($columns as $column) {
                if (in_array($column, $existing_columns)) {
                    $success_messages[] = "✓ Column {$table}.{$column} exists";
                } else {
                    $error_messages[] = "✗ Column {$table}.{$column} missing";
                }
            }
        } catch (PDOException $e) {
            $error_messages[] = "✗ Error checking table {$table}: " . $e->getMessage();
        }
    }
    
    // Update existing products with slugs if needed
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE slug IS NULL OR slug = ''");
        $stmt->execute();
        $null_slugs = $stmt->fetch()['count'];
        
        if ($null_slugs > 0) {
            $conn->exec("UPDATE products SET slug = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '(', ''), ')', '')) WHERE slug IS NULL OR slug = ''");
            $success_messages[] = "✓ Updated {$null_slugs} product slugs";
        }
    } catch (PDOException $e) {
        $warnings[] = "⚠ Could not update product slugs: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $error_messages[] = "✗ Setup failed: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hi5ve MarketPlace - Phase 3 Setup Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8 text-center">
            <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-16 w-auto mx-auto mb-4">
            <h1 class="text-3xl font-bold text-gray-900">Phase 3 Setup Results</h1>
            <p class="text-gray-600 mt-2">Advanced features installation complete</p>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <i class="fas fa-check-circle text-3xl text-green-600 mb-3"></i>
                <h3 class="text-lg font-semibold text-green-800">Successful Operations</h3>
                <p class="text-2xl font-bold text-green-600"><?= $total_success ?></p>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-600 mb-3"></i>
                <h3 class="text-lg font-semibold text-yellow-800">Warnings</h3>
                <p class="text-2xl font-bold text-yellow-600"><?= count($warnings) ?></p>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-times-circle text-3xl text-red-600 mb-3"></i>
                <h3 class="text-lg font-semibold text-red-800">Errors</h3>
                <p class="text-2xl font-bold text-red-600"><?= count($error_messages) ?></p>
            </div>
        </div>

        <!-- Success Messages -->
        <?php if (!empty($success_messages)): ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-green-800 mb-4">
                <i class="fas fa-check-circle mr-2"></i>Successful Operations
            </h2>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                <?php foreach ($success_messages as $message): ?>
                <div class="flex items-center text-green-700 bg-green-50 p-2 rounded">
                    <span class="font-mono text-sm"><?= htmlspecialchars($message) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Warnings -->
        <?php if (!empty($warnings)): ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-yellow-800 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>Warnings
            </h2>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                <?php foreach ($warnings as $warning): ?>
                <div class="flex items-center text-yellow-700 bg-yellow-50 p-2 rounded">
                    <span class="font-mono text-sm"><?= htmlspecialchars($warning) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($error_messages)): ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-red-800 mb-4">
                <i class="fas fa-times-circle mr-2"></i>Errors
            </h2>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                <?php foreach ($error_messages as $error): ?>
                <div class="flex items-center text-red-700 bg-red-50 p-2 rounded">
                    <span class="font-mono text-sm"><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Phase 3 Features -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-rocket mr-2"></i>Phase 3 Features Installed
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <i class="fas fa-heart text-blue-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-blue-800">Wishlist System</h3>
                    <p class="text-sm text-blue-600">Save favorite products for later</p>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <i class="fas fa-palette text-green-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-green-800">Product Variants</h3>
                    <p class="text-sm text-green-600">Size, color, weight options</p>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <i class="fas fa-images text-purple-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-purple-800">Product Gallery</h3>
                    <p class="text-sm text-purple-600">Multiple product images</p>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <i class="fas fa-balance-scale text-yellow-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-yellow-800">Product Comparison</h3>
                    <p class="text-sm text-yellow-600">Compare up to 4 products</p>
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg">
                    <i class="fas fa-eye text-red-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-red-800">View Tracking</h3>
                    <p class="text-sm text-red-600">Product analytics & insights</p>
                </div>
                
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <i class="fas fa-tags text-indigo-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-indigo-800">Coupons & Discounts</h3>
                    <p class="text-sm text-indigo-600">Advanced promotion system</p>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-list-check mr-2"></i>Next Steps
            </h2>
            <div class="space-y-3">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span>Visit the <a href="index.php" class="text-blue-600 hover:underline">homepage</a> to see the updated interface</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span>Test the <a href="wishlist.php" class="text-blue-600 hover:underline">wishlist functionality</a> (requires login)</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span>Try the <a href="compare.php" class="text-blue-600 hover:underline">product comparison</a> feature</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span>Configure product variants in the <a href="admin/" class="text-blue-600 hover:underline">admin panel</a></span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                    <span class="text-yellow-700">Delete this setup file for security</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="text-center space-x-4">
            <a href="index.php" class="inline-flex items-center bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-300">
                <i class="fas fa-home mr-2"></i>Go to Homepage
            </a>
            <a href="admin/" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300">
                <i class="fas fa-cog mr-2"></i>Admin Panel
            </a>
        </div>

        <!-- Security Notice -->
        <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <i class="fas fa-shield-alt text-red-600 text-2xl mb-2"></i>
            <h3 class="font-semibold text-red-800 mb-2">Security Notice</h3>
            <p class="text-red-700">Please delete this setup file (setup_phase3.php) and the database files after successful installation for security reasons.</p>
        </div>
    </div>
</body>
</html> 