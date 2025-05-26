<?php
session_start();
require_once 'config/config.php';
require_once 'classes/User/Wishlist.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$wishlist = new Wishlist();
$page = (int)($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// Get wishlist items and summary
$wishlist_items = $wishlist->getUserWishlist($_SESSION['user_id'], $limit, $offset);
$wishlist_summary = $wishlist->getWishlistSummary($_SESSION['user_id']);
$total_items = $wishlist_summary['total_items'];
$total_pages = ceil($total_items / $limit);

$page_title = "My Wishlist";
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-heart text-red-500 mr-3"></i>My Wishlist
                    </h1>
                    <p class="text-gray-600">Save your favorite products for later</p>
                </div>
                
                <!-- Wishlist Summary -->
                <div class="mt-4 md:mt-0 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="bg-blue-50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-blue-600"><?= $wishlist_summary['total_items'] ?></div>
                        <div class="text-sm text-blue-600">Total Items</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-green-600">₦<?= number_format($wishlist_summary['total_value'], 2) ?></div>
                        <div class="text-sm text-green-600">Total Value</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-yellow-600"><?= $wishlist_summary['discounted_items'] ?></div>
                        <div class="text-sm text-yellow-600">On Sale</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-red-600"><?= $wishlist_summary['out_of_stock_items'] ?></div>
                        <div class="text-sm text-red-600">Out of Stock</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <?php if ($total_items > 0): ?>
            <div class="mt-6 flex flex-wrap gap-3">
                <button onclick="selectAllItems()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                    <i class="fas fa-check-square mr-2"></i>Select All
                </button>
                <button onclick="bulkMoveToCart()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300">
                    <i class="fas fa-shopping-cart mr-2"></i>Move Selected to Cart
                </button>
                <button onclick="bulkRemove()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300">
                    <i class="fas fa-trash mr-2"></i>Remove Selected
                </button>
                <button onclick="shareWishlist()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-300">
                    <i class="fas fa-share mr-2"></i>Share Wishlist
                </button>
                <button onclick="clearWishlist()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                    <i class="fas fa-broom mr-2"></i>Clear All
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($wishlist_items)): ?>
        <!-- Empty Wishlist -->
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <div class="max-w-md mx-auto">
                <i class="fas fa-heart text-6xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Your wishlist is empty</h2>
                <p class="text-gray-600 mb-8">Start adding products you love to your wishlist. You can save items for later and keep track of your favorites.</p>
                <a href="products.php" class="inline-flex items-center bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-300">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Start Shopping
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Wishlist Items Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            <?php foreach ($wishlist_items as $item): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition duration-300 wishlist-item" data-product-id="<?= $item['product_id'] ?>">
                <!-- Selection Checkbox -->
                <div class="absolute top-3 left-3 z-10">
                    <input type="checkbox" class="item-checkbox w-5 h-5 text-blue-600 rounded" value="<?= $item['product_id'] ?>">
                </div>
                
                <!-- Product Image -->
                <div class="relative">
                    <img src="<?= $item['image'] ? 'uploads/products/' . $item['image'] : 'get_placeholder_image.php?text=' . urlencode($item['name']) ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         class="w-full h-48 object-cover">
                    
                    <!-- Stock Status -->
                    <?php if ($item['stock_quantity'] == 0): ?>
                    <div class="absolute top-3 right-3 bg-red-500 text-white px-2 py-1 rounded text-xs font-medium">
                        Out of Stock
                    </div>
                    <?php elseif ($item['stock_quantity'] <= 5): ?>
                    <div class="absolute top-3 right-3 bg-yellow-500 text-white px-2 py-1 rounded text-xs font-medium">
                        Low Stock
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quick Actions -->
                    <div class="absolute bottom-3 right-3 flex space-x-2">
                        <button onclick="addToComparison(<?= $item['product_id'] ?>)" 
                                class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full shadow-sm transition duration-300"
                                title="Add to Comparison">
                            <i class="fas fa-balance-scale text-sm"></i>
                        </button>
                        <button onclick="removeFromWishlist(<?= $item['product_id'] ?>)" 
                                class="bg-white bg-opacity-90 hover:bg-opacity-100 text-red-500 p-2 rounded-full shadow-sm transition duration-300"
                                title="Remove from Wishlist">
                            <i class="fas fa-heart-broken text-sm"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <div class="mb-2">
                        <span class="text-xs text-gray-500 uppercase tracking-wide"><?= htmlspecialchars($item['category_name']) ?></span>
                    </div>
                    
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                        <a href="product-details.php?id=<?= $item['product_id'] ?>" class="hover:text-green-600 transition duration-300">
                            <?= htmlspecialchars($item['name']) ?>
                        </a>
                    </h3>
                    
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars($item['description']) ?></p>
                    
                    <!-- Price -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <?php if ($item['discount_price'] > 0): ?>
                            <span class="text-lg font-bold text-green-600">₦<?= number_format($item['discount_price'], 2) ?></span>
                            <span class="text-sm text-gray-500 line-through">₦<?= number_format($item['price'], 2) ?></span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">
                                <?= round((($item['price'] - $item['discount_price']) / $item['price']) * 100) ?>% OFF
                            </span>
                            <?php else: ?>
                            <span class="text-lg font-bold text-gray-900">₦<?= number_format($item['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-2">
                        <?php if ($item['stock_quantity'] > 0): ?>
                        <button onclick="moveToCart(<?= $item['product_id'] ?>)" 
                                class="flex-1 bg-green-600 text-white py-2 px-3 rounded-lg hover:bg-green-700 transition duration-300 text-sm">
                            <i class="fas fa-cart-plus mr-1"></i>Add to Cart
                        </button>
                        <?php else: ?>
                        <button disabled class="flex-1 bg-gray-300 text-gray-500 py-2 px-3 rounded-lg text-sm cursor-not-allowed">
                            <i class="fas fa-times mr-1"></i>Out of Stock
                        </button>
                        <?php endif; ?>
                        
                        <a href="product-details.php?id=<?= $item['product_id'] ?>" 
                           class="bg-blue-600 text-white py-2 px-3 rounded-lg hover:bg-blue-700 transition duration-300 text-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $total_items) ?> of <?= $total_items ?> items
                </div>
                
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> px-3 py-2 rounded-lg transition duration-300">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Share Your Wishlist</h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <p class="text-gray-600 mb-3">Share your wishlist with friends and family. The link will be valid for 24 hours.</p>
                <div class="flex">
                    <input type="text" id="shareUrl" readonly 
                           class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50">
                    <button onclick="copyShareUrl()" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex space-x-3">
                <button onclick="shareViaWhatsApp()" class="flex-1 bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition duration-300">
                    <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                </button>
                <button onclick="shareViaEmail()" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-300">
                    <i class="fas fa-envelope mr-2"></i>Email
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Wishlist Management Functions
function selectAllItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
}

function getSelectedItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    return Array.from(checkboxes).map(cb => parseInt(cb.value));
}

function bulkMoveToCart() {
    const selectedItems = getSelectedItems();
    if (selectedItems.length === 0) {
        showNotification('Please select items to move to cart', 'warning');
        return;
    }
    
    if (confirm(`Move ${selectedItems.length} item(s) to cart?`)) {
        fetch('ajax/phase3.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=wishlist_bulk_operation&operation=move_to_cart&product_ids=${JSON.stringify(selectedItems)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred', 'error');
        });
    }
}

function bulkRemove() {
    const selectedItems = getSelectedItems();
    if (selectedItems.length === 0) {
        showNotification('Please select items to remove', 'warning');
        return;
    }
    
    if (confirm(`Remove ${selectedItems.length} item(s) from wishlist?`)) {
        fetch('ajax/phase3.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=wishlist_bulk_operation&operation=remove&product_ids=${JSON.stringify(selectedItems)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred', 'error');
        });
    }
}

function removeFromWishlist(productId) {
    if (confirm('Remove this item from your wishlist?')) {
        fetch('ajax/phase3.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_from_wishlist&product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                document.querySelector(`[data-product-id="${productId}"]`).remove();
                updateWishlistCount();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred', 'error');
        });
    }
}

function moveToCart(productId) {
    fetch('ajax/phase3.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=move_to_cart&product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            document.querySelector(`[data-product-id="${productId}"]`).remove();
            updateCartCount();
            updateWishlistCount();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred', 'error');
    });
}

function addToComparison(productId) {
    fetch('ajax/phase3.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_comparison&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'warning');
    })
    .catch(error => {
        showNotification('An error occurred', 'error');
    });
}

function clearWishlist() {
    if (confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
        fetch('ajax/phase3.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_wishlist'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred', 'error');
        });
    }
}

// Share Functions
function shareWishlist() {
    fetch('ajax/phase3.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=share_wishlist'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('shareUrl').value = data.share_url;
            document.getElementById('shareModal').classList.remove('hidden');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred', 'error');
    });
}

function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}

function copyShareUrl() {
    const shareUrl = document.getElementById('shareUrl');
    shareUrl.select();
    document.execCommand('copy');
    showNotification('Link copied to clipboard!', 'success');
}

function shareViaWhatsApp() {
    const shareUrl = document.getElementById('shareUrl').value;
    const message = `Check out my wishlist from Hi5ve MarketPlace: ${shareUrl}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank');
}

function shareViaEmail() {
    const shareUrl = document.getElementById('shareUrl').value;
    const subject = 'My Hi5ve MarketPlace Wishlist';
    const body = `Hi! I wanted to share my wishlist from Hi5ve MarketPlace with you. Check it out: ${shareUrl}`;
    window.open(`mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`, '_blank');
}

// Utility Functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle'
            } mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function updateWishlistCount() {
    fetch('ajax/phase3.php?action=get_wishlist_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const countElement = document.getElementById('wishlist-count');
                if (countElement) {
                    countElement.textContent = data.count;
                }
            }
        });
}

function updateCartCount() {
    fetch('ajax/cart.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const countElement = document.getElementById('cart-count');
                if (countElement) {
                    countElement.textContent = data.count;
                }
            }
        });
}
</script>

<?php include 'includes/footer.php'; ?> 