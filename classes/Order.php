<?php
require_once __DIR__ . '/../config/config.php';

class Order {
    private $conn;
    private $table = 'orders';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new order
    public function create($order_data, $cart_items) {
        try {
            $this->conn->beginTransaction();

            // Generate unique order ID
            $order_id = generateOrderId();

            // Insert order
            $query = "INSERT INTO " . $this->table . " 
                     (order_id, user_id, customer_name, customer_email, customer_phone, 
                      delivery_address, total_amount, payment_method, notes) 
                     VALUES (:order_id, :user_id, :customer_name, :customer_email, :customer_phone, 
                             :delivery_address, :total_amount, :payment_method, :notes)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':user_id', $order_data['user_id']);
            $stmt->bindParam(':customer_name', $order_data['customer_name']);
            $stmt->bindParam(':customer_email', $order_data['customer_email']);
            $stmt->bindParam(':customer_phone', $order_data['customer_phone']);
            $stmt->bindParam(':delivery_address', $order_data['delivery_address']);
            $stmt->bindParam(':total_amount', $order_data['total_amount']);
            $stmt->bindParam(':payment_method', $order_data['payment_method']);
            $stmt->bindParam(':notes', $order_data['notes']);

            if (!$stmt->execute()) {
                throw new Exception('Failed to create order');
            }

            $db_order_id = $this->conn->lastInsertId();

            // Insert order items
            $item_query = "INSERT INTO order_items 
                          (order_id, product_id, product_name, price, quantity, subtotal) 
                          VALUES (:order_id, :product_id, :product_name, :price, :quantity, :subtotal)";

            $item_stmt = $this->conn->prepare($item_query);

            foreach ($cart_items as $item) {
                $subtotal = $item['effective_price'] * $item['quantity'];
                
                $item_stmt->bindParam(':order_id', $db_order_id);
                $item_stmt->bindParam(':product_id', $item['product_id']);
                $item_stmt->bindParam(':product_name', $item['name']);
                $item_stmt->bindParam(':price', $item['effective_price']);
                $item_stmt->bindParam(':quantity', $item['quantity']);
                $item_stmt->bindParam(':subtotal', $subtotal);

                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to create order item');
                }

                // Update product stock
                $this->updateProductStock($item['product_id'], $item['quantity']);
            }

            $this->conn->commit();

            // Send WhatsApp confirmation
            $this->sendWhatsAppConfirmation($db_order_id);

            return [
                'success' => true, 
                'order_id' => $order_id,
                'db_order_id' => $db_order_id
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get order by ID
    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $order = $stmt->fetch();
            if ($order) {
                $order['items'] = $this->getOrderItems($id);
            }

            return $order;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get order by order ID
    public function getByOrderId($order_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE order_id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();

            $order = $stmt->fetch();
            if ($order) {
                $order['items'] = $this->getOrderItems($order['id']);
            }

            return $order;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get order items
    public function getOrderItems($order_id) {
        try {
            $query = "SELECT oi.*, p.image, p.unit 
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.id 
                     WHERE oi.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get user orders
    public function getUserOrders($user_id, $limit = 10, $offset = 0, $status = '') {
        try {
            $where_conditions = ["user_id = :user_id"];
            $params = [':user_id' => $user_id];
            
            if (!empty($status)) {
                $where_conditions[] = "order_status = :status";
                $params[':status'] = $status;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $query = "SELECT * FROM " . $this->table . " 
                     WHERE " . $where_clause . " 
                     ORDER BY created_at DESC 
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

    // Get user orders count
    public function getUserOrdersCount($user_id, $status = '') {
        try {
            $where_conditions = ["user_id = :user_id"];
            $params = [':user_id' => $user_id];
            
            if (!empty($status)) {
                $where_conditions[] = "order_status = :status";
                $params[':status'] = $status;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                     WHERE " . $where_clause;
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get all orders (for admin)
    public function getAll($page = 1, $limit = 20, $status = null) {
        try {
            $offset = ($page - 1) * $limit;
            
            $where_clause = "";
            $params = [];
            
            if ($status) {
                $where_clause = "WHERE order_status = :status";
                $params[':status'] = $status;
            }

            $query = "SELECT * FROM " . $this->table . " 
                     " . $where_clause . " 
                     ORDER BY created_at DESC 
                     LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $orders = $stmt->fetchAll();

            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM " . $this->table . " " . $where_clause;
            $count_stmt = $this->conn->prepare($count_query);
            foreach ($params as $key => $value) {
                $count_stmt->bindValue($key, $value);
            }
            $count_stmt->execute();
            $total = $count_stmt->fetch()['total'];

            return [
                'orders' => $orders,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            return ['orders' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }

    // Update order status
    public function updateStatus($id, $status) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET order_status = :status, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                // Send WhatsApp notification for status updates
                $this->sendStatusUpdateNotification($id, $status);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update payment status
    public function updatePaymentStatus($id, $status) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET payment_status = :status, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get order statistics
    public function getStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_orders,
                        COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders,
                        COUNT(CASE WHEN order_status = 'confirmed' THEN 1 END) as confirmed_orders,
                        COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as delivered_orders,
                        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as avg_order_value
                     FROM " . $this->table;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return [
                'total_orders' => 0,
                'pending_orders' => 0,
                'confirmed_orders' => 0,
                'delivered_orders' => 0,
                'paid_orders' => 0,
                'total_revenue' => 0,
                'avg_order_value' => 0
            ];
        }
    }

    // Update product stock after order
    private function updateProductStock($product_id, $quantity) {
        try {
            $query = "UPDATE products 
                     SET stock_quantity = stock_quantity - :quantity,
                         status = CASE WHEN (stock_quantity - :quantity) <= 0 THEN 'out_of_stock' ELSE status END
                     WHERE id = :product_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':quantity', $quantity);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Send WhatsApp order confirmation
    public function sendWhatsAppConfirmation($order_id) {
        try {
            $order = $this->getById($order_id);
            if (!$order) return false;

            $message = $this->generateOrderConfirmationMessage($order);
            
            // Log WhatsApp message
            $this->logWhatsAppMessage($order_id, $order['customer_phone'], $message, 'order_confirmation');
            
            // Mark as WhatsApp sent
            $query = "UPDATE " . $this->table . " SET whatsapp_sent = 1 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $order_id);
            $stmt->execute();

            return getWhatsAppLink($message, $order['customer_phone']);
        } catch (Exception $e) {
            return false;
        }
    }

    // Send status update notification
    public function sendStatusUpdateNotification($order_id, $status) {
        try {
            $order = $this->getById($order_id);
            if (!$order) return false;

            $message = $this->generateStatusUpdateMessage($order, $status);
            
            // Log WhatsApp message
            $this->logWhatsAppMessage($order_id, $order['customer_phone'], $message, 'order_update');

            return getWhatsAppLink($message, $order['customer_phone']);
        } catch (Exception $e) {
            return false;
        }
    }

    // Generate order confirmation message
    private function generateOrderConfirmationMessage($order) {
        $message = "🛒 *Hi5ve MarketPlace Order Confirmation*\n\n";
        $message .= "Order ID: *{$order['order_id']}*\n";
        $message .= "Customer: {$order['customer_name']}\n";
        $message .= "Phone: {$order['customer_phone']}\n\n";
        
        $message .= "*Order Items:*\n";
        foreach ($order['items'] as $item) {
            $message .= "• {$item['product_name']} x{$item['quantity']} - " . formatCurrency($item['subtotal']) . "\n";
        }
        
        $message .= "\n*Total Amount:* " . formatCurrency($order['total_amount']) . "\n";
        $message .= "*Payment Method:* " . ucfirst($order['payment_method']) . "\n";
        $message .= "*Delivery Address:* {$order['delivery_address']}\n\n";
        
        $message .= "Thank you for shopping with Hi5ve MarketPlace! 🙏\n";
        $message .= "We'll process your order and contact you soon.";

        return $message;
    }

    // Generate status update message
    private function generateStatusUpdateMessage($order, $status) {
        $status_messages = [
            'confirmed' => '✅ Your order has been confirmed and is being prepared.',
            'processing' => '📦 Your order is being processed.',
            'shipped' => '🚚 Your order has been shipped and is on the way!',
            'delivered' => '🎉 Your order has been delivered. Thank you for shopping with us!',
            'cancelled' => '❌ Your order has been cancelled.'
        ];

        $message = "🛒 *Hi5ve MarketPlace Order Update*\n\n";
        $message .= "Order ID: *{$order['order_id']}*\n";
        $message .= "Status: *" . ucfirst($status) . "*\n\n";
        $message .= $status_messages[$status] ?? "Your order status has been updated to: " . ucfirst($status);
        
        if ($status === 'delivered') {
            $message .= "\n\nWe hope you enjoyed your shopping experience! 😊";
        }

        return $message;
    }

    // Log WhatsApp message
    private function logWhatsAppMessage($order_id, $phone, $message, $type) {
        try {
            $query = "INSERT INTO whatsapp_messages (order_id, phone, message, message_type) 
                     VALUES (:order_id, :phone, :message, :message_type)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':message_type', $type);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get recent orders for dashboard
    public function getRecentOrders($limit = 5) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                     ORDER BY created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Cancel order
    public function cancel($id, $reason = '') {
        try {
            $this->conn->beginTransaction();

            // Get order details
            $order = $this->getById($id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            // Restore product stock
            foreach ($order['items'] as $item) {
                $query = "UPDATE products 
                         SET stock_quantity = stock_quantity + :quantity,
                             status = CASE WHEN status = 'out_of_stock' THEN 'active' ELSE status END
                         WHERE id = :product_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->execute();
            }

            // Update order status
            $query = "UPDATE " . $this->table . " 
                     SET order_status = 'cancelled', 
                         notes = CONCAT(COALESCE(notes, ''), '\nCancellation reason: ', :reason),
                         updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':reason', $reason);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to cancel order');
            }

            $this->conn->commit();

            // Send cancellation notification
            $this->sendStatusUpdateNotification($id, 'cancelled');

            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Get all orders for admin with filtering and pagination
    public function getAllOrders($limit = 20, $offset = 0, $status_filter = '', $search = '') {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($status_filter)) {
                $where_conditions[] = "o.order_status = :status";
                $params[':status'] = $status_filter;
            }
            
            if (!empty($search)) {
                $where_conditions[] = "(o.order_id LIKE :search OR o.customer_name LIKE :search OR o.customer_email LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT o.*, 
                             COUNT(oi.id) as total_items,
                             o.order_status as status
                     FROM " . $this->table . " o 
                     LEFT JOIN order_items oi ON o.id = oi.order_id 
                     " . $where_clause . " 
                     GROUP BY o.id 
                     ORDER BY o.created_at DESC 
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

    // Get total orders count for admin with filtering
    public function getTotalOrdersCount($status_filter = '', $search = '') {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($status_filter)) {
                $where_conditions[] = "order_status = :status";
                $params[':status'] = $status_filter;
            }
            
            if (!empty($search)) {
                $where_conditions[] = "(order_id LIKE :search OR customer_name LIKE :search OR customer_email LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " " . $where_clause;
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Delete order (admin only)
    public function delete($id) {
        try {
            $this->conn->beginTransaction();

            // First, get order items to restore stock
            $items = $this->getOrderItems($id);
            
            // Restore product stock
            foreach ($items as $item) {
                $this->restoreProductStock($item['product_id'], $item['quantity']);
            }

            // Delete order items
            $query = "DELETE FROM order_items WHERE order_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Delete order
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $result = $stmt->execute();

            $this->conn->commit();
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Restore product stock when order is deleted
    private function restoreProductStock($product_id, $quantity) {
        try {
            $query = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':product_id', $product_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?> 