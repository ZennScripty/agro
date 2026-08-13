<?php
/**
 * SAMRIDHI AGRO - Agent Earnings
 * 
 * This page displays detailed earnings report for the agent.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Earnings Report';

// Include agent header
require_once '../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get agent data
$sql = "SELECT a.* FROM agents a WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET FILTER PARAMETERS
// ============================================

$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? 0);

// ============================================
// GET EARNINGS DATA
// ============================================

// Monthly earnings breakdown
$monthlyEarnings = [];
for ($m = 1; $m <= 12; $m++) {
    $sql = "SELECT COALESCE(SUM(o.total_amount * a.commission_rate / 100), 0) as total,
            COUNT(o.id) as order_count
            FROM agents a
            LEFT JOIN shops s ON a.id = s.agent_id
            LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
            WHERE a.id = ? AND MONTH(o.order_date) = ? AND YEAR(o.order_date) = ?";
    $result = $db->fetchOne($sql, [$agent['id'], $m, $year]);
    $monthlyEarnings[$m] = [
        'total' => $result['total'] ?? 0,
        'orders' => $result['order_count'] ?? 0
    ];
}

// Yearly summary
$sql = "SELECT 
        COALESCE(SUM(o.total_amount * a.commission_rate / 100), 0) as total_earnings,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_revenue
        FROM agents a
        LEFT JOIN shops s ON a.id = s.agent_id
        LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
        WHERE a.id = ? AND YEAR(o.order_date) = ?";
$yearlySummary = $db->fetchOne($sql, [$agent['id'], $year]);

// Total lifetime earnings
$sql = "SELECT COALESCE(SUM(o.total_amount * a.commission_rate / 100), 0) as total
        FROM agents a
        LEFT JOIN shops s ON a.id = s.agent_id
        LEFT JOIN orders o ON s.id = o.shop_id AND o.status = 'delivered'
        WHERE a.id = ?";
$result = $db->fetchOne($sql, [$agent['id']]);
$lifetimeEarnings = $result['total'] ?? 0;

// Get available years for filter
$sql = "SELECT DISTINCT YEAR(o.order_date) as year 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ? AND o.status = 'delivered'
        ORDER BY year DESC";
$years = $db->fetchAll($sql, [$agent['id']]);
?>

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
        color: #16A34A;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
    }
    
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        margin-bottom: 20px;
        padding: 16px 20px;
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
    }
    
    .filter-bar label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #14532D;
    }
    
    .filter-bar select {
        padding: 8px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
    }
    
    .filter-bar .btn-filter {
        padding: 8px 24px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .earning-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .earning-item:last-child {
        border-bottom: none;
    }
    
    .earning-item .month-name {
        font-weight: 500;
        color: #052E16;
    }
    
    .earning-item .month-amount {
        font-weight: 600;
        color: #14532D;
    }
    
    .earning-item .month-orders {
        font-size: 13px;
        color: #6B7A7B;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-line" style="color: #D97706;"></i>
            Earnings Report
        </h3>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">₹ <?php echo number_format($lifetimeEarnings, 0); ?></div>
            <div class="stat-label">Lifetime Earnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">₹ <?php echo number_format($yearlySummary['total_earnings'] ?? 0, 0); ?></div>
            <div class="stat-label"><?php echo $year; ?> Earnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($yearlySummary['total_orders'] ?? 0); ?></div>
            <div class="stat-label">Total Orders (<?php echo $year; ?>)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">₹ <?php echo number_format($yearlySummary['total_revenue'] ?? 0, 0); ?></div>
            <div class="stat-label">Total Revenue (<?php echo $year; ?>)</div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; width: 100%;">
            <label for="year">Year:</label>
            <select id="year" name="year">
                <?php if (empty($years)): ?>
                    <option value="<?php echo date('Y'); ?>"><?php echo date('Y'); ?></option>
                <?php else: ?>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y['year']; ?>" <?php echo $year == $y['year'] ? 'selected' : ''; ?>>
                            <?php echo $y['year']; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Apply
            </button>
        </form>
    </div>
    
    <!-- Monthly Breakdown -->
    <div style="background: white; border: 1px solid #E5EDE7; border-radius: 12px; padding: 20px;">
        <h4 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; color: #052E16; margin-bottom: 16px;">
            <i class="fas fa-calendar-alt" style="color: #16A34A;"></i>
            Monthly Breakdown - <?php echo $year; ?>
        </h4>
        
        <?php 
        $hasEarnings = false;
        foreach ($monthlyEarnings as $month => $data) {
            if ($data['total'] > 0) {
                $hasEarnings = true;
                break;
            }
        }
        ?>
        
        <?php if (!$hasEarnings): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No earnings data available for <?php echo $year; ?>
            </p>
        <?php else: ?>
            <?php 
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            foreach ($monthlyEarnings as $month => $data): 
            ?>
            <div class="earning-item">
                <div>
                    <span class="month-name"><?php echo $monthNames[$month - 1]; ?></span>
                    <span class="month-orders">(<?php echo $data['orders']; ?> orders)</span>
                </div>
                <div>
                    <span class="month-amount">₹ <?php echo number_format($data['total'], 0); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>