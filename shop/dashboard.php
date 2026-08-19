<?php

/**
 * SAMRIDHI AGRO - Shop Dashboard
 * 
 * This is the shop dashboard displaying key metrics,
 * recent orders, and shop information.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Dashboard';

// Include shop header
require_once '../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get shop data
$sql = "SELECT s.*, u.full_name, u.username, u.email, u.phone, u.last_login,
        a.full_name as agent_name
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN agents ag ON s.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        WHERE s.user_id = ?";
$shop = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Get shop statistics
// Total orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE shop_id = ?";
$result = $db->fetchOne($sql, [$shop['id']]);
$totalOrders = $result['count'] ?? 0;

// Pending orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE shop_id = ? AND status = 'pending'";
$result = $db->fetchOne($sql, [$shop['id']]);
$pendingOrders = $result['count'] ?? 0;

// Total revenue (delivered orders)
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM orders 
        WHERE shop_id = ? AND status = 'delivered'";
$result = $db->fetchOne($sql, [$shop['id']]);
$totalRevenue = $result['total'] ?? 0;

// Recent orders
$sql = "SELECT * FROM orders 
        WHERE shop_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$shop['id']]);

// Recent activity
$sql = "SELECT al.*, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.module = 'order' AND al.description LIKE ?
        ORDER BY al.created_at DESC 
        LIMIT 5";
$recentActivities = $db->fetchAll($sql, ['%#' . $shop['shop_code'] . '%']);
?>

<style>
    /* Dashboard specific styles - using utility classes from style.css */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 18px 20px;
        transition: all 0.3s ease;
        /* box-shadow: 0 2px 8px rgba(5, 46, 22, 0.06); */
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.12);
    }

    .stat-card .stat-icon {
        font-size: 24px;
        margin-bottom: 8px;
        display: block;
    }

    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #052E16;
        line-height: 1.2;
    }

    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
    }

    .stat-card .stat-icon.orders {
        color: #7C3AED;
    }

    .stat-card .stat-icon.revenue {
        color: #16A34A;
    }

    .stat-card .stat-icon.pending {
        color: #F59E0B;
    }

    .stat-card .stat-icon.shop {
        color: #2563EB;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .content-card {
        background: white;
        border-radius: 12px;
        padding: 20px 24px;
        border: 1px solid #E5EDE7;
        /* box-shadow: 0 2px 8px rgba(5, 46, 22, 0.06); */
        transition: box-shadow 0.3s ease;
    }

    .content-card:hover {
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.10);
    }

    .content-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .content-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin: 0;
    }

    .content-card .card-action {
        font-size: 13px;
        color: #16A34A;
        text-decoration: none;
        font-weight: 500;
    }

    .content-card .card-action:hover {
        color: #14532D;
    }

    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-status.badge-success {
        background: #DCFCE7;
        color: #065F46;
    }

    .badge-status.badge-warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .badge-status.badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .badge-status.badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-status.badge-primary {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-item .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #F0FDF4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #16A34A;
        flex-shrink: 0;
    }

    .activity-item .activity-content {
        flex: 1;
    }

    .activity-item .activity-content .activity-text {
        font-size: 14px;
        color: #052E16;
    }

    .activity-item .activity-content .activity-text strong {
        font-weight: 600;
    }

    .activity-item .activity-content .activity-time {
        font-size: 12px;
        color: #6B7A7B;
    }

    /* ===== MOBILE RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .content-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .stat-card .stat-number {
            font-size: 24px;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .stat-card {
            padding: 14px 16px;
        }

        .stat-card .stat-number {
            font-size: 22px;
        }

        .stat-card .stat-label {
            font-size: 12px;
        }

        .content-card {
            padding: 16px 18px;
        }

        /* Welcome section mobile */
        .welcome-wrap {
            flex-direction: column;
            align-items: center !important;
            text-align: center;
            gap: 8px;
        }

        .welcome-wrap h2 {
            font-size: 18px;
        }

        .welcome-wrap p {
            font-size: 13px;
        }

        .order-item-wrap {
            flex-wrap: wrap;
            gap: 4px;
        }

        .order-item-wrap .order-details {
            width: 100%;
        }

        .order-item-wrap .order-right {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            gap: 10px;
        }

        .stat-card {
            padding: 12px 14px;
            text-align: center;
        }

        .stat-card .stat-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .stat-card .stat-number {
            font-size: 20px;
        }

        .stat-card .stat-label {
            font-size: 11px;
        }

        .content-card {
            padding: 14px 16px;
        }

        .content-card .card-title {
            font-size: 14px;
        }

        .welcome-wrap h2 {
            font-size: 16px;
        }

        .welcome-wrap p {
            font-size: 12px;
        }

        .badge-status {
            font-size: 11px;
            padding: 3px 10px;
        }

        .activity-item .activity-icon {
            width: 28px;
            height: 28px;
            font-size: 12px;
        }

        .activity-item .activity-content .activity-text {
            font-size: 13px;
        }

        .activity-item .activity-content .activity-time {
            font-size: 11px;
        }

        .order-item-wrap .order-number {
            font-size: 13px;
        }

        .order-item-wrap .order-amount {
            font-size: 14px;
        }
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">

    <!-- Welcome Section -->
    <div style="background: linear-gradient(135deg, #14532D 0%, #16A34A 100%); border-radius: 12px; padding: 24px 28px; margin-bottom: 24px; color: white;">
        <div class="welcome-wrap" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; margin: 0;">
                    Welcome back, <?php echo escapeHtml($shop['shop_name']); ?>! 🏪
                </h2>
                <p style="opacity: 0.8; margin: 4px 0 0 0; font-size: 14px;">
                    Shop Code: <strong><?php echo escapeHtml($shop['shop_code']); ?></strong>
                    | Owner: <?php echo escapeHtml($shop['full_name']); ?>
                    <?php if ($shop['agent_name']): ?>
                        | Agent: <?php echo escapeHtml($shop['agent_name']); ?>
                    <?php endif; ?>
                    | Last Login: <?php echo $shop['last_login'] ? timeAgo($shop['last_login']) : 'First login'; ?>
                </p>
            </div>
            <?php if ($shop['status'] === 'approved'): ?>
                <span style="background: #DCFCE7; color: #065F46; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> Active
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card sdbg">
            <span class="stat-icon orders"><i class="fas fa-shopping-cart"></i></span>
            <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card sdbg">
            <span class="stat-icon revenue"><i class="fas fa-rupee-sign"></i></span>
            <div class="stat-number">₹ <?php echo number_format($totalRevenue, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card sdbg">
            <span class="stat-icon pending"><i class="fas fa-clock"></i></span>
            <div class="stat-number"><?php echo number_format($pendingOrders); ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card sdbg">
            <span class="stat-icon shop"><i class="fas fa-store"></i></span>
            <div class="stat-number"><?php echo escapeHtml($shop['shop_type'] ?? 'N/A'); ?></div>
            <div class="stat-label">Shop Type</div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Recent Orders -->
        <div class="content-card sdbg">
            <div class="card-header">
                <h3 class="card-title">Recent Orders</h3>
                <a href="orders.php" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                    <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    No orders yet
                </p>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-item-wrap" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #F7FCF7;">
                        <div>
                            <div style="font-weight: 600; color: #052E16;">#<?php echo escapeHtml($order['order_number']); ?></div>
                            <div style="font-size: 12px; color: #6B7A7B;"><?php echo formatDate($order['created_at']); ?></div>
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
                            <span class="badge-status <?php echo $color; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Activities -->
        <div class="content-card sdbg">
            <div class="card-header">
                <h3 class="card-title">Recent Activities</h3>
            </div>
            <?php if (empty($recentActivities)): ?>
                <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                    <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    No activities yet
                </p>
            <?php else: ?>
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php
                                                echo match ($activity['action']) {
                                                    'create' => 'plus',
                                                    'update' => 'edit',
                                                    'delete' => 'trash',
                                                    default => 'circle'
                                                };
                                                ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <?php if ($activity['full_name']): ?>
                                    <strong><?php echo escapeHtml($activity['full_name']); ?></strong>
                                <?php endif; ?>
                                <?php echo escapeHtml($activity['description'] ?? $activity['action']); ?>
                            </div>
                            <div class="activity-time">
                                <i class="far fa-clock"></i> <?php echo timeAgo($activity['created_at']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/shop_footer.php'; ?>