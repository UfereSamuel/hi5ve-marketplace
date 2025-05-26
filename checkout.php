<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Cart.php';
require_once 'classes/PaymentGateway.php';
require_once 'classes/Order.php';

// Check if user is logged in or has guest cart
if (!isset($_SESSION['user_id']) && !isset($_SESSION['guest_id'])) {
    header('Location: login.php?redirect=checkout');
    exit();
}

$cart = new Cart();
$paymentGateway = new PaymentGateway();
$order = new Order();

// Get cart items
$cart_items = $cart->getItems($_SESSION['user_id'] ?? null);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_fee = 500; // ₦500 delivery fee
$total = $subtotal + $delivery_fee;

// Get available payment methods
$payment_methods = $paymentGateway->getAvailablePaymentMethods($total);

// Handle form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    if (empty($customer_name)) $errors[] = "Customer name is required";
    if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    }
    if (empty($customer_phone)) $errors[] = "Phone number is required";
    if (empty($delivery_address)) $errors[] = "Delivery address is required";
    if (empty($payment_method)) $errors[] = "Please select a payment method";

    if (empty($errors)) {
        try {
            // Create order
            $order_data = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'delivery_address' => $delivery_address,
                'total_amount' => $total,
                'payment_method' => $payment_method,
                'notes' => $notes,
                'cart_items' => $cart_items
            ];

            $order_result = $order->create($order_data, $cart_items);

            if ($order_result['success']) {
                $order_id = $order_result['order_id'];
                
                // Process payment based on selected method
                if (strpos($payment_method, 'paystack') !== false) {
                    $payment_result = $paymentGateway->initializePaystackPayment(
                        $order_id, 
                        $total, 
                        $customer_email, 
                        $customer_phone,
                        SITE_URL . '/payment/paystack-callback.php'
                    );

                    if ($payment_result['success']) {
                        // Clear cart and redirect to payment
                        $cart->clear($_SESSION['user_id'] ?? null);
                        header('Location: ' . $payment_result['authorization_url']);
                        exit();
                    } else {
                        $errors[] = "Payment initialization failed: " . $payment_result['message'];
                    }
                } elseif (strpos($payment_method, 'flutterwave') !== false) {
                    $payment_result = $paymentGateway->initializeFlutterwavePayment(
                        $order_id, 
                        $total, 
                        $customer_email, 
                        $customer_phone, 
                        $customer_name
                    );

                    if ($payment_result['success']) {
                        // Clear cart and redirect to payment
                        $cart->clear($_SESSION['user_id'] ?? null);
                        header('Location: ' . $payment_result['payment_link']);
                        exit();
                    } else {
                        $errors[] = "Payment initialization failed: " . $payment_result['message'];
                    }
                } elseif ($payment_method === 'bank_transfer') {
                    $payment_result = $paymentGateway->processBankTransfer($order_id, $total, []);
                    
                    if ($payment_result['success']) {
                        $cart->clear($_SESSION['user_id'] ?? null);
                        header('Location: order-confirmation.php?order_id=' . $order_id . '&payment_method=bank_transfer');
                        exit();
                    } else {
                        $errors[] = "Bank transfer processing failed: " . $payment_result['message'];
                    }
                } elseif ($payment_method === 'cod') {
                    $payment_result = $paymentGateway->processCashOnDelivery($order_id, $total);
                    
                    if ($payment_result['success']) {
                        $cart->clear($_SESSION['user_id'] ?? null);
                        header('Location: order-confirmation.php?order_id=' . $order_id . '&payment_method=cod');
                        exit();
                    } else {
                        $errors[] = "Cash on delivery processing failed: " . $payment_result['message'];
                    }
                } elseif ($payment_method === 'whatsapp_payment') {
                    $payment_result = $paymentGateway->processWhatsAppPayment(
                        $order_id, 
                        $total, 
                        $customer_email, 
                        $customer_phone, 
                        $customer_name
                    );
                    
                    if ($payment_result['success']) {
                        $cart->clear($_SESSION['user_id'] ?? null);
                        // Redirect to WhatsApp and then to confirmation page
                        $_SESSION['whatsapp_payment_link'] = $payment_result['whatsapp_link'];
                        header('Location: order-confirmation.php?order_id=' . $order_id . '&payment_method=whatsapp_payment&reference=' . $payment_result['reference']);
                        exit();
                    } else {
                        $errors[] = "WhatsApp payment processing failed: " . $payment_result['message'];
                    }
                }
            } else {
                $errors[] = "Failed to create order: " . $order_result['message'];
            }
        } catch (Exception $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}

// Get public keys for frontend payment integration
$public_keys = $paymentGateway->getPublicKeys();

// Safely get user session data with fallbacks
$default_name = '';
$default_email = '';
$default_phone = '';

if (isset($_SESSION['user_id'])) {
    $default_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    $default_email = $_SESSION['email'] ?? '';
    $default_phone = $_SESSION['phone'] ?? '';
}

$page_title = "Checkout";
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                <a href="cart.php" class="text-gray-600 hover:text-gray-800 transition duration-300">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Checkout</h1>
                    <p class="text-gray-600">Complete your order securely</p>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="ml-2 text-sm font-medium text-green-600">Cart</span>
                </div>
                <div class="w-16 h-1 bg-green-500"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium text-blue-600">Checkout</span>
                </div>
                <div class="w-16 h-1 bg-gray-300"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-semibold">
                        3
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Payment</span>
                </div>
                <div class="w-16 h-1 bg-gray-300"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-semibold">
                        4
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Confirmation</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Checkout Form -->
            <div class="lg:col-span-2">
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span class="font-semibold">Please fix the following errors:</span>
                    </div>
                    <ul class="mt-2 ml-6 list-disc">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" id="checkoutForm" class="space-y-6">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Customer Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="customer_name" 
                                       name="customer_name" 
                                       value="<?= htmlspecialchars($_POST['customer_name'] ?? $default_name) ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter your full name"
                                       required>
                            </div>

                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       id="customer_email" 
                                       name="customer_email" 
                                       value="<?= htmlspecialchars($_POST['customer_email'] ?? $default_email) ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="your@email.com"
                                       required>
                            </div>

                            <div>
                                <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" 
                                       id="customer_phone" 
                                       name="customer_phone" 
                                       value="<?= htmlspecialchars($_POST['customer_phone'] ?? $default_phone) ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="+234 800 000 0000"
                                       required>
                            </div>

                            <div class="md:col-span-2">
                                <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-2">
                                    Delivery Address <span class="text-red-500">*</span>
                                </label>
                                <textarea id="delivery_address" 
                                          name="delivery_address" 
                                          rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Enter your complete delivery address including landmarks"
                                          required><?= htmlspecialchars($_POST['delivery_address'] ?? '') ?></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Order Notes <span class="text-gray-500">(Optional)</span>
                                </label>
                                <textarea id="notes" 
                                          name="notes" 
                                          rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Any special instructions for your order..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Payment Method</h2>
                        </div>

                        <?php if (!empty($payment_methods)): ?>
                            <div class="space-y-4">
                                <?php foreach ($payment_methods as $method): ?>
                                    <?php
                                    $fee = $paymentGateway->calculateTransactionFee($total, $method['name']);
                                    $method_icons = [
                                        'paystack_card' => 'fas fa-credit-card',
                                        'paystack_bank' => 'fas fa-university',
                                        'paystack_ussd' => 'fas fa-mobile-alt',
                                        'flutterwave_card' => 'fas fa-credit-card',
                                        'flutterwave_bank' => 'fas fa-university',
                                        'bank_transfer' => 'fas fa-university',
                                        'cod' => 'fas fa-money-bill-wave'
                                    ];
                                    $icon = $method_icons[$method['name']] ?? 'fas fa-payment';
                                    ?>
                                    <label class="block cursor-pointer">
                                        <div class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 hover:bg-blue-50 transition duration-300 payment-method-card">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <input type="radio" 
                                                           name="payment_method" 
                                                           value="<?= $method['name'] ?>"
                                                           class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                           <?= ($_POST['payment_method'] ?? '') === $method['name'] ? 'checked' : '' ?>>
                                                    <div class="ml-4">
                                                        <div class="flex items-center">
                                                            <i class="<?= $icon ?> text-gray-600 mr-2"></i>
                                                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($method['display_name']) ?></span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($method['description']) ?></p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <?php if ($fee > 0): ?>
                                                        <span class="text-sm text-orange-600 font-medium">
                                                            Fee: ₦<?= number_format($fee, 2) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-sm text-green-600 font-medium">
                                                            <i class="fas fa-check-circle mr-1"></i>No Fee
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <span>No payment methods available for this order amount.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Place Order Button -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <button type="submit" 
                                id="placeOrderBtn"
                                class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-lock mr-2"></i>
                            <span id="buttonText">Place Order - ₦<?= number_format($total, 2) ?></span>
                        </button>
                        
                        <div class="flex items-center justify-center mt-4 text-sm text-gray-500">
                            <i class="fas fa-shield-alt mr-2"></i>
                            <span>Your payment information is encrypted and secure</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Order Summary</h2>
                    
                    <!-- Cart Items -->
                    <div class="space-y-4 mb-6">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             class="w-full h-full object-cover rounded-lg">
                                    <?php else: ?>
                                        <i class="fas fa-image text-gray-400"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                                    <p class="text-sm text-gray-600">
                                        Qty: <?= $item['quantity'] ?> × ₦<?= number_format($item['price'], 2) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold text-gray-800">
                                        ₦<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order Totals -->
                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal:</span>
                            <span>₦<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Delivery Fee:</span>
                            <span>₦<?= number_format($delivery_fee, 2) ?></span>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex justify-between text-lg font-semibold text-gray-800">
                                <span>Total:</span>
                                <span>₦<?= number_format($total, 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-green-600 text-2xl mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-green-800">Secure Payment</h3>
                                <p class="text-sm text-green-700 mt-1">
                                    SSL encrypted. We support all major Nigerian banks and payment methods.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Support Contact -->
                    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-headset text-blue-600 text-xl mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-blue-800">Need Help?</h3>
                                <p class="text-sm text-blue-700 mt-1">
                                    Contact us via WhatsApp or call our support team.
                                </p>
                                <div class="mt-2 space-x-2">
                                    <a href="https://wa.me/2348000000000" 
                                       target="_blank"
                                       class="inline-flex items-center text-xs bg-green-600 text-white px-2 py-1 rounded">
                                        <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                    </a>
                                    <a href="tel:+2348000000000" 
                                       class="inline-flex items-center text-xs bg-blue-600 text-white px-2 py-1 rounded">
                                        <i class="fas fa-phone mr-1"></i>Call
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const buttonText = document.getElementById('buttonText');
    const checkoutForm = document.getElementById('checkoutForm');
    
    // Payment method selection handling
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            updateOrderButton();
            updatePaymentMethodCards();
        });
    });
    
    function updatePaymentMethodCards() {
        const cards = document.querySelectorAll('.payment-method-card');
        cards.forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            if (radio.checked) {
                card.classList.add('border-blue-500', 'bg-blue-50');
                card.classList.remove('border-gray-300');
            } else {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-300');
            }
        });
    }
    
    function updateOrderButton() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (selectedMethod) {
            const methodName = selectedMethod.value;
            let newButtonText = 'Place Order - ₦<?= number_format($total, 2) ?>';
            let icon = 'fas fa-lock';
            
            if (methodName.includes('paystack') || methodName.includes('flutterwave')) {
                newButtonText = 'Pay Now - ₦<?= number_format($total, 2) ?>';
                icon = 'fas fa-credit-card';
            } else if (methodName === 'bank_transfer') {
                newButtonText = 'Get Bank Details - ₦<?= number_format($total, 2) ?>';
                icon = 'fas fa-university';
            } else if (methodName === 'cod') {
                newButtonText = 'Order (Pay on Delivery) - ₦<?= number_format($total, 2) ?>';
                icon = 'fas fa-money-bill-wave';
            } else if (methodName === 'whatsapp_payment') {
                newButtonText = 'Pay via WhatsApp - ₦<?= number_format($total, 2) ?>';
                icon = 'fab fa-whatsapp';
            }
            
            buttonText.innerHTML = `<i class="${icon} mr-2"></i>${newButtonText}`;
        }
    }
    
    // Form validation and submission
    checkoutForm.addEventListener('submit', function(e) {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            e.preventDefault();
            alert('Please select a payment method');
            return false;
        }
        
        // Show loading state
        placeOrderBtn.disabled = true;
        buttonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        
        // Re-enable button after 30 seconds as fallback
        setTimeout(() => {
            placeOrderBtn.disabled = false;
            updateOrderButton();
        }, 30000);
    });
    
    // Initialize
    updateOrderButton();
    updatePaymentMethodCards();
    
    // Phone number formatting
    const phoneInput = document.getElementById('customer_phone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('234')) {
            value = '+' + value;
        } else if (value.startsWith('0')) {
            value = '+234' + value.substring(1);
        } else if (value.length > 0 && !value.startsWith('+')) {
            value = '+234' + value;
        }
        e.target.value = value;
    });
});
</script>

<?php include 'includes/footer.php'; ?> 