<?php
require_once 'config/database.php';

// Simple authentication for setup
$setup_password = 'hi5ve_setup_2024';
$authenticated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $setup_password) {
        $authenticated = true;
    } else {
        $error = 'Invalid setup password';
    }
}

if (isset($_POST['setup']) && $authenticated) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Read and execute schema
        $schema = file_get_contents('database/schema.sql');
        $statements = explode(';', $schema);
        
        $results = [];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $conn->exec($statement);
                    $results[] = ['success' => true, 'message' => 'Executed: ' . substr($statement, 0, 50) . '...'];
                } catch (PDOException $e) {
                    $results[] = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
                }
            }
        }
        
        $setup_complete = true;
        
    } catch (Exception $e) {
        $setup_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hi5ve MarketPlace - Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-shopping-basket text-green-600 mr-2"></i>
                    Hi5ve MarketPlace
                </h2>
                <p class="mt-2 text-sm text-gray-600">Database Setup</p>
            </div>

            <?php if (!$authenticated): ?>
            <!-- Authentication Form -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-lg font-semibold mb-4">Setup Authentication</h3>
                
                <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Setup Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="text-sm text-gray-500 mt-1">Enter the setup password to continue</p>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                        <i class="fas fa-unlock mr-2"></i>Authenticate
                    </button>
                </form>
            </div>
            
            <?php elseif (isset($setup_complete)): ?>
            <!-- Setup Complete -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="text-center mb-6">
                    <i class="fas fa-check-circle text-green-600 text-4xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-green-800">Setup Complete!</h3>
                </div>
                
                <div class="space-y-2 mb-6">
                    <?php foreach ($results as $result): ?>
                    <div class="flex items-center text-sm <?= $result['success'] ? 'text-green-700' : 'text-red-700' ?>">
                        <i class="fas <?= $result['success'] ? 'fa-check' : 'fa-times' ?> mr-2"></i>
                        <?= htmlspecialchars($result['message']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-2">Default Admin Account</h4>
                    <p class="text-sm text-blue-700">
                        <strong>Email:</strong> admin@hi5ve.com<br>
                        <strong>Password:</strong> password
                    </p>
                    <p class="text-xs text-blue-600 mt-2">Please change the admin password after first login!</p>
                </div>
                
                <div class="space-y-3">
                    <a href="index.php" 
                       class="w-full bg-green-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>Go to Homepage
                    </a>
                    
                    <a href="login.php" 
                       class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login as Admin
                    </a>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        For security, delete this setup.php file after setup is complete.
                    </p>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Setup Form -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-lg font-semibold mb-4">Database Setup</h3>
                
                <?php if (isset($setup_error)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    Setup Error: <?= htmlspecialchars($setup_error) ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-6">
                    <h4 class="font-medium text-gray-800 mb-2">What this setup will do:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Create database tables</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Add sample categories</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Create default admin account</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Set up initial configuration</li>
                    </ul>
                </div>
                
                <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-semibold text-yellow-800 mb-2">Important Notes:</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• Make sure your database 'mart3' exists</li>
                        <li>• Ensure database credentials in config/database.php are correct</li>
                        <li>• This will create/overwrite existing tables</li>
                        <li>• Delete this file after setup for security</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="password" value="<?= htmlspecialchars($_POST['password']) ?>">
                    <input type="hidden" name="setup" value="1">
                    
                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300"
                            onclick="return confirm('Are you sure you want to proceed with the setup? This will create/overwrite database tables.')">
                        <i class="fas fa-database mr-2"></i>Run Database Setup
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800 text-sm">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Homepage
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 