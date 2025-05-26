<?php
session_start();
require_once '../config/config.php';
require_once '../classes/UserProfile.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $userProfile = new UserProfile();
    $user_id = $_SESSION['user_id'];
    
    // Get form data
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'alternative_phone' => trim($_POST['alternative_phone'] ?? ''),
        'address' => trim($_POST['address'] ?? '')
    ];
    
    // Update profile
    $result = $userProfile->updateProfile($user_id, $data);
    
    if ($result['success']) {
        // Check if profile is now complete
        $profile_check = $userProfile->isProfileComplete($user_id);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'profile_complete' => $profile_check['complete'],
            'redirect_to_checkout' => $profile_check['complete']
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?> 