<?php
require_once __DIR__ . '/../config/config.php';

class Review {
    private $conn;
    private $table = 'product_reviews';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Add review
    public function add($data) {
        try {
            // Check if user already reviewed this product
            if ($this->hasUserReviewed($data['product_id'], $data['user_id'])) {
                return ['success' => false, 'message' => 'You have already reviewed this product'];
            }

            $query = "INSERT INTO " . $this->table . " 
                     (product_id, user_id, rating, comment, status) 
                     VALUES (:product_id, :user_id, :rating, :comment, :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':rating', $data['rating']);
            $stmt->bindParam(':comment', $data['comment']);
            $stmt->bindParam(':status', $data['status']);
            
            if ($stmt->execute()) {
                $review_id = $this->conn->lastInsertId();
                
                // Update product average rating
                $this->updateProductRating($data['product_id']);
                
                return ['success' => true, 'review_id' => $review_id, 'message' => 'Review added successfully'];
            }
            return ['success' => false, 'message' => 'Failed to add review'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get product reviews
    public function getProductReviews($product_id, $status = 'approved', $limit = 10, $offset = 0) {
        try {
            $query = "SELECT r.*, u.first_name, u.last_name, u.username 
                     FROM " . $this->table . " r 
                     JOIN users u ON r.user_id = u.id 
                     WHERE r.product_id = :product_id AND r.status = :status 
                     ORDER BY r.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get review statistics for product
    public function getProductStats($product_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_reviews,
                        AVG(rating) as average_rating,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                     FROM " . $this->table . " 
                     WHERE product_id = :product_id AND status = 'approved'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();

            $stats = $stmt->fetch();
            
            // Calculate percentages
            if ($stats['total_reviews'] > 0) {
                $stats['five_star_percent'] = round(($stats['five_star'] / $stats['total_reviews']) * 100);
                $stats['four_star_percent'] = round(($stats['four_star'] / $stats['total_reviews']) * 100);
                $stats['three_star_percent'] = round(($stats['three_star'] / $stats['total_reviews']) * 100);
                $stats['two_star_percent'] = round(($stats['two_star'] / $stats['total_reviews']) * 100);
                $stats['one_star_percent'] = round(($stats['one_star'] / $stats['total_reviews']) * 100);
                $stats['average_rating'] = round($stats['average_rating'], 1);
            } else {
                $stats['five_star_percent'] = 0;
                $stats['four_star_percent'] = 0;
                $stats['three_star_percent'] = 0;
                $stats['two_star_percent'] = 0;
                $stats['one_star_percent'] = 0;
                $stats['average_rating'] = 0;
            }

            return $stats;
        } catch (PDOException $e) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0, 'four_star' => 0, 'three_star' => 0, 'two_star' => 0, 'one_star' => 0,
                'five_star_percent' => 0, 'four_star_percent' => 0, 'three_star_percent' => 0, 'two_star_percent' => 0, 'one_star_percent' => 0
            ];
        }
    }

    // Check if user has reviewed product
    public function hasUserReviewed($product_id, $user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                     WHERE product_id = :product_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update product average rating
    private function updateProductRating($product_id) {
        try {
            $query = "UPDATE products 
                     SET average_rating = (
                         SELECT AVG(rating) 
                         FROM " . $this->table . " 
                         WHERE product_id = :product_id AND status = 'approved'
                     ),
                     review_count = (
                         SELECT COUNT(*) 
                         FROM " . $this->table . " 
                         WHERE product_id = :product_id AND status = 'approved'
                     )
                     WHERE id = :product_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get all reviews for admin
    public function getAllReviews($status = null, $limit = 20, $offset = 0) {
        try {
            $where_clause = $status ? "WHERE r.status = :status" : "";
            $query = "SELECT r.*, u.first_name, u.last_name, u.username, p.name as product_name 
                     FROM " . $this->table . " r 
                     JOIN users u ON r.user_id = u.id 
                     JOIN products p ON r.product_id = p.id 
                     " . $where_clause . " 
                     ORDER BY r.created_at DESC 
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

    // Get total reviews count for admin
    public function getTotalReviewsCount($status = null) {
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

    // Update review status
    public function updateStatus($review_id, $status) {
        try {
            $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $review_id);
            
            if ($stmt->execute()) {
                // Get product ID to update rating
                $query = "SELECT product_id FROM " . $this->table . " WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $review_id);
                $stmt->execute();
                $result = $stmt->fetch();
                
                if ($result) {
                    $this->updateProductRating($result['product_id']);
                }
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete review
    public function delete($review_id) {
        try {
            // Get product ID before deletion
            $query = "SELECT product_id FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $review_id);
            $stmt->execute();
            $result = $stmt->fetch();
            
            // Delete review
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $review_id);
            
            if ($stmt->execute()) {
                // Update product rating
                if ($result) {
                    $this->updateProductRating($result['product_id']);
                }
                return ['success' => true, 'message' => 'Review deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete review'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get review by ID
    public function getById($review_id) {
        try {
            $query = "SELECT r.*, u.first_name, u.last_name, u.username, p.name as product_name 
                     FROM " . $this->table . " r 
                     JOIN users u ON r.user_id = u.id 
                     JOIN products p ON r.product_id = p.id 
                     WHERE r.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $review_id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get user reviews
    public function getUserReviews($user_id, $limit = 10, $offset = 0) {
        try {
            $query = "SELECT r.*, p.name as product_name, p.image 
                     FROM " . $this->table . " r 
                     JOIN products p ON r.product_id = p.id 
                     WHERE r.user_id = :user_id 
                     ORDER BY r.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Generate star rating HTML
    public static function generateStarRating($rating, $max_rating = 5, $show_text = true) {
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
        $empty_stars = $max_rating - $full_stars - $half_star;
        
        $html = '<div class="flex items-center">';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<i class="fas fa-star text-yellow-400"></i>';
        }
        
        // Half star
        if ($half_star) {
            $html .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<i class="far fa-star text-gray-300"></i>';
        }
        
        if ($show_text) {
            $html .= '<span class="ml-2 text-sm text-gray-600">(' . number_format($rating, 1) . ')</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
?> 