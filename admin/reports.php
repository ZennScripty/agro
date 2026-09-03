<?php
/**
 * SAMRIDHI AGRO - Reports Dashboard
 * 
 * This page displays sales reports, revenue analytics,
 * payment statistics, and other business insights with interactive charts.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// ============================================
// STEP 1: Set page title and include admin header
// ============================================

// Set page title
$pageTitle = 'Reports Dashboard';

// Include admin header (which already includes all configs)
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('report.view', 'reports.php');

// Get database instance
$db = getDB();

// ============================================
// GET FILTER PARAMETERS (Simplified)
// ============================================

$year = (int)($_GET['year'] ?? date('Y'));

// ============================================
// SUMMARY STATISTICS - ORDERS
// ============================================

// Total Orders (All)
$sql = "SELECT COUNT(*) as count FROM orders";
$result = $db->fetchOne($sql);
$totalOrders = $result['count'] ?? 0;

// Total Revenue (All orders - total amount)
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'";
$result = $db->fetchOne($sql);
$totalRevenue = $result['total'] ?? 0;

// Delivered Revenue (for comparison)
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'delivered'";
$result = $db->fetchOne($sql);
$deliveredRevenue = $result['total'] ?? 0;

// Pending Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingOrders = $result['count'] ?? 0;

// Delivered Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'";
$result = $db->fetchOne($sql);
$deliveredOrders = $result['count'] ?? 0;

// Average Order Value
$avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

// ============================================
// PAYMENT STATISTICS
// ============================================

// Total Payments (All)
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments";
$result = $db->fetchOne($sql);
$totalPayments = $result['count'] ?? 0;
$totalPaymentsAmount = $result['total'] ?? 0;

// Pending Collection (pay_to = 'agent' AND status = 'pending')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE pay_to = 'agent' AND status = 'pending'";
$result = $db->fetchOne($sql);
$pendingCollection = $result['count'] ?? 0;
$pendingCollectionAmount = $result['total'] ?? 0;

// Collected by Agents (pay_to = 'agent' AND status = 'collected')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE pay_to = 'agent' AND status = 'collected'";
$result = $db->fetchOne($sql);
$collectedByAgents = $result['count'] ?? 0;
$collectedByAgentsAmount = $result['total'] ?? 0;

// Submitted to Admin (pay_to = 'agent' AND status = 'submitted')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE pay_to = 'agent' AND status = 'submitted'";
$result = $db->fetchOne($sql);
$submittedToAdmin = $result['count'] ?? 0;
$submittedToAdminAmount = $result['total'] ?? 0;

// Confirmed/Paid (status = 'confirmed')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'confirmed'";
$result = $db->fetchOne($sql);
$confirmedPayments = $result['count'] ?? 0;
$confirmedPaymentsAmount = $result['total'] ?? 0;

// Failed Payments (status = 'failed')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'failed'";
$result = $db->fetchOne($sql);
$failedPayments = $result['count'] ?? 0;
$failedPaymentsAmount = $result['total'] ?? 0;

// Direct Payments (pay_to = 'admin')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE pay_to = 'admin'";
$result = $db->fetchOne($sql);
$directPayments = $result['count'] ?? 0;
$directPaymentsAmount = $result['total'] ?? 0;

// Agent Payments (pay_to = 'agent')
$sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE pay_to = 'agent'";
$result = $db->fetchOne($sql);
$agentPayments = $result['count'] ?? 0;
$agentPaymentsAmount = $result['total'] ?? 0;

// ============================================
// SHOP & PRODUCT STATISTICS
// ============================================

// Total Shops
$sql = "SELECT COUNT(*) as count FROM shops WHERE status = 'approved'";
$result = $db->fetchOne($sql);
$totalShops = $result['count'] ?? 0;

// Total Products
$sql = "SELECT COUNT(*) as count FROM products WHERE status = 'active'";
$result = $db->fetchOne($sql);
$totalProducts = $result['count'] ?? 0;

// Total Agents
$sql = "SELECT COUNT(*) as count FROM agents WHERE status = 'approved'";
$result = $db->fetchOne($sql);
$totalAgents = $result['count'] ?? 0;

// ============================================
// MONTHLY REVENUE DATA (for chart)
// ============================================

$monthlyRevenue = [];
$monthlyOrders = [];
$monthLabels = [];

for ($i = 11; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $monthLabels[] = date('M Y', strtotime($date));
    
    $start = date('Y-m-01', strtotime($date));
    $end = date('Y-m-t', strtotime($date));
    
    // Revenue (All non-cancelled orders)
    $sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
            FROM orders 
            WHERE status != 'cancelled'
            AND order_date BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyRevenue[] = round($result['total'] ?? 0, 2);
    
    // Orders count
    $sql = "SELECT COUNT(*) as count 
            FROM orders 
            WHERE order_date BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyOrders[] = (int)($result['count'] ?? 0);
}

// ============================================
// ORDER STATUS DISTRIBUTION (for chart)
// ============================================

$sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$orderStatusData = $db->fetchAll($sql);

$statusLabels = [];
$statusCounts = [];
$statusColors = [
    'pending' => '#F59E0B',
    'confirmed' => '#3B82F6',
    'processing' => '#8B5CF6',
    'shipped' => '#06B6D4',
    'delivered' => '#22C55E',
    'cancelled' => '#EF4444',
    'returned' => '#F59E0B'
];

foreach ($orderStatusData as $row) {
    $statusLabels[] = ucfirst($row['status']);
    $statusCounts[] = (int)$row['count'];
}

// ============================================
// TOP SELLING PRODUCTS
// ============================================

$sql = "SELECT p.id, p.product_name, p.sku, p.price,
        COALESCE(SUM(oi.quantity), 0) as total_sold,
        COALESCE(SUM(oi.total), 0) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 10";
$topProducts = $db->fetchAll($sql);

// ============================================
// TOP SHOPS BY REVENUE
// ============================================

$sql = "SELECT s.id, s.shop_name, s.shop_code,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_revenue
        FROM shops s
        LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
        GROUP BY s.id
        ORDER BY total_revenue DESC
        LIMIT 10";
$topShops = $db->fetchAll($sql);

// ============================================
// CSS STYLES
// ============================================
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 18px 20px;
        border: 1px solid #E5EDE7;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
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
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
    }
    
    .stat-card .stat-sub {
        font-size: 11px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .stat-card.revenue .stat-icon { color: #16A34A; }
    .stat-card.orders .stat-icon { color: #7C3AED; }
    .stat-card.shops .stat-icon { color: #2563EB; }
    .stat-card.products .stat-icon { color: #D97706; }
    .stat-card.avg-order .stat-icon { color: #0891B2; }
    .stat-card.payments .stat-icon { color: #14532D; }
    .stat-card.pending-collection .stat-icon { color: #F59E0B; }
    .stat-card.collected .stat-icon { color: #3B82F6; }
    .stat-card.submitted .stat-icon { color: #7C3AED; }
    .stat-card.confirmed .stat-icon { color: #16A34A; }
    .stat-card.agents .stat-icon { color: #7C3AED; }
    
    .chart-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .chart-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .chart-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
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
        height: 280px;
    }
    
    .rank-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .rank-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        background: #F7FCF7;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .rank-item:hover {
        background: #F0FDF4;
    }
    
    .rank-item .rank-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #E5EDE7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #4A5B5D;
        flex-shrink: 0;
    }
    
    .rank-item .rank-number.top-1 { background: #EAB308; color: white; }
    .rank-item .rank-number.top-2 { background: #94A3B8; color: white; }
    .rank-item .rank-number.top-3 { background: #CD7F32; color: white; }
    
    .rank-item .rank-info {
        flex: 1;
    }
    
    .rank-item .rank-info .rank-name {
        font-weight: 500;
        color: #052E16;
    }
    
    .rank-item .rank-info .rank-meta {
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .rank-item .rank-value {
        font-weight: 600;
        color: #14532D;
        text-align: right;
    }
    
    .rank-item .rank-value .sub {
        font-weight: 400;
        font-size: 12px;
        color: #6B7A7B;
    }
    


    .payment-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .payment-stat-card {
        background: white;
        border-radius: 10px;
        padding: 14px 16px;
        border: 1px solid #E5EDE7;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .payment-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .payment-stat-card .number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
    }
    
    .payment-stat-card .label {
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .payment-stat-card .amount {
        font-size: 13px;
        font-weight: 600;
        margin-top: 2px;
    }
    
    .payment-stat-card.pending .number { color: #F59E0B; }
    .payment-stat-card.collected .number { color: #3B82F6; }
    .payment-stat-card.submitted .number { color: #7C3AED; }
    .payment-stat-card.confirmed .number { color: #16A34A; }
    .payment-stat-card.direct .number { color: #DC2626; }
    .payment-stat-card.total .number { color: #14532D; }
    
    .section-divider {
        border: none;
        border-top: 2px dashed #E5EDE7;
        margin: 24px 0;
    }
    
    @media (max-width: 1024px) {
        .chart-grid {
            grid-template-columns: 1fr;
        }
        .chart-grid-2 {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .payment-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
      
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .payment-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- ============================================
HTML CONTENT
============================================ -->

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; color: #052E16; margin: 0;">
            <i class="fas fa-chart-bar" style="color: #16A34A;"></i>
            Reports Dashboard
        </h2>
    </div>
    
  
    
    <!-- ============================================
    ORDER STATISTICS
    ============================================ -->
    <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 18px; color: #052E16; margin-bottom: 16px;">
        <i class="fas fa-shopping-cart" style="color: #16A34A;"></i>
        Order Statistics
    </h3>
    
    <div class="stats-grid">
        <div class="stat-card revenue">
            <span class="stat-icon"><i class="fas fa-rupee-sign"></i></span>
            <div class="stat-number">₹ <?php echo number_format($totalRevenue, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-sub">All orders (non-cancelled)</div>
        </div>
        
        <div class="stat-card orders">
            <span class="stat-icon"><i class="fas fa-shopping-cart"></i></span>
            <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-sub"><?php echo number_format($deliveredOrders); ?> delivered</div>
        </div>
        
        <div class="stat-card shops">
            <span class="stat-icon"><i class="fas fa-store"></i></span>
            <div class="stat-number"><?php echo number_format($totalShops); ?></div>
            <div class="stat-label">Active Shops</div>
        </div>
        
        <div class="stat-card agents">
            <span class="stat-icon"><i class="fas fa-user-tie"></i></span>
            <div class="stat-number"><?php echo number_format($totalAgents); ?></div>
            <div class="stat-label">Active Agents</div>
        </div>
        
        <div class="stat-card products">
            <span class="stat-icon"><i class="fas fa-box"></i></span>
            <div class="stat-number"><?php echo number_format($totalProducts); ?></div>
            <div class="stat-label">Active Products</div>
        </div>
        
        <div class="stat-card avg-order">
            <span class="stat-icon"><i class="fas fa-calculator"></i></span>
            <div class="stat-number">₹ <?php echo number_format($avgOrderValue, 2); ?></div>
            <div class="stat-label">Avg Order Value</div>
        </div>
    </div>
    
    <!-- ============================================
    PAYMENT STATISTICS
    ============================================ -->
    <hr class="section-divider">
    
    <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 18px; color: #052E16; margin-bottom: 16px;">
        <i class="fas fa-credit-card" style="color: #16A34A;"></i>
        Payment Statistics
    </h3>
    
    <div class="payment-stats-grid">
        <!-- Total Payments -->
        <div class="payment-stat-card total">
            <div class="number"><?php echo number_format($totalPayments); ?></div>
            <div class="label">Total Payments</div>
            <div class="amount">₹ <?php echo number_format($totalPaymentsAmount, 0); ?></div>
        </div>
        
        <!-- Pending Collection -->
        <div class="payment-stat-card pending">
            <div class="number"><?php echo number_format($pendingCollection); ?></div>
            <div class="label">Pending Collection</div>
            <div class="amount">₹ <?php echo number_format($pendingCollectionAmount, 0); ?></div>
        </div>
        
        <!-- Collected by Agents -->
        <div class="payment-stat-card collected">
            <div class="number"><?php echo number_format($collectedByAgents); ?></div>
            <div class="label">Collected by Agents</div>
            <div class="amount">₹ <?php echo number_format($collectedByAgentsAmount, 0); ?></div>
        </div>
        
        <!-- Submitted to Admin -->
        <div class="payment-stat-card submitted">
            <div class="number"><?php echo number_format($submittedToAdmin); ?></div>
            <div class="label">Submitted to Admin</div>
            <div class="amount">₹ <?php echo number_format($submittedToAdminAmount, 0); ?></div>
        </div>
        
        <!-- Confirmed/Paid -->
        <div class="payment-stat-card confirmed">
            <div class="number"><?php echo number_format($confirmedPayments); ?></div>
            <div class="label">Confirmed / Paid</div>
            <div class="amount">₹ <?php echo number_format($confirmedPaymentsAmount, 0); ?></div>
        </div>
        
        <!-- Direct Payments -->
        <div class="payment-stat-card direct">
            <div class="number"><?php echo number_format($directPayments); ?></div>
            <div class="label">Direct to Admin</div>
            <div class="amount">₹ <?php echo number_format($directPaymentsAmount, 0); ?></div>
        </div>
    </div>
    
    <!-- Payment Flow Summary -->
    <div style="background: #F7FCF7; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid #E5EDE7;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; text-align: center;">
            <div>
                <div style="font-size: 12px; color: #6B7A7B;">Agent Payments</div>
                <div style="font-weight: 700; color: #7C3AED;">₹ <?php echo number_format($agentPaymentsAmount, 0); ?></div>
                <div style="font-size: 11px; color: #6B7A7B;"><?php echo number_format($agentPayments); ?> payments</div>
            </div>
            <div style="border-left: 1px solid #E5EDE7; padding-left: 12px;">
                <div style="font-size: 12px; color: #6B7A7B;">Direct Payments</div>
                <div style="font-weight: 700; color: #DC2626;">₹ <?php echo number_format($directPaymentsAmount, 0); ?></div>
                <div style="font-size: 11px; color: #6B7A7B;"><?php echo number_format($directPayments); ?> payments</div>
            </div>
            <div style="border-left: 1px solid #E5EDE7; padding-left: 12px;">
                <div style="font-size: 12px; color: #6B7A7B;">Failed Payments</div>
                <div style="font-weight: 700; color: #DC2626;">₹ <?php echo number_format($failedPaymentsAmount, 0); ?></div>
                <div style="font-size: 11px; color: #6B7A7B;"><?php echo number_format($failedPayments); ?> payments</div>
            </div>
        </div>
    </div>
    
    <!-- ============================================
    CHARTS
    ============================================ -->
    <hr class="section-divider">
    
    <div class="chart-grid">
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-line" style="color: #16A34A;"></i>
                Revenue & Orders Trend
            </div>
            <div class="chart-wrapper">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-pie" style="color: #16A34A;"></i>
                Order Status Distribution
            </div>
            <div class="chart-wrapper">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- ============================================
    RANKINGS
    ============================================ -->
    <div class="chart-grid-2">
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-trophy" style="color: #EAB308;"></i>
                Top Selling Products
            </div>
            <div class="rank-list">
                <?php if (empty($topProducts)): ?>
                    <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">No products sold yet.</p>
                <?php else: ?>
                    <?php foreach ($topProducts as $index => $product): ?>
                        <?php if ($product['total_sold'] > 0): ?>
                        <div class="rank-item">
                            <div class="rank-number <?php echo $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')); ?>">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="rank-info">
                                <div class="rank-name"><?php echo escapeHtml($product['product_name']); ?></div>
                                <div class="rank-meta">SKU: <?php echo escapeHtml($product['sku']); ?></div>
                            </div>
                            <div class="rank-value">
                                <?php echo number_format($product['total_sold']); ?>
                                <span class="sub">sold</span>
                                <br>
                                ₹ <?php echo number_format($product['total_revenue'], 0); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-store" style="color: #2563EB;"></i>
                Top Shops by Revenue
            </div>
            <div class="rank-list">
                <?php if (empty($topShops)): ?>
                    <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">No shops have orders yet.</p>
                <?php else: ?>
                    <?php foreach ($topShops as $index => $shop): ?>
                        <?php if ($shop['total_revenue'] > 0): ?>
                        <div class="rank-item">
                            <div class="rank-number <?php echo $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')); ?>">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="rank-info">
                                <div class="rank-name"><?php echo escapeHtml($shop['shop_name']); ?></div>
                                <div class="rank-meta"><?php echo $shop['order_count']; ?> orders</div>
                            </div>
                            <div class="rank-value">
                                ₹ <?php echo number_format($shop['total_revenue'], 0); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Category Revenue -->
    <div class="chart-card" style="margin-top: 20px;">
        <div class="chart-title">
            <i class="fas fa-tags" style="color: #16A34A;"></i>
            Revenue by Category
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <?php if (empty($categoryRevenue)): ?>
                <p style="color: #6B7A7B; text-align: center; padding: 20px 0; grid-column: 1 / -1;">No category revenue data available.</p>
            <?php else: ?>
                <?php 
                $maxRevenue = !empty($categoryRevenue) ? max(array_column($categoryRevenue, 'total_revenue')) : 1;
                foreach ($categoryRevenue as $cat):
                    if ($cat['total_revenue'] > 0):
                        $percentage = ($cat['total_revenue'] / $maxRevenue) * 100;
                ?>
                <div style="background: #F7FCF7; border-radius: 8px; padding: 12px 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-weight: 500; color: #052E16;"><?php echo escapeHtml($cat['category_name']); ?></span>
                        <span style="font-weight: 600; color: #14532D;">₹ <?php echo number_format($cat['total_revenue'], 0); ?></span>
                    </div>
                    <div style="width: 100%; height: 6px; background: #E5EDE7; border-radius: 4px; overflow: hidden;">
                        <div style="width: <?php echo $percentage; ?>%; height: 100%; background: linear-gradient(90deg, #16A34A, #22C55E); border-radius: 4px; transition: width 0.5s ease;"></div>
                    </div>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // REVENUE CHART
    // ============================================
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    
    const revenueData = {
        labels: <?php echo json_encode($monthLabels); ?>,
        datasets: [
            {
                label: 'Revenue (₹)',
                data: <?php echo json_encode($monthlyRevenue); ?>,
                borderColor: '#16A34A',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: <?php echo json_encode($monthlyOrders); ?>,
                borderColor: '#7C3AED',
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    };
    
    new Chart(revenueCtx, {
        type: 'line',
        data: revenueData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
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
                        callback: function(value) { return '₹' + value.toLocaleString(); }
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
            }
        }
    });
    
    // ============================================
    // ORDER STATUS CHART
    // ============================================
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    
    const statusColors = {
        'pending': '#F59E0B',
        'confirmed': '#3B82F6',
        'processing': '#8B5CF6',
        'shipped': '#06B6D4',
        'delivered': '#22C55E',
        'cancelled': '#EF4444',
        'returned': '#F59E0B'
    };
    
    const statusData = {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($statusCounts); ?>,
            backgroundColor: <?php 
                $colors = [];
                foreach ($statusLabels as $label) {
                    $key = strtolower($label);
                    $colors[] = $statusColors[$key] ?? '#6B7280';
                }
                echo json_encode($colors);
            ?>,
            borderWidth: 1
        }]
    };
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: statusData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Inter', size: 12 },
                        padding: 12
                    }
                }
            },
            cutout: '60%'
        }
    });
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>