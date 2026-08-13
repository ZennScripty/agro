<?php
/**
 * SAMRIDHI AGRO - Agent Order View
 * 
 * This page displays detailed information about a specific order.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Order Details';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get order ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    setFlashMessage('error', 'Invalid order ID.');
    redirect('agent/orders.php');
    exit;
}

// Get agent data
$sql = "SELECT a.* FROM agents a WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Get order details with shop verification
$sql = "SELECT o.*, s.shop_name, s.shop_code, s.owner_name,
        u.full_name as shop_owner,
        sp.id as payment_id,
        sp.amount as payment_amount,
        sp.paid_amount,
        sp.remaining_amount,
        sp.status as payment_status,
        sp.payment_method as payment_method,
        sp.transaction_id,
        sp.agent_collection_date,
        sp.submitted_to_admin_date,
        sp.admin_confirm_date,
        sp.notes as payment_notes,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN shop_payments sp ON o.id = sp.order_id
        WHERE o.id = ? AND s.agent_id = ?";
$order = $db->fetchOne($sql, [$orderId, $agent['id']]);

if (!$order) {
    setFlashMessage('error', 'Order not found or not assigned to you.');
    redirect('agent/orders.php');
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

    .payment-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .payment-badge.pending { background: #FEF3C7; color: #92400E; }
    .payment-badge.collected { background: #DBEAFE; color: #1E40AF; }
    .payment-badge.submitted { background: #EDE9FE; color: #5B21B6; }
    .payment-badge.confirmed { background: #DCFCE7; color: #065F46; }

    .product-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
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

    .timeline-item {
        display: flex;
        gap: 12px;
        padding: 6px 0;
        border-bottom: 1px solid #F7FCF7;
        align-items: flex-start;
    }

    .timeline-item:last-child {
        border-bottom: none;
    }

    .timeline-item .timeline-icon {
        width: 28px;
        height: 28px;
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

    .btn-back {
        padding: 6px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: #E5E7EB;
    }

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
                <span><i class="fas fa-store"></i> <?php echo escapeHtml($order['shop_name']); ?></span>
            </div>
        </div>
        <a href="orders.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
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
                <?php if ($order['payment_id']): ?>
                    <span class="payment-badge <?php echo $order['payment_status']; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                <?php else: ?>
                    <span class="badge-status badge-warning">No Payment</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($order['payment_amount']): ?>
        <div class="detail-row">
            <span class="detail-label">Order Amount</span>
            <span class="detail-value" style="font-weight: 700; color: #14532D;">₹ <?php echo number_format($order['payment_amount'], 2); ?></span>
        </div>
        <?php if ($order['paid_amount'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Amount Paid</span>
            <span class="detail-value" style="color: #16A34A; font-weight: 600;">₹ <?php echo number_format($order['paid_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($order['remaining_amount'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Remaining</span>
            <span class="detail-value" style="color: #DC2626; font-weight: 600;">₹ <?php echo number_format($order['remaining_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($order['payment_method']): ?>
        <div class="detail-row">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value"><?php echo ucfirst($order['payment_method']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($order['transaction_id']): ?>
        <div class="detail-row">
            <span class="detail-label">Transaction ID</span>
            <span class="detail-value" style="font-family: monospace;"><?php echo escapeHtml($order['transaction_id']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($order['delivery_notes']): ?>
        <div class="detail-row">
            <span class="detail-label">Delivery Notes</span>
            <span class="detail-value"><?php echo escapeHtml($order['delivery_notes']); ?></span>
        </div>
        <?php endif; ?>
    </div>

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

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>