<?php
/**
 * Phase 3 AJAX Handler - Hi5ve MarketPlace
 * Handles advanced features: Wishlist, Product Variants, Gallery, etc.
 */

session_start();
require_once '../config/config.php';
require_once '../classes/Core/BaseModel.php';
require_once '../classes/User/Wishlist.php';
require_once '../classes/Product/ProductVariant.php';
require_once '../classes/Product/ProductGallery.php';

header('Content-Type: application/json');

// Check if user is logged in for certain operations
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ==================== WISHLIST OPERATIONS ====================
        case 'add_to_wishlist':
            requireAuth();
            $wishlist = new Wishlist();
            $result = $wishlist->addToWishlist($_SESSION['user_id'], $_POST['product_id']);
            echo json_encode($result);
            break;
            
        case 'remove_from_wishlist':
            requireAuth();
            $wishlist = new Wishlist();
            $result = $wishlist->removeFromWishlist($_SESSION['user_id'], $_POST['product_id']);
            echo json_encode($result);
            break;
            
        case 'get_wishlist':
            requireAuth();
            $wishlist = new Wishlist();
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $items = $wishlist->getUserWishlist($_SESSION['user_id'], $limit, $offset);
            $total = $wishlist->getWishlistCount($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ]);
            break;
            
        case 'get_wishlist_count':
            if (isset($_SESSION['user_id'])) {
                $wishlist = new Wishlist();
                $count = $wishlist->getWishlistCount($_SESSION['user_id']);
                echo json_encode(['success' => true, 'count' => $count]);
            } else {
                echo json_encode(['success' => true, 'count' => 0]);
            }
            break;
            
        case 'move_to_cart':
            requireAuth();
            $wishlist = new Wishlist();
            $result = $wishlist->moveToCart($_SESSION['user_id'], $_POST['product_id'], $_POST['quantity'] ?? 1);
            echo json_encode($result);
            break;
            
        case 'clear_wishlist':
            requireAuth();
            $wishlist = new Wishlist();
            $result = $wishlist->clearWishlist($_SESSION['user_id']);
            echo json_encode(['success' => $result, 'message' => $result ? 'Wishlist cleared' : 'Failed to clear wishlist']);
            break;
            
        case 'share_wishlist':
            requireAuth();
            $wishlist = new Wishlist();
            $result = $wishlist->shareWishlist($_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'wishlist_bulk_operation':
            requireAuth();
            $wishlist = new Wishlist();
            $product_ids = $_POST['product_ids'] ?? [];
            $operation = $_POST['operation'] ?? '';
            
            if (is_string($product_ids)) {
                $product_ids = json_decode($product_ids, true);
            }
            
            $result = $wishlist->bulkOperation($_SESSION['user_id'], $product_ids, $operation);
            echo json_encode($result);
            break;
            
        // ==================== PRODUCT VARIANTS ====================
        case 'get_product_variants':
            $variant = new ProductVariant();
            $product_id = $_GET['product_id'] ?? 0;
            $variants = $variant->getVariantsByType($product_id);
            echo json_encode(['success' => true, 'variants' => $variants]);
            break;
            
        case 'get_variant_price':
            $variant = new ProductVariant();
            $variant_id = $_GET['variant_id'] ?? 0;
            $base_price = (float)($_GET['base_price'] ?? 0);
            $price = $variant->getVariantPrice($variant_id, $base_price);
            echo json_encode(['success' => true, 'price' => $price]);
            break;
            
        case 'check_variant_stock':
            $variant = new ProductVariant();
            $variant_id = $_GET['variant_id'] ?? 0;
            $quantity = (int)($_GET['quantity'] ?? 1);
            $has_stock = $variant->hasStock($variant_id, $quantity);
            echo json_encode(['success' => true, 'has_stock' => $has_stock]);
            break;
            
        case 'get_variant_combination':
            $variant = new ProductVariant();
            $product_id = $_POST['product_id'] ?? 0;
            $combinations = $_POST['combinations'] ?? [];
            
            if (is_string($combinations)) {
                $combinations = json_decode($combinations, true);
            }
            
            $variant_data = $variant->getVariantByCombination($product_id, $combinations);
            echo json_encode(['success' => true, 'variant' => $variant_data]);
            break;
            
        // ==================== PRODUCT GALLERY ====================
        case 'get_product_gallery':
            $gallery = new ProductGallery();
            $product_id = $_GET['product_id'] ?? 0;
            $images = $gallery->getProductImages($product_id);
            echo json_encode(['success' => true, 'images' => $images]);
            break;
            
        case 'set_primary_image':
            // Admin only
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            $gallery = new ProductGallery();
            $image_id = $_POST['image_id'] ?? 0;
            $product_id = $_POST['product_id'] ?? 0;
            $result = $gallery->setPrimaryImage($image_id, $product_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Primary image updated' : 'Failed to update primary image']);
            break;
            
        case 'reorder_images':
            // Admin only
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            $gallery = new ProductGallery();
            $product_id = $_POST['product_id'] ?? 0;
            $image_orders = $_POST['image_orders'] ?? [];
            
            if (is_string($image_orders)) {
                $image_orders = json_decode($image_orders, true);
            }
            
            $result = $gallery->reorderImages($product_id, $image_orders);
            echo json_encode(['success' => $result, 'message' => $result ? 'Images reordered' : 'Failed to reorder images']);
            break;
            
        case 'delete_gallery_image':
            // Admin only
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            $gallery = new ProductGallery();
            $image_id = $_POST['image_id'] ?? 0;
            $result = $gallery->deleteImage($image_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Image deleted' : 'Failed to delete image']);
            break;
            
        // ==================== PRODUCT VIEWS TRACKING ====================
        case 'track_product_view':
            $product_id = $_POST['product_id'] ?? 0;
            $user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            
            // Insert product view
            $database = new Database();
            $conn = $database->getConnection();
            
            $sql = "INSERT INTO product_views (product_id, user_id, session_id, ip_address, user_agent, referrer) 
                    VALUES (:product_id, :user_id, :session_id, :ip_address, :user_agent, :referrer)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':session_id', $session_id);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':referrer', $referrer);
            
            $result = $stmt->execute();
            
            // Update product view count
            if ($result) {
                $update_sql = "UPDATE products SET view_count = view_count + 1 WHERE id = :product_id";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(':product_id', $product_id);
                $update_stmt->execute();
            }
            
            // Add to recently viewed
            if ($user_id || $session_id) {
                $recent_sql = "INSERT INTO recently_viewed (user_id, session_id, product_id) 
                              VALUES (:user_id, :session_id, :product_id)
                              ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP";
                
                $recent_stmt = $conn->prepare($recent_sql);
                $recent_stmt->bindParam(':user_id', $user_id);
                $recent_stmt->bindParam(':session_id', $session_id);
                $recent_stmt->bindParam(':product_id', $product_id);
                $recent_stmt->execute();
            }
            
            echo json_encode(['success' => $result]);
            break;
            
        case 'get_recently_viewed':
            $user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            $limit = (int)($_GET['limit'] ?? 10);
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $where_conditions = [];
            $params = [];
            
            if ($user_id) {
                $where_conditions[] = "rv.user_id = :user_id";
                $params[':user_id'] = $user_id;
            } else {
                $where_conditions[] = "rv.session_id = :session_id";
                $params[':session_id'] = $session_id;
            }
            
            $where_clause = implode(' OR ', $where_conditions);
            
            $sql = "SELECT DISTINCT p.id, p.name, p.price, p.discount_price, p.image, rv.viewed_at
                    FROM recently_viewed rv
                    JOIN products p ON rv.product_id = p.id
                    WHERE ({$where_clause}) AND p.status = 'active'
                    ORDER BY rv.viewed_at DESC
                    LIMIT :limit";
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'products' => $products]);
            break;
            
        // ==================== PRODUCT COMPARISON ====================
        case 'add_to_comparison':
            $product_id = $_POST['product_id'] ?? 0;
            $user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            
            // Get current comparison list
            $comparison_key = $user_id ? "comparison_user_{$user_id}" : "comparison_session_{$session_id}";
            $comparison_list = $_SESSION[$comparison_key] ?? [];
            
            // Add product if not already in list (max 4 products)
            if (!in_array($product_id, $comparison_list)) {
                if (count($comparison_list) >= 4) {
                    echo json_encode(['success' => false, 'message' => 'Maximum 4 products can be compared']);
                    break;
                }
                $comparison_list[] = $product_id;
                $_SESSION[$comparison_key] = $comparison_list;
                
                echo json_encode(['success' => true, 'message' => 'Product added to comparison', 'count' => count($comparison_list)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product already in comparison']);
            }
            break;
            
        case 'remove_from_comparison':
            $product_id = $_POST['product_id'] ?? 0;
            $user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            
            $comparison_key = $user_id ? "comparison_user_{$user_id}" : "comparison_session_{$session_id}";
            $comparison_list = $_SESSION[$comparison_key] ?? [];
            
            $key = array_search($product_id, $comparison_list);
            if ($key !== false) {
                unset($comparison_list[$key]);
                $_SESSION[$comparison_key] = array_values($comparison_list);
                echo json_encode(['success' => true, 'message' => 'Product removed from comparison', 'count' => count($comparison_list)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not in comparison']);
            }
            break;
            
        case 'get_comparison_list':
            $user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            
            $comparison_key = $user_id ? "comparison_user_{$user_id}" : "comparison_session_{$session_id}";
            $comparison_list = $_SESSION[$comparison_key] ?? [];
            
            if (empty($comparison_list)) {
                echo json_encode(['success' => true, 'products' => [], 'count' => 0]);
                break;
            }
            
            // Get product details
            $database = new Database();
            $conn = $database->getConnection();
            
            $placeholders = str_repeat('?,', count($comparison_list) - 1) . '?';
            $sql = "SELECT id, name, price, discount_price, image, description, stock_quantity 
                    FROM products 
                    WHERE id IN ({$placeholders}) AND status = 'active'";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($comparison_list);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'products' => $products, 'count' => count($products)]);
            break;
            
        case 'clear_comparison':
            $user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            
            $comparison_key = $user_id ? "comparison_user_{$user_id}" : "comparison_session_{$session_id}";
            unset($_SESSION[$comparison_key]);
            
            echo json_encode(['success' => true, 'message' => 'Comparison list cleared']);
            break;
            
        // ==================== SEARCH SUGGESTIONS ====================
        case 'search_suggestions':
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'suggestions' => []]);
                break;
            }
            
            $database = new Database();
            $conn = $database->getConnection();
            
            $sql = "SELECT DISTINCT name as suggestion, 'product' as type
                    FROM products 
                    WHERE name LIKE :query AND status = 'active'
                    UNION
                    SELECT DISTINCT name as suggestion, 'category' as type
                    FROM categories 
                    WHERE name LIKE :query AND status = 'active'
                    ORDER BY suggestion
                    LIMIT :limit";
            
            $stmt = $conn->prepare($sql);
            $search_term = "%{$query}%";
            $stmt->bindParam(':query', $search_term);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'suggestions' => $suggestions]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Phase 3 AJAX Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
} 