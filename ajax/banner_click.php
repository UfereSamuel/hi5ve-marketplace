<?php
require_once '../config/config.php';
require_once '../classes/Banner.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['banner_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Banner ID required']);
    exit;
}

$banner_id = (int)$_POST['banner_id'];

try {
    $banner = new Banner();
    $result = $banner->recordClick($banner_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Click recorded']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record click']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
} 