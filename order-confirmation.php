<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Order.php';
require_once 'classes/PaymentGateway.php';

$order = new Order();
$paymentGateway = new PaymentGateway();

// Get order ID from URL
$order_id = $_GET['order_id'] ?? '';
$payment_status = $_GET['payment'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';

if (empty($order_id)) {
    header('Location: index.php');
    exit();
}

// Get order details
$order_details = $order->getByOrderId($order_id);

if (!$order_details) {
    header('Location: index.php');
    exit();
}

// Get payment details
$payment_details = $paymentGateway->getOrderPayments($order_id);
$latest_payment = !empty($payment_details) ? $payment_details[0] : null;

// Check for payment success/error messages
$payment_success = $_SESSION['payment_success'] ?? null;
$payment_error = $_SESSION['payment_error'] ?? null;

// Clear session messages
unset($_SESSION['payment_success'], $_SESSION['payment_error']);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Payment Status Alert -->
            <?php if ($payment_success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Payment Successful!</strong> Your payment of ₦<?php echo number_format($payment_success['amount'], 2); ?> 
                    has been processed successfully via <?php echo $payment_success['gateway']; ?>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($payment_error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Payment Failed!</strong> <?php echo htmlspecialchars($payment_error['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Order Confirmation Card -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                Order Confirmed!
                            </h4>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-light text-dark fs-6">
                                #<?php echo htmlspecialchars($order_details['order_id']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Thank You Message -->
                    <div class="text-center mb-4">
                        <i class="fas fa-shopping-bag text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Thank you for your order, <?php echo htmlspecialchars($order_details['customer_name']); ?>!</h5>
                        <p class="text-muted">
                            We've received your order and will process it shortly. 
                            You'll receive updates via WhatsApp and email.
                        </p>
                    </div>

                    <!-- Order Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Order Status</h6>
                                    <?php
                                    $status_colors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'shipped' => 'secondary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $status_color = $status_colors[$order_details['order_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $status_color; ?> fs-6">
                                        <?php echo ucfirst($order_details['order_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Payment Status</h6>
                                    <?php
                                    $payment_colors = [
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'failed' => 'danger'
                                    ];
                                    $payment_color = $payment_colors[$order_details['payment_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $payment_color; ?> fs-6">
                                        <?php echo ucfirst($order_details['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['customer_phone']); ?></p>
                            <p class="mb-3"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order_details['delivery_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Order Information</h6>
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('M d, Y g:i A', strtotime($order_details['created_at'])); ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> 
                                <?php 
                                $payment_method_names = [
                                    'paystack_card' => 'Debit/Credit Card (Paystack)',
                                    'paystack_bank' => 'Bank Transfer (Paystack)',
                                    'paystack_ussd' => 'USSD Payment',
                                    'flutterwave_card' => 'Debit/Credit Card (Flutterwave)',
                                    'flutterwave_bank' => 'Bank Transfer (Flutterwave)',
                                    'whatsapp_payment' => 'WhatsApp Payment',
                                    'bank_transfer' => 'Direct Bank Transfer',
                                    'cod' => 'Cash on Delivery'
                                ];
                                echo $payment_method_names[$order_details['payment_method']] ?? ucfirst(str_replace('_', ' ', $order_details['payment_method']));
                                ?>
                            </p>
                            <?php if ($order_details['estimated_delivery']): ?>
                                <p class="mb-1"><strong>Estimated Delivery:</strong> <?php echo date('M d, Y', strtotime($order_details['estimated_delivery'])); ?></p>
                            <?php endif; ?>
                            <?php if ($latest_payment && $latest_payment['gateway_reference']): ?>
                                <p class="mb-1"><strong>Payment Reference:</strong> <code><?php echo htmlspecialchars($latest_payment['gateway_reference']); ?></code></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h6 class="border-bottom pb-2 mt-4">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_details['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image']): ?>
                                                    <img src="uploads/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                         class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>₦<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?> <?php echo $item['unit'] ?? 'pcs'; ?></td>
                                        <td class="text-end">₦<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Subtotal:</th>
                                    <th class="text-end">₦<?php echo number_format($order_details['total_amount'] - 500, 2); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3">Delivery Fee:</th>
                                    <th class="text-end">₦500.00</th>
                                </tr>
                                <tr class="table-success">
                                    <th colspan="3">Total:</th>
                                    <th class="text-end">₦<?php echo number_format($order_details['total_amount'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Special Instructions -->
                    <?php if ($order_details['notes']): ?>
                        <h6 class="border-bottom pb-2 mt-4">Special Instructions</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($order_details['notes'])); ?></p>
                    <?php endif; ?>

                    <!-- Payment Instructions for Bank Transfer -->
                    <?php if ($order_details['payment_method'] === 'bank_transfer' && $order_details['payment_status'] === 'pending'): ?>
                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-university me-2"></i>Bank Transfer Instructions</h6>
                            <p class="mb-2">Please transfer ₦<?php echo number_format($order_details['total_amount'], 2); ?> to the following account:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Bank Name:</strong> First Bank Nigeria</p>
                                    <p class="mb-1"><strong>Account Name:</strong> Hi5ve MarketPlace</p>
                                    <p class="mb-1"><strong>Account Number:</strong> 1234567890</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Reference:</strong> <?php echo $order_details['order_id']; ?></p>
                                    <p class="mb-1"><strong>Amount:</strong> ₦<?php echo number_format($order_details['total_amount'], 2); ?></p>
                                </div>
                            </div>
                            <p class="mt-2 mb-0">
                                <small>After making the transfer, please send proof of payment to our WhatsApp: +234 123 456 7890</small>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Cash on Delivery Instructions -->
                    <?php if ($order_details['payment_method'] === 'cod'): ?>
                        <div class="alert alert-warning mt-4">
                            <h6><i class="fas fa-money-bill-wave me-2"></i>Cash on Delivery</h6>
                            <p class="mb-0">
                                Please have ₦<?php echo number_format($order_details['total_amount'], 2); ?> ready when your order arrives. 
                                Our delivery agent will collect payment upon delivery.
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- WhatsApp Payment Instructions -->
                    <?php if ($order_details['payment_method'] === 'whatsapp_payment'): ?>
                        <div class="alert alert-success mt-4">
                            <h6><i class="fab fa-whatsapp me-2"></i>WhatsApp Payment</h6>
                            <p class="mb-2">Complete your payment via WhatsApp for ₦<?php echo number_format($order_details['total_amount'], 2); ?></p>
                            
                            <?php if (isset($_SESSION['whatsapp_payment_link'])): ?>
                                <div class="d-grid gap-2 mb-3">
                                    <a href="<?php echo $_SESSION['whatsapp_payment_link']; ?>" 
                                       target="_blank" 
                                       class="btn btn-success">
                                        <i class="fab fa-whatsapp me-2"></i>Continue Payment via WhatsApp
                                    </a>
                                </div>
                                <?php unset($_SESSION['whatsapp_payment_link']); ?>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Payment Options:</strong></p>
                                    <ul class="mb-2">
                                        <li>Bank Transfer</li>
                                        <li>Mobile Money Transfer</li>
                                        <li>Cash Deposit</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Order Reference:</strong> <?php echo $order_details['order_id']; ?></p>
                                    <p class="mb-1"><strong>Amount:</strong> ₦<?php echo number_format($order_details['total_amount'], 2); ?></p>
                                    <?php if (isset($_GET['reference'])): ?>
                                        <p class="mb-1"><strong>Payment Reference:</strong> <code><?php echo htmlspecialchars($_GET['reference']); ?></code></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="bg-light p-3 rounded mt-3">
                                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Payment Status</h6>
                                <?php if ($order_details['payment_status'] === 'pending'): ?>
                                    <p class="mb-2 text-warning">
                                        <i class="fas fa-clock me-1"></i>
                                        <strong>Awaiting Payment Confirmation</strong>
                                    </p>
                                    <p class="mb-0 small">
                                        After making payment via WhatsApp, our admin will confirm your payment and update your order status. 
                                        You will receive a confirmation message once payment is verified.
                                    </p>
                                <?php elseif ($order_details['payment_status'] === 'paid'): ?>
                                    <p class="mb-0 text-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong>Payment Confirmed!</strong> Your order is being processed.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="index.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-home me-2"></i>Continue Shopping
                            </a>
                        </div>
                        <div class="col-md-6">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="account/orders.php" class="btn btn-primary w-100">
                                    <i class="fas fa-list me-2"></i>View All Orders
                                </a>
                            <?php else: ?>
                                <a href="track-order.php?order_id=<?php echo $order_details['order_id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Track This Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h6>Need Help?</h6>
                    <p class="text-muted mb-3">
                        If you have any questions about your order, feel free to contact us.
                    </p>
                    <div class="row">
                        <div class="col-md-4">
                            <a href="https://wa.me/2341234567890" class="btn btn-success btn-sm w-100" target="_blank">
                                <i class="fab fa-whatsapp me-1"></i>WhatsApp
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="mailto:support@hi5vemarketplace.com" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-envelope me-1"></i>Email
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="tel:+2341234567890" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-phone me-1"></i>Call
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-dismiss alerts after 10 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 10000);
</script>

<?php include 'includes/footer.php'; ?> 