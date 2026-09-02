<?php

/**
 * SAMRIDHI AGRO - Agent Dashboard
 * 
 * This is the agent dashboard displaying key metrics,
 * widgets, recent activities, and system statistics.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'Dashboard';

// Include agent header
require_once '../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get agent data
$sql = "SELECT a.*, u.full_name, u.username, u.email, u.phone, u.last_login 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET WIDGET DATA
// ============================================

// 1. Shop Statistics
$sql = "SELECT 
        COUNT(*) as total_shops,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_shops,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_shops,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_shops
        FROM shops 
        WHERE agent_id = ?";
$shopStats = $db->fetchOne($sql, [$agent['id']]);

// 2. Order Statistics
$sql = "SELECT 
        COUNT(*) AS total_orders,
        SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN o.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_orders,
        SUM(CASE WHEN o.status = 'processing' THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN o.status = 'shipped' THEN 1 ELSE 0 END) AS shipped_orders,
        SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
        SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ?";
$orderStats = $db->fetchOne($sql, [$agent['id']]);

// 3. Payment Statistics (New)
// Total Business = All orders except cancelled
$sql = "SELECT COALESCE(SUM(o.total_amount), 0) as total_business
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ? AND o.status != 'cancelled'";
$businessData = $db->fetchOne($sql, [$agent['id']]);
$totalBusiness = $businessData['total_business'] ?? 0;

// Total Paid = Confirmed payments
$sql = "SELECT COALESCE(SUM(p.amount), 0) as total_paid
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.agent_id = ? AND p.status = 'confirmed'";
$paidData = $db->fetchOne($sql, [$agent['id']]);
$totalPaid = $paidData['total_paid'] ?? 0;

// Pending Collection (Agent route - pending status)
$sql = "SELECT COALESCE(SUM(p.amount), 0) as pending_collection
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.agent_id = ? AND p.pay_to = 'agent' AND p.status = 'pending'";
$pendingData = $db->fetchOne($sql, [$agent['id']]);
$pendingCollection = $pendingData['pending_collection'] ?? 0;

// Remaining Amount
$remainingAmount = max(0, $totalBusiness - $totalPaid);



// 5. Today's Attendance
$today = date('Y-m-d');
$sql = "SELECT status, check_in_time, check_out_time 
        FROM attendance 
        WHERE user_id = ? AND date = ?";
$todayAttendance = $db->fetchOne($sql, [$_SESSION['user_id'], $today]);

// 6. Week Attendance Summary
$sql = "SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
        FROM attendance 
        WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$weekAttendance = $db->fetchOne($sql, [$_SESSION['user_id']]);

// 7. Recent Orders
$sql = "SELECT o.*, s.shop_name 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$agent['id']]);

// 8. Recent Shops
$sql = "SELECT id, shop_name, shop_code, city, status, created_at 
        FROM shops 
        WHERE agent_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentShops = $db->fetchAll($sql, [$agent['id']]);

// 9. Monthly Revenue for Chart (Payment based)
$monthlyRevenue = [];
$monthlyOrders = [];
$monthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $monthLabels[] = date('M', strtotime($date));
    
    $start = date('Y-m-01', strtotime($date));
    $end = date('Y-m-t', strtotime($date));
    
    // Revenue from delivered orders
    $sql = "SELECT COALESCE(SUM(o.total_amount), 0) as total 
            FROM orders o 
            JOIN shops s ON o.shop_id = s.id 
            WHERE s.agent_id = ? AND o.status = 'delivered' 
            AND o.order_date BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$agent['id'], $start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyRevenue[] = round($result['total'] ?? 0, 2);
    
    // Orders count
    $sql = "SELECT COUNT(*) as count 
            FROM orders o 
            JOIN shops s ON o.shop_id = s.id 
            WHERE s.agent_id = ? 
            AND o.order_date BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$agent['id'], $start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyOrders[] = (int)($result['count'] ?? 0);
}

// 10. Payment Status Distribution
$sql = "SELECT 
        p.status,
        COUNT(*) as count,
        COALESCE(SUM(p.amount), 0) as total_amount
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.agent_id = ?
        GROUP BY p.status";
$paymentStatusData = $db->fetchAll($sql, [$agent['id']]);

$paymentStatusLabels = [];
$paymentStatusCounts = [];
$paymentStatusAmounts = [];
$paymentStatusColors = [
    'pending' => '#F59E0B',
    'collected' => '#3B82F6',
    'submitted' => '#7C3AED',
    'confirmed' => '#22C55E'
];

foreach ($paymentStatusData as $row) {
    $paymentStatusLabels[] = ucfirst($row['status']);
    $paymentStatusCounts[] = (int)$row['count'];
    $paymentStatusAmounts[] = (float)$row['total_amount'];
}

// 11. Demanding Products (Top products by order quantity)
$sql = "SELECT 
        p.id, p.product_name, p.sku, p.unit,
        COALESCE(SUM(oi.quantity), 0) as total_quantity,
        COALESCE(SUM(oi.total), 0) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        LEFT JOIN shops s ON o.shop_id = s.id
        WHERE s.agent_id = ? OR s.agent_id IS NULL
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT 5";
$topProducts = $db->fetchAll($sql, [$agent['id']]);
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .widgets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .widget-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        border-radius: 12px;
        padding: 16px 18px;
        border: 1px solid #E5EDE7;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        cursor: pointer;
    }

    .widget-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .widget-card .widget-icon {
        font-size: 20px;
        margin-bottom: 6px;
        display: block;
    }

    .widget-card .widget-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
    }

    .widget-card .widget-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }

    .widget-card .widget-change {
        font-size: 11px;
        font-weight: 500;
        margin-top: 4px;
    }

    .widget-card .widget-change.positive { color: #16A34A; }
    .widget-card .widget-change.negative { color: #DC2626; }
    .widget-card .widget-change.neutral { color: #6B7A7B; }

    .widget-card .icon-business { color: #14532D; }
    .widget-card .icon-paid { color: #16A34A; }
    .widget-card .icon-pending-collection { color: #F59E0B; }
    .widget-card .icon-remaining { color: #DC2626; }
    .widget-card .icon-shops { color: #2563EB; }
    .widget-card .icon-orders { color: #7C3AED; }
    .widget-card .icon-attendance { color: #0891B2; }

    .widget-card .widget-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        font-size: 10px;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: 600;
    }

    .widget-card .widget-badge.badge-success { background: #DCFCE7; color: #065F46; }
    .widget-card .widget-badge.badge-warning { background: #FEF3C7; color: #92400E; }
    .widget-card .widget-badge.badge-danger { background: #FEE2E2; color: #991B1B; }

    .widget-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .chart-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        border-radius: 12px;
        padding: 20px 24px;
        border: 1px solid #E5EDE7;
    }

    .chart-card .chart-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
    }

    .chart-card .chart-wrapper {
        position: relative;
        height: 250px;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .content-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .content-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        border-radius: 12px;
        padding: 20px 24px;
        border: 1px solid #E5EDE7;
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

    .list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .list-item:last-child {
        border-bottom: none;
    }

    .list-item .item-info .item-name {
        font-weight: 500;
        color: #052E16;
    }

    .list-item .item-info .item-meta {
        font-size: 12px;
        color: #6B7A7B;
    }

    .attendance-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .attendance-status.checked-in { background: #DCFCE7; color: #065F46; }
    .attendance-status.checked-out { background: #F3F4F6; color: #6B7A7B; }
    .attendance-status.absent { background: #FEE2E2; color: #991B1B; }

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

    .product-item .product-rank {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        background: #F3F4F6;
        color: #6B7A7B;
        flex-shrink: 0;
    }

    .product-item .product-rank.rank-1 { background: #EAB308; color: white; }
    .product-item .product-rank.rank-2 { background: #94A3B8; color: white; }
    .product-item .product-rank.rank-3 { background: #CD7F32; color: white; }

    .product-item .product-info {
        flex: 1;
    }

    .product-item .product-info .product-name {
        font-weight: 500;
        color: #052E16;
        font-size: 14px;
    }

    .product-item .product-info .product-meta {
        font-size: 12px;
        color: #6B7A7B;
    }

    .product-item .product-stats {
        text-align: right;
    }

    .product-item .product-stats .qty {
        font-weight: 600;
        color: #14532D;
        font-size: 14px;
    }

    .product-item .product-stats .revenue {
        font-size: 12px;
        color: #6B7A7B;
    }

    @media (max-width: 1024px) {
        .charts-grid { grid-template-columns: 1fr; }
        .content-grid { grid-template-columns: 1fr; }
        .content-grid-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .widgets-grid { grid-template-columns: repeat(2, 1fr); }
    }

  
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">

    <!-- Welcome Section -->
    <div style="background: linear-gradient(135deg, #14532D 0%, #16A34A 100%); border-radius: 12px; padding: 24px 28px; margin-bottom: 24px; color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 21px; margin: 0;">
                    Welcome back, <?php echo escapeHtml($agent['full_name']); ?>! 👋
                </h2>
                <p style="opacity: 0.8; margin: 4px 0 0 0; font-size: 12px;">
                    Agent Code: <strong><?php echo escapeHtml($agent['agent_code']); ?></strong>
                   
                    | Last Login: <?php echo $agent['last_login'] ? timeAgo($agent['last_login']) : 'First login'; ?>
                </p>
            </div>

            <!-- Attendance Status -->
            <div>
                <?php if ($todayAttendance): ?>
                    <?php if ($todayAttendance['check_out_time']): ?>
                        <span class="attendance-status checked-out">
                            <i class="fas fa-check-circle"></i> Checked Out
                        </span>
                    <?php else: ?>
                        <span class="attendance-status checked-in">
                            <i class="fas fa-clock"></i> Checked In
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="attendance-status absent">
                        <i class="fas fa-times-circle"></i> Not Checked In
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widgets -->
    <div class="widgets-grid">
        <!-- Total Business -->
        <a href="shop-payments.php" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-business"><i class="fas fa-chart-line"></i></span>
                <div class="widget-number">₹ <?php echo number_format($totalBusiness, 0); ?></div>
                <div class="widget-label">Total Business</div>
                <div class="widget-change neutral">
                    <i class="fas fa-shopping-cart"></i> All orders
                </div>
            </div>
        </a>

        <!-- Total Paid -->
        <a href="shop-payments.php?status=confirmed" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-paid"><i class="fas fa-check-circle"></i></span>
                <div class="widget-number">₹ <?php echo number_format($totalPaid, 0); ?></div>
                <div class="widget-label">Total Paid</div>
                <div class="widget-change positive">
                    <i class="fas fa-check"></i> Confirmed payments
                </div>
            </div>
        </a>

        <!-- Pending Collection -->
        <a href="shop-payments.php?status=pending" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-pending-collection"><i class="fas fa-clock"></i></span>
                <div class="widget-number">₹ <?php echo number_format($pendingCollection, 0); ?></div>
                <div class="widget-label">Pending Collection</div>
                <div class="widget-change negative">
                    <i class="fas fa-hourglass-half"></i> Awaiting collection
                </div>
            </div>
        </a>

        <!-- Remaining -->
        <a href="shop-payments.php" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-remaining"><i class="fas fa-rupee-sign"></i></span>
                <div class="widget-number">₹ <?php echo number_format($remainingAmount, 0); ?></div>
                <div class="widget-label">Remaining</div>
                <div class="widget-change <?php echo $remainingAmount <= 0 ? 'positive' : 'negative'; ?>">
                    <?php if ($remainingAmount <= 0): ?>
                        <i class="fas fa-check-circle"></i> Fully Paid
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i> Pending payment
                    <?php endif; ?>
                </div>
            </div>
        </a>

       

        <!-- Total Shops -->
        <a href="shops.php" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-shops"><i class="fas fa-store"></i></span>
                <div class="widget-number"><?php echo number_format($shopStats['total_shops'] ?? 0); ?></div>
                <div class="widget-label">Total Shops</div>
                <?php if (($shopStats['pending_shops'] ?? 0) > 0): ?>
                    <div class="widget-change negative">
                        <i class="fas fa-clock"></i> <?php echo $shopStats['pending_shops']; ?> pending
                    </div>
                <?php else: ?>
                    <div class="widget-change neutral">
                        <i class="fas fa-check"></i> All approved
                    </div>
                <?php endif; ?>
            </div>
        </a>

        <!-- Total Orders -->
        <a href="orders.php" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-orders"><i class="fas fa-shopping-cart"></i></span>
                <div class="widget-number"><?php echo number_format($orderStats['total_orders'] ?? 0); ?></div>
                <div class="widget-label">Total Orders</div>
                <?php if (($orderStats['pending_orders'] ?? 0) > 0): ?>
                    <div class="widget-change negative">
                        <i class="fas fa-clock"></i> <?php echo $orderStats['pending_orders']; ?> pending
                    </div>
                <?php else: ?>
                    <div class="widget-change neutral">
                        <i class="fas fa-check"></i> All processed
                    </div>
                <?php endif; ?>
            </div>
        </a>

        <!-- Attendance -->
        <a href="attendance.php" class="widget-link">
            <div class="widget-card">
                <span class="widget-icon icon-attendance"><i class="fas fa-calendar-check"></i></span>
                <div class="widget-number">
                    <?php
                    $presentDays = $weekAttendance['present_days'] ?? 0;
                    $totalDays = $weekAttendance['total_days'] ?? 0;
                    echo $presentDays . '/' . $totalDays;
                    ?>
                </div>
                <div class="widget-label">Attendance (This Week)</div>
                <?php
                $attPercent = $totalDays > 0 ? round($presentDays / $totalDays * 100) : 0;
                ?>
                <div class="widget-change <?php echo $attPercent >= 80 ? 'positive' : ($attPercent >= 50 ? 'neutral' : 'negative'); ?>">
                    <i class="fas fa-<?php echo $attPercent >= 80 ? 'check' : ($attPercent >= 50 ? 'minus' : 'times'); ?>"></i>
                    <?php echo $attPercent; ?>% attendance
                </div>
            </div>
        </a>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-line" style="color: #16A34A;"></i>
                Revenue & Orders Trend (Last 6 Months)
            </div>
            <div class="chart-wrapper">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-pie" style="color: #7C3AED;"></i>
                Payment Status Distribution
            </div>
            <div class="chart-wrapper">
                <canvas id="paymentChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Demanding Products -->
    <div class="content-grid-2">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-trophy" style="color: #EAB308;"></i>
                    Demanding Products
                </h3>
                <a href="products.php" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($topProducts) || array_sum(array_column($topProducts, 'total_quantity')) == 0): ?>
                <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                    <i class="fas fa-box-open" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    No products sold yet
                </p>
            <?php else: ?>
                <?php 
                $rank = 1;
                foreach ($topProducts as $product): 
                    if ($product['total_quantity'] == 0) continue;
                ?>
                    <div class="product-item">
                        <div class="product-rank <?php echo $rank <= 3 ? 'rank-' . $rank : ''; ?>">
                            <?php echo $rank; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo escapeHtml($product['product_name']); ?></div>
                            <div class="product-meta">
                                SKU: <?php echo escapeHtml($product['sku']); ?>
                                | Unit: <?php echo escapeHtml($product['unit']); ?>
                            </div>
                        </div>
                        <div class="product-stats">
                            <div class="qty"><?php echo number_format($product['total_quantity']); ?> units</div>
                            <div class="revenue">₹ <?php echo number_format($product['total_revenue'], 0); ?></div>
                        </div>
                    </div>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
        <div class="content-card">
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
                    <div class="list-item">
                        <div class="item-info">
                            <div class="item-name">#<?php echo escapeHtml($order['order_number']); ?></div>
                            <div class="item-meta"><?php echo escapeHtml($order['shop_name']); ?></div>
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
    </div>

    <!-- Recent Shops -->
    <div class="content-grid" style="margin-bottom: 0;">
        <div class="content-card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h3 class="card-title">Recent Shops</h3>
                <a href="shops.php" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($recentShops)): ?>
                <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                    <i class="fas fa-store" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    No shops assigned yet
                </p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;">
                    <?php foreach ($recentShops as $shop): ?>
                        <div style="background: #F7FCF7; border-radius: 10px; padding: 12px 16px; border: 1px solid #E5EDE7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($shop['shop_name']); ?></div>
                                    <div style="font-size: 12px; color: #6B7A7B;">
                                        Code: <?php echo escapeHtml($shop['shop_code']); ?>
                                        <?php if ($shop['city']): ?>
                                            • <?php echo escapeHtml($shop['city']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'badge-warning',
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'suspended' => 'badge-secondary'
                                    ];
                                    $color = $statusColors[$shop['status']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge-status <?php echo $color; ?>">
                                        <?php echo ucfirst($shop['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="font-size: 11px; color: #6B7A7B; margin-top: 4px;">
                                <i class="far fa-calendar"></i> <?php echo formatDate($shop['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================
        // REVENUE & ORDERS CHART
        // ============================================
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthLabels); ?>,
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: <?php echo json_encode($monthlyRevenue); ?>,
                        borderColor: '#16A34A',
                        backgroundColor: 'rgba(22, 163, 74, 0.2)',
                        borderWidth: 2,
                        borderRadius: 4,
                        type: 'line',
                        tension: 0.4,
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode($monthlyOrders); ?>,
                        backgroundColor: 'rgba(124, 58, 237, 0.2)',
                        borderColor: '#7C3AED',
                        borderWidth: 2,
                        borderRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter', size: 12 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            stepSize: 1
                        },
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 } }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // ============================================
        // PAYMENT STATUS CHART
        // ============================================
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');

        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($paymentStatusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($paymentStatusCounts); ?>,
                    backgroundColor: <?php 
                        $colors = [];
                        foreach ($paymentStatusLabels as $label) {
                            $key = strtolower($label);
                            $colors[] = $paymentStatusColors[$key] ?? '#6B7280';
                        }
                        echo json_encode($colors);
                    ?>,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 11 },
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                let amount = <?php echo json_encode($paymentStatusAmounts); ?>[context.dataIndex] || 0;
                                return label + ': ' + value + ' payments (₹ ' + amount.toLocaleString() + ', ' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>