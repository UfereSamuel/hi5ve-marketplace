<?php
/**
 * Wishlist Class - Handles user wishlists
 * Extends BaseModel for DRY principles
 * Hi5ve MarketPlace - Phase 3
 */

require_once __DIR__ . '/../Core/BaseModel.php';

class Wishlist extends BaseModel {
    protected $table = 'wishlists';
    protected $fillable = ['user_id', 'product_id'];
    
    /**
     * Add product to wishlist
     */
    public function addToWishlist($user_id, $product_id) {
        // Check if already in wishlist
        if ($this->isInWishlist($user_id, $product_id)) {
            return ['success' => false, 'message' => 'Product already in wishlist'];
        }
        
        $wishlist_id = $this->create([
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);
        
        if ($wishlist_id) {
            return ['success' => true, 'message' => 'Product added to wishlist', 'id' => $wishlist_id];
        }
        
        return ['success' => false, 'message' => 'Failed to add product to wishlist'];
    }
    
    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist($user_id, $product_id) {
        $result = $this->query(
            "DELETE FROM {$this->table} WHERE user_id = :user_id AND product_id = :product_id",
            [':user_id' => $user_id, ':product_id' => $product_id]
        );
        
        if ($result > 0) {
            return ['success' => true, 'message' => 'Product removed from wishlist'];
        }
        
        return ['success' => false, 'message' => 'Product not found in wishlist'];
    }
    
    /**
     * Check if product is in user's wishlist
     */
    public function isInWishlist($user_id, $product_id) {
        $item = $this->where(
            'user_id = :user_id AND product_id = :product_id',
            [':user_id' => $user_id, ':product_id' => $product_id],
            1
        );
        
        return $item !== false;
    }
    
    /**
     * Get user's wishlist with product details
     */
    public function getUserWishlist($user_id, $limit = 20, $offset = 0) {
        $sql = "
            SELECT 
                w.*,
                p.name,
                p.description,
                p.price,
                p.discount_price,
                p.image,
                p.stock_quantity,
                p.status as product_status,
                c.name as category_name
            FROM {$this->table} w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = :user_id AND p.status = 'active'
            ORDER BY w.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Wishlist Get Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get wishlist count for user
     */
    public function getWishlistCount($user_id) {
        return $this->count('user_id = :user_id', [':user_id' => $user_id]);
    }
    
    /**
     * Move wishlist item to cart
     */
    public function moveToCart($user_id, $product_id, $quantity = 1) {
        // Check if product is in wishlist
        if (!$this->isInWishlist($user_id, $product_id)) {
            return ['success' => false, 'message' => 'Product not in wishlist'];
        }
        
        // Add to cart (using existing Cart class)
        require_once __DIR__ . '/../../classes/Cart.php';
        $cart = new Cart();
        
        $cart_result = $cart->addItem($product_id, $quantity, $user_id);
        
        if ($cart_result) {
            // Remove from wishlist
            $this->removeFromWishlist($user_id, $product_id);
            return ['success' => true, 'message' => 'Product moved to cart'];
        }
        
        return ['success' => false, 'message' => 'Failed to move product to cart'];
    }
    
    /**
     * Clear user's entire wishlist
     */
    public function clearWishlist($user_id) {
        $result = $this->query(
            "DELETE FROM {$this->table} WHERE user_id = :user_id",
            [':user_id' => $user_id]
        );
        
        return $result > 0;
    }
    
    /**
     * Get wishlist items by product IDs
     */
    public function getWishlistByProducts($user_id, $product_ids) {
        if (empty($product_ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND product_id IN ({$placeholders})";
        
        $params = array_merge([$user_id], $product_ids);
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Wishlist Get By Products Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get popular wishlist products
     */
    public function getPopularWishlistProducts($limit = 10) {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.price,
                p.discount_price,
                p.image,
                COUNT(w.product_id) as wishlist_count
            FROM products p
            JOIN {$this->table} w ON p.id = w.product_id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY wishlist_count DESC
            LIMIT :limit
        ";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Popular Wishlist Products Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get wishlist analytics for admin
     */
    public function getWishlistAnalytics($days = 30) {
        $sql = "
            SELECT 
                DATE(w.created_at) as date,
                COUNT(*) as items_added,
                COUNT(DISTINCT w.user_id) as unique_users,
                COUNT(DISTINCT w.product_id) as unique_products
            FROM {$this->table} w
            WHERE w.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(w.created_at)
            ORDER BY date DESC
        ";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Wishlist Analytics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get products frequently wishlisted together
     */
    public function getFrequentlyWishlistedTogether($product_id, $limit = 5) {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.price,
                p.discount_price,
                p.image,
                COUNT(*) as frequency
            FROM {$this->table} w1
            JOIN {$this->table} w2 ON w1.user_id = w2.user_id AND w1.product_id != w2.product_id
            JOIN products p ON w2.product_id = p.id
            WHERE w1.product_id = :product_id AND p.status = 'active'
            GROUP BY p.id
            ORDER BY frequency DESC
            LIMIT :limit
        ";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Frequently Wishlisted Together Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Share wishlist (generate shareable link)
     */
    public function shareWishlist($user_id) {
        // Generate a unique share token
        $share_token = bin2hex(random_bytes(16));
        
        // Store in session or database for temporary access
        $_SESSION['shared_wishlist_' . $share_token] = [
            'user_id' => $user_id,
            'expires' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        return [
            'success' => true,
            'share_url' => SITE_URL . "/shared-wishlist.php?token=" . $share_token,
            'token' => $share_token
        ];
    }
    
    /**
     * Get shared wishlist by token
     */
    public function getSharedWishlist($token) {
        if (!isset($_SESSION['shared_wishlist_' . $token])) {
            return ['success' => false, 'message' => 'Invalid or expired share link'];
        }
        
        $share_data = $_SESSION['shared_wishlist_' . $token];
        
        if ($share_data['expires'] < time()) {
            unset($_SESSION['shared_wishlist_' . $token]);
            return ['success' => false, 'message' => 'Share link has expired'];
        }
        
        $wishlist = $this->getUserWishlist($share_data['user_id']);
        
        return [
            'success' => true,
            'wishlist' => $wishlist,
            'user_id' => $share_data['user_id']
        ];
    }
    
    /**
     * Get wishlist summary for user
     */
    public function getWishlistSummary($user_id) {
        $sql = "
            SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN p.discount_price > 0 THEN p.discount_price ELSE p.price END) as total_value,
                COUNT(CASE WHEN p.stock_quantity = 0 THEN 1 END) as out_of_stock_items,
                COUNT(CASE WHEN p.discount_price > 0 THEN 1 END) as discounted_items
            FROM {$this->table} w
            JOIN products p ON w.product_id = p.id
            WHERE w.user_id = :user_id AND p.status = 'active'
        ";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Wishlist Summary Error: " . $e->getMessage());
            return [
                'total_items' => 0,
                'total_value' => 0,
                'out_of_stock_items' => 0,
                'discounted_items' => 0
            ];
        }
    }
    
    /**
     * Bulk operations on wishlist
     */
    public function bulkOperation($user_id, $product_ids, $operation) {
        if (empty($product_ids)) {
            return ['success' => false, 'message' => 'No products selected'];
        }
        
        switch ($operation) {
            case 'remove':
                $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND product_id IN ({$placeholders})";
                $params = array_merge([$user_id], $product_ids);
                
                try {
                    $stmt = $this->conn->prepare($sql);
                    $result = $stmt->execute($params);
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Products removed from wishlist'];
                    }
                } catch (PDOException $e) {
                    error_log("Bulk Remove Error: " . $e->getMessage());
                }
                break;
                
            case 'move_to_cart':
                $success_count = 0;
                foreach ($product_ids as $product_id) {
                    $result = $this->moveToCart($user_id, $product_id);
                    if ($result['success']) {
                        $success_count++;
                    }
                }
                
                if ($success_count > 0) {
                    return ['success' => true, 'message' => "{$success_count} products moved to cart"];
                }
                break;
        }
        
        return ['success' => false, 'message' => 'Operation failed'];
    }
} 