<?php
/**
 * SAMRIDHI AGRO - Agent Shop View
 * 
 * This page displays detailed information about a specific shop.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Shop Details';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get shop ID
$shopId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($shopId <= 0) {
    setFlashMessage('error', 'Invalid shop ID.');
    redirect('agent/shops.php');
    exit;
}

// Get shop data with agent verification
$sql = "SELECT s.*, u.full_name as owner_name, u.username, u.email, u.phone,
        u.created_at as user_created_at,
        a.company_name as agent_company_name,
        a.commission_rate,
        (SELECT COUNT(*) FROM orders WHERE shop_id = s.id) as total_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE shop_id = s.id AND status = 'delivered') as total_revenue,
        (SELECT COALESCE(SUM(total_amount * ? / 100), 0) FROM orders o WHERE o.shop_id = s.id AND o.status = 'delivered') as commission_earned
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN agents a ON s.agent_id = a.id
        WHERE s.id = ? AND s.agent_id = ?";
$shop = $db->fetchOne($sql, [$agent['commission_rate'], $shopId, $agent['id']]);

if (!$shop) {
    setFlashMessage('error', 'Shop not found or not assigned to you.');
    redirect('agent/shops.php');
    exit;
}

// Get recent orders
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$shopId]);

// Get recent payments
$sql = "SELECT sp.*, o.order_number 
        FROM shop_payments sp 
        LEFT JOIN orders o ON sp.order_id = o.id
        WHERE sp.shop_id = ? 
        ORDER BY sp.created_at DESC 
        LIMIT 5";
$recentPayments = $db->fetchAll($sql, [$shopId]);

$csrfToken = generateCsrfToken();
?>

<style>
    .shop-profile {
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .shop-profile .shop-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #14532D, #16A34A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: white;
        flex-shrink: 0;
    }
    
    .shop-profile .shop-info h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
        margin: 0;
    }
    
    .shop-profile .shop-info .shop-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 14px;
        color: #4A5B5D;
        margin-top: 4px;
    }
    
    .shop-profile .shop-info .shop-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 16px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .stat-card.orders .stat-number { color: #7C3AED; }
    .stat-card.revenue .stat-number { color: #16A34A; }
    .stat-card.commission .stat-number { color: #D97706; }
    
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
            <i class="fas fa-store" style="color: #16A34A;"></i>
            Shop Details
        </h3>
        <a href="shops.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Shops
        </a>
    </div>
    
    <!-- Shop Profile -->
    <div class="shop-profile">
        <div class="shop-icon">
            <i class="fas fa-store"></i>
        </div>
        <div class="shop-info">
            <h2><?php echo escapeHtml($shop['shop_name']); ?></h2>
            <div class="shop-meta">
                <span><i class="fas fa-id-badge"></i> <?php echo escapeHtml($shop['shop_code']); ?></span>
                <span><i class="fas fa-user"></i> <?php echo escapeHtml($shop['owner_name']); ?></span>
                <span>
                    <i class="fas fa-circle" style="color: <?php 
                        echo match($shop['status']) {
                            'approved' => '#16A34A',
                            'pending' => '#F59E0B',
                            'suspended' => '#DC2626',
                            'rejected' => '#6B7A7B',
                            default => '#6B7A7B'
                        };
                    ?>; font-size: 10px;"></i>
                    <?php echo ucfirst($shop['status']); ?>
                </span>
                <?php if (!empty($shop['agent_company_name'])): ?>
                <span><i class="fas fa-building"></i> <?php echo escapeHtml($shop['agent_company_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card orders">
            <div class="stat-number"><?php echo number_format($shop['total_orders'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-number">₹ <?php echo number_format($shop['total_revenue'] ?? 0, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card commission">
            <div class="stat-number">₹ <?php echo number_format($shop['commission_earned'] ?? 0, 0); ?></div>
            <div class="stat-label">Commission Earned</div>
        </div>
        <div class="stat-card" style="border-color: #EDE9FE;">
            <div class="stat-number" style="color: #7C3AED; font-size: 18px;">
                <?php echo number_format($shop['commission_rate'] ?? 0, 1); ?>%
            </div>
            <div class="stat-label">Commission Rate</div>
        </div>
    </div>
    
    <!-- Shop Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle" style="color: #16A34A;"></i>
            Shop Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Name</span>
            <span class="detail-value"><?php echo escapeHtml($shop['shop_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Code</span>
            <span class="detail-value"><?php echo escapeHtml($shop['shop_code']); ?></span>
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
                echo $typeLabels[$shop['shop_type']] ?? $shop['shop_type'];
                ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Category</span>
            <span class="detail-value"><?php echo ucfirst($shop['shop_category'] ?? 'N/A'); ?></span>
        </div>
        <?php if (!empty($shop['establishment_year'])): ?>
        <div class="detail-row">
            <span class="detail-label">Established</span>
            <span class="detail-value"><?php echo $shop['establishment_year']; ?></span>
        </div>
        <?php endif; ?>
        <div class="detail-row">
            <span class="detail-label">Delivery Available</span>
            <span class="detail-value">
                <?php if ($shop['delivery_available']): ?>
                    <span style="color: #16A34A;"><i class="fas fa-check-circle"></i> Yes</span>
                <?php else: ?>
                    <span style="color: #6B7A7B;"><i class="fas fa-times-circle"></i> No</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if (!empty($shop['working_hours_start']) && !empty($shop['working_hours_end'])): ?>
        <div class="detail-row">
            <span class="detail-label">Working Hours</span>
            <span class="detail-value">
                <?php echo date('h:i A', strtotime($shop['working_hours_start'])); ?> - 
                <?php echo date('h:i A', strtotime($shop['working_hours_end'])); ?>
            </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($shop['weekend_days'])): ?>
        <div class="detail-row">
            <span class="detail-label">Weekend Days</span>
            <span class="detail-value"><?php echo escapeHtml($shop['weekend_days']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Owner Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-user-circle" style="color: #16A34A;"></i>
            Owner Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Owner Name</span>
            <span class="detail-value"><?php echo escapeHtml($shop['owner_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Username</span>
            <span class="detail-value"><?php echo escapeHtml($shop['username'] ?? ''); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo escapeHtml($shop['email'] ?? ''); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?php echo !empty($shop['phone']) ? escapeHtml($shop['phone']) : 'Not provided'; ?></span>
        </div>
        <?php if (!empty($shop['address'])): ?>
        <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value">
                <?php echo escapeHtml($shop['address']); ?>
                <?php if (!empty($shop['city']) || !empty($shop['state'])): ?>
                    <br>
                    <?php 
                    $locationParts = [];
                    if (!empty($shop['city'])) $locationParts[] = $shop['city'];
                    if (!empty($shop['state'])) $locationParts[] = $shop['state'];
                    if (!empty($shop['pincode'])) $locationParts[] = $shop['pincode'];
                    echo escapeHtml(implode(', ', $locationParts));
                    ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Orders -->
    <div class="detail-section">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-shopping-cart" style="color: #7C3AED;"></i> Recent Orders</span>
            <a href="orders.php?shop=<?php echo $shop['id']; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 12px 0;">
                <i class="fas fa-inbox" style="font-size: 20px; display: block; margin-bottom: 4px; opacity: 0.5;"></i>
                No orders yet
            </p>
        <?php else: ?>
            <?php foreach ($recentOrders as $order): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #F7FCF7;">
                <div>
                    <div style="font-weight: 600; color: #052E16;">#<?php echo escapeHtml($order['order_number']); ?></div>
                    <div style="font-size: 11px; color: #6B7A7B;"><?php echo formatDate($order['created_at']); ?></div>
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
    
    <!-- Recent Payments -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-credit-card" style="color: #16A34A;"></i> Recent Payments</span>
            <a href="shop-payments.php?shop=<?php echo $shop['id']; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($recentPayments)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 12px 0;">
                <i class="fas fa-wallet" style="font-size: 20px; display: block; margin-bottom: 4px; opacity: 0.5;"></i>
                No payments yet
            </p>
        <?php else: ?>
            <?php foreach ($recentPayments as $payment): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #F7FCF7;">
                <div>
                    <div style="font-weight: 600; color: #052E16;">
                        <?php if ($payment['order_number']): ?>
                            Order: #<?php echo escapeHtml($payment['order_number']); ?>
                        <?php else: ?>
                            Payment #<?php echo $payment['id']; ?>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 11px; color: #6B7A7B;"><?php echo formatDate($payment['payment_date']); ?></div>
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
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>