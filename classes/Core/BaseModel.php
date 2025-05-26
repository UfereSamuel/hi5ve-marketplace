<?php
/**
 * BaseModel Class - Foundation for all Phase 3 models
 * Implements DRY principles with common CRUD operations
 * Hi5ve MarketPlace - Phase 3
 */

abstract class BaseModel {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create a new record
     */
    public function create($data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = $this->filterFillable($data);
            
            // Add timestamps if enabled
            if ($this->timestamps) {
                $filteredData['created_at'] = date('Y-m-d H:i:s');
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = array_keys($filteredData);
            $placeholders = ':' . implode(', :', $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("BaseModel Create Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BaseModel Find Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update record by ID
     */
    public function update($id, $data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = $this->filterFillable($data);
            
            // Add updated timestamp if enabled
            if ($this->timestamps) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = [];
            foreach (array_keys($filteredData) as $field) {
                $fields[] = "{$field} = :{$field}";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :id";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("BaseModel Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete record by ID
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("BaseModel Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all records with pagination
     */
    public function getAll($limit = 10, $offset = 0, $orderBy = null, $orderDirection = 'ASC') {
        try {
            $orderClause = '';
            if ($orderBy) {
                $orderClause = " ORDER BY {$orderBy} {$orderDirection}";
            }
            
            $sql = "SELECT * FROM {$this->table}{$orderClause} LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BaseModel GetAll Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count total records
     */
    public function count($where = null, $params = []) {
        try {
            $whereClause = $where ? " WHERE {$where}" : '';
            $sql = "SELECT COUNT(*) as total FROM {$this->table}{$whereClause}";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("BaseModel Count Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Find records with conditions
     */
    public function where($conditions, $params = [], $limit = null, $offset = 0, $orderBy = null, $orderDirection = 'ASC') {
        try {
            $orderClause = '';
            if ($orderBy) {
                $orderClause = " ORDER BY {$orderBy} {$orderDirection}";
            }
            
            $limitClause = '';
            if ($limit) {
                $limitClause = " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $sql = "SELECT * FROM {$this->table} WHERE {$conditions}{$orderClause}{$limitClause}";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            if ($limit === 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BaseModel Where Error: " . $e->getMessage());
            return $limit === 1 ? false : [];
        }
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            // Return different results based on query type
            if (stripos($sql, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif (stripos($sql, 'INSERT') === 0) {
                return $this->conn->lastInsertId();
            } else {
                return $stmt->rowCount();
            }
        } catch (PDOException $e) {
            error_log("BaseModel Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($data, $required = []) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitize($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Generate unique slug
     */
    protected function generateSlug($text, $field = 'slug') {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->where("{$field} = :slug", [':slug' => $slug], 1)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Get table name
     */
    public function getTable() {
        return $this->table;
    }
    
    /**
     * Set fillable fields
     */
    public function setFillable($fillable) {
        $this->fillable = $fillable;
        return $this;
    }
    
    /**
     * Get fillable fields
     */
    public function getFillable() {
        return $this->fillable;
    }
} 