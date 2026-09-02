<?php
/**
 * SAMRIDHI AGRO - Agent Shop History
 * 
 * This page displays complete history of a shop including orders,
 * payments (from payments table), and activities.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'Shop History';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get shop ID
$shopId = isset($_GET['shop']) ? (int)$_GET['shop'] : 0;

if ($shopId <= 0) {
    setFlashMessage('error', 'Invalid shop ID.');
    redirect('agent/shops.php');
    exit;
}

// Verify shop belongs to agent
$sql = "SELECT s.*, u.full_name as owner_name 
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ? AND s.agent_id = ?";
$shop = $db->fetchOne($sql, [$shopId, $agent['id']]);

if (!$shop) {
    setFlashMessage('error', 'Shop not found or not assigned to you.');
    redirect('agent/shops.php');
    exit;
}

// ============================================
// GET FINANCIAL SUMMARY
// ============================================

// Total Business
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM orders 
        WHERE shop_id = ? AND status != 'cancelled'";
$result = $db->fetchOne($sql, [$shopId]);
$totalBusiness = $result['total'] ?? 0;

// Total Paid (Confirmed payments)
$sql = "SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE shop_id = ? AND status = 'confirmed'";
$result = $db->fetchOne($sql, [$shopId]);
$totalPaid = $result['total'] ?? 0;

// Remaining
$remaining = max(0, $totalBusiness - $totalPaid);

// ============================================
// GET ORDERS
// ============================================

$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC";
$orders = $db->fetchAll($sql, [$shopId]);

// ============================================
// GET PAYMENTS (FROM payments TABLE)
// ============================================

$sql = "SELECT p.*, 
        ua.full_name as agent_name,
        uc.full_name as confirmed_by_name
        FROM payments p
        LEFT JOIN agents ag ON p.agent_id = ag.id
        LEFT JOIN users ua ON ag.user_id = ua.id
        LEFT JOIN users uc ON p.confirmed_by = uc.id
        WHERE p.shop_id = ? 
        ORDER BY p.created_at DESC";
$payments = $db->fetchAll($sql, [$shopId]);

// ============================================
// GET ACTIVITY LOGS
// ============================================

$sql = "SELECT al.*, u.full_name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.module IN ('shop', 'order', 'payment') 
        AND (al.description LIKE ? OR al.description LIKE ?)
        ORDER BY al.created_at DESC
        LIMIT 20";
$searchShop = '%' . $shop['shop_name'] . '%';
$searchCode = '%' . $shop['shop_code'] . '%';
$activities = $db->fetchAll($sql, [$searchShop, $searchCode]);

// ============================================
// PAYMENT ROUTE LABELS
// ============================================

$payToLabels = [
    'agent' => 'Agent Collection',
    'admin' => 'Direct to Admin'
];

$payToColors = [
    'agent' => 'badge-primary',
    'admin' => 'badge-danger'
];

$statusLabels = [
    'pending' => 'Pending',
    'collected' => 'Collected by Agent',
    'submitted' => 'Submitted to Admin',
    'confirmed' => 'Confirmed',
    'failed' => 'Failed'
];

$statusColors = [
    'pending' => 'badge-warning',
    'collected' => 'badge-info',
    'submitted' => 'badge-primary',
    'confirmed' => 'badge-success',
    'failed' => 'badge-danger'
];
?>

<style>
    .shop-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .shop-header .shop-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
    }
    
    .shop-header .shop-meta {
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .history-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
    }
    
    .history-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .history-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .history-item:last-child {
        border-bottom: none;
    }
    
    .history-item .item-info .item-title {
        font-weight: 500;
        color: #052E16;
    }
    
    .history-item .item-info .item-date {
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .badge-status {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }

    .pay-to-badge {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }

    .pay-to-badge.agent { background: #EDE9FE; color: #5B21B6; }
    .pay-to-badge.admin { background: #FEE2E2; color: #991B1B; }
    
    .financial-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .financial-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 12px 16px;
        text-align: center;
    }
    
    .financial-card .amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
    }
    
    .financial-card .label {
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .financial-card .amount.positive { color: #14532D; }
    .financial-card .amount.zero { color: #16A34A; }
    .financial-card .amount.negative { color: #DC2626; }
    
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
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history" style="color: #16A34A;"></i>
            Shop History
        </h3>
        <a href="shops.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Shops
        </a>
    </div>
    
    <!-- Shop Header -->
    <div class="shop-header">
        <div>
            <div class="shop-name"><?php echo escapeHtml($shop['shop_name']); ?></div>
            <div class="shop-meta">
                <i class="fas fa-id-badge"></i> <?php echo escapeHtml($shop['shop_code']); ?>
                | <i class="fas fa-user"></i> <?php echo escapeHtml($shop['owner_name']); ?>
                | <i class="fas fa-calendar"></i> <?php echo formatDate($shop['created_at']); ?>
                <?php if ($shop['agent_id']): ?>
                | <i class="fas fa-user-tie"></i> <?php echo escapeHtml($agent['full_name'] ?? ''); ?>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php 
            $statusColors = [
                'approved' => 'badge-success',
                'pending' => 'badge-warning',
                'suspended' => 'badge-danger',
                'rejected' => 'badge-secondary'
            ];
            $color = $statusColors[$shop['status']] ?? 'badge-secondary';
            ?>
            <span class="badge-status <?php echo $color; ?>" style="font-size: 14px; padding: 4px 14px;">
                <?php echo ucfirst($shop['status']); ?>
            </span>
        </div>
    </div>
    
    <!-- Financial Summary -->
    <div class="financial-summary">
        <div class="financial-card">
            <div class="amount positive">₹ <?php echo number_format($totalBusiness, 0); ?></div>
            <div class="label">Total Business</div>
        </div>
        <div class="financial-card">
            <div class="amount positive">₹ <?php echo number_format($totalPaid, 0); ?></div>
            <div class="label">Paid</div>
        </div>
        <div class="financial-card">
            <?php 
            $class = $remaining <= 0 ? 'zero' : 'negative';
            ?>
            <div class="amount <?php echo $class; ?>">₹ <?php echo number_format($remaining, 0); ?></div>
            <div class="label">Remaining</div>
            <?php if ($remaining <= 0): ?>
                <span style="font-size: 9px; color: #16A34A;">
                    <i class="fas fa-check-circle"></i> Fully Paid
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Orders History -->
    <div class="history-section">
        <div class="section-title">
            <span><i class="fas fa-shopping-cart" style="color: #7C3AED;"></i> Orders History (<?php echo count($orders); ?>)</span>
            <a href="orders.php?shop=<?php echo $shopId; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($orders)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 12px 0;">
                <i class="fas fa-inbox" style="font-size: 20px; display: block; margin-bottom: 4px; opacity: 0.5;"></i>
                No orders found
            </p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="history-item">
                <div class="item-info">
                    <div class="item-title">#<?php echo escapeHtml($order['order_number']); ?> (<?php echo $order['item_count']; ?> items)</div>
                    <div class="item-date"><i class="far fa-calendar"></i> <?php echo formatDate($order['created_at']); ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; color: #14532D;">₹ <?php echo number_format($order['total_amount'], 2); ?></div>
                    <?php 
                    $oStatusColors = [
                        'pending' => 'badge-warning',
                        'confirmed' => 'badge-info',
                        'processing' => 'badge-primary',
                        'shipped' => 'badge-info',
                        'delivered' => 'badge-success',
                        'cancelled' => 'badge-danger',
                        'returned' => 'badge-warning'
                    ];
                    $oColor = $oStatusColors[$order['status']] ?? 'badge-secondary';
                    ?>
                    <span class="badge-status <?php echo $oColor; ?>"><?php echo ucfirst($order['status']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Payments History (from payments table) -->
    <div class="history-section">
        <div class="section-title">
            <span><i class="fas fa-credit-card" style="color: #16A34A;"></i> Payments History (<?php echo count($payments); ?>)</span>
            <a href="shop-payments.php?shop=<?php echo $shopId; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($payments)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 12px 0;">
                <i class="fas fa-wallet" style="font-size: 20px; display: block; margin-bottom: 4px; opacity: 0.5;"></i>
                No payments found
            </p>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
            <div class="history-item">
                <div class="item-info">
                    <div class="item-title">
                        Payment #<?php echo $payment['id']; ?>
                        <span class="pay-to-badge <?php echo $payment['pay_to']; ?>">
                            <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                            <?php echo $payToLabels[$payment['pay_to']] ?? ucfirst($payment['pay_to']); ?>
                        </span>
                        <?php if ($payment['payment_method']): ?>
                            <span style="font-size: 10px; color: #6B7A7B; margin-left: 4px;">
                                (<?php echo ucfirst($payment['payment_method']); ?>)
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($payment['transaction_id'])): ?>
                            <span style="font-size: 9px; color: #6B7A7B; margin-left: 4px; font-family: monospace;">
                                TXN: <?php echo escapeHtml($payment['transaction_id']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="item-date">
                        <i class="far fa-calendar"></i> <?php echo formatDate($payment['created_at']); ?>
                        <?php if ($payment['agent_collected_at']): ?>
                            <span style="margin-left: 8px;">
                                <i class="fas fa-hand-holding-usd"></i> Collected: <?php echo formatDate($payment['agent_collected_at']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($payment['submitted_at']): ?>
                            <span style="margin-left: 8px;">
                                <i class="fas fa-arrow-up"></i> Submitted: <?php echo formatDate($payment['submitted_at']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($payment['confirmed_at']): ?>
                            <span style="margin-left: 8px;">
                                <i class="fas fa-check-circle"></i> Confirmed: <?php echo formatDate($payment['confirmed_at']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; color: #14532D;">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                    <?php 
                    $pColor = $statusColors[$payment['status']] ?? 'badge-warning';
                    ?>
                    <span class="badge-status <?php echo $pColor; ?>">
                        <?php echo $statusLabels[$payment['status']] ?? ucfirst($payment['status']); ?>
                    </span>
                    <?php if ($payment['confirmed_by_name']): ?>
                        <div style="font-size: 9px; color: #6B7A7B;">
                            by <?php echo escapeHtml($payment['confirmed_by_name']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Activities -->
    <div class="history-section" style="margin-bottom: 0;">
        <div class="section-title">
            <span><i class="fas fa-clock" style="color: #2563EB;"></i> Recent Activities</span>
        </div>
        <?php if (empty($activities)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 12px 0;">
                <i class="fas fa-inbox" style="font-size: 20px; display: block; margin-bottom: 4px; opacity: 0.5;"></i>
                No activities found
            </p>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
            <div class="history-item">
                <div class="item-info">
                    <div class="item-title">
                        <?php if ($activity['user_name']): ?>
                            <strong><?php echo escapeHtml($activity['user_name']); ?></strong>
                        <?php endif; ?>
                        <?php echo escapeHtml($activity['description'] ?? $activity['action']); ?>
                    </div>
                    <div class="item-date"><i class="far fa-clock"></i> <?php echo timeAgo($activity['created_at']); ?></div>
                </div>
                <div>
                    <span style="font-size: 11px; color: #6B7A7B;"><?php echo formatDate($activity['created_at']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>