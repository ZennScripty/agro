<?php
/**
 * SAMRIDHI AGRO - Shop Order View
 * 
 * This page displays detailed information about a specific order,
 * including complete payment history with receiver details.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'Order Details';

// Include shop header
require_once __DIR__ . '/../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get shop data
$sql = "SELECT s.* FROM shops s WHERE s.user_id = ?";
$shop = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Get order ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    setFlashMessage('error', 'Invalid order ID.');
    redirect('shop/orders.php');
    exit;
}

// Get order details
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        WHERE o.id = ? AND o.shop_id = ?";
$order = $db->fetchOne($sql, [$orderId, $shop['id']]);

if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect('shop/orders.php');
    exit;
}

// Get order items
$sql = "SELECT oi.*, p.product_name, p.sku, p.unit, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC";
$orderItems = $db->fetchAll($sql, [$orderId]);

// Calculate totals
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['total'];
}
$tax = $order['tax'] ?? 0;
$discount = $order['discount'] ?? 0;
$totalAmount = $order['total_amount'] ?? 0;

// ============================================
// GET COMPLETE PAYMENT INFO WITH INSTALLMENTS
// ============================================
$sql = "SELECT sp.*, 
        (SELECT COUNT(*) FROM payment_installments WHERE payment_id = sp.id) as installment_count,
        (SELECT COALESCE(SUM(amount), 0) FROM payment_installments WHERE payment_id = sp.id AND status = 'confirmed') as confirmed_amount,
        (SELECT COALESCE(SUM(amount), 0) FROM payment_installments WHERE payment_id = sp.id) as total_paid_via_installments
        FROM shop_payments sp
        WHERE sp.order_id = ?";
$paymentInfo = $db->fetchOne($sql, [$orderId]);

// Get installment details with receiver info
$installments = [];
if ($paymentInfo) {
    $sql = "SELECT * FROM payment_installments WHERE payment_id = ? ORDER BY installment_number ASC";
    $installments = $db->fetchAll($sql, [$paymentInfo['id']]);
}

// Order timeline
$sql = "SELECT al.*, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.module = 'order' AND al.description LIKE ?
        ORDER BY al.created_at DESC 
        LIMIT 10";
$timeline = $db->fetchAll($sql, ['%#' . $order['order_number'] . '%']);

$csrfToken = generateCsrfToken();
?>

<style>
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 16px 20px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .order-header .order-info h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
        margin: 0;
    }

    .order-header .order-info .order-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 14px;
        color: #4A5B5D;
        margin-top: 4px;
    }

    .order-header .order-info .order-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .detail-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
    }

    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
    }

    .detail-row {
        display: flex;
        padding: 4px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-size: 13px;
        font-weight: 500;
        color: #6B7A7B;
        width: 140px;
        flex-shrink: 0;
    }

    .detail-value {
        font-size: 13px;
        color: #052E16;
        flex: 1;
    }

    .badge-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }

    .product-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .product-item:last-child {
        border-bottom: none;
    }

    .product-item .product-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #6B7A7B;
        flex-shrink: 0;
        border: 1px solid #E5EDE7;
    }

    .product-item .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }

    .product-item .product-details {
        flex: 1;
    }

    .product-item .product-details .product-name {
        font-weight: 500;
        color: #052E16;
    }

    .product-item .product-details .product-sku {
        font-size: 12px;
        color: #6B7A7B;
    }

    .product-item .product-price {
        text-align: right;
    }

    .product-item .product-price .price {
        font-weight: 600;
        color: #14532D;
    }

    .product-item .product-price .qty {
        font-size: 12px;
        color: #6B7A7B;
    }

    .order-totals {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 2px solid #E5EDE7;
        text-align: right;
    }

    .order-totals .total-row {
        display: flex;
        justify-content: flex-end;
        padding: 2px 0;
        font-size: 14px;
    }

    .order-totals .total-row .label {
        color: #6B7A7B;
        width: 100px;
    }

    .order-totals .total-row .value {
        width: 100px;
        text-align: right;
        font-weight: 500;
    }

    .order-totals .total-row.grand-total {
        font-size: 17px;
        font-weight: 700;
        color: #052E16;
        padding-top: 6px;
        border-top: 2px solid #E5EDE7;
        margin-top: 4px;
    }

    .order-totals .total-row.grand-total .value {
        color: #14532D;
    }

    /* Payment Progress */
    .payment-progress-container {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .payment-progress-container .progress-bar {
        height: 6px;
        background: #E5EDE7;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 4px;
    }

    .payment-progress-container .progress-bar .progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
        background: linear-gradient(90deg, #16A34A, #22C55E);
    }

    /* Installment List */
    .installment-list {
        margin-top: 8px;
    }

    .installment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        font-size: 12px;
        border-bottom: 1px solid #F7FCF7;
    }

    .installment-item:last-child {
        border-bottom: none;
    }

    .installment-item .inst-number {
        font-weight: 500;
        color: #052E16;
        min-width: 50px;
    }

    .installment-item .inst-amount {
        font-weight: 600;
        color: #14532D;
        min-width: 80px;
    }

    .installment-item .inst-receiver {
        font-size: 11px;
        color: #6B7A7B;
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
    }

    .installment-item .inst-receiver .receiver-agent {
        color: #7C3AED;
        font-weight: 500;
    }

    .installment-item .inst-receiver .receiver-admin {
        color: #DC2626;
        font-weight: 500;
    }

    .badge-installment {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-installment.pending { background: #FEF3C7; color: #92400E; }
    .badge-installment.collected { background: #DBEAFE; color: #1E40AF; }
    .badge-installment.submitted { background: #EDE9FE; color: #5B21B6; }
    .badge-installment.confirmed { background: #DCFCE7; color: #065F46; }

    .receiver-badge {
        display: inline-block;
        padding: 1px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }

    .receiver-badge.agent {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .receiver-badge.admin {
        background: #FEE2E2;
        color: #991B1B;
    }

    .timeline-item {
        display: flex;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #F7FCF7;
        align-items: flex-start;
    }

    .timeline-item:last-child {
        border-bottom: none;
    }

    .timeline-item .timeline-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #F0FDF4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #16A34A;
        flex-shrink: 0;
    }

    .timeline-item .timeline-content {
        flex: 1;
    }

    .timeline-item .timeline-content .timeline-text {
        font-size: 13px;
        color: #052E16;
    }

    .timeline-item .timeline-content .timeline-time {
        font-size: 11px;
        color: #6B7A7B;
    }

    .btn-action {
        padding: 6px 16px;
        border-radius: 6px;
        border: none;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-action:hover { transform: translateY(-1px); }

    .btn-back { background: #F3F4F6; color: #4A5B5D; }
    .btn-back:hover { background: #E5E7EB; }

    .btn-cancel { background: #FEE2E2; color: #DC2626; }
    .btn-cancel:hover { background: #FECACA; }

    @media (max-width: 768px) {
        .detail-row {
            flex-direction: column;
            padding: 8px 0;
        }
        .detail-label {
            width: 100%;
        }
        .product-item {
            flex-wrap: wrap;
        }
        .product-item .product-price {
            width: 100%;
            text-align: left;
            padding-left: 52px;
        }
        .order-totals .total-row {
            justify-content: space-between;
        }
        .order-totals .total-row .label {
            width: auto;
        }
        .order-totals .total-row .value {
            width: auto;
        }
        .installment-item {
            flex-wrap: wrap;
            gap: 4px;
        }
        .installment-item .inst-receiver {
            width: 100%;
            padding-left: 50px;
        }
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">

    <!-- Order Header -->
    <div class="order-header">
        <div class="order-info">
            <h2>Order #<?php echo escapeHtml($order['order_number']); ?></h2>
            <div class="order-meta">
                <span><i class="fas fa-calendar"></i> <?php echo formatDate($order['created_at']); ?></span>
                <span>
                    <i class="fas fa-circle" style="color: <?php
                        echo match ($order['status']) {
                            'pending' => '#F59E0B',
                            'confirmed' => '#3B82F6',
                            'processing' => '#8B5CF6',
                            'shipped' => '#06B6D4',
                            'delivered' => '#22C55E',
                            'cancelled' => '#EF4444',
                            'returned' => '#F59E0B',
                            default => '#6B7280'
                        };
                    ?>; font-size: 10px;"></i>
                    <?php echo ucfirst($order['status']); ?>
                </span>
                <span><i class="fas fa-rupee-sign"></i> ₹ <?php echo number_format($order['total_amount'], 2); ?></span>
                <span><i class="fas fa-box"></i> <?php echo $order['item_count']; ?> items</span>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="orders.php" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if ($order['status'] === 'pending'): ?>
                <a href="orders.php?action=cancel&id=<?php echo $order['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                    class="btn-action btn-cancel"
                    onclick="return confirm('Are you sure you want to cancel this order?')">
                    <i class="fas fa-times"></i> Cancel Order
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-boxes" style="color: #16A34A;"></i>
            Order Items
        </div>

        <?php foreach ($orderItems as $item): ?>
            <div class="product-item">
                <div class="product-thumb">
                    <?php if (!empty($item['image']) && file_exists('../uploads/products/' . $item['image'])): ?>
                        <img src="../uploads/products/<?php echo escapeHtml($item['image']); ?>" alt="<?php echo escapeHtml($item['product_name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-box"></i>
                    <?php endif; ?>
                </div>
                <div class="product-details">
                    <div class="product-name"><?php echo escapeHtml($item['product_name']); ?></div>
                    <div class="product-sku">SKU: <?php echo escapeHtml($item['sku']); ?></div>
                </div>
                <div class="product-price">
                    <div class="price">₹ <?php echo number_format($item['price'], 2); ?></div>
                    <div class="qty">Qty: <?php echo $item['quantity']; ?></div>
                    <div class="price" style="font-size: 14px; margin-top: 2px;">
                        ₹ <?php echo number_format($item['total'], 2); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Order Totals -->
        <div class="order-totals">
            <div class="total-row">
                <span class="label">Subtotal</span>
                <span class="value">₹ <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($tax > 0): ?>
                <div class="total-row">
                    <span class="label">Tax</span>
                    <span class="value">₹ <?php echo number_format($tax, 2); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($discount > 0): ?>
                <div class="total-row">
                    <span class="label">Discount</span>
                    <span class="value">-₹ <?php echo number_format($discount, 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span class="label">Total</span>
                <span class="value">₹ <?php echo number_format($totalAmount, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle" style="color: #16A34A;"></i>
            Order Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Order Status</span>
            <span class="detail-value">
                <?php
                $statusColors = [
                    'pending' => 'badge-warning',
                    'confirmed' => 'badge-info',
                    'processing' => 'badge-primary',
                    'shipped' => 'badge-info',
                    'delivered' => 'badge-success',
                    'cancelled' => 'badge-danger',
                    'returned' => 'badge-warning'
                ];
                $color = $statusColors[$order['status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $color; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Status</span>
            <span class="detail-value">
                <?php
                $paymentColors = [
                    'pending' => 'badge-warning',
                    'paid' => 'badge-success',
                    'failed' => 'badge-danger',
                    'refunded' => 'badge-info'
                ];
                $pColor = $paymentColors[$order['payment_status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $pColor; ?>">
                    <?php echo ucfirst($order['payment_status']); ?>
                </span>
            </span>
        </div>
        <?php if ($order['payment_method']): ?>
            <div class="detail-row">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value"><?php echo ucfirst($order['payment_method']); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($order['delivery_notes']): ?>
            <div class="detail-row">
                <span class="detail-label">Delivery Notes</span>
                <span class="detail-value"><?php echo escapeHtml($order['delivery_notes']); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================ -->
    <!-- COMPLETE PAYMENT INFORMATION WITH INSTALLMENTS -->
    <!-- ============================================ -->
    <?php if ($paymentInfo): ?>
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-credit-card" style="color: #16A34A;"></i>
            Payment Details
            <?php if ($paymentInfo['status'] === 'confirmed'): ?>
                <span class="badge-status badge-success" style="margin-left: 8px;">
                    <i class="fas fa-check-circle"></i> Completed
                </span>
            <?php elseif ($paymentInfo['status'] === 'pending' && $paymentInfo['paid_amount'] > 0): ?>
                <span class="badge-status badge-warning" style="margin-left: 8px;">
                    <i class="fas fa-clock"></i> Partial Payment
                </span>
            <?php endif; ?>
        </div>

        <!-- Payment Summary -->
        <div class="detail-row">
            <span class="detail-label">Total Amount</span>
            <span class="detail-value" style="font-weight: 700; color: #14532D;">₹ <?php echo number_format($paymentInfo['amount'], 2); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount Paid</span>
            <span class="detail-value" style="color: #16A34A; font-weight: 600;">
                ₹ <?php echo number_format($paymentInfo['paid_amount'], 2); ?>
                <?php if ($paymentInfo['paid_amount'] > 0): ?>
                    <span style="font-size: 12px; color: #6B7A7B; margin-left: 8px;">
                        (<?php echo round(($paymentInfo['paid_amount'] / $paymentInfo['amount']) * 100); ?>%)
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($paymentInfo['remaining_amount'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Remaining</span>
            <span class="detail-value" style="color: #DC2626; font-weight: 600;">
                ₹ <?php echo number_format($paymentInfo['remaining_amount'], 2); ?>
                <?php if ($paymentInfo['status'] !== 'confirmed'): ?>
                    <a href="payments.php" style="font-size: 12px; color: #16A34A; margin-left: 8px; text-decoration: none;">
                        <i class="fas fa-arrow-right"></i> Pay Now
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Payment Progress Bar -->
        <?php if ($paymentInfo['amount'] > 0): ?>
        <div class="payment-progress-container">
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #6B7A7B;">
                <span>Payment Progress</span>
                <span><?php echo round(($paymentInfo['paid_amount'] / $paymentInfo['amount']) * 100); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo round(($paymentInfo['paid_amount'] / $paymentInfo['amount']) * 100); ?>%;"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Installment History -->
        <?php if (!empty($installments)): ?>
        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #F0FDF4;">
            <div style="font-size: 12px; font-weight: 600; color: #6B7A7B; margin-bottom: 6px;">
                <i class="fas fa-list"></i> Installment History (<?php echo count($installments); ?> installments)
            </div>
            <div class="installment-list">
                <?php foreach ($installments as $inst): ?>
                <div class="installment-item">
                    <span class="inst-number">#<?php echo $inst['installment_number']; ?></span>
                    <span class="inst-amount">₹ <?php echo number_format($inst['amount'], 2); ?></span>
                    <span class="inst-receiver">
                        <?php 
                        $receiverType = $inst['received_by'] ?? 'agent';
                        $receiverName = $inst['received_by_name'] ?? ($receiverType === 'agent' ? 'Agent' : 'Admin');
                        ?>
                        <span class="receiver-badge <?php echo $receiverType; ?>">
                            <i class="fas fa-<?php echo $receiverType === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                            <?php echo escapeHtml($receiverName); ?>
                        </span>
                    </span>
                    <span>
                        <?php 
                        $instStatusColors = [
                            'pending' => 'pending',
                            'collected' => 'collected',
                            'submitted' => 'submitted',
                            'confirmed' => 'confirmed'
                        ];
                        $instColor = $instStatusColors[$inst['status']] ?? 'pending';
                        ?>
                        <span class="badge-installment <?php echo $instColor; ?>">
                            <?php echo ucfirst($inst['status']); ?>
                        </span>
                        <?php if ($inst['status'] === 'confirmed'): ?>
                            <span style="font-size: 10px; color: #16A34A;">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span style="font-size: 10px; color: #6B7A7B;">
                        <?php echo formatDate($inst['payment_date']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Method & Transaction -->
        <?php if ($paymentInfo['payment_method']): ?>
        <div class="detail-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #F0FDF4;">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value"><?php echo ucfirst($paymentInfo['payment_method']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($paymentInfo['transaction_id']): ?>
        <div class="detail-row">
            <span class="detail-label">Transaction ID</span>
            <span class="detail-value" style="font-family: monospace; font-size: 12px;"><?php echo escapeHtml($paymentInfo['transaction_id']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Shipping Address -->
    <?php if (!empty($order['shipping_address'])): ?>
        <div class="detail-section">
            <div class="section-title">
                <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
                Shipping Address
            </div>
            <div class="detail-row">
                <span class="detail-value">
                    <?php echo escapeHtml($order['shipping_address']); ?>
                    <?php if (!empty($order['shipping_city']) || !empty($order['shipping_state'])): ?>
                        <br>
                        <?php
                        $locationParts = [];
                        if (!empty($order['shipping_city'])) $locationParts[] = $order['shipping_city'];
                        if (!empty($order['shipping_state'])) $locationParts[] = $order['shipping_state'];
                        if (!empty($order['shipping_pincode'])) $locationParts[] = $order['shipping_pincode'];
                        echo escapeHtml(implode(', ', $locationParts));
                        ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Order Timeline -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Order Timeline
        </div>

        <?php if (empty($timeline)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 12px 0;">
                <i class="fas fa-history" style="font-size: 20px; display: block; margin-bottom: 4px; opacity: 0.5;"></i>
                No activity recorded for this order yet.
            </p>
        <?php else: ?>
            <?php foreach ($timeline as $activity): ?>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-<?php
                            echo match ($activity['action']) {
                                'create' => 'plus',
                                'update' => 'edit',
                                'delete' => 'trash',
                                default => 'circle'
                            };
                        ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-text">
                            <?php if ($activity['full_name']): ?>
                                <strong><?php echo escapeHtml($activity['full_name']); ?></strong>
                            <?php endif; ?>
                            <?php echo escapeHtml($activity['description'] ?? $activity['action']); ?>
                        </div>
                        <div class="timeline-time">
                            <i class="far fa-clock"></i> <?php echo formatDate($activity['created_at']); ?>
                            (<?php echo timeAgo($activity['created_at']); ?>)
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>