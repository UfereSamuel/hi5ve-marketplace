<?php
// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - Admin - ' . SITE_NAME : 'Admin - ' . SITE_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .sidebar-link.active {
            background-color: #10b981;
            color: white;
        }
        .sidebar-link:hover {
            background-color: #f3f4f6;
        }
        .sidebar-link.active:hover {
            background-color: #059669;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="../index.php" class="flex items-center">
                        <img src="../assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-10 w-auto mr-2">
                        <span class="text-2xl font-bold text-green-600">Hi5ve MarketPlace</span>
                    </a>
                    <span class="ml-4 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        Admin Panel
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</span>
                    <a href="../index.php" class="text-gray-600 hover:text-green-600">
                        <i class="fas fa-home mr-1"></i>View Site
                    </a>
                    <a href="../logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Admin Menu</h3>
                <nav class="space-y-2">
                    <a href="index.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="products.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                        <i class="fas fa-box mr-3"></i>Products
                    </a>
                    <a href="categories.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                        <i class="fas fa-tags mr-3"></i>Categories
                    </a>
                    <a href="orders.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                        <i class="fas fa-shopping-cart mr-3"></i>Orders
                    </a>
                    <a href="customers.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : '' ?>">
                        <i class="fas fa-users mr-3"></i>Customers
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8"> 