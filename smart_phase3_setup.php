<?php
/**
 * Smart Phase 3 Setup - Checks existing structure before making changes
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
        <title>Hi5ve MarketPlace - Smart Phase 3 Setup</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
            <div class="text-center mb-6">
                <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-16 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Smart Phase 3 Setup</h1>
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
    
    $total_success = 0;
    $total_errors = 0;
    
    // Helper function to check if table exists
    function tableExists($conn, $table_name) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table_name]);
        return $stmt->rowCount() > 0;
    }
    
    // Helper function to check if column exists
    function columnExists($conn, $table_name, $column_name) {
        try {
            $stmt = $conn->query("DESCRIBE {$table_name}");
            $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            return in_array($column_name, $columns);
        } catch (Exception $e) {
            return false;
        }
    }
    
    // 1. Create Phase 3 tables
    $success_messages[] = "=== Creating Phase 3 Tables ===";
    
    $tables_to_create = [
        'wishlist' => "CREATE TABLE `wishlist` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_wishlist` (`user_id`, `product_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_product_id` (`product_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'product_variants' => "CREATE TABLE `product_variants` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `variant_type` varchar(50) NOT NULL,
            `variant_value` varchar(100) NOT NULL,
            `price_modifier` decimal(10,2) DEFAULT 0.00,
            `stock_quantity` int(11) DEFAULT 0,
            `sku` varchar(100) DEFAULT NULL,
            `is_default` tinyint(1) DEFAULT 0,
            `sort_order` int(11) DEFAULT 0,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_product_id` (`product_id`),
            KEY `idx_variant_type` (`variant_type`),
            KEY `idx_status` (`status`),
            FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'product_gallery' => "CREATE TABLE `product_gallery` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `image_path` varchar(255) NOT NULL,
            `alt_text` varchar(255) DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `is_primary` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_product_id` (`product_id`),
            KEY `idx_sort_order` (`sort_order`),
            KEY `idx_is_primary` (`is_primary`),
            FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'product_views' => "CREATE TABLE `product_views` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `session_id` varchar(128) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `referrer` varchar(255) DEFAULT NULL,
            `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_product_id` (`product_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_session_id` (`session_id`),
            KEY `idx_viewed_at` (`viewed_at`),
            FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'recently_viewed' => "CREATE TABLE `recently_viewed` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `session_id` varchar(128) DEFAULT NULL,
            `product_id` int(11) NOT NULL,
            `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
            UNIQUE KEY `unique_session_product` (`session_id`, `product_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_session_id` (`session_id`),
            KEY `idx_product_id` (`product_id`),
            KEY `idx_viewed_at` (`viewed_at`),
            FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'wishlist_shares' => "CREATE TABLE `wishlist_shares` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `share_token` varchar(64) NOT NULL,
            `title` varchar(255) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `is_public` tinyint(1) DEFAULT 0,
            `expires_at` timestamp NULL DEFAULT NULL,
            `view_count` int(11) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_share_token` (`share_token`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_is_public` (`is_public`),
            KEY `idx_expires_at` (`expires_at`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'coupons' => "CREATE TABLE `coupons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `type` enum('percentage','fixed_amount','free_shipping') NOT NULL,
            `value` decimal(10,2) NOT NULL,
            `minimum_amount` decimal(10,2) DEFAULT 0.00,
            `maximum_discount` decimal(10,2) DEFAULT NULL,
            `usage_limit` int(11) DEFAULT NULL,
            `used_count` int(11) DEFAULT 0,
            `user_limit` int(11) DEFAULT 1,
            `valid_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `valid_until` timestamp NULL DEFAULT NULL,
            `status` enum('active','inactive','expired') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_code` (`code`),
            KEY `idx_status` (`status`),
            KEY `idx_valid_from` (`valid_from`),
            KEY `idx_valid_until` (`valid_until`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'coupon_usage' => "CREATE TABLE `coupon_usage` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `coupon_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `order_id` int(11) DEFAULT NULL,
            `discount_amount` decimal(10,2) NOT NULL,
            `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_coupon_id` (`coupon_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_used_at` (`used_at`),
            FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables_to_create as $table_name => $sql) {
        if (tableExists($conn, $table_name)) {
            $warnings[] = "Table '{$table_name}' already exists - skipped";
        } else {
            try {
                $conn->exec($sql);
                $success_messages[] = "✓ Created table: {$table_name}";
                $total_success++;
            } catch (PDOException $e) {
                $error_messages[] = "✗ Failed to create table '{$table_name}': " . $e->getMessage();
                $total_errors++;
            }
        }
    }
    
    // 2. Add missing columns
    $success_messages[] = "=== Adding Missing Columns ===";
    
    $columns_to_add = [
        'products' => [
            'view_count' => "ALTER TABLE `products` ADD COLUMN `view_count` int(11) DEFAULT 0"
        ],
        'users' => [
            'phone_verified' => "ALTER TABLE `users` ADD COLUMN `phone_verified` tinyint(1) DEFAULT 0"
        ],
        'orders' => [
            'coupon_id' => "ALTER TABLE `orders` ADD COLUMN `coupon_id` int(11) DEFAULT NULL"
        ]
    ];
    
    foreach ($columns_to_add as $table_name => $columns) {
        foreach ($columns as $column_name => $sql) {
            if (columnExists($conn, $table_name, $column_name)) {
                $warnings[] = "Column '{$table_name}.{$column_name}' already exists - skipped";
            } else {
                try {
                    $conn->exec($sql);
                    $success_messages[] = "✓ Added column: {$table_name}.{$column_name}";
                    $total_success++;
                } catch (PDOException $e) {
                    $error_messages[] = "✗ Failed to add column '{$table_name}.{$column_name}': " . $e->getMessage();
                    $total_errors++;
                }
            }
        }
    }
    
    // 3. Add indexes
    $success_messages[] = "=== Adding Indexes ===";
    
    $indexes_to_add = [
        "ALTER TABLE `products` ADD INDEX `idx_view_count` (`view_count`)",
        "ALTER TABLE `users` ADD INDEX `idx_phone_verified` (`phone_verified`)",
        "ALTER TABLE `orders` ADD INDEX `idx_coupon_id` (`coupon_id`)"
    ];
    
    foreach ($indexes_to_add as $sql) {
        try {
            $conn->exec($sql);
            $success_messages[] = "✓ Added index";
            $total_success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $warnings[] = "Index already exists - skipped";
            } else {
                $error_messages[] = "✗ Failed to add index: " . $e->getMessage();
                $total_errors++;
            }
        }
    }
    
    // 4. Add foreign key
    try {
        $conn->exec("ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL");
        $success_messages[] = "✓ Added foreign key constraint";
        $total_success++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $warnings[] = "Foreign key constraint already exists - skipped";
        } else {
            $error_messages[] = "✗ Failed to add foreign key: " . $e->getMessage();
            $total_errors++;
        }
    }
    
    // 5. Insert sample data
    $success_messages[] = "=== Inserting Sample Data ===";
    
    $sample_data = [
        "INSERT IGNORE INTO `product_variants` (`product_id`, `variant_type`, `variant_value`, `price_modifier`, `stock_quantity`, `is_default`) VALUES
        (1, 'size', 'Small (1kg)', 0.00, 50, 1),
        (1, 'size', 'Medium (2kg)', 500.00, 30, 0),
        (2, 'size', 'Small (500g)', 0.00, 40, 1),
        (3, 'color', 'Green', 0.00, 100, 1)",
        
        "INSERT IGNORE INTO `coupons` (`code`, `name`, `description`, `type`, `value`, `minimum_amount`, `usage_limit`, `valid_from`, `valid_until`) VALUES
        ('WELCOME10', 'Welcome Discount', 'Get 10% off your first order', 'percentage', 10.00, 1000.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
        ('SAVE500', 'Save ₦500', 'Get ₦500 off orders above ₦5000', 'fixed_amount', 500.00, 5000.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))"
    ];
    
    foreach ($sample_data as $sql) {
        try {
            $conn->exec($sql);
            $success_messages[] = "✓ Inserted sample data";
            $total_success++;
        } catch (PDOException $e) {
            $warnings[] = "Sample data insertion warning: " . $e->getMessage();
        }
    }
    
    // 6. Update existing data
    $success_messages[] = "=== Updating Existing Data ===";
    
    try {
        $conn->exec("UPDATE `products` SET `view_count` = FLOOR(RAND() * 100) + 10 WHERE `view_count` IS NULL OR `view_count` = 0");
        $success_messages[] = "✓ Updated product view counts";
        $total_success++;
    } catch (PDOException $e) {
        $warnings[] = "Could not update product view counts: " . $e->getMessage();
    }
    
    try {
        $conn->exec("UPDATE `users` SET `phone_verified` = 0 WHERE `phone_verified` IS NULL");
        $success_messages[] = "✓ Updated user phone verification status";
        $total_success++;
    } catch (PDOException $e) {
        $warnings[] = "Could not update user phone verification: " . $e->getMessage();
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
    <title>Hi5ve MarketPlace - Smart Phase 3 Setup Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8 text-center">
            <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-16 w-auto mx-auto mb-4">
            <h1 class="text-3xl font-bold text-gray-900">Smart Phase 3 Setup Results</h1>
            <p class="text-gray-600 mt-2">Intelligent setup with conflict detection</p>
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
                <div class="text-green-700 bg-green-50 p-2 rounded font-mono text-sm">
                    <?= htmlspecialchars($message) ?>
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
                <div class="text-yellow-700 bg-yellow-50 p-2 rounded font-mono text-sm">
                    <?= htmlspecialchars($warning) ?>
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
                <div class="text-red-700 bg-red-50 p-2 rounded font-mono text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="text-center space-x-4">
            <a href="index.php" class="inline-flex items-center bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-300">
                <i class="fas fa-home mr-2"></i>Go to Homepage
            </a>
            <a href="wishlist.php" class="inline-flex items-center bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition duration-300">
                <i class="fas fa-heart mr-2"></i>Test Wishlist
            </a>
            <a href="compare.php" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300">
                <i class="fas fa-balance-scale mr-2"></i>Test Comparison
            </a>
        </div>
    </div>
</body>
</html> 