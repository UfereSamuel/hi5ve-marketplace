<?php
require_once 'config/config.php';
require_once 'classes/Cart.php';
require_once 'classes/UserProfile.php';

$cart = new Cart();
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

// Get cart summary
$cart_summary = $cart->getSummary($user_id);
$cart_count = $cart_summary['total_items'];

// Check profile completion for logged-in users
$profile_complete = true;
$missing_fields = [];
$user_data = null;

if (isLoggedIn()) {
    $userProfile = new UserProfile();
    $profile_check = $userProfile->isProfileComplete($_SESSION['user_id']);
    $profile_complete = $profile_check['complete'];
    $missing_fields = $profile_check['missing'];
    $user_data = $userProfile->getUserProfile($_SESSION['user_id']);
}

$page_title = "Shopping Cart";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Checkout Error Message -->
    <?php if (isset($_SESSION['checkout_error'])): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?= htmlspecialchars($_SESSION['checkout_error']) ?></span>
        </div>
    </div>
    <?php 
    unset($_SESSION['checkout_error']); 
    endif; 
    ?>

    <div class="flex items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Shopping Cart</h1>
        <span class="ml-4 bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
            <?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($cart_summary['items'])): ?>
    <!-- Empty Cart -->
    <div class="text-center py-16">
        <div class="mb-8">
            <i class="fas fa-shopping-cart text-gray-300 text-6xl"></i>
        </div>
        <h2 class="text-2xl font-semibold text-gray-600 mb-4">Your cart is empty</h2>
        <p class="text-gray-500 mb-8">Looks like you haven't added any items to your cart yet.</p>
        <div class="space-y-4">
            <a href="products.php" 
               class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                <i class="fas fa-shopping-basket mr-2"></i>
                Start Shopping
            </a>
            <div class="text-center">
                <p class="text-gray-600 mb-4">Or order directly via WhatsApp</p>
                <a href="<?= getWhatsAppLink('Hello! I would like to browse your products and place an order.') ?>" 
                   target="_blank" 
                   class="inline-block bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition duration-300">
                    <i class="fab fa-whatsapp mr-2"></i>
                    Order via WhatsApp
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Cart Items</h2>
                </div>
                
                <div id="cart-items">
                    <?php foreach ($cart_summary['items'] as $item): ?>
                    <div class="cart-item p-6 border-b border-gray-100" data-product-id="<?= $item['product_id'] ?>">
                        <div class="flex items-center space-x-4">
                            <!-- Product Image -->
                            <div class="flex-shrink-0">
                                <img src="<?= $item['image'] ? 'uploads/products/' . $item['image'] : 'get_placeholder_image.php?w=80&h=80&text=Item' ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="w-20 h-20 object-cover rounded-lg">
                            </div>
                            
                            <!-- Product Details -->
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="text-gray-600 text-sm">per <?= htmlspecialchars($item['unit']) ?></p>
                                <div class="flex items-center mt-2">
                                    <span class="text-lg font-bold text-green-600">
                                        <?= formatCurrency($item['effective_price']) ?>
                                    </span>
                                    <?php if ($item['discount_price'] && $item['discount_price'] < $item['price']): ?>
                                    <span class="text-sm text-gray-500 line-through ml-2">
                                        <?= formatCurrency($item['price']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Stock Warning -->
                                <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                <div class="mt-2 text-red-600 text-sm">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Only <?= $item['stock_quantity'] ?> available
                                </div>
                                <?php elseif ($item['stock_quantity'] <= 5): ?>
                                <div class="mt-2 text-orange-600 text-sm">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Only <?= $item['stock_quantity'] ?> left in stock
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quantity Controls -->
                            <div class="flex items-center space-x-3">
                                <button onclick="updateQuantity(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)" 
                                        class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition duration-300"
                                        <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                    <i class="fas fa-minus text-sm"></i>
                                </button>
                                
                                <span class="quantity-display w-12 text-center font-semibold">
                                    <?= $item['quantity'] ?>
                                </span>
                                
                                <button onclick="updateQuantity(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)" 
                                        class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition duration-300"
                                        <?= $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : '' ?>>
                                    <i class="fas fa-plus text-sm"></i>
                                </button>
                            </div>
                            
                            <!-- Item Total -->
                            <div class="text-right">
                                <div class="font-bold text-lg text-gray-800 item-total">
                                    <?= formatCurrency($item['effective_price'] * $item['quantity']) ?>
                                </div>
                                <button onclick="removeItem(<?= $item['product_id'] ?>)" 
                                        class="text-red-600 hover:text-red-700 text-sm mt-2 transition duration-300">
                                    <i class="fas fa-trash mr-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cart Actions -->
                <div class="p-6 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <button onclick="clearCart()" 
                                class="text-red-600 hover:text-red-700 transition duration-300">
                            <i class="fas fa-trash mr-2"></i>Clear Cart
                        </button>
                        <a href="products.php" 
                           class="text-green-600 hover:text-green-700 transition duration-300">
                            <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Order Summary</h2>
                
                <div class="space-y-4 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span id="subtotal" class="font-semibold"><?= formatCurrency($cart_summary['subtotal']) ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Delivery Fee</span>
                        <span id="delivery-fee" class="font-semibold"><?= formatCurrency($cart_summary['delivery_fee']) ?></span>
                    </div>
                    
                    <?php if ($cart_summary['delivery_fee'] == 0 && $cart_summary['subtotal'] > 0): ?>
                    <div class="text-green-600 text-sm">
                        <i class="fas fa-check-circle mr-1"></i>
                        Free delivery on orders above ₦1,000
                    </div>
                    <?php endif; ?>
                    
                    <hr class="border-gray-200">
                    
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span id="total" class="text-green-600"><?= formatCurrency($cart_summary['total']) ?></span>
                    </div>
                </div>
                
                <!-- Checkout Options -->
                <div class="space-y-3">
                    <!-- WhatsApp Checkout -->
                    <button onclick="whatsappCheckout()" 
                            class="w-full bg-green-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-600 transition duration-300 flex items-center justify-center">
                        <i class="fab fa-whatsapp mr-2"></i>
                        Order via WhatsApp
                    </button>
                    
                    <!-- Regular Checkout -->
                    <?php if (isLoggedIn()): ?>
                    <button onclick="proceedToCheckout()" 
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-credit-card mr-2"></i>
                        Proceed to Checkout
                    </button>
                    <?php else: ?>
                    <a href="checkout.php" 
                       class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-credit-card mr-2"></i>
                        Proceed to Checkout
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!isLoggedIn()): ?>
                    <div class="text-center text-sm text-gray-600 mt-4">
                        <p>Have an account? <a href="login.php" class="text-green-600 hover:text-green-700">Login</a> for faster checkout</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Payment Methods -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">We Accept</h3>
                    <div class="flex space-x-2">
                        <div class="bg-gray-100 rounded px-3 py-2 text-xs font-semibold text-gray-700">
                            Online Payment
                        </div>
                        <div class="bg-green-100 rounded px-3 py-2 text-xs font-semibold text-green-700">
                            Cash on Delivery
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<!-- Profile Completion Modal -->
<?php if (isLoggedIn()): ?>
<div id="profileModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Complete Your Profile</h3>
                    <button onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Please complete your profile information to proceed with checkout.</p>
            </div>
            
            <form id="profileForm" class="p-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" 
                                   name="first_name" 
                                   id="first_name"
                                   value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" 
                                   name="last_name" 
                                   id="last_name"
                                   value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                   required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" 
                               name="email" 
                               id="email"
                               value="<?= htmlspecialchars($user_data['email'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input type="tel" 
                                   name="phone" 
                                   id="phone"
                                   value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alternative Phone</label>
                            <input type="tel" 
                                   name="alternative_phone" 
                                   id="alternative_phone"
                                   value="<?= htmlspecialchars($user_data['alternative_phone'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address *</label>
                        <textarea name="address" 
                                  id="address"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                  placeholder="Enter your complete delivery address"
                                  required><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex space-x-3">
                    <button type="button" 
                            onclick="closeProfileModal()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300">
                        <span id="updateButtonText">Update & Continue</span>
                        <i id="updateButtonSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 0) return;
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (quantity === 0) {
                // Remove item from display
                document.querySelector(`[data-product-id="${productId}"]`).remove();
                
                // Check if cart is empty
                if (document.querySelectorAll('.cart-item').length === 0) {
                    location.reload();
                }
            } else {
                // Update quantity display
                document.querySelector(`[data-product-id="${productId}"] .quantity-display`).textContent = quantity;
            }
            
            // Update totals
            updateCartTotals(data);
            updateCartCount();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function removeItem(productId) {
    if (!confirm('Are you sure you want to remove this item?')) return;
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-product-id="${productId}"]`).remove();
            
            // Check if cart is empty
            if (document.querySelectorAll('.cart-item').length === 0) {
                location.reload();
            } else {
                updateCartTotals(data);
                updateCartCount();
            }
            
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) return;
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function whatsappCheckout() {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    button.disabled = true;
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=whatsapp_checkout'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
        
        if (data.success) {
            showNotification(data.message, 'success');
            // Small delay to show success message before opening WhatsApp
            setTimeout(() => {
                window.open(data.whatsapp_link, '_blank');
            }, 500);
        } else {
            showNotification(data.message || 'Failed to process WhatsApp checkout', 'error');
            if (data.errors && Array.isArray(data.errors)) {
                data.errors.forEach(error => {
                    if (error.message) {
                        showNotification(error.message, 'error');
                    }
                });
            }
        }
    })
    .catch(error => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
        
        console.error('WhatsApp checkout error:', error);
        showNotification('Unable to process WhatsApp checkout. Please try again.', 'error');
    });
}

function updateCartTotals(data) {
    if (data.subtotal) document.getElementById('subtotal').textContent = data.subtotal;
    if (data.delivery_fee) document.getElementById('delivery-fee').textContent = data.delivery_fee;
    if (data.total) document.getElementById('total').textContent = data.total;
}

function updateCartCount() {
    fetch('ajax/cart.php?action=count')
    .then(response => response.json())
    .then(data => {
        document.getElementById('cart-count').textContent = data.count || 0;
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Profile completion and checkout functions
function proceedToCheckout() {
    <?php if (!$profile_complete): ?>
    // Show profile completion modal
    showProfileModal();
    <?php else: ?>
    // Profile is complete, proceed to checkout
    window.location.href = 'checkout.php';
    <?php endif; ?>
}

function showProfileModal() {
    document.getElementById('profileModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Handle profile form submission
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const updateButton = this.querySelector('button[type="submit"]');
            const buttonText = document.getElementById('updateButtonText');
            const buttonSpinner = document.getElementById('updateButtonSpinner');
            
            // Show loading state
            updateButton.disabled = true;
            buttonText.textContent = 'Updating...';
            buttonSpinner.classList.remove('hidden');
            
            fetch('ajax/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    if (data.redirect_to_checkout) {
                        // Profile is now complete, close modal and redirect
                        setTimeout(() => {
                            closeProfileModal();
                            window.location.href = 'checkout.php';
                        }, 1000);
                    } else {
                        // Reset button state
                        updateButton.disabled = false;
                        buttonText.textContent = 'Update & Continue';
                        buttonSpinner.classList.add('hidden');
                    }
                } else {
                    showNotification(data.message, 'error');
                    
                    // Reset button state
                    updateButton.disabled = false;
                    buttonText.textContent = 'Update & Continue';
                    buttonSpinner.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                showNotification('An error occurred while updating your profile. Please try again.', 'error');
                
                // Reset button state
                updateButton.disabled = false;
                buttonText.textContent = 'Update & Continue';
                buttonSpinner.classList.add('hidden');
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?> 