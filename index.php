<?php
require_once 'config/config.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';
require_once 'classes/Cart.php';
require_once 'includes/banner_display.php';

$product = new Product();
$category = new Category();
$cart = new Cart();

// Get featured products
$featured_products = $product->getFeatured(8);

// Get categories with products
$categories = $category->getCategoriesWithProducts();

// Get cart count
$cart_count = $cart->getCount(isLoggedIn() ? $_SESSION['user_id'] : null);

$page_title = "Welcome to Hi5ve MarketPlace";
include 'includes/header.php';

// Check for success messages
$show_success = false;
$success_message = '';

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $show_success = true;
    $success_message = 'Welcome to Hi5ve MarketPlace! Your account has been created successfully.';
}

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $show_success = true;
    $success_message = 'You have been logged out successfully. Thank you for using Hi5ve MarketPlace!';
}
?>

<?php if ($show_success): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3">
    <div class="container mx-auto flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <?= htmlspecialchars($success_message) ?>
    </div>
</div>
<?php endif; ?>

<!-- Hero Banners Section -->
<?php 
// Check if there are any hero banners
$banner = new Banner();
$hero_banners = $banner->getActiveByPosition('hero');

if (!empty($hero_banners)): 
    // Display dynamic banners
    displayBanners('hero');
else: 
    // Fallback to static hero section if no banners
?>
<!-- Static Hero Section (Fallback) -->
<section class="hero-section bg-gradient-to-r from-green-600 to-blue-600 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <div class="flex justify-center mb-6">
            <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-20 w-auto">
        </div>
        <h1 class="text-5xl font-bold mb-6">Welcome to Hi5ve MarketPlace</h1>
        <p class="text-xl mb-8">Your one-stop shop for fresh groceries delivered to your doorstep</p>
        <div class="flex justify-center space-x-4">
            <a href="#products" class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                Shop Now
            </a>
            <a href="<?= getWhatsAppLink('Hello! I would like to know more about Hi5ve MarketPlace.') ?>" 
               target="_blank" 
               class="bg-green-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300 flex items-center">
                <i class="fab fa-whatsapp mr-2"></i> Chat with Us
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-truck text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Fast Delivery</h3>
                <p class="text-gray-600">Get your groceries delivered within hours</p>
            </div>
            <div class="text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-leaf text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Fresh Products</h3>
                <p class="text-gray-600">Quality guaranteed fresh groceries</p>
            </div>
            <div class="text-center">
                <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fab fa-whatsapp text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">WhatsApp Support</h3>
                <p class="text-gray-600">Order and get support via WhatsApp</p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<?php if (!empty($categories)): ?>
<section class="py-16">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?= $cat['id'] ?>" class="category-card group">
                <div class="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-lg transition duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-basket text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 group-hover:text-green-600 transition duration-300">
                        <?= htmlspecialchars($cat['name']) ?>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1"><?= $cat['product_count'] ?> items</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products Section -->
<?php if (!empty($featured_products)): ?>
<section id="products" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Featured Products</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured_products as $prod): ?>
            <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                <div class="relative">
                    <img src="<?= $prod['image'] ? 'uploads/products/' . $prod['image'] : 'get_placeholder_image.php?w=300&h=200&text=Product' ?>" 
                         alt="<?= htmlspecialchars($prod['name']) ?>" 
                         class="w-full h-48 object-cover">
                    <?php if (Product::hasDiscount($prod)): ?>
                    <span class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-sm">
                        -<?= Product::getDiscountPercentage($prod) ?>%
                    </span>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 mb-2"><?= htmlspecialchars($prod['name']) ?></h3>
                    <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars(substr($prod['description'], 0, 80)) ?>...</p>
                    
                    <div class="flex items-center justify-between mb-3">
                        <div class="price">
                            <?php if (Product::hasDiscount($prod)): ?>
                            <span class="text-lg font-bold text-green-600"><?= formatCurrency($prod['discount_price']) ?></span>
                            <span class="text-sm text-gray-500 line-through ml-2"><?= formatCurrency($prod['price']) ?></span>
                            <?php else: ?>
                            <span class="text-lg font-bold text-green-600"><?= formatCurrency($prod['price']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-gray-500">per <?= htmlspecialchars($prod['unit']) ?></span>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="addToCart(<?= $prod['id'] ?>)" 
                                class="flex-1 bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition duration-300">
                            <i class="fas fa-cart-plus mr-1"></i> Add to Cart
                        </button>
                        <a href="product.php?id=<?= $prod['id'] ?>" 
                           class="bg-gray-200 text-gray-700 py-2 px-4 rounded hover:bg-gray-300 transition duration-300">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12">
            <a href="products.php" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                View All Products
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- WhatsApp CTA Section -->
<section class="py-16 bg-green-600 text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6">Need Help? Chat with Us!</h2>
        <p class="text-xl mb-8">Get instant support, place orders, or ask questions via WhatsApp</p>
        <div class="flex justify-center space-x-4">
            <a href="<?= getWhatsAppLink('Hello! I would like to place an order.') ?>" 
               target="_blank" 
               class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 flex items-center">
                <i class="fab fa-whatsapp mr-2"></i> Order via WhatsApp
            </a>
            <a href="<?= getWhatsAppLink('Hello! I need help with my order.') ?>" 
               target="_blank" 
               class="bg-green-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-400 transition duration-300 flex items-center">
                <i class="fas fa-headset mr-2"></i> Get Support
            </a>
        </div>
    </div>
</section>

<script>
function addToCart(productId) {
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&product_id=' + productId + '&quantity=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            updateCartCount();
            showNotification('Product added to cart!', 'success');
        } else {
            showNotification(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function updateCartCount() {
    fetch('ajax/cart.php?action=count')
    .then(response => response.json())
    .then(data => {
        document.getElementById('cart-count').textContent = data.count || 0;
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?> 