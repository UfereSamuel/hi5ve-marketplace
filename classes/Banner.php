<?php
require_once __DIR__ . '/../config/config.php';

class Banner {
    private $conn;
    protected $table = 'banners';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Create a new banner
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (title, description, image_path, link_type, link_value, position, display_order, start_date, end_date) 
                     VALUES (:title, :description, :image_path, :link_type, :link_value, :position, :display_order, :start_date, :end_date)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':image_path', $data['image_path']);
            $stmt->bindParam(':link_type', $data['link_type']);
            $stmt->bindParam(':link_value', $data['link_value']);
            $stmt->bindParam(':position', $data['position']);
            $stmt->bindParam(':display_order', $data['display_order']);
            $stmt->bindParam(':start_date', $data['start_date']);
            $stmt->bindParam(':end_date', $data['end_date']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'banner_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create banner'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get active banners by position
    public function getActiveByPosition($position) {
        try {
            $query = "SELECT b.*, 
                             CASE 
                                 WHEN b.link_type = 'product' THEN p.name
                                 WHEN b.link_type = 'category' THEN c.name
                                 ELSE NULL
                             END as link_name
                     FROM " . $this->table . " b
                     LEFT JOIN products p ON b.link_type = 'product' AND b.link_value = p.id
                     LEFT JOIN categories c ON b.link_type = 'category' AND c.id = b.link_value
                     WHERE b.is_active = 1 
                     AND b.position = :position
                     AND (b.start_date IS NULL OR b.start_date <= NOW())
                     AND (b.end_date IS NULL OR b.end_date >= NOW())
                     ORDER BY b.display_order ASC, b.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':position', $position);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get all banners for admin management
    public function getAllForAdmin($limit = 20, $offset = 0) {
        try {
            $query = "SELECT b.*, 
                             CASE 
                                 WHEN b.link_type = 'product' THEN p.name
                                 WHEN b.link_type = 'category' THEN c.name
                                 ELSE b.link_value
                             END as link_name,
                             CASE 
                                 WHEN b.end_date < NOW() THEN 'expired'
                                 WHEN b.start_date > NOW() THEN 'scheduled'
                                 WHEN b.is_active = 1 THEN 'active'
                                 ELSE 'inactive'
                             END as status
                     FROM " . $this->table . " b
                     LEFT JOIN products p ON b.link_type = 'product' AND b.link_value = p.id
                     LEFT JOIN categories c ON b.link_type = 'category' AND c.id = b.link_value
                     ORDER BY b.created_at DESC
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
    
    // Get banner statistics
    public function getStats() {
        try {
            $query = "SELECT 
                         COUNT(*) as total_banners,
                         SUM(CASE WHEN is_active = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) THEN 1 ELSE 0 END) as active_banners,
                         SUM(CASE WHEN end_date < NOW() THEN 1 ELSE 0 END) as expired_banners,
                         SUM(click_count) as total_clicks,
                         SUM(view_count) as total_views
                     FROM " . $this->table;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return [
                'total_banners' => 0,
                'active_banners' => 0,
                'expired_banners' => 0,
                'total_clicks' => 0,
                'total_views' => 0
            ];
        }
    }
    
    // Update banner
    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET title = :title, description = :description, image_path = :image_path,
                         link_type = :link_type, link_value = :link_value, position = :position,
                         display_order = :display_order, start_date = :start_date, end_date = :end_date,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':image_path', $data['image_path']);
            $stmt->bindParam(':link_type', $data['link_type']);
            $stmt->bindParam(':link_value', $data['link_value']);
            $stmt->bindParam(':position', $data['position']);
            $stmt->bindParam(':display_order', $data['display_order']);
            $stmt->bindParam(':start_date', $data['start_date']);
            $stmt->bindParam(':end_date', $data['end_date']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Toggle banner status
    public function toggleStatus($id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Record banner view
    public function recordView($id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET view_count = view_count + 1 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Record banner click
    public function recordClick($id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET click_count = click_count + 1 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get banner link URL
    public function getBannerLink($banner) {
        switch ($banner['link_type']) {
            case 'product':
                return 'product.php?id=' . $banner['link_value'];
            case 'category':
                return 'category.php?id=' . $banner['link_value'];
            case 'url':
                return $banner['link_value'];
            default:
                return '#';
        }
    }
    
    // Get banner by ID
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
    
    // Delete banner
    public function delete($id) {
        try {
            // First get the banner to delete the image file
            $banner = $this->getById($id);
            if ($banner && file_exists($banner['image_path'])) {
                unlink($banner['image_path']);
            }
            
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get total count for pagination
    public function getTotalCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
} 