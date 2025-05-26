<?php
require_once __DIR__ . '/../config/config.php';

class Settings {
    private $conn;
    private $table = 'site_settings';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Get all settings
    public function getAll($category = null) {
        try {
            $where_clause = $category ? "WHERE category = :category" : "";
            $query = "SELECT * FROM " . $this->table . " " . $where_clause . " ORDER BY category, setting_key";
            
            $stmt = $this->conn->prepare($query);
            if ($category) {
                $stmt->bindParam(':category', $category);
            }
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get setting by key
    public function get($key, $default = null) {
        try {
            $query = "SELECT setting_value FROM " . $this->table . " WHERE setting_key = :key";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $key);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result ? $result['setting_value'] : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }

    // Update setting
    public function update($key, $value) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET setting_value = :value, updated_at = CURRENT_TIMESTAMP 
                     WHERE setting_key = :key";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update multiple settings
    public function updateMultiple($settings) {
        try {
            $this->conn->beginTransaction();

            foreach ($settings as $key => $value) {
                $this->update($key, $value);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Create new setting
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (setting_key, setting_value, setting_type, category, description) 
                     VALUES (:key, :value, :type, :category, :description)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $data['setting_key']);
            $stmt->bindParam(':value', $data['setting_value']);
            $stmt->bindParam(':type', $data['setting_type']);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':description', $data['description']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete setting
    public function delete($key) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE setting_key = :key";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $key);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get settings grouped by category
    public function getGrouped() {
        try {
            $settings = $this->getAll();
            $grouped = [];

            foreach ($settings as $setting) {
                $grouped[$setting['category']][] = $setting;
            }

            return $grouped;
        } catch (Exception $e) {
            return [];
        }
    }

    // Get boolean setting
    public function getBool($key, $default = false) {
        $value = $this->get($key, $default ? '1' : '0');
        return $value === '1' || $value === 'true' || $value === true;
    }

    // Get numeric setting
    public function getNumber($key, $default = 0) {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (float)$value : $default;
    }

    // Get array setting (JSON)
    public function getArray($key, $default = []) {
        $value = $this->get($key);
        if (!$value) return $default;
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    // Set array setting (JSON)
    public function setArray($key, $array) {
        return $this->update($key, json_encode($array));
    }
}
?> 