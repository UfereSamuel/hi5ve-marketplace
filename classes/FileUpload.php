<?php
require_once __DIR__ . '/../config/config.php';

class FileUpload {
    private $conn;
    private $table = 'uploads';
    private $upload_dir;
    private $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $max_size = 5242880; // 5MB

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Set absolute path for uploads directory
        $this->upload_dir = __DIR__ . '/../uploads/';
        
        // Create upload directories if they don't exist
        $this->createDirectories();
    }

    // Create necessary directories
    private function createDirectories() {
        $directories = [
            $this->upload_dir,
            $this->upload_dir . 'products/',
            $this->upload_dir . 'categories/',
            $this->upload_dir . 'blog/',
            $this->upload_dir . 'pages/',
            $this->upload_dir . 'settings/',
            $this->upload_dir . 'temp/'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // Upload single file
    public function upload($file, $type = 'other', $user_id = null) {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            
            // Determine upload path
            $upload_path = $this->getUploadPath($type);
            $file_path = $upload_path . $filename;
            $full_path = $this->upload_dir . $file_path;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $full_path)) {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }

            // Resize image if needed
            $this->resizeImage($full_path, $type);

            // Try to save to database (optional - don't fail if this fails)
            $upload_id = null;
            try {
                $upload_id = $this->saveToDatabase($filename, $file['name'], $file_path, $file['size'], $file['type'], $user_id, $type);
            } catch (Exception $e) {
                // Log the error but don't fail the upload
                error_log("Database save failed for upload: " . $e->getMessage());
            }

            return [
                'success' => true,
                'upload_id' => $upload_id,
                'filename' => $filename,
                'file_path' => $file_path,
                'full_path' => $full_path,
                'url' => $this->getFileUrl($file_path)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
        }
    }

    // Upload multiple files
    public function uploadMultiple($files, $type = 'other', $user_id = null) {
        $results = [];
        $success_count = 0;

        foreach ($files['name'] as $key => $name) {
            $file = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];

            $result = $this->upload($file, $type, $user_id);
            $results[] = $result;
            
            if ($result['success']) {
                $success_count++;
            }
        }

        return [
            'success' => $success_count > 0,
            'total' => count($files['name']),
            'uploaded' => $success_count,
            'results' => $results
        ];
    }

    // Validate uploaded file
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error: ' . $this->getUploadError($file['error'])];
        }

        // Check file size
        if ($file['size'] > $this->max_size) {
            return ['success' => false, 'message' => 'File size exceeds maximum allowed size of ' . ($this->max_size / 1024 / 1024) . 'MB'];
        }

        // Check file type
        if (!in_array($file['type'], $this->allowed_types)) {
            return ['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_types)];
        }

        // Additional security check - verify actual file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actual_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($actual_type, $this->allowed_types)) {
            return ['success' => false, 'message' => 'File type verification failed'];
        }

        return ['success' => true];
    }

    // Get upload path based on type
    private function getUploadPath($type) {
        switch ($type) {
            case 'product':
                return 'products/';
            case 'category':
                return 'categories/';
            case 'blog':
                return 'blog/';
            case 'page':
                return 'pages/';
            case 'setting':
                return 'settings/';
            default:
                return 'temp/';
        }
    }

    // Resize image based on type
    private function resizeImage($file_path, $type) {
        $dimensions = $this->getImageDimensions($type);
        if (!$dimensions) return;

        $image_info = getimagesize($file_path);
        if (!$image_info) return;

        $width = $image_info[0];
        $height = $image_info[1];
        $mime_type = $image_info['mime'];

        // Only resize if image is larger than target dimensions
        if ($width <= $dimensions['width'] && $height <= $dimensions['height']) {
            return;
        }

        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($dimensions['width'] / $width, $dimensions['height'] / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);

        // Create image resource
        switch ($mime_type) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $source = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($file_path);
                break;
            default:
                return;
        }

        if (!$source) return;

        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }

        // Resize image
        imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Save resized image
        switch ($mime_type) {
            case 'image/jpeg':
                imagejpeg($new_image, $file_path, 85);
                break;
            case 'image/png':
                imagepng($new_image, $file_path, 8);
                break;
            case 'image/gif':
                imagegif($new_image, $file_path);
                break;
        }

        // Clean up
        imagedestroy($source);
        imagedestroy($new_image);
    }

    // Get image dimensions for different types
    private function getImageDimensions($type) {
        switch ($type) {
            case 'product':
                return ['width' => 800, 'height' => 800];
            case 'category':
                return ['width' => 400, 'height' => 400];
            case 'blog':
                return ['width' => 1200, 'height' => 600];
            case 'setting':
                return ['width' => 300, 'height' => 300];
            default:
                return ['width' => 800, 'height' => 600];
        }
    }

    // Save file information to database
    private function saveToDatabase($filename, $original_name, $file_path, $file_size, $mime_type, $user_id, $upload_type) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (filename, original_name, file_path, file_size, mime_type, uploaded_by, upload_type) 
                     VALUES (:filename, :original_name, :file_path, :file_size, :mime_type, :uploaded_by, :upload_type)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':original_name', $original_name);
            $stmt->bindParam(':file_path', $file_path);
            $stmt->bindParam(':file_size', $file_size);
            $stmt->bindParam(':mime_type', $mime_type);
            $stmt->bindParam(':uploaded_by', $user_id);
            $stmt->bindParam(':upload_type', $upload_type);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get file URL
    public function getFileUrl($file_path) {
        return '/mart3/uploads/' . $file_path;
    }

    // Delete file
    public function delete($upload_id) {
        try {
            // Get file info
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $upload_id);
            $stmt->execute();
            
            $file = $stmt->fetch();
            if (!$file) {
                return ['success' => false, 'message' => 'File not found'];
            }

            // Delete physical file
            $full_path = $this->upload_dir . $file['file_path'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }

            // Delete from database
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $upload_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'File deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete file record'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Delete error: ' . $e->getMessage()];
        }
    }

    // Get upload error message
    private function getUploadError($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    // Get all uploads
    public function getAll($type = null, $limit = 50, $offset = 0) {
        try {
            $where_clause = $type ? "WHERE upload_type = :type" : "";
            $query = "SELECT * FROM " . $this->table . " " . $where_clause . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            if ($type) {
                $stmt->bindParam(':type', $type);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get upload by ID
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
}
?> 