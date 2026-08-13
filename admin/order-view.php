<?php
/**
 * SAMRIDHI AGRO - View Order
 * 
 * This page displays detailed information about a specific order.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: Set page title and include admin header
// ============================================

// Set page title
$pageTitle = 'View Order';

// Include admin header (which already includes all configs)
require_once '../includes/admin_header.php';

// Require admin login and permission
requireLogin();
requireRole('admin');
requirePermission('order.view');

// Get database instance
$db = getDB();

// ============================================
// GET ORDER DATA
// ============================================

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to order list
if ($orderId <= 0) {
    setFlashMessage('error', 'Invalid order ID.');
    redirect('admin/orders.php');
    exit;
}

// Get order data with shop and user details
$sql = "SELECT o.*, 
        s.shop_name, s.shop_code, s.shop_type, s.owner_name,
        s.address as shop_address, s.city as shop_city, s.state as shop_state, s.pincode as shop_pincode,
        s.phone as shop_phone, s.email as shop_email,
        u.full_name as shop_owner,
        u2.full_name as approved_by_name,
        a.full_name as agent_name
        FROM orders o 
        LEFT JOIN shops s ON o.shop_id = s.id 
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN users u2 ON o.approved_by = u2.id
        LEFT JOIN agents ag ON s.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        WHERE o.id = ?";
$order = $db->fetchOne($sql, [$orderId]);

// If order not found, redirect
if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect('admin/orders.php');
    exit;
}

// Get order items
$sql = "SELECT oi.*, p.product_name, p.sku, p.unit, p.image,
        (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE product_id = p.id) as total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC";
$orderItems = $db->fetchAll($sql, [$orderId]);

// Calculate order totals
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['total'];
}
$tax = $order['tax'] ?? 0;
$discount = $order['discount'] ?? 0;
$totalAmount = $order['total_amount'] ?? 0;

// Get order timeline (activity log for this order)
$sql = "SELECT al.*, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.module = 'order' AND al.description LIKE ?
        ORDER BY al.created_at DESC 
        LIMIT 10";
$timeline = $db->fetchAll($sql, ['%#' . $order['order_number'] . '%']);

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// HTML CONTENT
// ============================================
?>

<style>
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 20px 24px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .order-header .order-info h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #052E16;
        margin: 0 0 4px 0;
    }
    
    .order-header .order-info .order-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .order-header .order-info .order-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .order-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }
    
    .detail-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
    }
    
    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
    }
    
    .detail-row {
        display: flex;
        padding: 6px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
        width: 160px;
        flex-shrink: 0;
    }
    
    .detail-value {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
        flex: 1;
    }
    
    .product-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .product-item:last-child {
        border-bottom: none;
    }
    
    .product-item .product-thumb {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        background: #F3F4F6;
        border: 1px solid #E5EDE7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #6B7A7B;
        flex-shrink: 0;
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
        font-weight: 600;
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
        font-size: 13px;
        color: #6B7A7B;
    }
    
    .order-totals {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 2px solid #E5EDE7;
        text-align: right;
    }
    
    .order-totals .total-row {
        display: flex;
        justify-content: flex-end;
        padding: 4px 0;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
    }
    
    .order-totals .total-row .label {
        color: #6B7A7B;
        width: 120px;
    }
    
    .order-totals .total-row .value {
        width: 120px;
        text-align: right;
        font-weight: 500;
    }
    
    .order-totals .total-row.grand-total {
        font-size: 18px;
        font-weight: 700;
        color: #052E16;
        padding-top: 8px;
        border-top: 2px solid #E5EDE7;
        margin-top: 4px;
    }
    
    .order-totals .total-row.grand-total .value {
        color: #14532D;
    }
    
    .btn-action-sm {
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
    
    .btn-action-sm:hover {
        transform: translateY(-1px);
    }
    
    .btn-action-sm.btn-primary { background: #14532D; color: white; }
    .btn-action-sm.btn-primary:hover { background: #052E16; }
    
    .btn-action-sm.btn-secondary { background: #F3F4F6; color: #4A5B5D; }
    .btn-action-sm.btn-secondary:hover { background: #E5E7EB; }
    
    .btn-action-sm.btn-danger { background: #FEE2E2; color: #DC2626; }
    .btn-action-sm.btn-danger:hover { background: #FECACA; }
    
    .timeline-item {
        display: flex;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid #F7FCF7;
        align-items: flex-start;
    }
    
    .timeline-item:last-child {
        border-bottom: none;
    }
    
    .timeline-item .timeline-icon {
        width: 36px;
        height: 36px;
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
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
    }
    
    .timeline-item .timeline-content .timeline-time {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    @media (max-width: 768px) {
        .order-header {
            flex-direction: column;
        }
        
        .order-actions {
            width: 100%;
            justify-content: flex-start;
        }
        
        .detail-row {
            flex-direction: column;
            padding: 10px 0;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 2px;
        }
        
        .product-item {
            flex-wrap: wrap;
        }
        
        .product-item .product-price {
            width: 100%;
            text-align: left;
            padding-left: 66px;
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
                <span><i class="fas fa-store"></i> <?php echo escapeHtml($order['shop_name'] ?? 'N/A'); ?></span>
                <span>
                    <i class="fas fa-circle" style="color: <?php 
                        echo match($order['status']) {
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
            </div>
        </div>
        <div class="order-actions">
            <a href="orders.php" class="btn-action-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if ($order['status'] === 'pending'): ?>
            <a href="orders.php?action=cancel&id=<?php echo $order['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
               class="btn-action-sm btn-danger"
               onclick="return confirm('Are you sure you want to cancel this order?')">
                <i class="fas fa-times"></i> Cancel Order
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Order Details Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Shop Information -->
        <div class="detail-section" style="margin-bottom: 0;">
            <div class="section-title">
                <i class="fas fa-store" style="color: #16A34A;"></i>
                Shop Information
            </div>
            <div class="detail-row">
                <span class="detail-label">Shop Name</span>
                <span class="detail-value"><?php echo escapeHtml($order['shop_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shop Code</span>
                <span class="detail-value"><?php echo escapeHtml($order['shop_code'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shop Type</span>
                <span class="detail-value">
                    <?php 
                    $typeLabels = [
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                        'both' => 'Both'
                    ];
                    echo $typeLabels[$order['shop_type']] ?? $order['shop_type'];
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Owner</span>
                <span class="detail-value"><?php echo escapeHtml($order['owner_name'] ?? 'N/A'); ?></span>
            </div>
            <?php if (!empty($order['shop_address'])): ?>
            <div class="detail-row">
                <span class="detail-label">Address</span>
                <span class="detail-value">
                    <?php echo escapeHtml($order['shop_address']); ?>
                    <?php if (!empty($order['shop_city']) || !empty($order['shop_state'])): ?>
                        <br>
                        <?php 
                        $locationParts = [];
                        if (!empty($order['shop_city'])) $locationParts[] = $order['shop_city'];
                        if (!empty($order['shop_state'])) $locationParts[] = $order['shop_state'];
                        if (!empty($order['shop_pincode'])) $locationParts[] = $order['shop_pincode'];
                        echo escapeHtml(implode(', ', $locationParts));
                        ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Contact</span>
                <span class="detail-value">
                    <?php echo !empty($order['shop_phone']) ? escapeHtml($order['shop_phone']) : 'N/A'; ?>
                    <?php if (!empty($order['shop_email'])): ?>
                    <br><?php echo escapeHtml($order['shop_email']); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php if (!empty($order['agent_name'])): ?>
            <div class="detail-row">
                <span class="detail-label">Assigned Agent</span>
                <span class="detail-value"><?php echo escapeHtml($order['agent_name']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Information -->
        <div class="detail-section" style="margin-bottom: 0;">
            <div class="section-title">
                <i class="fas fa-info-circle" style="color: #16A34A;"></i>
                Order Information
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Number</span>
                <span class="detail-value">
                    <span style="font-weight: 600;">#<?php echo escapeHtml($order['order_number']); ?></span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Date</span>
                <span class="detail-value"><?php echo formatDate($order['created_at']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
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
                    <?php if (!empty($order['payment_method'])): ?>
                    <span style="font-size: 13px; color: #6B7A7B; margin-left: 8px;">
                        (<?php echo escapeHtml(ucfirst($order['payment_method'])); ?>)
                    </span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if (!empty($order['delivery_notes'])): ?>
            <div class="detail-row">
                <span class="detail-label">Delivery Notes</span>
                <span class="detail-value"><?php echo escapeHtml($order['delivery_notes']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['approved_by_name']): ?>
            <div class="detail-row">
                <span class="detail-label">Approved By</span>
                <span class="detail-value"><?php echo escapeHtml($order['approved_by_name']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['approved_at']): ?>
            <div class="detail-row">
                <span class="detail-label">Approved At</span>
                <span class="detail-value"><?php echo formatDate($order['approved_at']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Order Items -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-boxes" style="color: #16A34A;"></i>
            Order Items
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo count($orderItems); ?> items)
            </span>
        </div>
        
        <?php if (empty($orderItems)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-box-open" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No items in this order.
            </p>
        <?php else: ?>
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
                    <div style="font-size: 13px; color: #6B7A7B;">
                        Unit: <?php echo escapeHtml(ucfirst($item['unit'])); ?>
                    </div>
                </div>
                <div class="product-price">
                    <div class="price">₹ <?php echo number_format($item['price'], 2); ?></div>
                    <div class="qty">Qty: <?php echo $item['quantity']; ?></div>
                    <div class="price" style="font-size: 15px; margin-top: 4px;">
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
        <?php endif; ?>
    </div>
    
    <!-- Order Timeline -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Order Timeline
        </div>
        
        <?php if (empty($timeline)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-history" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No activity recorded for this order yet.
            </p>
        <?php else: ?>
            <?php foreach ($timeline as $activity): ?>
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="fas fa-<?php 
                        echo match($activity['action']) {
                            'create' => 'plus',
                            'update' => 'edit',
                            'delete' => 'trash',
                            'login' => 'sign-in-alt',
                            'logout' => 'sign-out-alt',
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

<?php require_once '../includes/admin_footer.php'; ?>