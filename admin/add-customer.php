<?php
require_once '../config/config.php';
require_once '../classes/User.php';

$user = new User();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($user->emailExists($email)) {
        $error = 'Email address already exists';
    } elseif ($user->usernameExists($username)) {
        $error = 'Username already exists';
    } else {
        // Create customer
        $customer_data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'address' => $address
        ];
        
        $result = $user->register($customer_data);
        
        if ($result['success']) {
            $success = 'Customer added successfully!';
            // Clear form data
            $username = $email = $first_name = $last_name = $phone = $address = '';
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = "Add Customer";
include 'includes/admin_header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Add New Customer</h1>
            <p class="text-gray-600">Create a new customer account</p>
        </div>
        <div>
            <a href="customers.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-700 transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>Back to Customers
            </a>
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

    <!-- Add Customer Form -->
    <div class="bg-white rounded-lg shadow-md p-8">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($username ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           required>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           required>
                </div>

                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?= htmlspecialchars($first_name ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           required>
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?= htmlspecialchars($last_name ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           required>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?= htmlspecialchars($phone ?? '') ?>"
                           placeholder="+234..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           required minlength="6">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           required minlength="6">
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Address
                    </label>
                    <textarea id="address" name="address" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="Enter customer's address..."><?= htmlspecialchars($address ?? '') ?></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8 flex justify-end space-x-4">
                <a href="customers.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    Cancel
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-user-plus mr-2"></i>Add Customer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 