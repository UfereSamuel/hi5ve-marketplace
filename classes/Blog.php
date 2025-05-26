<?php
require_once __DIR__ . '/../config/config.php';

class Blog {
    private $conn;
    private $table = 'blog_posts';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new blog post
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (title, slug, excerpt, content, featured_image, meta_title, meta_description, status, author_id, published_at) 
                     VALUES (:title, :slug, :excerpt, :content, :featured_image, :meta_title, :meta_description, :status, :author_id, :published_at)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':slug', $data['slug']);
            $stmt->bindParam(':excerpt', $data['excerpt']);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':featured_image', $data['featured_image']);
            $stmt->bindParam(':meta_title', $data['meta_title']);
            $stmt->bindParam(':meta_description', $data['meta_description']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':author_id', $data['author_id']);
            $stmt->bindParam(':published_at', $data['published_at']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'post_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create blog post'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Blog post slug already exists'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Update blog post
    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET title = :title, slug = :slug, excerpt = :excerpt, content = :content, 
                         featured_image = :featured_image, meta_title = :meta_title, meta_description = :meta_description, 
                         status = :status, published_at = :published_at, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':slug', $data['slug']);
            $stmt->bindParam(':excerpt', $data['excerpt']);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':featured_image', $data['featured_image']);
            $stmt->bindParam(':meta_title', $data['meta_title']);
            $stmt->bindParam(':meta_description', $data['meta_description']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':published_at', $data['published_at']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get all blog posts
    public function getAll($status = null, $limit = 20, $offset = 0) {
        try {
            $where_clause = $status ? "WHERE bp.status = :status" : "";
            $query = "SELECT bp.*, u.first_name, u.last_name 
                     FROM " . $this->table . " bp 
                     LEFT JOIN users u ON bp.author_id = u.id 
                     " . $where_clause . " 
                     ORDER BY bp.created_at DESC 
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

    // Get published blog posts (for frontend)
    public function getPublished($limit = 10, $offset = 0) {
        try {
            $query = "SELECT bp.*, u.first_name, u.last_name 
                     FROM " . $this->table . " bp 
                     LEFT JOIN users u ON bp.author_id = u.id 
                     WHERE bp.status = 'published' AND bp.published_at <= NOW()
                     ORDER BY bp.published_at DESC 
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

    // Get blog post by ID
    public function getById($id) {
        try {
            $query = "SELECT bp.*, u.first_name, u.last_name 
                     FROM " . $this->table . " bp 
                     LEFT JOIN users u ON bp.author_id = u.id 
                     WHERE bp.id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get blog post by slug
    public function getBySlug($slug) {
        try {
            $query = "SELECT bp.*, u.first_name, u.last_name 
                     FROM " . $this->table . " bp 
                     LEFT JOIN users u ON bp.author_id = u.id 
                     WHERE bp.slug = :slug AND bp.status = 'published' AND bp.published_at <= NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete blog post
    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Blog post deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete blog post'];
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

    // Get total blog posts count
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

    // Toggle blog post status
    public function toggleStatus($id) {
        try {
            // Get current status
            $query = "SELECT status FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $current = $stmt->fetch();
            
            if (!$current) return false;
            
            // Determine new status
            $new_status = match($current['status']) {
                'draft' => 'published',
                'published' => 'archived',
                'archived' => 'draft',
                default => 'draft'
            };
            
            // Update status
            $query = "UPDATE " . $this->table . " 
                     SET status = :status, 
                         published_at = CASE WHEN :status = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get blog statistics
    public function getStats() {
        try {
            $stats = [];
            
            // Total posts
            $query = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_posts'] = $stmt->fetch()['total'];
            
            // Published posts
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'published'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['published_posts'] = $stmt->fetch()['total'];
            
            // Draft posts
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'draft'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['draft_posts'] = $stmt->fetch()['total'];
            
            // Archived posts
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'archived'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['archived_posts'] = $stmt->fetch()['total'];
            
            // Recent posts (last 30 days)
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['recent_posts'] = $stmt->fetch()['total'];
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_posts' => 0,
                'published_posts' => 0,
                'draft_posts' => 0,
                'archived_posts' => 0,
                'recent_posts' => 0
            ];
        }
    }

    // Get recent posts for dashboard
    public function getRecentPosts($limit = 5) {
        try {
            $query = "SELECT bp.*, u.first_name, u.last_name 
                     FROM " . $this->table . " bp 
                     LEFT JOIN users u ON bp.author_id = u.id 
                     ORDER BY bp.created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?> 