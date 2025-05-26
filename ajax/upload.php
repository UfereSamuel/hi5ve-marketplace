<?php
require_once '../config/config.php';
require_once '../classes/FileUpload.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$fileUpload = new FileUpload();
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_single':
            if (isset($_FILES['file'])) {
                $type = $_POST['type'] ?? 'other';
                $user_id = $_SESSION['user_id'];
                
                $result = $fileUpload->upload($_FILES['file'], $type, $user_id);
                $response = $result;
            } else {
                $response = ['success' => false, 'message' => 'No file uploaded'];
            }
            break;
            
        case 'upload_multiple':
            if (isset($_FILES['files'])) {
                $type = $_POST['type'] ?? 'other';
                $user_id = $_SESSION['user_id'];
                
                $result = $fileUpload->uploadMultiple($_FILES['files'], $type, $user_id);
                $response = $result;
            } else {
                $response = ['success' => false, 'message' => 'No files uploaded'];
            }
            break;
            
        case 'delete':
            $upload_id = (int)($_POST['upload_id'] ?? 0);
            if ($upload_id > 0) {
                $result = $fileUpload->delete($upload_id);
                $response = $result;
            } else {
                $response = ['success' => false, 'message' => 'Invalid upload ID'];
            }
            break;
            
        case 'get_files':
            $type = $_POST['type'] ?? null;
            $limit = (int)($_POST['limit'] ?? 50);
            $offset = (int)($_POST['offset'] ?? 0);
            
            $files = $fileUpload->getAll($type, $limit, $offset);
            
            // Add URLs to files
            foreach ($files as &$file) {
                $file['url'] = $fileUpload->getFileUrl($file['file_path']);
                $file['size_formatted'] = formatFileSize($file['file_size']);
            }
            
            $response = [
                'success' => true,
                'files' => $files,
                'count' => count($files)
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
}

echo json_encode($response);

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?> 