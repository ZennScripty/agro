<?php

/**
 * SAMRIDHI AGRO - Admin Dashboard
 * 
 * This is the main admin dashboard displaying key metrics,
 * interactive charts, recent activities, and system statistics.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.2.0
 */

// Set page title BEFORE including header
$pageTitle = 'Dashboard';

// Include admin header (which includes all configs)
require_once '../includes/admin_header.php';

// Require admin login and role (already handled in header, but double-check)
requireLogin();
requireRole('admin');

// Get database instance
$db = getDB();

// ============================================
// DASHBOARD STATISTICS
// ============================================

// Total Shops
$sql = "SELECT COUNT(*) as count FROM shops WHERE status != 'suspended'";
$result = $db->fetchOne($sql);
$totalShops = $result['count'] ?? 0;

// Total Agents
$sql = "SELECT COUNT(*) as count FROM agents WHERE status != 'suspended'";
$result = $db->fetchOne($sql);
$totalAgents = $result['count'] ?? 0;

// Total Staff
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'active'";
$result = $db->fetchOne($sql);
$totalStaff = $result['count'] ?? 0;

// Total Products
$sql = "SELECT COUNT(*) as count FROM products WHERE status != 'inactive'";
$result = $db->fetchOne($sql);
$totalProducts = $result['count'] ?? 0;

// Pending Approvals
$sql = "SELECT COUNT(*) as count FROM shops WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingShops = $result['count'] ?? 0;

$sql = "SELECT COUNT(*) as count FROM agents WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingAgents = $result['count'] ?? 0;

$totalPendingApprovals = $pendingShops + $pendingAgents;

// Pending Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingOrders = $result['count'] ?? 0;

// Total Orders
$sql = "SELECT COUNT(*) as count FROM orders";
$result = $db->fetchOne($sql);
$totalOrders = $result['count'] ?? 0;

// Total Revenue
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'delivered'";
$result = $db->fetchOne($sql);
$totalRevenue = $result['total'] ?? 0;

// Recent Orders (last 5)
$sql = "SELECT o.*, s.shop_name 
        FROM orders o 
        LEFT JOIN shops s ON o.shop_id = s.id 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql);


?>
<!-- --------------add--------- -->
<?php


// Get dashboard widgets based on permissions
$widgets = [];

// Staff Attendance Widget (for staff management)
if (hasPermission('staff.attendance.view')) {
    $sql = "SELECT 
            COUNT(*) as total_staff,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_today
            FROM users u
            LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURDATE()
            WHERE u.role = 'staff' AND u.status = 'active'";
    $staffAttendance = $db->fetchOne($sql);

    $widgets['staff_attendance'] = [
        'title' => 'Staff Attendance Today',
        'icon' => 'fa-calendar-check',
        'color' => 'icon-blue',
        'total' => $staffAttendance['total_staff'] ?? 0,
        'present' => $staffAttendance['present_today'] ?? 0,
        'percentage' => ($staffAttendance['total_staff'] ?? 0) > 0 ?
            round(($staffAttendance['present_today'] ?? 0) / ($staffAttendance['total_staff'] ?? 0) * 100) : 0
    ];
}

// Agent Attendance Widget
if (hasPermission('agent.view')) {
    $sql = "SELECT 
            COUNT(*) as total_agents,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_today
            FROM users u
            JOIN agents ag ON u.id = ag.user_id
            LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURDATE()
            WHERE u.role = 'agent' AND u.status = 'active' AND ag.status = 'approved'";
    $agentAttendance = $db->fetchOne($sql);

    $widgets['agent_attendance'] = [
        'title' => 'Agents Active Today',
        'icon' => 'fa-user-tie',
        'color' => 'icon-purple',
        'total' => $agentAttendance['total_agents'] ?? 0,
        'present' => $agentAttendance['present_today'] ?? 0,
        'percentage' => ($agentAttendance['total_agents'] ?? 0) > 0 ?
            round(($agentAttendance['present_today'] ?? 0) / ($agentAttendance['total_agents'] ?? 0) * 100) : 0
    ];
}

// Staff Visits Widget
if (hasPermission('staff.visits.view')) {
    $sql = "SELECT COUNT(*) as visits_today FROM staff_visits WHERE visit_date = CURDATE() AND status = 'completed'";
    $visitsToday = $db->fetchOne($sql);

    $sql = "SELECT COUNT(*) as visits_week FROM staff_visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'completed'";
    $visitsWeek = $db->fetchOne($sql);

    $widgets['staff_visits'] = [
        'title' => 'Staff Visits',
        'icon' => 'fa-route',
        'color' => 'icon-orange',
        'today' => $visitsToday['visits_today'] ?? 0,
        'week' => $visitsWeek['visits_week'] ?? 0
    ];
}

// Staff Leads Widget
if (hasPermission('staff.leads.view')) {
    $sql = "SELECT 
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads
            FROM staff_leads";
    $leadsData = $db->fetchOne($sql);

    $widgets['staff_leads'] = [
        'title' => 'Staff Leads',
        'icon' => 'fa-bullhorn',
        'color' => 'icon-green',
        'new' => $leadsData['new_leads'] ?? 0,
        'converted' => $leadsData['converted_leads'] ?? 0
    ];
}

// Render widgets
if (!empty($widgets)):
?>
    <div class="stats-grid">
        <?php foreach ($widgets as $key => $widget): ?>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title"><?php echo $widget['title']; ?></span>
                    <div class="stat-icon <?php echo $widget['color']; ?>">
                        <i class="fas <?php echo $widget['icon']; ?>"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    if ($key === 'staff_attendance' || $key === 'agent_attendance') {
                        echo $widget['present'] . '/' . $widget['total'];
                        echo '<span style="font-size: 14px; color: #6B7A7B; margin-left: 8px;">(' . $widget['percentage'] . '%)</span>';
                    } elseif ($key === 'staff_visits') {
                        echo $widget['today'];
                        echo '<span style="font-size: 14px; color: #6B7A7B; margin-left: 8px;">Today</span>';
                        echo '<br><span style="font-size: 14px; color: #6B7A7B;">Week: ' . $widget['week'] . '</span>';
                    } elseif ($key === 'staff_leads') {
                        echo $widget['new'];
                        echo '<span style="font-size: 14px; color: #6B7A7B; margin-left: 8px;">New</span>';
                        echo '<br><span style="font-size: 14px; color: #16A34A;">Converted: ' . $widget['converted'] . '</span>';
                    } else {
                        echo $widget['value'] ?? '-';
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* Dashboard Specific Styles */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        border: 1px solid #E5EDE7;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .stat-card .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .stat-card .stat-title {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
    }

    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .stat-card .stat-value {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #052E16;
    }

    .stat-card .stat-change {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 500;
        margin-top: 4px;
    }

    .stat-card .stat-change.positive {
        color: #16A34A;
    }

    .stat-card .stat-change.negative {
        color: #DC2626;
    }

    .icon-green {
        background: #DCFCE7;
        color: #16A34A;
    }

    .icon-blue {
        background: #DBEAFE;
        color: #2563EB;
    }

    .icon-orange {
        background: #FEF3C7;
        color: #D97706;
    }

    .icon-purple {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .icon-red {
        background: #FEE2E2;
        color: #DC2626;
    }

    /* Charts Grid */
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .charts-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #E5EDE7;
    }

    .chart-card .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .chart-card .chart-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
    }

    .chart-card .chart-wrapper {
        position: relative;
        height: 280px;
    }

    .chart-card .chart-wrapper.pie-chart {
        height: 260px;
    }

    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
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

    .badge-status.badge-secondary {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    /* Activity List */
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        background: #F7FCF7;
        transition: all 0.3s ease;
    }

    .activity-item:hover {
        background: #DCFCE7;
    }

    .activity-item .activity-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 14px;
        color: #16A34A;
    }

    .activity-item .activity-content {
        flex: 1;
    }

    .activity-item .activity-text {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
        margin-bottom: 4px;
    }

    .activity-item .activity-text strong {
        font-weight: 600;
    }

    .activity-item .activity-time {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }

    /* Loading State */
    .chart-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 280px;
        color: #6B7A7B;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
    }

    .chart-loading .spinner {
        width: 30px;
        height: 30px;
        border: 3px solid #E5EDE7;
        border-top-color: #16A34A;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 12px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Mobile Responsive */
    @media (max-width: 1024px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }

        .charts-grid-2 {
            grid-template-columns: 1fr;
        }

        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .stat-card .stat-value {
            font-size: 22px;
        }

        .chart-card .chart-wrapper {
            height: 220px;
        }

        .chart-card .chart-wrapper.pie-chart {
            height: 200px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Shops</span>
            <div class="stat-icon icon-green"><i class="fas fa-store"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalShops); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 12% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Agents</span>
            <div class="stat-icon icon-blue"><i class="fas fa-user-tie"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalAgents); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 8% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Staff</span>
            <div class="stat-icon icon-purple"><i class="fas fa-users"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalStaff); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 5% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Products</span>
            <div class="stat-icon icon-orange"><i class="fas fa-box"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 15% from last month
        </div>
    </div>
</div>

<!-- Second Row Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Orders</span>
            <div class="stat-icon icon-blue"><i class="fas fa-shopping-cart"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 18% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Pending Orders</span>
            <div class="stat-icon icon-orange"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($pendingOrders); ?></div>
        <div class="stat-change negative">
            <i class="fas fa-arrow-up"></i> Needs attention
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Pending Approvals</span>
            <div class="stat-icon icon-red"><i class="fas fa-check-double"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalPendingApprovals); ?></div>
        <div class="stat-change negative">
            <i class="fas fa-arrow-up"></i> <?php echo $totalPendingApprovals; ?> pending
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Revenue</span>
            <div class="stat-icon icon-green"><i class="fas fa-rupee-sign"></i></div>
        </div>
        <div class="stat-value">₹ <?php echo number_format($totalRevenue, 0); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 22% from last month
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Sales Trend (Last 12 Months)</h3>
            <span style="font-size:12px; color:#6B7A7B;">Revenue in ₹</span>
        </div>
        <div class="chart-wrapper">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Order Status</h3>
        </div>
        <div class="chart-wrapper pie-chart">
            <canvas id="orderStatusChart"></canvas>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="charts-grid-2">
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Monthly Revenue & Orders</h3>
        </div>
        <div class="chart-wrapper">
            <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Category Distribution</h3>
        </div>
        <div class="chart-wrapper pie-chart">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="content-grid">
    <!-- Recent Orders -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Recent Orders</h3>
            <a href="#" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="table-wrapper">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Shop</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:#6B7A7B;">
                                <i class="fas fa-inbox" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                                No orders found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo escapeHtml($order['order_number']); ?></strong></td>
                                <td><?php echo escapeHtml($order['shop_name'] ?? 'N/A'); ?></td>
                                <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
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
                                </td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Recent Activities</h3>
            <a href="#" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="activity-list" id="activityList">
            <div style="text-align:center; padding:30px; color:#6B7A7B;">
                <div class="spinner" style="margin:0 auto 12px;"></div>
                Loading activities...
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================
        // LOAD CHART DATA
        // ============================================

        // Fetch dashboard data
        fetch('dashboard-data.php?type=all')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Render Sales Trend Chart
                    if (data.salesTrend) {
                        renderSalesTrendChart(data.salesTrend);
                    }

                    // Render Order Status Chart
                    if (data.orderStatus) {
                        renderOrderStatusChart(data.orderStatus);
                    }

                    // Render Monthly Revenue Chart
                    if (data.monthlyRevenue) {
                        renderMonthlyRevenueChart(data.monthlyRevenue);
                    }

                    // Render Category Chart
                    if (data.categoryDistribution) {
                        renderCategoryChart(data.categoryDistribution);
                    }

                    // Render Activities
                    if (data.recentActivity) {
                        renderActivities(data.recentActivity);
                    }
                } else {
                    console.error('API Error:', data.error);
                    showChartError('Failed to load dashboard data');
                }
            })
            .catch(error => {
                console.error('Error loading dashboard data:', error);
                showChartError('Failed to load dashboard data. Please refresh the page.');
            });

        function showChartError(message) {
            document.querySelectorAll('.chart-wrapper').forEach(el => {
                el.innerHTML = `
                <div style="text-align:center; padding:40px; color:#DC2626;">
                    <i class="fas fa-exclamation-circle" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                    ${escapeHtml(message)}
                </div>
            `;
            });
            document.getElementById('activityList').innerHTML = `
            <div style="text-align:center; padding:30px; color:#DC2626;">
                <i class="fas fa-exclamation-circle" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                Failed to load activities
            </div>
        `;
        }

        // ============================================
        // CHART RENDER FUNCTIONS
        // ============================================

        let salesTrendChartInstance = null;

        function renderSalesTrendChart(data) {
            const ctx = document.getElementById('salesTrendChart').getContext('2d');

            if (salesTrendChartInstance) {
                salesTrendChartInstance.destroy();
            }

            // Check if data has labels and datasets
            if (!data.labels || !data.datasets || data.datasets.length === 0) {
                document.getElementById('salesTrendChart').parentElement.innerHTML = `
                <div style="text-align:center; padding:40px; color:#6B7A7B;">
                    <i class="fas fa-chart-line" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                    No sales data available
                </div>
            `;
                return;
            }

            salesTrendChartInstance = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 12
                                }
                            }
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
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        let orderStatusChartInstance = null;

        function renderOrderStatusChart(data) {
            const ctx = document.getElementById('orderStatusChart').getContext('2d');

            if (orderStatusChartInstance) {
                orderStatusChartInstance.destroy();
            }

            if (!data.labels || !data.datasets || data.datasets.length === 0) {
                document.getElementById('orderStatusChart').parentElement.innerHTML = `
                <div style="text-align:center; padding:40px; color:#6B7A7B;">
                    <i class="fas fa-chart-pie" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                    No order data available
                </div>
            `;
                return;
            }

            orderStatusChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 12
                                },
                                padding: 12
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        let monthlyRevenueChartInstance = null;

        function renderMonthlyRevenueChart(data) {
            const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');

            if (monthlyRevenueChartInstance) {
                monthlyRevenueChartInstance.destroy();
            }

            if (!data.labels || !data.datasets || data.datasets.length === 0) {
                document.getElementById('monthlyRevenueChart').parentElement.innerHTML = `
                <div style="text-align:center; padding:40px; color:#6B7A7B;">
                    <i class="fas fa-chart-bar" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                    No revenue data available
                </div>
            `;
                return;
            }

            monthlyRevenueChartInstance = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
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
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                stepSize: 1
                            },
                            grid: {
                                display: false
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
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        let categoryChartInstance = null;

        function renderCategoryChart(data) {
            const ctx = document.getElementById('categoryChart').getContext('2d');

            if (categoryChartInstance) {
                categoryChartInstance.destroy();
            }

            if (!data.labels || !data.datasets || data.datasets.length === 0) {
                document.getElementById('categoryChart').parentElement.innerHTML = `
                <div style="text-align:center; padding:40px; color:#6B7A7B;">
                    <i class="fas fa-chart-pie" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                    No category data available
                </div>
            `;
                return;
            }

            // Add colors if not present
            const colors = ['#14532D', '#16A34A', '#22C55E', '#65A30D', '#EAB308', '#B45309', '#DC2626', '#2563EB'];
            if (data.datasets && data.datasets[0] && !data.datasets[0].backgroundColor) {
                data.datasets[0].backgroundColor = colors.slice(0, data.labels.length);
            }

            categoryChartInstance = new Chart(ctx, {
                type: 'pie',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 12
                                },
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        // ============================================
        // ACTIVITIES RENDER FUNCTION
        // ============================================

        function renderActivities(activities) {
            const container = document.getElementById('activityList');

            if (!activities || activities.length === 0) {
                container.innerHTML = `
                <div style="text-align:center; padding:30px; color:#6B7A7B;">
                    <i class="fas fa-inbox" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                    No recent activities
                </div>
            `;
                return;
            }

            let html = '';
            activities.forEach(function(activity) {
                const iconMap = {
                    'login': 'sign-in-alt',
                    'logout': 'sign-out-alt',
                    'create': 'plus',
                    'update': 'edit',
                    'delete': 'trash',
                    'approve': 'check',
                    'reject': 'times'
                };
                const icon = iconMap[activity.action] || 'circle';

                html += `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-${icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">
                            <strong>${escapeHtml(activity.user)}</strong>
                            ${escapeHtml(activity.description)}
                        </div>
                        <div class="activity-time">
                            <i class="far fa-clock"></i> ${escapeHtml(activity.time)}
                        </div>
                    </div>
                </div>
            `;
            });

            container.innerHTML = html;
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>