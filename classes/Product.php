<?php
require_once __DIR__ . '/../config/config.php';

class Product {
    private $conn;
    private $table = 'products';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new product
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (name, description, category_id, price, discount_price, stock_quantity, unit, image, gallery, featured) 
                     VALUES (:name, :description, :category_id, :price, :discount_price, :stock_quantity, :unit, :image, :gallery, :featured)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':discount_price', $data['discount_price']);
            $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':gallery', $data['gallery']);
            $stmt->bindParam(':featured', $data['featured']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'product_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create product'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get all products with pagination
    public function getAll($limit = 12, $offset = 0) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.status = 'active' 
                     ORDER BY p.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get product by ID
    public function getById($id) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update product
    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET name = :name, description = :description, category_id = :category_id, 
                         price = :price, discount_price = :discount_price, stock_quantity = :stock_quantity, 
                         unit = :unit, image = :image, gallery = :gallery, featured = :featured, 
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':discount_price', $data['discount_price']);
            $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':gallery', $data['gallery']);
            $stmt->bindParam(':featured', $data['featured']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete product
    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Toggle product status
    public function toggleStatus($id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update stock quantity
    public function updateStock($id, $quantity) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET stock_quantity = stock_quantity - :quantity,
                         status = CASE WHEN (stock_quantity - :quantity) <= 0 THEN 'out_of_stock' ELSE status END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':quantity', $quantity);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get featured products
    public function getFeatured($limit = 8, $offset = 0) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.featured = 1 AND p.status = 'active' 
                     ORDER BY p.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get products by category
    public function getByCategory($category_id, $limit = 12, $offset = 0) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.category_id = :category_id AND p.status = 'active' 
                     ORDER BY p.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get category products count
    public function getCategoryCount($category_id) {
        try {
            $query = "SELECT COUNT(*) as total 
                     FROM " . $this->table . " 
                     WHERE category_id = :category_id AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Search products
    public function search($keyword, $limit = 20, $offset = 0) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE (p.name LIKE :keyword OR p.description LIKE :keyword) 
                     AND p.status = 'active' 
                     ORDER BY p.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $search_term = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $search_term);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get search results count
    public function getSearchCount($keyword) {
        try {
            $query = "SELECT COUNT(*) as total 
                     FROM " . $this->table . " 
                     WHERE (name LIKE :keyword OR description LIKE :keyword) 
                     AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $search_term = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $search_term);
            $stmt->execute();
            
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get total products count
    public function getTotalCount() {
        try {
            $query = "SELECT COUNT(*) as total 
                     FROM " . $this->table . " 
                     WHERE status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get low stock products (for admin alerts)
    public function getLowStock($threshold = 10) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.stock_quantity <= :threshold AND p.status != 'inactive' 
                     ORDER BY p.stock_quantity ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':threshold', $threshold);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get product statistics (for admin dashboard)
    public function getStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_products,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products,
                        COUNT(CASE WHEN status = 'out_of_stock' THEN 1 END) as out_of_stock,
                        COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_products,
                        AVG(price) as avg_price
                     FROM " . $this->table;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return [
                'total_products' => 0,
                'active_products' => 0,
                'out_of_stock' => 0,
                'featured_products' => 0,
                'avg_price' => 0
            ];
        }
    }

    // Get effective price (considering discount)
    public static function getEffectivePrice($product) {
        return $product['discount_price'] && $product['discount_price'] > 0 
               ? $product['discount_price'] 
               : $product['price'];
    }

    // Check if product has discount
    public static function hasDiscount($product) {
        return $product['discount_price'] && $product['discount_price'] > 0 && $product['discount_price'] < $product['price'];
    }

    // Calculate discount percentage
    public static function getDiscountPercentage($product) {
        if (!self::hasDiscount($product)) {
            return 0;
        }
        return round((($product['price'] - $product['discount_price']) / $product['price']) * 100);
    }

    // Get featured products count
    public function getFeaturedCount() {
        try {
            $query = "SELECT COUNT(*) as total 
                     FROM " . $this->table . " 
                     WHERE featured = 1 AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?> 