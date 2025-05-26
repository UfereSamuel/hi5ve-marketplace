<?php
/**
 * Simple File Upload Class - For debugging upload issues
 */

class SimpleFileUpload {
    private $upload_dir;
    private $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $max_size = 5242880; // 5MB

    public function __construct() {
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
            $this->upload_dir . 'temp/'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // Upload single file (simplified - no database)
    public function upload($file, $type = 'product') {
        try {
            // Debug information
            $debug = [
                'file_info' => $file,
                'upload_dir' => $this->upload_dir,
                'target_dir' => $this->upload_dir . $this->getUploadPath($type)
            ];
            
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return array_merge($validation, ['debug' => $debug]);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            
            // Determine upload path
            $upload_path = $this->getUploadPath($type);
            $file_path = $upload_path . $filename;
            $full_path = $this->upload_dir . $file_path;

            // Debug the paths
            $debug['filename'] = $filename;
            $debug['file_path'] = $file_path;
            $debug['full_path'] = $full_path;
            $debug['full_path_exists'] = file_exists(dirname($full_path));
            $debug['full_path_writable'] = is_writable(dirname($full_path));
            $debug['tmp_file_exists'] = file_exists($file['tmp_name']);
            $debug['tmp_file_readable'] = is_readable($file['tmp_name']);

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $full_path)) {
                $error = error_get_last();
                return [
                    'success' => false, 
                    'message' => 'Failed to move uploaded file',
                    'debug' => $debug,
                    'last_error' => $error
                ];
            }

            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $file_path,
                'full_path' => $full_path,
                'url' => '/mart3/uploads/' . $file_path,
                'debug' => $debug
            ];

        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Upload error: ' . $e->getMessage(),
                'debug' => $debug ?? []
            ];
        }
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

        return ['success' => true];
    }

    // Get upload path based on type
    private function getUploadPath($type) {
        switch ($type) {
            case 'product':
                return 'products/';
            case 'category':
                return 'categories/';
            default:
                return 'temp/';
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
}
?> 