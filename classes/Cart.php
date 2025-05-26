<?php
require_once __DIR__ . '/../config/config.php';

class Cart {
    private $conn;
    private $user_table = 'cart';
    private $guest_table = 'guest_cart';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Add item to cart
    public function addItem($product_id, $quantity = 1, $user_id = null) {
        try {
            if ($user_id) {
                // Registered user cart
                $query = "INSERT INTO " . $this->user_table . " (user_id, product_id, quantity) 
                         VALUES (:user_id, :product_id, :quantity)
                         ON DUPLICATE KEY UPDATE quantity = quantity + :quantity";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', $quantity);
            } else {
                // Guest cart
                $session_id = session_id();
                $query = "INSERT INTO " . $this->guest_table . " (session_id, product_id, quantity) 
                         VALUES (:session_id, :product_id, :quantity)
                         ON DUPLICATE KEY UPDATE quantity = quantity + :quantity";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', $quantity);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update item quantity
    public function updateQuantity($product_id, $quantity, $user_id = null) {
        try {
            if ($quantity <= 0) {
                return $this->removeItem($product_id, $user_id);
            }

            if ($user_id) {
                // Registered user cart
                $query = "UPDATE " . $this->user_table . " 
                         SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
                         WHERE user_id = :user_id AND product_id = :product_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', $quantity);
            } else {
                // Guest cart
                $session_id = session_id();
                $query = "UPDATE " . $this->guest_table . " 
                         SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
                         WHERE session_id = :session_id AND product_id = :product_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', $quantity);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Remove item from cart
    public function removeItem($product_id, $user_id = null) {
        try {
            if ($user_id) {
                // Registered user cart
                $query = "DELETE FROM " . $this->user_table . " 
                         WHERE user_id = :user_id AND product_id = :product_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':product_id', $product_id);
            } else {
                // Guest cart
                $session_id = session_id();
                $query = "DELETE FROM " . $this->guest_table . " 
                         WHERE session_id = :session_id AND product_id = :product_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':product_id', $product_id);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get cart items
    public function getItems($user_id = null) {
        try {
            if ($user_id) {
                // Registered user cart
                $query = "SELECT c.*, p.name, p.price, p.discount_price, p.image, p.unit, p.stock_quantity,
                                CASE 
                                    WHEN p.discount_price > 0 THEN p.discount_price 
                                    ELSE p.price 
                                END as effective_price
                         FROM " . $this->user_table . " c
                         JOIN products p ON c.product_id = p.id
                         WHERE c.user_id = :user_id AND p.status = 'active'
                         ORDER BY c.created_at DESC";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
            } else {
                // Guest cart
                $session_id = session_id();
                $query = "SELECT c.*, p.name, p.price, p.discount_price, p.image, p.unit, p.stock_quantity,
                                CASE 
                                    WHEN p.discount_price > 0 THEN p.discount_price 
                                    ELSE p.price 
                                END as effective_price
                         FROM " . $this->guest_table . " c
                         JOIN products p ON c.product_id = p.id
                         WHERE c.session_id = :session_id AND p.status = 'active'
                         ORDER BY c.created_at DESC";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get cart summary
    public function getSummary($user_id = null) {
        $items = $this->getItems($user_id);
        $total_items = 0;
        $subtotal = 0;

        foreach ($items as $item) {
            $total_items += $item['quantity'];
            $subtotal += $item['effective_price'] * $item['quantity'];
        }

        return [
            'items' => $items,
            'total_items' => $total_items,
            'subtotal' => $subtotal,
            'delivery_fee' => $this->getDeliveryFee($subtotal),
            'total' => $subtotal + $this->getDeliveryFee($subtotal)
        ];
    }

    // Get cart count
    public function getCount($user_id = null) {
        try {
            if ($user_id) {
                // Registered user cart
                $query = "SELECT SUM(quantity) as total FROM " . $this->user_table . " WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
            } else {
                // Guest cart
                $session_id = session_id();
                $query = "SELECT SUM(quantity) as total FROM " . $this->guest_table . " WHERE session_id = :session_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Clear cart
    public function clear($user_id = null) {
        try {
            if ($user_id) {
                // Registered user cart
                $query = "DELETE FROM " . $this->user_table . " WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
            } else {
                // Guest cart
                $session_id = session_id();
                $query = "DELETE FROM " . $this->guest_table . " WHERE session_id = :session_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Transfer guest cart to user cart (when guest registers or logs in)
    public function transferGuestCart($user_id) {
        try {
            $session_id = session_id();
            
            // Get guest cart items
            $guest_items = $this->getItems();
            
            if (empty($guest_items)) {
                return true;
            }

            // Transfer each item
            foreach ($guest_items as $item) {
                $this->addItem($item['product_id'], $item['quantity'], $user_id);
            }

            // Clear guest cart
            $this->clear();
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get delivery fee based on order amount
    private function getDeliveryFee($subtotal) {
        // Get delivery fee from settings or use default
        try {
            $query = "SELECT setting_value FROM site_settings WHERE setting_key = 'delivery_fee'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $delivery_fee = $result ? (float)$result['setting_value'] : 500;
            
            // Get minimum order amount for free delivery
            $query = "SELECT setting_value FROM site_settings WHERE setting_key = 'min_order_amount'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $min_order = $result ? (float)$result['setting_value'] : 1000;
            
            // Free delivery if order meets minimum amount
            return $subtotal >= $min_order ? 0 : $delivery_fee;
        } catch (PDOException $e) {
            return 500; // Default delivery fee
        }
    }

    // Validate cart items (check stock availability)
    public function validateCart($user_id = null) {
        $items = $this->getItems($user_id);
        $errors = [];

        foreach ($items as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                $errors[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $item['stock_quantity'],
                    'message' => "Only {$item['stock_quantity']} {$item['unit']}(s) of {$item['name']} available"
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    // Clean old guest carts (cleanup function)
    public function cleanOldGuestCarts($days = 7) {
        try {
            $query = "DELETE FROM " . $this->guest_table . " 
                     WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?> 