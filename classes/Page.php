<?php
require_once __DIR__ . '/../config/config.php';

class Page {
    private $conn;
    private $table = 'pages';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new page
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (title, slug, content, meta_title, meta_description, status, created_by) 
                     VALUES (:title, :slug, :content, :meta_title, :meta_description, :status, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':slug', $data['slug']);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':meta_title', $data['meta_title']);
            $stmt->bindParam(':meta_description', $data['meta_description']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'page_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create page'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Page slug already exists'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Update page
    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET title = :title, slug = :slug, content = :content, 
                         meta_title = :meta_title, meta_description = :meta_description, 
                         status = :status, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':slug', $data['slug']);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':meta_title', $data['meta_title']);
            $stmt->bindParam(':meta_description', $data['meta_description']);
            $stmt->bindParam(':status', $data['status']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get all pages
    public function getAll($status = null, $limit = 50, $offset = 0) {
        try {
            $where_clause = $status ? "WHERE status = :status" : "";
            $query = "SELECT p.*, u.first_name, u.last_name 
                     FROM " . $this->table . " p 
                     LEFT JOIN users u ON p.created_by = u.id 
                     " . $where_clause . " 
                     ORDER BY p.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get page by ID
    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get page by slug
    public function getBySlug($slug) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE slug = :slug AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete page
    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Page deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete page'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Generate slug from title
    public function generateSlug($title, $exclude_id = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $original_slug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $exclude_id)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Check if slug exists
    private function slugExists($slug, $exclude_id = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE slug = :slug";
            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get total pages count
    public function getTotalCount($status = null) {
        try {
            $where_clause = $status ? "WHERE status = :status" : "";
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " " . $where_clause;
            
            $stmt = $this->conn->prepare($query);
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Toggle page status
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

    // Get page statistics
    public function getStats() {
        try {
            $stats = [];
            
            // Total pages
            $query = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_pages'] = $stmt->fetch()['total'];
            
            // Active pages
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['active_pages'] = $stmt->fetch()['total'];
            
            // Inactive pages
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'inactive'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['inactive_pages'] = $stmt->fetch()['total'];
            
            // Recent pages (last 30 days)
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['recent_pages'] = $stmt->fetch()['total'];
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_pages' => 0,
                'active_pages' => 0,
                'inactive_pages' => 0,
                'recent_pages' => 0
            ];
        }
    }
}
?> 