<?php
require_once __DIR__ . '/../config/config.php';

class Category {
    private $conn;
    private $table = 'categories';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new category
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " (name, description, image) 
                     VALUES (:name, :description, :image)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':image', $data['image']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'category_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create category'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get all categories
    public function getAll($active_only = true) {
        try {
            $where_clause = $active_only ? "WHERE c.status = 'active'" : "";
            
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM " . $this->table . " c 
                     LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                     " . $where_clause . " 
                     GROUP BY c.id 
                     ORDER BY c.name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get category by ID
    public function getById($id) {
        try {
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM " . $this->table . " c 
                     LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                     WHERE c.id = :id 
                     GROUP BY c.id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update category
    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET name = :name, description = :description, image = :image, 
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':image', $data['image']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete category
    public function delete($id) {
        try {
            // Check if category has products
            $query = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete category with existing products'];
            }
            
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Category deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete category'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Toggle category status
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

    // Get categories with products
    public function getCategoriesWithProducts() {
        try {
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM " . $this->table . " c 
                     INNER JOIN products p ON c.id = p.category_id 
                     WHERE c.status = 'active' AND p.status = 'active'
                     GROUP BY c.id 
                     HAVING product_count > 0
                     ORDER BY c.name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Check if category name exists
    public function nameExists($name, $exclude_id = null) {
        try {
            $query = "SELECT id FROM " . $this->table . " WHERE name = :name";
            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?> 