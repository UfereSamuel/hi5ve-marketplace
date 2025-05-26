<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Set proper headers
header('Content-Type: application/json');

try {
    // Use absolute paths to avoid include issues
    $config_path = dirname(__DIR__) . '/config/config.php';
    $cart_path = dirname(__DIR__) . '/classes/Cart.php';
    $product_path = dirname(__DIR__) . '/classes/Product.php';
    
    if (!file_exists($config_path)) {
        throw new Exception('Configuration file not found');
    }
    
    require_once $config_path;
    require_once $cart_path;
    require_once $product_path;

    $cart = new Cart();
    $product = new Product();

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

    switch ($action) {
        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if ($product_id <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
                exit;
            }
            
            // Check if product exists and is active
            $product_data = $product->getById($product_id);
            if (!$product_data || $product_data['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Product not available']);
                exit;
            }
            
            // Check stock availability
            if ($product_data['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
                exit;
            }
            
            $result = $cart->addItem($product_id, $quantity, $user_id);
            
            if ($result) {
                $cart_count = $cart->getCount($user_id);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product added to cart',
                    'cart_count' => $cart_count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
            }
            break;
            
        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
                exit;
            }
            
            if ($quantity > 0) {
                // Check stock availability
                $product_data = $product->getById($product_id);
                if ($product_data && $product_data['stock_quantity'] < $quantity) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
                    exit;
                }
            }
            
            $result = $cart->updateQuantity($product_id, $quantity, $user_id);
            
            if ($result) {
                $cart_summary = $cart->getSummary($user_id);
                echo json_encode([
                    'success' => true,
                    'message' => $quantity > 0 ? 'Cart updated' : 'Item removed from cart',
                    'cart_count' => $cart_summary['total_items'],
                    'subtotal' => formatCurrency($cart_summary['subtotal']),
                    'delivery_fee' => formatCurrency($cart_summary['delivery_fee']),
                    'total' => formatCurrency($cart_summary['total'])
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            }
            break;
            
        case 'remove':
            $product_id = (int)($_POST['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
                exit;
            }
            
            $result = $cart->removeItem($product_id, $user_id);
            
            if ($result) {
                $cart_summary = $cart->getSummary($user_id);
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'cart_count' => $cart_summary['total_items'],
                    'subtotal' => formatCurrency($cart_summary['subtotal']),
                    'delivery_fee' => formatCurrency($cart_summary['delivery_fee']),
                    'total' => formatCurrency($cart_summary['total'])
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
            }
            break;
            
        case 'clear':
            $result = $cart->clear($user_id);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared',
                    'cart_count' => 0
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
            break;
            
        case 'count':
            $count = $cart->getCount($user_id);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'summary':
            $summary = $cart->getSummary($user_id);
            echo json_encode([
                'success' => true,
                'items' => $summary['items'],
                'total_items' => $summary['total_items'],
                'subtotal' => $summary['subtotal'],
                'subtotal_formatted' => formatCurrency($summary['subtotal']),
                'delivery_fee' => $summary['delivery_fee'],
                'delivery_fee_formatted' => formatCurrency($summary['delivery_fee']),
                'total' => $summary['total'],
                'total_formatted' => formatCurrency($summary['total'])
            ]);
            break;
            
        case 'validate':
            $validation = $cart->validateCart($user_id);
            echo json_encode([
                'success' => true,
                'valid' => $validation['valid'],
                'errors' => $validation['errors']
            ]);
            break;
            
        case 'whatsapp_checkout':
            try {
                $summary = $cart->getSummary($user_id);
                
                if (empty($summary['items'])) {
                    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                    exit;
                }
                
                // Validate cart
                $validation = $cart->validateCart($user_id);
                if (!$validation['valid']) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Some items in your cart are not available',
                        'errors' => $validation['errors']
                    ]);
                    exit;
                }
                
                // Generate WhatsApp message
                $message = "🛒 *Hi5ve MarketPlace Order Request*\n\n";
                $message .= "*Items:*\n";
                
                foreach ($summary['items'] as $item) {
                    $item_name = $item['name'] ?? 'Unknown Item';
                    $item_quantity = $item['quantity'] ?? 1;
                    $item_price = $item['effective_price'] ?? 0;
                    $message .= "• {$item_name} x{$item_quantity} - " . formatCurrency($item_price * $item_quantity) . "\n";
                }
                
                $message .= "\n*Order Summary:*\n";
                $message .= "Subtotal: " . formatCurrency($summary['subtotal']) . "\n";
                $message .= "Delivery Fee: " . formatCurrency($summary['delivery_fee']) . "\n";
                $message .= "*Total: " . formatCurrency($summary['total']) . "*\n\n";
                
                if (isLoggedIn()) {
                    $message .= "*Customer Details:*\n";
                    $message .= "Name: " . ($_SESSION['first_name'] ?? '') . " " . ($_SESSION['last_name'] ?? '') . "\n";
                    $message .= "Email: " . ($_SESSION['email'] ?? '') . "\n";
                }
                
                $message .= "\nPlease confirm this order and provide your delivery address.";
                
                // Check if getWhatsAppLink function exists
                if (!function_exists('getWhatsAppLink')) {
                    throw new Exception('WhatsApp function not available');
                }
                
                $whatsapp_link = getWhatsAppLink($message);
                
                echo json_encode([
                    'success' => true,
                    'whatsapp_link' => $whatsapp_link,
                    'message' => 'Redirecting to WhatsApp...'
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'WhatsApp checkout failed: ' . $e->getMessage()
                ]);
            }
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?> 