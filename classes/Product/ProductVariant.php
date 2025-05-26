<?php
/**
 * ProductVariant Class - Handles product variations
 * Extends BaseModel for DRY principles
 * Hi5ve MarketPlace - Phase 3
 */

require_once __DIR__ . '/../Core/BaseModel.php';

class ProductVariant extends BaseModel {
    protected $table = 'product_variants';
    protected $fillable = [
        'product_id', 'variant_type', 'variant_name', 'variant_value',
        'price_adjustment', 'stock_quantity', 'sku', 'status'
    ];
    
    /**
     * Get all variants for a product
     */
    public function getProductVariants($product_id) {
        return $this->where(
            'product_id = :product_id AND status = :status',
            [':product_id' => $product_id, ':status' => 'active'],
            null, 0, 'variant_type, variant_name'
        );
    }
    
    /**
     * Get variants grouped by type
     */
    public function getVariantsByType($product_id) {
        $variants = $this->getProductVariants($product_id);
        $grouped = [];
        
        foreach ($variants as $variant) {
            $type = $variant['variant_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'name' => ucfirst($variant['variant_name']),
                    'options' => []
                ];
            }
            
            $grouped[$type]['options'][] = [
                'id' => $variant['id'],
                'value' => $variant['variant_value'],
                'price_adjustment' => $variant['price_adjustment'],
                'stock' => $variant['stock_quantity'],
                'sku' => $variant['sku']
            ];
        }
        
        return $grouped;
    }
    
    /**
     * Create multiple variants for a product
     */
    public function createProductVariants($product_id, $variants) {
        $this->beginTransaction();
        
        try {
            $created_variants = [];
            
            foreach ($variants as $variant) {
                $variant['product_id'] = $product_id;
                
                // Generate SKU if not provided
                if (empty($variant['sku'])) {
                    $variant['sku'] = $this->generateVariantSKU($product_id, $variant);
                }
                
                $variant_id = $this->create($variant);
                if ($variant_id) {
                    $created_variants[] = $variant_id;
                } else {
                    throw new Exception("Failed to create variant");
                }
            }
            
            $this->commit();
            return $created_variants;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("ProductVariant Creation Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update variant stock
     */
    public function updateStock($variant_id, $quantity, $operation = 'set') {
        $variant = $this->find($variant_id);
        if (!$variant) {
            return false;
        }
        
        $new_quantity = $variant['stock_quantity'];
        
        switch ($operation) {
            case 'add':
                $new_quantity += $quantity;
                break;
            case 'subtract':
                $new_quantity -= $quantity;
                break;
            case 'set':
            default:
                $new_quantity = $quantity;
                break;
        }
        
        // Ensure stock doesn't go negative
        $new_quantity = max(0, $new_quantity);
        
        return $this->update($variant_id, ['stock_quantity' => $new_quantity]);
    }
    
    /**
     * Get variant by specific combination
     */
    public function getVariantByCombination($product_id, $combinations) {
        $conditions = ['product_id = :product_id'];
        $params = [':product_id' => $product_id];
        
        foreach ($combinations as $type => $value) {
            $conditions[] = "(variant_type = :type_{$type} AND variant_value = :value_{$type})";
            $params[":type_{$type}"] = $type;
            $params[":value_{$type}"] = $value;
        }
        
        $where = implode(' AND ', $conditions);
        
        return $this->where($where, $params, 1);
    }
    
    /**
     * Check if variant has sufficient stock
     */
    public function hasStock($variant_id, $quantity = 1) {
        $variant = $this->find($variant_id);
        return $variant && $variant['stock_quantity'] >= $quantity;
    }
    
    /**
     * Get low stock variants
     */
    public function getLowStockVariants($threshold = 5) {
        return $this->where(
            'stock_quantity <= :threshold AND status = :status',
            [':threshold' => $threshold, ':status' => 'active'],
            null, 0, 'stock_quantity'
        );
    }
    
    /**
     * Get variant price (base price + adjustment)
     */
    public function getVariantPrice($variant_id, $base_price) {
        $variant = $this->find($variant_id);
        if (!$variant) {
            return $base_price;
        }
        
        return $base_price + $variant['price_adjustment'];
    }
    
    /**
     * Generate unique SKU for variant
     */
    private function generateVariantSKU($product_id, $variant) {
        $base_sku = "P{$product_id}";
        $type_code = strtoupper(substr($variant['variant_type'], 0, 1));
        $value_code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $variant['variant_value']), 0, 3));
        
        $sku = "{$base_sku}-{$type_code}{$value_code}";
        
        // Ensure uniqueness
        $counter = 1;
        $original_sku = $sku;
        
        while ($this->where('sku = :sku', [':sku' => $sku], 1)) {
            $sku = $original_sku . $counter;
            $counter++;
        }
        
        return $sku;
    }
    
    /**
     * Delete all variants for a product
     */
    public function deleteProductVariants($product_id) {
        return $this->query(
            "DELETE FROM {$this->table} WHERE product_id = :product_id",
            [':product_id' => $product_id]
        );
    }
    
    /**
     * Get variant statistics
     */
    public function getVariantStats($product_id = null) {
        $where = $product_id ? "WHERE product_id = :product_id" : "";
        $params = $product_id ? [':product_id' => $product_id] : [];
        
        $sql = "
            SELECT 
                variant_type,
                COUNT(*) as total_variants,
                SUM(stock_quantity) as total_stock,
                AVG(price_adjustment) as avg_price_adjustment,
                COUNT(CASE WHEN stock_quantity <= 5 THEN 1 END) as low_stock_count
            FROM {$this->table} 
            {$where}
            GROUP BY variant_type
        ";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Bulk update variant status
     */
    public function bulkUpdateStatus($variant_ids, $status) {
        if (empty($variant_ids)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($variant_ids) - 1) . '?';
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})";
        
        $params = array_merge([$status], $variant_ids);
        
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Bulk Update Status Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available variant types
     */
    public function getVariantTypes() {
        return [
            'size' => 'Size',
            'color' => 'Color',
            'weight' => 'Weight',
            'custom' => 'Custom'
        ];
    }
    
    /**
     * Validate variant data
     */
    public function validateVariant($data) {
        $errors = [];
        
        // Required fields
        $required = ['product_id', 'variant_type', 'variant_name', 'variant_value'];
        $missing = $this->validateRequired($data, $required);
        
        if (!empty($missing)) {
            $errors[] = "Missing required fields: " . implode(', ', $missing);
        }
        
        // Validate variant type
        $valid_types = array_keys($this->getVariantTypes());
        if (isset($data['variant_type']) && !in_array($data['variant_type'], $valid_types)) {
            $errors[] = "Invalid variant type";
        }
        
        // Validate price adjustment
        if (isset($data['price_adjustment']) && !is_numeric($data['price_adjustment'])) {
            $errors[] = "Price adjustment must be numeric";
        }
        
        // Validate stock quantity
        if (isset($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
            $errors[] = "Stock quantity must be a non-negative number";
        }
        
        return $errors;
    }
} 