<?php
class UserProfile {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Check if user profile is complete for checkout
     */
    public function isProfileComplete($user_id) {
        $stmt = $this->conn->prepare("
            SELECT first_name, last_name, email, phone, address 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['complete' => false, 'missing' => ['user_not_found']];
        }
        
        $missing_fields = [];
        
        // Check required fields for checkout
        if (empty(trim($user['first_name'] ?? ''))) $missing_fields[] = 'first_name';
        if (empty(trim($user['last_name'] ?? ''))) $missing_fields[] = 'last_name';
        if (empty(trim($user['email'] ?? ''))) $missing_fields[] = 'email';
        if (empty(trim($user['phone'] ?? ''))) $missing_fields[] = 'phone';
        if (empty(trim($user['address'] ?? ''))) $missing_fields[] = 'address';
        
        return [
            'complete' => empty($missing_fields),
            'missing' => $missing_fields,
            'user_data' => $user
        ];
    }
    
    /**
     * Get user profile data
     */
    public function getUserProfile($user_id) {
        $stmt = $this->conn->prepare("
            SELECT id, username, email, first_name, last_name, phone, alternative_phone, address, role, status
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($user_id, $data) {
        try {
            // Validate required fields
            $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty(trim($data[$field] ?? ''))) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                    'missing_fields' => $missing_fields
                ];
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }
            
            // Check if email is already taken by another user
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $user_id]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Email address is already taken by another user'
                ];
            }
            
            // Update profile
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, alternative_phone = ?, address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                trim($data['first_name']),
                trim($data['last_name']),
                trim($data['email']),
                trim($data['phone']),
                trim($data['alternative_phone'] ?? ''),
                trim($data['address']),
                $user_id
            ]);
            
            if ($result) {
                // Update session data
                $_SESSION['first_name'] = trim($data['first_name']);
                $_SESSION['last_name'] = trim($data['last_name']);
                $_SESSION['email'] = trim($data['email']);
                $_SESSION['phone'] = trim($data['phone']);
                
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update profile'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get missing field labels for display
     */
    public function getMissingFieldLabels($missing_fields) {
        $labels = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'address' => 'Delivery Address'
        ];
        
        $missing_labels = [];
        foreach ($missing_fields as $field) {
            $missing_labels[] = $labels[$field] ?? $field;
        }
        
        return $missing_labels;
    }
    
    /**
     * Check if user has complete delivery information
     */
    public function hasCompleteDeliveryInfo($user_id) {
        $profile_check = $this->isProfileComplete($user_id);
        
        if (!$profile_check['complete']) {
            return [
                'complete' => false,
                'missing' => $profile_check['missing'],
                'user_data' => $profile_check['user_data'] ?? null
            ];
        }
        
        return [
            'complete' => true,
            'user_data' => $profile_check['user_data']
        ];
    }
}
?> 