<?php
require_once __DIR__ . '/../config/config.php';

class Analytics {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Get sales analytics for dashboard
    public function getSalesAnalytics($period = 'today') {
        try {
            $analytics = [];
            
            // Define date ranges
            $date_conditions = $this->getDateConditions($period);
            
            // Total Revenue
            $query = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
                     FROM orders 
                     WHERE status IN ('completed', 'delivered') 
                     AND " . $date_conditions['condition'];
            
            $stmt = $this->conn->prepare($query);
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $analytics['total_revenue'] = $stmt->fetch()['total_revenue'] ?? 0;
            
            // Total Orders
            $query = "SELECT COUNT(*) as total_orders 
                     FROM orders 
                     WHERE " . $date_conditions['condition'];
            
            $stmt = $this->conn->prepare($query);
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $analytics['total_orders'] = $stmt->fetch()['total_orders'] ?? 0;
            
            // Average Order Value
            $analytics['average_order_value'] = $analytics['total_orders'] > 0 
                ? $analytics['total_revenue'] / $analytics['total_orders'] 
                : 0;
            
            // New Customers
            $query = "SELECT COUNT(*) as new_customers 
                     FROM users 
                     WHERE role_id IS NULL 
                     AND " . str_replace('created_at', 'created_at', $date_conditions['condition']);
            
            $stmt = $this->conn->prepare($query);
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $analytics['new_customers'] = $stmt->fetch()['new_customers'] ?? 0;
            
            // Conversion Rate (orders vs visitors - simplified)
            $analytics['conversion_rate'] = $analytics['new_customers'] > 0 
                ? ($analytics['total_orders'] / ($analytics['new_customers'] * 10)) * 100 
                : 0;
            
            return $analytics;
        } catch (PDOException $e) {
            return [
                'total_revenue' => 0,
                'total_orders' => 0,
                'average_order_value' => 0,
                'new_customers' => 0,
                'conversion_rate' => 0
            ];
        }
    }

    // Get sales trend data for charts
    public function getSalesTrend($period = 'week', $days = 7) {
        try {
            $trend_data = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                
                $query = "SELECT 
                            COALESCE(SUM(total_amount), 0) as revenue,
                            COUNT(*) as orders
                         FROM orders 
                         WHERE DATE(created_at) = :date 
                         AND status IN ('completed', 'delivered')";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                $result = $stmt->fetch();
                
                $trend_data[] = [
                    'date' => $date,
                    'formatted_date' => date('M j', strtotime($date)),
                    'revenue' => $result['revenue'] ?? 0,
                    'orders' => $result['orders'] ?? 0
                ];
            }
            
            return $trend_data;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get top selling products
    public function getTopSellingProducts($limit = 10, $period = 'month') {
        try {
            $date_conditions = $this->getDateConditions($period);
            
            $query = "SELECT 
                        p.id,
                        p.name,
                        p.price,
                        p.image,
                        SUM(oi.quantity) as total_sold,
                        SUM(oi.quantity * oi.price) as total_revenue,
                        COUNT(DISTINCT o.id) as order_count
                     FROM products p
                     JOIN order_items oi ON p.id = oi.product_id
                     JOIN orders o ON oi.order_id = o.id
                     WHERE o.status IN ('completed', 'delivered')
                     AND " . str_replace('created_at', 'o.created_at', $date_conditions['condition']) . "
                     GROUP BY p.id, p.name, p.price, p.image
                     ORDER BY total_sold DESC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get category performance
    public function getCategoryPerformance($period = 'month') {
        try {
            $date_conditions = $this->getDateConditions($period);
            
            $query = "SELECT 
                        c.id,
                        c.name,
                        c.image,
                        COUNT(DISTINCT p.id) as product_count,
                        COALESCE(SUM(oi.quantity), 0) as total_sold,
                        COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
                     FROM categories c
                     LEFT JOIN products p ON c.id = p.category_id
                     LEFT JOIN order_items oi ON p.id = oi.product_id
                     LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('completed', 'delivered')
                     AND " . str_replace('created_at', 'o.created_at', $date_conditions['condition']) . "
                     GROUP BY c.id, c.name, c.image
                     ORDER BY total_revenue DESC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get customer analytics
    public function getCustomerAnalytics($period = 'month') {
        try {
            $date_conditions = $this->getDateConditions($period);
            
            $analytics = [];
            
            // Total customers
            $query = "SELECT COUNT(*) as total_customers FROM users WHERE role_id IS NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $analytics['total_customers'] = $stmt->fetch()['total_customers'] ?? 0;
            
            // Active customers (made purchase in period)
            $query = "SELECT COUNT(DISTINCT user_id) as active_customers 
                     FROM orders 
                     WHERE user_id IS NOT NULL 
                     AND " . $date_conditions['condition'];
            
            $stmt = $this->conn->prepare($query);
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $analytics['active_customers'] = $stmt->fetch()['active_customers'] ?? 0;
            
            // Customer lifetime value
            $query = "SELECT AVG(customer_total) as avg_lifetime_value
                     FROM (
                         SELECT user_id, SUM(total_amount) as customer_total
                         FROM orders 
                         WHERE user_id IS NOT NULL 
                         AND status IN ('completed', 'delivered')
                         GROUP BY user_id
                     ) as customer_totals";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $analytics['avg_lifetime_value'] = $stmt->fetch()['avg_lifetime_value'] ?? 0;
            
            // Repeat customer rate
            $query = "SELECT 
                        COUNT(CASE WHEN order_count > 1 THEN 1 END) as repeat_customers,
                        COUNT(*) as total_customers_with_orders
                     FROM (
                         SELECT user_id, COUNT(*) as order_count
                         FROM orders 
                         WHERE user_id IS NOT NULL 
                         AND status IN ('completed', 'delivered')
                         GROUP BY user_id
                     ) as customer_orders";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $analytics['repeat_customer_rate'] = $result['total_customers_with_orders'] > 0 
                ? ($result['repeat_customers'] / $result['total_customers_with_orders']) * 100 
                : 0;
            
            return $analytics;
        } catch (PDOException $e) {
            return [
                'total_customers' => 0,
                'active_customers' => 0,
                'avg_lifetime_value' => 0,
                'repeat_customer_rate' => 0
            ];
        }
    }

    // Get order status distribution
    public function getOrderStatusDistribution($period = 'month') {
        try {
            $date_conditions = $this->getDateConditions($period);
            
            $query = "SELECT 
                        status,
                        COUNT(*) as count,
                        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM orders WHERE " . $date_conditions['condition'] . ")), 2) as percentage
                     FROM orders 
                     WHERE " . $date_conditions['condition'] . "
                     GROUP BY status
                     ORDER BY count DESC";
            
            $stmt = $this->conn->prepare($query);
            if ($date_conditions['params']) {
                foreach ($date_conditions['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get inventory insights
    public function getInventoryInsights() {
        try {
            $insights = [];
            
            // Low stock products (stock < 10)
            $query = "SELECT COUNT(*) as low_stock_count FROM products WHERE stock < 10 AND stock > 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $insights['low_stock_count'] = $stmt->fetch()['low_stock_count'] ?? 0;
            
            // Out of stock products
            $query = "SELECT COUNT(*) as out_of_stock_count FROM products WHERE stock = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $insights['out_of_stock_count'] = $stmt->fetch()['out_of_stock_count'] ?? 0;
            
            // Total inventory value
            $query = "SELECT SUM(price * stock) as total_inventory_value FROM products WHERE stock > 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $insights['total_inventory_value'] = $stmt->fetch()['total_inventory_value'] ?? 0;
            
            // Most stocked products
            $query = "SELECT name, stock, price, (stock * price) as stock_value 
                     FROM products 
                     WHERE stock > 0 
                     ORDER BY stock DESC 
                     LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $insights['most_stocked'] = $stmt->fetchAll();
            
            return $insights;
        } catch (PDOException $e) {
            return [
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'total_inventory_value' => 0,
                'most_stocked' => []
            ];
        }
    }

    // Get recent activity
    public function getRecentActivity($limit = 10) {
        try {
            $activities = [];
            
            // Recent orders
            $query = "SELECT 
                        'order' as type,
                        CONCAT('New order #', id, ' from ', 
                               COALESCE(CONCAT(customer_name), 'Guest')) as description,
                        total_amount as amount,
                        created_at
                     FROM orders 
                     ORDER BY created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $activities = array_merge($activities, $stmt->fetchAll());
            
            // Recent user registrations
            $query = "SELECT 
                        'user' as type,
                        CONCAT('New customer: ', first_name, ' ', last_name) as description,
                        NULL as amount,
                        created_at
                     FROM users 
                     WHERE role_id IS NULL 
                     ORDER BY created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $activities = array_merge($activities, $stmt->fetchAll());
            
            // Sort by created_at
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return array_slice($activities, 0, $limit);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Helper method to get date conditions
    private function getDateConditions($period) {
        switch ($period) {
            case 'today':
                return [
                    'condition' => 'DATE(created_at) = CURDATE()',
                    'params' => []
                ];
            
            case 'yesterday':
                return [
                    'condition' => 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
                    'params' => []
                ];
            
            case 'week':
                return [
                    'condition' => 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
                    'params' => []
                ];
            
            case 'month':
                return [
                    'condition' => 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
                    'params' => []
                ];
            
            case 'quarter':
                return [
                    'condition' => 'created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)',
                    'params' => []
                ];
            
            case 'year':
                return [
                    'condition' => 'created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)',
                    'params' => []
                ];
            
            default:
                return [
                    'condition' => '1=1',
                    'params' => []
                ];
        }
    }

    // Get comparison data (current vs previous period)
    public function getComparisonData($period = 'month') {
        try {
            $current = $this->getSalesAnalytics($period);
            
            // Get previous period data
            $previous_period = $this->getPreviousPeriod($period);
            $previous = $this->getSalesAnalytics($previous_period);
            
            $comparison = [];
            foreach ($current as $key => $value) {
                $prev_value = $previous[$key] ?? 0;
                $change = $prev_value > 0 ? (($value - $prev_value) / $prev_value) * 100 : 0;
                
                $comparison[$key] = [
                    'current' => $value,
                    'previous' => $prev_value,
                    'change' => $change,
                    'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ];
            }
            
            return $comparison;
        } catch (Exception $e) {
            return [];
        }
    }

    // Helper to get previous period identifier
    private function getPreviousPeriod($period) {
        switch ($period) {
            case 'today':
                return 'yesterday';
            case 'week':
                return 'prev_week';
            case 'month':
                return 'prev_month';
            case 'quarter':
                return 'prev_quarter';
            case 'year':
                return 'prev_year';
            default:
                return 'prev_month';
        }
    }
}
?> 