<?php
session_start();
require_once '../../config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$gateway_filter = $_GET['gateway'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($gateway_filter) {
    $where_conditions[] = "p.gateway = ?";
    $params[] = $gateway_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(p.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

if ($search) {
    $where_conditions[] = "(p.gateway_reference LIKE ? OR p.customer_email LIKE ? OR o.order_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get payments for export
    $query = "SELECT 
                p.id,
                p.order_id,
                p.gateway_reference,
                p.gateway,
                p.amount,
                p.currency,
                p.transaction_fee,
                p.net_amount,
                p.status,
                p.customer_email,
                p.customer_phone,
                p.payment_method,
                p.ip_address,
                p.webhook_verified,
                p.created_at,
                p.updated_at,
                p.verified_at,
                o.customer_name,
                o.delivery_address,
                o.notes,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                u.email as user_email
              FROM payments p 
              LEFT JOIN orders o ON p.order_id = o.order_id 
              LEFT JOIN users u ON p.user_id = u.id
              $where_clause 
              ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'payments_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    $headers = [
        'Payment ID',
        'Order ID',
        'Reference',
        'Gateway',
        'Amount (NGN)',
        'Currency',
        'Transaction Fee',
        'Net Amount',
        'Status',
        'Customer Name',
        'Customer Email',
        'Customer Phone',
        'User Account',
        'User Email',
        'Payment Method',
        'IP Address',
        'Webhook Verified',
        'Created Date',
        'Updated Date',
        'Verified Date',
        'Delivery Address',
        'Order Notes'
    ];
    
    fputcsv($output, $headers);
    
    // Add payment data
    foreach ($payments as $payment) {
        $row = [
            $payment['id'],
            $payment['order_id'],
            $payment['gateway_reference'],
            ucfirst($payment['gateway']),
            number_format($payment['amount'], 2),
            $payment['currency'],
            number_format($payment['transaction_fee'], 2),
            number_format($payment['net_amount'], 2),
            ucfirst($payment['status']),
            $payment['customer_name'] ?? 'N/A',
            $payment['customer_email'],
            $payment['customer_phone'] ?? 'N/A',
            $payment['user_name'] ?? 'Guest',
            $payment['user_email'] ?? 'N/A',
            $payment['payment_method'] ?? 'N/A',
            $payment['ip_address'] ?? 'N/A',
            $payment['webhook_verified'] ? 'Yes' : 'No',
            date('Y-m-d H:i:s', strtotime($payment['created_at'])),
            date('Y-m-d H:i:s', strtotime($payment['updated_at'])),
            $payment['verified_at'] ? date('Y-m-d H:i:s', strtotime($payment['verified_at'])) : 'N/A',
            $payment['delivery_address'] ?? 'N/A',
            $payment['notes'] ?? 'N/A'
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Payment export error: " . $e->getMessage());
    http_response_code(500);
    echo "Error exporting payments: " . $e->getMessage();
}
?> 