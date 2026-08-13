<?php
/**
 * SAMRIDHI AGRO - Agent Shop History
 * 
 * This page displays complete history of a shop including orders,
 * payments, and activities.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
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

// Get all orders
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC";
$orders = $db->fetchAll($sql, [$shopId]);

// Get all payments
$sql = "SELECT sp.*, o.order_number 
        FROM shop_payments sp 
        LEFT JOIN orders o ON sp.order_id = o.id
        WHERE sp.shop_id = ? 
        ORDER BY sp.created_at DESC";
$payments = $db->fetchAll($sql, [$shopId]);

// Get activity logs
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
                    <span class="badge-status <?php echo $color; ?>"><?php echo ucfirst($order['status']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Payments History -->
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
                        <?php if ($payment['order_number']): ?>
                            Order: #<?php echo escapeHtml($payment['order_number']); ?>
                        <?php else: ?>
                            Payment #<?php echo $payment['id']; ?>
                        <?php endif; ?>
                    </div>
                    <div class="item-date"><i class="far fa-calendar"></i> <?php echo formatDate($payment['payment_date']); ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; color: #14532D;">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                    <?php 
                    $pStatusColors = [
                        'pending' => 'badge-warning',
                        'collected' => 'badge-info',
                        'submitted' => 'badge-primary',
                        'confirmed' => 'badge-success',
                        'failed' => 'badge-danger'
                    ];
                    $pColor = $pStatusColors[$payment['status']] ?? 'badge-secondary';
                    ?>
                    <span class="badge-status <?php echo $pColor; ?>"><?php echo ucfirst($payment['status']); ?></span>
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