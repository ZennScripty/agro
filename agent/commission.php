<?php
/**
 * SAMRIDHI AGRO - Agent Commission
 * 
 * This page displays commission earned by the agent.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Commission';

// Include agent header
require_once '../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get agent data
$sql = "SELECT a.*, u.full_name 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Get commission summary
$sql = "SELECT 
        COALESCE(SUM(o.total_amount * a.commission_rate / 100), 0) as total_commission,
        COALESCE(SUM(CASE WHEN MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE()) 
            THEN o.total_amount * a.commission_rate / 100 ELSE 0 END), 0) as this_month_commission,
        COUNT(o.id) as total_orders,
        COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered_orders
        FROM agents a
        LEFT JOIN shops s ON a.id = s.agent_id
        LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
        WHERE a.id = ?";
$commissionSummary = $db->fetchOne($sql, [$agent['id']]);

// Get commission breakdown by shop
$sql = "SELECT 
        s.id, s.shop_name, s.shop_code,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        COALESCE(SUM(o.total_amount * ? / 100), 0) as commission
        FROM shops s
        LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
        WHERE s.agent_id = ?
        GROUP BY s.id
        ORDER BY commission DESC";
$commissionBreakdown = $db->fetchAll($sql, [$agent['commission_rate'], $agent['id']]);

// Get monthly commission chart data
$monthlyCommission = [];
$monthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $monthLabels[] = date('M', strtotime($date));
    
    $start = date('Y-m-01', strtotime($date));
    $end = date('Y-m-t', strtotime($date));
    
    $sql = "SELECT COALESCE(SUM(o.total_amount * a.commission_rate / 100), 0) as total 
            FROM orders o 
            JOIN shops s ON o.shop_id = s.id 
            JOIN agents a ON s.agent_id = a.id 
            WHERE a.id = ? AND o.status = 'delivered' 
            AND o.order_date BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$agent['id'], $start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyCommission[] = round($result['total'] ?? 0, 2);
}
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 16px 20px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-card .stat-number.positive { color: #16A34A; }
    .stat-card .stat-number.warning { color: #D97706; }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
    }
    
    .stat-card .stat-sub {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 4px;
    }
    
    .stat-card .stat-icon {
        font-size: 20px;
        margin-bottom: 4px;
        display: block;
    }
    
    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 20px 24px;
        border: 1px solid #E5EDE7;
        margin-bottom: 24px;
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
    
    .commission-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .commission-item:last-child {
        border-bottom: none;
    }
    
    .commission-item .shop-info .shop-name {
        font-weight: 500;
        color: #052E16;
    }
    
    .commission-item .shop-info .shop-code {
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .commission-item .commission-value {
        text-align: right;
    }
    
    .commission-item .commission-value .amount {
        font-weight: 600;
        color: #14532D;
    }
    
    .commission-item .commission-value .sub {
        font-size: 12px;
        color: #6B7A7B;
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    
    <!-- Commission Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon"><i class="fas fa-rupee-sign" style="color: #16A34A;"></i></span>
            <div class="stat-number positive">₹ <?php echo number_format($commissionSummary['total_commission'] ?? 0, 0); ?></div>
            <div class="stat-label">Total Commission Earned</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><i class="fas fa-calendar-alt" style="color: #D97706;"></i></span>
            <div class="stat-number warning">₹ <?php echo number_format($commissionSummary['this_month_commission'] ?? 0, 0); ?></div>
            <div class="stat-label">This Month</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><i class="fas fa-shopping-cart" style="color: #7C3AED;"></i></span>
            <div class="stat-number"><?php echo number_format($commissionSummary['total_orders'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-sub"><?php echo number_format($commissionSummary['delivered_orders'] ?? 0); ?> delivered</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><i class="fas fa-percentage" style="color: #2563EB;"></i></span>
            <div class="stat-number"><?php echo number_format($agent['commission_rate'], 1); ?>%</div>
            <div class="stat-label">Commission Rate</div>
        </div>
    </div>
    
    <!-- Commission Chart -->
    <div class="chart-card">
        <div class="chart-title">
            <i class="fas fa-chart-bar" style="color: #D97706;"></i>
            Monthly Commission (Last 6 Months)
        </div>
        <div class="chart-wrapper">
            <canvas id="commissionChart"></canvas>
        </div>
    </div>
    
    <!-- Commission Breakdown -->
    <div class="content-card" style="border: 1px solid #E5EDE7; border-radius: 12px;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-store" style="color: #16A34A;"></i>
                Commission by Shop
            </h3>
        </div>
        <?php if (empty($commissionBreakdown)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No commission data available yet
            </p>
        <?php else: ?>
            <?php foreach ($commissionBreakdown as $breakdown): ?>
            <div class="commission-item">
                <div class="shop-info">
                    <div class="shop-name"><?php echo escapeHtml($breakdown['shop_name']); ?></div>
                    <div class="shop-code">Code: <?php echo escapeHtml($breakdown['shop_code']); ?> • <?php echo $breakdown['order_count']; ?> orders</div>
                </div>
                <div class="commission-value">
                    <div class="amount">₹ <?php echo number_format($breakdown['commission'], 0); ?></div>
                    <div class="sub">Revenue: ₹ <?php echo number_format($breakdown['total_revenue'], 0); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('commissionChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [{
                label: 'Commission (₹)',
                data: <?php echo json_encode($monthlyCommission); ?>,
                backgroundColor: 'rgba(217, 119, 6, 0.2)',
                borderColor: '#D97706',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
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
                        font: { family: 'Inter', size: 11 },
                        callback: function(value) { return '₹' + value.toLocaleString(); }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>

