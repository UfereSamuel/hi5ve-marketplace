<?php
/**
 * ProductGallery Class - Handles product image galleries
 * Extends BaseModel for DRY principles
 * Hi5ve MarketPlace - Phase 3
 */

require_once __DIR__ . '/../Core/BaseModel.php';

class ProductGallery extends BaseModel {
    protected $table = 'product_gallery';
    protected $fillable = [
        'product_id', 'image_path', 'alt_text', 'sort_order', 'is_primary'
    ];
    
    /**
     * Add image to product gallery
     */
    public function addImage($product_id, $image_path, $alt_text = '', $is_primary = false) {
        // If this is set as primary, unset other primary images
        if ($is_primary) {
            $this->unsetPrimaryImages($product_id);
        }
        
        // Get next sort order
        $sort_order = $this->getNextSortOrder($product_id);
        
        return $this->create([
            'product_id' => $product_id,
            'image_path' => $image_path,
            'alt_text' => $alt_text,
            'sort_order' => $sort_order,
            'is_primary' => $is_primary
        ]);
    }
    
    /**
     * Get all images for a product
     */
    public function getProductImages($product_id, $include_primary = true) {
        $conditions = 'product_id = :product_id';
        $params = [':product_id' => $product_id];
        
        if (!$include_primary) {
            $conditions .= ' AND is_primary = 0';
        }
        
        return $this->where($conditions, $params, null, 0, 'sort_order', 'ASC');
    }
    
    /**
     * Get primary image for a product
     */
    public function getPrimaryImage($product_id) {
        return $this->where(
            'product_id = :product_id AND is_primary = 1',
            [':product_id' => $product_id],
            1
        );
    }
    
    /**
     * Set image as primary
     */
    public function setPrimaryImage($image_id, $product_id) {
        $this->beginTransaction();
        
        try {
            // Unset all primary images for this product
            $this->unsetPrimaryImages($product_id);
            
            // Set this image as primary
            $result = $this->update($image_id, ['is_primary' => true]);
            
            if ($result) {
                $this->commit();
                return true;
            } else {
                $this->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->rollback();
            error_log("Set Primary Image Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update image sort order
     */
    public function updateSortOrder($image_id, $new_order) {
        return $this->update($image_id, ['sort_order' => $new_order]);
    }
    
    /**
     * Reorder images for a product
     */
    public function reorderImages($product_id, $image_orders) {
        $this->beginTransaction();
        
        try {
            foreach ($image_orders as $image_id => $sort_order) {
                $this->update($image_id, ['sort_order' => $sort_order]);
            }
            
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            error_log("Reorder Images Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete image from gallery
     */
    public function deleteImage($image_id) {
        $image = $this->find($image_id);
        if (!$image) {
            return false;
        }
        
        // Delete physical file
        $file_path = __DIR__ . '/../../uploads/products/' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        return $this->delete($image_id);
    }
    
    /**
     * Delete all images for a product
     */
    public function deleteProductImages($product_id) {
        $images = $this->getProductImages($product_id);
        
        foreach ($images as $image) {
            $this->deleteImage($image['id']);
        }
        
        return true;
    }
    
    /**
     * Get image statistics
     */
    public function getImageStats($product_id = null) {
        $where = $product_id ? "WHERE product_id = :product_id" : "";
        $params = $product_id ? [':product_id' => $product_id] : [];
        
        $sql = "
            SELECT 
                COUNT(*) as total_images,
                COUNT(CASE WHEN is_primary = 1 THEN 1 END) as primary_images,
                AVG(sort_order) as avg_sort_order
            FROM {$this->table} 
            {$where}
        ";
        
        $result = $this->query($sql, $params);
        return $result[0] ?? [
            'total_images' => 0,
            'primary_images' => 0,
            'avg_sort_order' => 0
        ];
    }
    
    /**
     * Get products without images
     */
    public function getProductsWithoutImages() {
        $sql = "
            SELECT p.id, p.name, p.status
            FROM products p
            LEFT JOIN {$this->table} pg ON p.id = pg.product_id
            WHERE pg.product_id IS NULL
            ORDER BY p.name
        ";
        
        return $this->query($sql);
    }
    
    /**
     * Bulk upload images for a product
     */
    public function bulkUpload($product_id, $images) {
        $this->beginTransaction();
        
        try {
            $uploaded_images = [];
            $sort_order = $this->getNextSortOrder($product_id);
            
            foreach ($images as $index => $image_data) {
                $image_id = $this->create([
                    'product_id' => $product_id,
                    'image_path' => $image_data['path'],
                    'alt_text' => $image_data['alt_text'] ?? '',
                    'sort_order' => $sort_order + $index,
                    'is_primary' => $image_data['is_primary'] ?? false
                ]);
                
                if ($image_id) {
                    $uploaded_images[] = $image_id;
                } else {
                    throw new Exception("Failed to upload image: " . $image_data['path']);
                }
            }
            
            $this->commit();
            return $uploaded_images;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Bulk Upload Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate image thumbnails
     */
    public function generateThumbnails($image_id, $sizes = ['150x150', '300x300', '600x600']) {
        $image = $this->find($image_id);
        if (!$image) {
            return false;
        }
        
        $source_path = __DIR__ . '/../../uploads/products/' . $image['image_path'];
        if (!file_exists($source_path)) {
            return false;
        }
        
        $thumbnails = [];
        $path_info = pathinfo($image['image_path']);
        
        foreach ($sizes as $size) {
            list($width, $height) = explode('x', $size);
            
            $thumbnail_name = $path_info['filename'] . '_' . $size . '.' . $path_info['extension'];
            $thumbnail_path = __DIR__ . '/../../uploads/products/thumbnails/' . $thumbnail_name;
            
            // Create thumbnails directory if it doesn't exist
            $thumbnail_dir = dirname($thumbnail_path);
            if (!is_dir($thumbnail_dir)) {
                mkdir($thumbnail_dir, 0755, true);
            }
            
            if ($this->resizeImage($source_path, $thumbnail_path, $width, $height)) {
                $thumbnails[$size] = 'thumbnails/' . $thumbnail_name;
            }
        }
        
        return $thumbnails;
    }
    
    /**
     * Get image with thumbnails
     */
    public function getImageWithThumbnails($image_id) {
        $image = $this->find($image_id);
        if (!$image) {
            return false;
        }
        
        $image['thumbnails'] = $this->generateThumbnails($image_id);
        return $image;
    }
    
    /**
     * Validate image file
     */
    public function validateImage($file) {
        $errors = [];
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image file size must be less than 5MB";
        }
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Invalid image type. Allowed: JPEG, PNG, GIF, WebP";
        }
        
        // Check image dimensions
        if ($file['tmp_name']) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info) {
                $width = $image_info[0];
                $height = $image_info[1];
                
                // Minimum dimensions
                if ($width < 200 || $height < 200) {
                    $errors[] = "Image dimensions must be at least 200x200 pixels";
                }
                
                // Maximum dimensions
                if ($width > 2000 || $height > 2000) {
                    $errors[] = "Image dimensions must not exceed 2000x2000 pixels";
                }
            } else {
                $errors[] = "Invalid image file";
            }
        }
        
        return $errors;
    }
    
    /**
     * Private helper methods
     */
    private function unsetPrimaryImages($product_id) {
        return $this->query(
            "UPDATE {$this->table} SET is_primary = 0 WHERE product_id = :product_id",
            [':product_id' => $product_id]
        );
    }
    
    private function getNextSortOrder($product_id) {
        $sql = "SELECT MAX(sort_order) as max_order FROM {$this->table} WHERE product_id = :product_id";
        $result = $this->query($sql, [':product_id' => $product_id]);
        
        return ($result[0]['max_order'] ?? 0) + 1;
    }
    
    private function resizeImage($source, $destination, $width, $height) {
        $image_info = getimagesize($source);
        if (!$image_info) {
            return false;
        }
        
        $source_width = $image_info[0];
        $source_height = $image_info[1];
        $source_type = $image_info[2];
        
        // Create source image
        switch ($source_type) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $source_image = imagecreatefromgif($source);
                break;
            case IMAGETYPE_WEBP:
                $source_image = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }
        
        // Calculate aspect ratio
        $aspect_ratio = $source_width / $source_height;
        $target_ratio = $width / $height;
        
        if ($aspect_ratio > $target_ratio) {
            // Source is wider
            $new_width = $width;
            $new_height = $width / $aspect_ratio;
        } else {
            // Source is taller
            $new_height = $height;
            $new_width = $height * $aspect_ratio;
        }
        
        // Create destination image
        $destination_image = imagecreatetruecolor($width, $height);
        
        // Handle transparency for PNG and GIF
        if ($source_type == IMAGETYPE_PNG || $source_type == IMAGETYPE_GIF) {
            imagealphablending($destination_image, false);
            imagesavealpha($destination_image, true);
            $transparent = imagecolorallocatealpha($destination_image, 255, 255, 255, 127);
            imagefill($destination_image, 0, 0, $transparent);
        }
        
        // Calculate position to center the image
        $x = ($width - $new_width) / 2;
        $y = ($height - $new_height) / 2;
        
        // Resize and copy
        imagecopyresampled(
            $destination_image, $source_image,
            $x, $y, 0, 0,
            $new_width, $new_height,
            $source_width, $source_height
        );
        
        // Save destination image
        $result = false;
        switch ($source_type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($destination_image, $destination, 90);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($destination_image, $destination, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($destination_image, $destination);
                break;
            case IMAGETYPE_WEBP:
                $result = imagewebp($destination_image, $destination, 90);
                break;
        }
        
        // Clean up
        imagedestroy($source_image);
        imagedestroy($destination_image);
        
        return $result;
    }
} 