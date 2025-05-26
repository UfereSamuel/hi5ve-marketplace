<?php
require_once 'config/config.php';

// Security check
$setup_password = 'hi5ve_phase2_2024';
$entered_password = $_POST['password'] ?? $_GET['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $entered_password === $setup_password) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Hi5ve MarketPlace - Phase 2 Setup</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100'>
            <div class='max-w-4xl mx-auto py-8 px-4'>
                <div class='bg-white rounded-lg shadow-md p-8'>
                    <h1 class='text-3xl font-bold text-green-600 mb-6'>Hi5ve MarketPlace - Phase 2 Setup</h1>
                    <div class='space-y-4'>";
        
        // Read and execute the Phase 2 schema
        $schema_file = 'database/phase2_schema.sql';
        if (!file_exists($schema_file)) {
            throw new Exception("Phase 2 schema file not found: $schema_file");
        }
        
        $sql_content = file_get_contents($schema_file);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue; // Skip empty statements and comments
            }
            
            try {
                $conn->exec($statement);
                $success_count++;
                
                // Extract table name for display
                if (preg_match('/CREATE TABLE.*?`([^`]+)`/', $statement, $matches)) {
                    echo "<div class='flex items-center text-green-600'>
                            <i class='fas fa-check-circle mr-2'></i>
                            <span>Created table: {$matches[1]}</span>
                          </div>";
                } elseif (preg_match('/INSERT INTO.*?`([^`]+)`/', $statement, $matches)) {
                    echo "<div class='flex items-center text-blue-600'>
                            <i class='fas fa-plus-circle mr-2'></i>
                            <span>Inserted data into: {$matches[1]}</span>
                          </div>";
                } elseif (preg_match('/CREATE INDEX.*?ON ([^(]+)/', $statement, $matches)) {
                    echo "<div class='flex items-center text-purple-600'>
                            <i class='fas fa-database mr-2'></i>
                            <span>Created index on: {$matches[1]}</span>
                          </div>";
                } else {
                    echo "<div class='flex items-center text-gray-600'>
                            <i class='fas fa-cog mr-2'></i>
                            <span>Executed SQL statement</span>
                          </div>";
                }
                
            } catch (PDOException $e) {
                $error_count++;
                $errors[] = $e->getMessage();
                echo "<div class='flex items-center text-red-600'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        <span>Error: " . htmlspecialchars($e->getMessage()) . "</span>
                      </div>";
            }
        }
        
        // Add missing columns to existing tables if they don't exist
        $additional_updates = [
            "ALTER TABLE products ADD COLUMN IF NOT EXISTS low_stock_threshold INT DEFAULT 10",
            "ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(100) UNIQUE",
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending'",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 0"
        ];
        
        foreach ($additional_updates as $update) {
            try {
                $conn->exec($update);
                echo "<div class='flex items-center text-green-600'>
                        <i class='fas fa-check-circle mr-2'></i>
                        <span>Applied update: " . htmlspecialchars($update) . "</span>
                      </div>";
            } catch (PDOException $e) {
                // Ignore errors for columns that already exist
                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                    echo "<div class='flex items-center text-yellow-600'>
                            <i class='fas fa-exclamation-triangle mr-2'></i>
                            <span>Update skipped: " . htmlspecialchars($e->getMessage()) . "</span>
                          </div>";
                }
            }
        }
        
        echo "</div>
                    <div class='mt-8 p-6 bg-gray-50 rounded-lg'>
                        <h3 class='text-xl font-semibold mb-4'>Setup Summary</h3>
                        <div class='grid grid-cols-2 gap-4'>
                            <div class='text-center p-4 bg-green-100 rounded-lg'>
                                <div class='text-2xl font-bold text-green-600'>$success_count</div>
                                <div class='text-sm text-gray-600'>Successful Operations</div>
                            </div>
                            <div class='text-center p-4 bg-red-100 rounded-lg'>
                                <div class='text-2xl font-bold text-red-600'>$error_count</div>
                                <div class='text-sm text-gray-600'>Errors</div>
                            </div>
                        </div>
                    </div>";
        
        if ($error_count === 0) {
            echo "<div class='mt-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg'>
                    <h4 class='font-semibold'>✅ Phase 2 Setup Completed Successfully!</h4>
                    <p class='mt-2'>All Phase 2 features have been installed and are ready to use.</p>
                    <div class='mt-4'>
                        <h5 class='font-semibold'>New Features Available:</h5>
                        <ul class='list-disc list-inside mt-2 space-y-1'>
                            <li>Advanced Analytics Dashboard</li>
                            <li>Payment Gateway Integration (Paystack, Flutterwave)</li>
                            <li>Inventory Management System</li>
                            <li>Email Marketing & Campaigns</li>
                            <li>Customer Support Tickets</li>
                            <li>Promotions & Coupon System</li>
                            <li>Shipping Zones & Tracking</li>
                            <li>Customer Wallet System</li>
                            <li>Loyalty Points Program</li>
                            <li>Wishlist Functionality</li>
                        </ul>
                    </div>
                  </div>";
        } else {
            echo "<div class='mt-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg'>
                    <h4 class='font-semibold'>⚠️ Setup Completed with Some Errors</h4>
                    <p class='mt-2'>Some operations failed, but the core functionality should still work.</p>
                  </div>";
        }
        
        echo "<div class='mt-6 text-center'>
                <a href='admin/index.php' class='inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-300'>
                    Go to Admin Dashboard
                </a>
                <a href='index.php' class='inline-block ml-4 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300'>
                    View Website
                </a>
              </div>";
        
        echo "<div class='mt-8 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg'>
                <h4 class='font-semibold'>🔒 Security Notice</h4>
                <p class='mt-2'>For security reasons, please delete this setup file after completion:</p>
                <code class='block mt-2 p-2 bg-gray-200 text-gray-800 rounded'>rm setup_phase2.php</code>
              </div>";
        
        echo "</div>
            </div>
        </body>
        </html>";
        
    } catch (Exception $e) {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Setup Error</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100'>
            <div class='max-w-2xl mx-auto py-8 px-4'>
                <div class='bg-white rounded-lg shadow-md p-8'>
                    <h1 class='text-2xl font-bold text-red-600 mb-4'>Setup Error</h1>
                    <div class='p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg'>
                        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    </div>
                    <div class='mt-6'>
                        <a href='setup_phase2.php' class='inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>
                            Try Again
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hi5ve MarketPlace - Phase 2 Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="mx-auto h-16 w-auto">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Phase 2 Setup</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Install advanced features and enhancements
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">What's New in Phase 2?</h3>
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar text-green-600 mr-3"></i>
                            <span>Advanced Analytics Dashboard</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-credit-card text-blue-600 mr-3"></i>
                            <span>Payment Gateway Integration</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-warehouse text-purple-600 mr-3"></i>
                            <span>Inventory Management System</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-orange-600 mr-3"></i>
                            <span>Email Marketing & Campaigns</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-headset text-teal-600 mr-3"></i>
                            <span>Customer Support System</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-tags text-red-600 mr-3"></i>
                            <span>Promotions & Coupons</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-heart text-pink-600 mr-3"></i>
                            <span>Wishlist & Loyalty Points</span>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Setup Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                               placeholder="Enter setup password">
                        <p class="mt-1 text-xs text-gray-500">
                            Password: <code class="bg-gray-100 px-1 rounded">hi5ve_phase2_2024</code>
                        </p>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-400 mr-2 mt-0.5"></i>
                            <div class="text-sm text-yellow-700">
                                <p class="font-semibold">Important:</p>
                                <ul class="mt-1 list-disc list-inside space-y-1">
                                    <li>Ensure Phase 1 is completely installed</li>
                                    <li>Backup your database before proceeding</li>
                                    <li>This will add new tables and features</li>
                                    <li>Delete this file after setup completion</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-300">
                        <i class="fas fa-rocket mr-2"></i>
                        Install Phase 2 Features
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="index.php" class="text-sm text-gray-600 hover:text-gray-900">
                        ← Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 