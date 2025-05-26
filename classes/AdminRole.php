<?php
require_once __DIR__ . '/../config/config.php';

class AdminRole {
    private $conn;
    private $table = 'admin_roles';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Get all roles
    public function getAll() {
        try {
            $query = "SELECT * FROM " . $this->table . " ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get role by ID
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

    // Create new role
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (name, description, permissions) 
                     VALUES (:name, :description, :permissions)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':permissions', json_encode($data['permissions']));
            
            if ($stmt->execute()) {
                return ['success' => true, 'role_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create role'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Role name already exists'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Update role
    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET name = :name, description = :description, permissions = :permissions,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':permissions', json_encode($data['permissions']));
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete role
    public function delete($id) {
        try {
            // Check if role is in use
            $query = "SELECT COUNT(*) as count FROM users WHERE role_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetch()['count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete role that is assigned to users'];
            }

            // Delete role
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Role deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete role'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Check if user has permission
    public function hasPermission($user_id, $permission) {
        try {
            $query = "SELECT ar.permissions 
                     FROM users u 
                     JOIN admin_roles ar ON u.role_id = ar.id 
                     WHERE u.id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $result = $stmt->fetch();
            if (!$result) return false;

            $permissions = json_decode($result['permissions'], true);
            
            // Super admin has all permissions
            if (in_array('all', $permissions)) return true;
            
            // Check specific permission
            return in_array($permission, $permissions);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get user permissions
    public function getUserPermissions($user_id) {
        try {
            $query = "SELECT ar.permissions 
                     FROM users u 
                     JOIN admin_roles ar ON u.role_id = ar.id 
                     WHERE u.id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $result = $stmt->fetch();
            if (!$result) return [];

            return json_decode($result['permissions'], true);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get available permissions
    public function getAvailablePermissions() {
        return [
            'products' => 'Manage Products',
            'categories' => 'Manage Categories',
            'orders' => 'Manage Orders',
            'customers' => 'Manage Customers',
            'users' => 'Manage Admin Users',
            'roles' => 'Manage Roles & Permissions',
            'settings' => 'Manage Site Settings',
            'content' => 'Manage Content (Pages, Blog)',
            'reports' => 'View Reports & Analytics',
            'files' => 'Manage File Uploads',
            'all' => 'Full System Access (Super Admin)'
        ];
    }

    // Assign role to user
    public function assignRole($user_id, $role_id) {
        try {
            $query = "UPDATE users SET role_id = :role_id WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':role_id', $role_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?> 