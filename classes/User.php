<?php
require_once __DIR__ . '/../config/config.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Register new user
    public function register($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (username, email, password, first_name, last_name, phone, address) 
                     VALUES (:username, :email, :password, :first_name, :last_name, :phone, :address)";
            
            $stmt = $this->conn->prepare($query);
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'user_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Registration failed'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Login user
    public function login($username, $password) {
        try {
            $query = "SELECT id, username, email, password, first_name, last_name, role, status 
                     FROM " . $this->table . " 
                     WHERE (username = :username OR email = :username) AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    return ['success' => true, 'user' => $user];
                }
                return ['success' => false, 'message' => 'Invalid password'];
            }
            return ['success' => false, 'message' => 'User not found'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get user by ID
    public function getUserById($id) {
        try {
            $query = "SELECT id, username, email, first_name, last_name, phone, address, role, status, created_at 
                     FROM " . $this->table . " WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update user profile
    public function updateProfile($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET first_name = :first_name, last_name = :last_name, 
                         phone = :phone, address = :address, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Change password
    public function changePassword($id, $current_password, $new_password) {
        try {
            // First verify current password
            $query = "SELECT password FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $query = "UPDATE " . $this->table . " 
                     SET password = :password, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Password updated successfully'];
            }
            return ['success' => false, 'message' => 'Failed to update password'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get all customers (for admin) with pagination and filtering
    public function getAllCustomers($limit = 20, $offset = 0, $search = '', $status_filter = '') {
        try {
            $where_conditions = ["role = 'customer'"];
            $params = [];
            
            if (!empty($search)) {
                $where_conditions[] = "(username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR phone LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            if (!empty($status_filter)) {
                $where_conditions[] = "status = :status";
                $params[':status'] = $status_filter;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $query = "SELECT u.*, 
                             COUNT(o.id) as total_orders,
                             COALESCE(SUM(o.total_amount), 0) as total_spent,
                             MAX(o.created_at) as last_order_date
                     FROM " . $this->table . " u 
                     LEFT JOIN orders o ON u.id = o.user_id 
                     WHERE " . $where_clause . " 
                     GROUP BY u.id 
                     ORDER BY u.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get total customers count with filtering
    public function getTotalCustomersCount($search = '', $status_filter = '') {
        try {
            $where_conditions = ["role = 'customer'"];
            $params = [];
            
            if (!empty($search)) {
                $where_conditions[] = "(username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR phone LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            if (!empty($status_filter)) {
                $where_conditions[] = "status = :status";
                $params[':status'] = $status_filter;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE " . $where_clause;
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get customer statistics
    public function getCustomerStats() {
        try {
            $stats = [];
            
            // Total customers
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE role = 'customer'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_customers'] = $stmt->fetch()['total'];
            
            // Active customers
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE role = 'customer' AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['active_customers'] = $stmt->fetch()['total'];
            
            // Customers with orders
            $query = "SELECT COUNT(DISTINCT user_id) as total FROM orders WHERE user_id IS NOT NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['customers_with_orders'] = $stmt->fetch()['total'];
            
            // New customers this month
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                     WHERE role = 'customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['new_this_month'] = $stmt->fetch()['total'];
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_customers' => 0,
                'active_customers' => 0,
                'customers_with_orders' => 0,
                'new_this_month' => 0
            ];
        }
    }

    // Toggle user status (for admin)
    public function toggleStatus($id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND role = 'customer'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete user (admin only, only if no orders)
    public function delete($id) {
        try {
            // Check if user has orders
            $query = "SELECT COUNT(*) as total FROM orders WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetch()['total'] > 0) {
                return false; // Cannot delete user with orders
            }
            
            // Check if user is admin
            $query = "SELECT role FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if ($user['role'] === 'admin') {
                return false; // Cannot delete admin user
            }
            
            // Delete user
            $query = "DELETE FROM " . $this->table . " WHERE id = :id AND role = 'customer'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Logout user
    public static function logout() {
        session_destroy();
        return true;
    }

    // Check if email exists
    public function emailExists($email, $exclude_id = null) {
        try {
            $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Check if username exists
    public function usernameExists($username, $exclude_id = null) {
        try {
            $query = "SELECT id FROM " . $this->table . " WHERE username = :username";
            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get database connection (for admin operations)
    public function getConnection() {
        return $this->conn;
    }
}
?> 