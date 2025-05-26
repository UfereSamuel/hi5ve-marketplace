<?php
require_once __DIR__ . '/../config/config.php';

class Inventory {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Log inventory changes
    public function logInventoryChange($product_id, $type, $quantity, $reason = '', $reference = '', $created_by = null) {
        try {
            // Get current stock
            $query = "SELECT stock_quantity FROM products WHERE id = :product_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            $product = $stmt->fetch();
            
            if (!$product) {
                return false;
            }
            
            $previous_stock = $product['stock_quantity'];
            
            // Calculate new stock based on type
            switch ($type) {
                case 'stock_in':
                case 'return':
                    $new_stock = $previous_stock + $quantity;
                    break;
                case 'stock_out':
                case 'sale':
                    $new_stock = $previous_stock - $quantity;
                    break;
                case 'adjustment':
                    $new_stock = $quantity; // Direct adjustment to specific quantity
                    $quantity = $new_stock - $previous_stock; // Calculate the difference
                    break;
                default:
                    return false;
            }
            
            // Ensure stock doesn't go negative
            if ($new_stock < 0) {
                $new_stock = 0;
            }
            
            // Update product stock
            $update_query = "UPDATE products SET stock_quantity = :new_stock WHERE id = :product_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':new_stock', $new_stock);
            $update_stmt->bindParam(':product_id', $product_id);
            $update_stmt->execute();
            
            // Log the change
            $log_query = "INSERT INTO inventory_logs (product_id, type, quantity, previous_stock, new_stock, reason, reference, created_by, created_at) 
                         VALUES (:product_id, :type, :quantity, :previous_stock, :new_stock, :reason, :reference, :created_by, NOW())";
            
            $log_stmt = $this->conn->prepare($log_query);
            $log_stmt->bindParam(':product_id', $product_id);
            $log_stmt->bindParam(':type', $type);
            $log_stmt->bindParam(':quantity', $quantity);
            $log_stmt->bindParam(':previous_stock', $previous_stock);
            $log_stmt->bindParam(':new_stock', $new_stock);
            $log_stmt->bindParam(':reason', $reason);
            $log_stmt->bindParam(':reference', $reference);
            $log_stmt->bindParam(':created_by', $created_by);
            
            if ($log_stmt->execute()) {
                // Check for stock alerts
                $this->checkStockAlerts($product_id, $new_stock);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Check and create stock alerts
    private function checkStockAlerts($product_id, $current_stock) {
        try {
            // Get product details
            $query = "SELECT name, low_stock_threshold FROM products WHERE id = :product_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            $product = $stmt->fetch();
            
            if (!$product) {
                return false;
            }
            
            $low_stock_threshold = $product['low_stock_threshold'] ?: 10; // Default threshold
            
            // Determine alert type
            $alert_type = null;
            if ($current_stock == 0) {
                $alert_type = 'out_of_stock';
            } elseif ($current_stock <= $low_stock_threshold) {
                $alert_type = 'low_stock';
            }
            
            if ($alert_type) {
                // Check if alert already exists
                $check_query = "SELECT id FROM stock_alerts 
                               WHERE product_id = :product_id 
                               AND alert_type = :alert_type 
                               AND status = 'active'";
                
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bindParam(':product_id', $product_id);
                $check_stmt->bindParam(':alert_type', $alert_type);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() == 0) {
                    // Create new alert
                    $alert_query = "INSERT INTO stock_alerts (product_id, alert_type, threshold, current_stock, created_at) 
                                   VALUES (:product_id, :alert_type, :threshold, :current_stock, NOW())";
                    
                    $alert_stmt = $this->conn->prepare($alert_query);
                    $alert_stmt->bindParam(':product_id', $product_id);
                    $alert_stmt->bindParam(':alert_type', $alert_type);
                    $alert_stmt->bindParam(':threshold', $low_stock_threshold);
                    $alert_stmt->bindParam(':current_stock', $current_stock);
                    $alert_stmt->execute();
                }
            } else {
                // Resolve existing alerts if stock is now sufficient
                $resolve_query = "UPDATE stock_alerts 
                                 SET status = 'resolved', resolved_at = NOW() 
                                 WHERE product_id = :product_id 
                                 AND status = 'active'";
                
                $resolve_stmt = $this->conn->prepare($resolve_query);
                $resolve_stmt->bindParam(':product_id', $product_id);
                $resolve_stmt->execute();
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get inventory logs with pagination
    public function getInventoryLogs($limit = 50, $offset = 0, $product_id = null, $type = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($product_id) {
                $conditions[] = "il.product_id = :product_id";
                $params[':product_id'] = $product_id;
            }
            
            if ($type) {
                $conditions[] = "il.type = :type";
                $params[':type'] = $type;
            }
            
            $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $query = "SELECT il.*, p.name as product_name, u.first_name, u.last_name 
                     FROM inventory_logs il 
                     LEFT JOIN products p ON il.product_id = p.id 
                     LEFT JOIN users u ON il.created_by = u.id 
                     $where_clause 
                     ORDER BY il.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get stock alerts
    public function getStockAlerts($status = 'active', $limit = 50, $offset = 0) {
        try {
            $query = "SELECT sa.*, p.name as product_name, p.stock_quantity 
                     FROM stock_alerts sa 
                     LEFT JOIN products p ON sa.product_id = p.id 
                     WHERE sa.status = :status 
                     ORDER BY sa.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Resolve stock alert
    public function resolveStockAlert($alert_id) {
        try {
            $query = "UPDATE stock_alerts 
                     SET status = 'resolved', resolved_at = NOW() 
                     WHERE id = :alert_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':alert_id', $alert_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get inventory summary
    public function getInventorySummary() {
        try {
            $summary = [];
            
            // Total products
            $query = "SELECT COUNT(*) as total_products FROM products WHERE status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $summary['total_products'] = $stmt->fetch()['total_products'];
            
            // Low stock products
            $query = "SELECT COUNT(*) as low_stock_count 
                     FROM products 
                     WHERE status = 'active' 
                     AND stock_quantity <= COALESCE(low_stock_threshold, 10) 
                     AND stock_quantity > 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $summary['low_stock_count'] = $stmt->fetch()['low_stock_count'];
            
            // Out of stock products
            $query = "SELECT COUNT(*) as out_of_stock_count 
                     FROM products 
                     WHERE status = 'active' 
                     AND stock_quantity = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $summary['out_of_stock_count'] = $stmt->fetch()['out_of_stock_count'];
            
            // Total inventory value
            $query = "SELECT SUM(price * stock_quantity) as total_inventory_value 
                     FROM products 
                     WHERE status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $summary['total_inventory_value'] = $stmt->fetch()['total_inventory_value'] ?: 0;
            
            // Active alerts
            $query = "SELECT COUNT(*) as active_alerts FROM stock_alerts WHERE status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $summary['active_alerts'] = $stmt->fetch()['active_alerts'];
            
            return $summary;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Bulk stock update
    public function bulkStockUpdate($updates, $created_by = null) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($updates as $update) {
                $product_id = $update['product_id'];
                $new_quantity = $update['quantity'];
                $reason = $update['reason'] ?? 'Bulk update';
                
                $this->logInventoryChange($product_id, 'adjustment', $new_quantity, $reason, 'bulk_update_' . time(), $created_by);
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Get products with low stock
    public function getLowStockProducts($limit = 20) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.status = 'active' 
                     AND p.stock_quantity <= COALESCE(p.low_stock_threshold, 10) 
                     ORDER BY p.stock_quantity ASC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get inventory movement report
    public function getInventoryMovementReport($start_date, $end_date, $product_id = null) {
        try {
            $conditions = ["il.created_at BETWEEN :start_date AND :end_date"];
            $params = [
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ];
            
            if ($product_id) {
                $conditions[] = "il.product_id = :product_id";
                $params[':product_id'] = $product_id;
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $conditions);
            
            $query = "SELECT 
                        p.name as product_name,
                        SUM(CASE WHEN il.type IN ('stock_in', 'return') THEN il.quantity ELSE 0 END) as total_in,
                        SUM(CASE WHEN il.type IN ('stock_out', 'sale') THEN il.quantity ELSE 0 END) as total_out,
                        SUM(CASE WHEN il.type = 'adjustment' THEN il.quantity ELSE 0 END) as total_adjustments,
                        COUNT(*) as total_movements
                     FROM inventory_logs il 
                     LEFT JOIN products p ON il.product_id = p.id 
                     $where_clause 
                     GROUP BY il.product_id, p.name 
                     ORDER BY total_movements DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Export inventory data
    public function exportInventoryData($format = 'csv') {
        try {
            $query = "SELECT 
                        p.id,
                        p.name,
                        p.sku,
                        c.name as category,
                        p.price,
                        p.stock_quantity,
                        p.low_stock_threshold,
                        p.status,
                        p.created_at
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            if ($format === 'csv') {
                $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
                $filepath = 'exports/' . $filename;
                
                // Create exports directory if it doesn't exist
                if (!file_exists('exports')) {
                    mkdir('exports', 0755, true);
                }
                
                $file = fopen($filepath, 'w');
                
                // Write headers
                fputcsv($file, ['ID', 'Name', 'SKU', 'Category', 'Price', 'Stock Quantity', 'Low Stock Threshold', 'Status', 'Created At']);
                
                // Write data
                foreach ($products as $product) {
                    fputcsv($file, [
                        $product['id'],
                        $product['name'],
                        $product['sku'],
                        $product['category'],
                        $product['price'],
                        $product['stock_quantity'],
                        $product['low_stock_threshold'],
                        $product['status'],
                        $product['created_at']
                    ]);
                }
                
                fclose($file);
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath
                ];
            }
            
            return ['success' => false, 'message' => 'Unsupported format'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Import inventory data
    public function importInventoryData($file_path, $created_by = null) {
        try {
            if (!file_exists($file_path)) {
                return ['success' => false, 'message' => 'File not found'];
            }
            
            $file = fopen($file_path, 'r');
            $headers = fgetcsv($file); // Skip header row
            
            $imported = 0;
            $errors = [];
            
            $this->conn->beginTransaction();
            
            while (($data = fgetcsv($file)) !== FALSE) {
                try {
                    // Assuming CSV format: product_id, quantity, reason
                    $product_id = $data[0];
                    $quantity = $data[1];
                    $reason = $data[2] ?? 'Import update';
                    
                    if ($this->logInventoryChange($product_id, 'adjustment', $quantity, $reason, 'import_' . time(), $created_by)) {
                        $imported++;
                    } else {
                        $errors[] = "Failed to update product ID: $product_id";
                    }
                } catch (Exception $e) {
                    $errors[] = "Error processing row: " . implode(',', $data) . " - " . $e->getMessage();
                }
            }
            
            fclose($file);
            
            if (empty($errors)) {
                $this->conn->commit();
                return [
                    'success' => true,
                    'imported' => $imported,
                    'message' => "$imported products updated successfully"
                ];
            } else {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'imported' => 0,
                    'errors' => $errors,
                    'message' => 'Import failed with errors'
                ];
            }
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get inventory valuation
    public function getInventoryValuation($category_id = null) {
        try {
            $conditions = ["p.status = 'active'"];
            $params = [];
            
            if ($category_id) {
                $conditions[] = "p.category_id = :category_id";
                $params[':category_id'] = $category_id;
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $conditions);
            
            $query = "SELECT 
                        c.name as category_name,
                        COUNT(p.id) as product_count,
                        SUM(p.stock_quantity) as total_quantity,
                        SUM(p.price * p.stock_quantity) as total_value,
                        AVG(p.price) as avg_price
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     $where_clause 
                     GROUP BY p.category_id, c.name 
                     ORDER BY total_value DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?> 