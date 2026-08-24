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
 * @version 1.0.0
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

// 3. Revenue & Commission
$sql = "SELECT 
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        COALESCE(SUM(o.total_amount * a.commission_rate / 100), 0) as total_commission,
        a.commission_rate
        FROM agents a
        LEFT JOIN shops s ON a.id = s.agent_id
        LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
        WHERE a.id = ?";
$financeData = $db->fetchOne($sql, [$agent['id']]);

// 4. Today's Attendance
$today = date('Y-m-d');
$sql = "SELECT status, check_in_time, check_out_time 
        FROM attendance 
        WHERE user_id = ? AND date = ?";
$todayAttendance = $db->fetchOne($sql, [$_SESSION['user_id'], $today]);

// 5. Week Attendance Summary
$sql = "SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
        FROM attendance 
        WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$weekAttendance = $db->fetchOne($sql, [$_SESSION['user_id']]);

// 6. Recent Orders
$sql = "SELECT o.*, s.shop_name 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$agent['id']]);

// 7. Recent Shops
$sql = "SELECT id, shop_name, shop_code, city, status, created_at 
        FROM shops 
        WHERE agent_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentShops = $db->fetchAll($sql, [$agent['id']]);

// 8. Monthly Revenue for Chart
$monthlyRevenue = [];
$monthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $monthLabels[] = date('M', strtotime($date));

    $start = date('Y-m-01', strtotime($date));
    $end = date('Y-m-t', strtotime($date));

    $sql = "SELECT COALESCE(SUM(o.total_amount), 0) as total 
            FROM orders o 
            JOIN shops s ON o.shop_id = s.id 
            WHERE s.agent_id = ? AND o.status = 'delivered' 
            AND o.order_date BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$agent['id'], $start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyRevenue[] = round($result['total'] ?? 0, 2);
}
?>

<!-- Chart.js CDN -->

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

    .widget-card .widget-change.positive {
        color: #16A34A;
    }

    .widget-card .widget-change.negative {
        color: #DC2626;
    }

    .widget-card .widget-change.neutral {
        color: #6B7A7B;
    }

    .widget-card .icon-shops {
        color: #2563EB;
    }

    .widget-card .icon-orders {
        color: #7C3AED;
    }

    .widget-card .icon-revenue {
        color: #16A34A;
    }

    .widget-card .icon-commission {
        color: #D97706;
    }

    .widget-card .icon-pending {
        color: #DC2626;
    }

    .widget-card .icon-attendance {
        color: #0891B2;
    }

    .widget-card .widget-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        font-size: 10px;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: 600;
    }

    .widget-card .widget-badge.badge-success {
        background: #DCFCE7;
        color: #065F46;
    }

    .widget-card .widget-badge.badge-warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .widget-card .widget-badge.badge-danger {
        background: #FEE2E2;
        color: #991B1B;
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

    .attendance-status.checked-in {
        background: #DCFCE7;
        color: #065F46;
    }

    .attendance-status.checked-out {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    .attendance-status.absent {
        background: #FEE2E2;
        color: #991B1B;
    }

    @media (max-width: 1024px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }

        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .widgets-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }


</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">

    <!-- Welcome Section -->
    <div style="background: linear-gradient(135deg, #14532D 0%, #16A34A 100%); border-radius: 12px; padding: 24px 28px; margin-bottom: 24px; color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; margin: 0;">
                    Welcome back, <?php echo escapeHtml($agent['full_name']); ?>! 👋
                </h2>
                <p style="opacity: 0.8; margin: 4px 0 0 0; font-size: 14px;">
                    Agent Code: <strong><?php echo escapeHtml($agent['agent_code']); ?></strong>
                    | Commission Rate: <strong><?php echo number_format($agent['commission_rate'], 2); ?>%</strong>
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
        <!-- Total Shops -->
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

        <!-- Total Orders -->
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

        <!-- Total Revenue -->
        <div class="widget-card">
            <span class="widget-icon icon-revenue"><i class="fas fa-rupee-sign"></i></span>
            <div class="widget-number">₹ <?php echo number_format($financeData['total_revenue'] ?? 0, 0); ?></div>
            <div class="widget-label">Total Revenue</div>
            <div class="widget-change positive">
                <i class="fas fa-arrow-up"></i> From delivered orders
            </div>
        </div>

        <!-- Commission Earned -->
        <div class="widget-card">
            <span class="widget-icon icon-commission"><i class="fas fa-percentage"></i></span>
            <div class="widget-number">₹ <?php echo number_format($financeData['total_commission'] ?? 0, 0); ?></div>
            <div class="widget-label">Commission Earned</div>
            <div class="widget-change positive">
                <i class="fas fa-arrow-up"></i> Rate: <?php echo number_format($financeData['commission_rate'] ?? 0, 1); ?>%
            </div>
        </div>

        <!-- Attendance -->
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
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-line" style="color: #16A34A;"></i>
                Revenue Trend (Last 6 Months)
            </div>
            <div class="chart-wrapper">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-pie" style="color: #7C3AED;"></i>
                Order Status Distribution
            </div>
            <div class="chart-wrapper">
                <canvas id="orderChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
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

        <!-- Recent Shops -->
        <div class="content-card">
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
                <?php foreach ($recentShops as $shop): ?>
                    <div class="list-item">
                        <div class="item-info">
                            <div class="item-name"><?php echo escapeHtml($shop['shop_name']); ?></div>
                            <div class="item-meta">Code: <?php echo escapeHtml($shop['shop_code']); ?> • <?php echo escapeHtml($shop['city'] ?? 'N/A'); ?></div>
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
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [{
                label: 'Revenue (₹)',
                data: <?php echo json_encode($monthlyRevenue); ?>,
                borderColor: '#16A34A',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                fill: true,
                tension: 0.4
            }]
        };

        new Chart(revenueCtx, {
            type: 'line',
            data: revenueData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            },
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Order Status Chart
        const orderCtx = document.getElementById('orderChart').getContext('2d');

        const orderStatusLabels = [];
        const orderStatusData = [];
        const orderStatusColors = [];

        <?php
        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        $statusColors = [
            'pending' => '#F59E0B',
            'confirmed' => '#3B82F6',
            'processing' => '#8B5CF6',
            'shipped' => '#06B6D4',
            'delivered' => '#22C55E',
            'cancelled' => '#EF4444'
        ];
        foreach ($statuses as $s):
            $count = $orderStats[$s . '_orders'] ?? 0;
            if ($count > 0):
        ?>
                orderStatusLabels.push('<?php echo ucfirst($s); ?>');
                orderStatusData.push(<?php echo $count; ?>);
                orderStatusColors.push('<?php echo $statusColors[$s]; ?>');
        <?php
            endif;
        endforeach;
        ?>

        new Chart(orderCtx, {
            type: 'doughnut',
            data: {
                labels: orderStatusLabels,
                datasets: [{
                    data: orderStatusData,
                    backgroundColor: orderStatusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'Inter',
                                size: 11
                            },
                            padding: 10
                        }
                    }
                },
                cutout: '60%'
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>