<?php

/**
 * SAMRIDHI AGRO - Shop Dashboard
 * 
 * This is the shop dashboard displaying key metrics,
 * recent orders, financial summary, and product insights.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 2.0.0
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

// ============================================
// FINANCIAL STATISTICS
// ============================================

// Total Business (all orders except cancelled)
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM orders 
        WHERE shop_id = ? AND status != 'cancelled'";
$result = $db->fetchOne($sql, [$shop['id']]);
$totalBusiness = $result['total'] ?? 0;

// Total Paid (confirmed payments)
$sql = "SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE shop_id = ? AND status = 'confirmed'";
$result = $db->fetchOne($sql, [$shop['id']]);
$totalPaid = $result['total'] ?? 0;

// Remaining Amount
$remainingAmount = max(0, $totalBusiness - $totalPaid);

// Total Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE shop_id = ?";
$result = $db->fetchOne($sql, [$shop['id']]);
$totalOrders = $result['count'] ?? 0;
// deliverd Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE shop_id = ? AND status = 'delivered'";
$result = $db->fetchOne($sql, [$shop['id']]);
$deliveredOrders = $result['count'] ?? 0;

// Pending Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE shop_id = ? AND status = 'pending'";
$result = $db->fetchOne($sql, [$shop['id']]);
$pendingOrders = $result['count'] ?? 0;

// ============================================
// ORDER GRAPH DATA (Last 6 months)
// ============================================

$monthlyOrders = [];
$monthlyRevenue = [];
$monthLabels = [];

for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $monthLabels[] = date('M', strtotime($date));
    
    $start = date('Y-m-01', strtotime($date));
    $end = date('Y-m-t', strtotime($date));
    
    // Order count
    $sql = "SELECT COUNT(*) as count 
            FROM orders 
            WHERE shop_id = ? 
            AND created_at BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$shop['id'], $start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyOrders[] = (int)($result['count'] ?? 0);
    
    // Revenue
    $sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
            FROM orders 
            WHERE shop_id = ? AND status != 'cancelled'
            AND created_at BETWEEN ? AND ?";
    $result = $db->fetchOne($sql, [$shop['id'], $start . ' 00:00:00', $end . ' 23:59:59']);
    $monthlyRevenue[] = round($result['total'] ?? 0, 2);
}

// ============================================
// MOST BOUGHT PRODUCTS
// ============================================

$sql = "SELECT 
            p.id, p.product_name, p.sku, p.price, p.unit, p.image,
            COALESCE(SUM(oi.quantity), 0) as total_quantity,
            COALESCE(SUM(oi.total), 0) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        WHERE o.shop_id = ? OR o.shop_id IS NULL
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT 5";
$topProducts = $db->fetchAll($sql, [$shop['id']]);

// ============================================
// RECENT ORDERS
// ============================================

$sql = "SELECT * FROM orders 
        WHERE shop_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$shop['id']]);

// ============================================
// RECENT ACTIVITIES
// ============================================

$sql = "SELECT al.*, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.module = 'order' AND al.description LIKE ?
        ORDER BY al.created_at DESC 
        LIMIT 5";
$recentActivities = $db->fetchAll($sql, ['%#' . $shop['shop_code'] . '%']);
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

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
        text-decoration: none;
        display: block;
        cursor: pointer;
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

    .stat-card .stat-sub {
        font-size: 11px;
        color: #6B7A7B;
        margin-top: 2px;
    }

    .stat-card .stat-icon.business { color: #14532D; }
    .stat-card .stat-icon.paid { color: #16A34A; }
    .stat-card .stat-icon.remaining { color: #DC2626; }
    .stat-card .stat-icon.orders { color: #7C3AED; }
    .stat-card .stat-icon.pending { color: #F59E0B; }

    .stat-card .remaining-zero { color: #16A34A; }
    .stat-card .remaining-negative { color: #DC2626; }
    .stat-card .delivered { color: #6dcb9b; }

    /* Chart Card */
    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 20px 24px;
        border: 1px solid #E5EDE7;
        margin-bottom: 24px;
        transition: box-shadow 0.3s ease;
    }

    .chart-card:hover {
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.10);
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

    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }

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

    /* Product List */
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

    /* Order Item */
    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .order-item .order-number {
        font-weight: 600;
        color: #052E16;
    }

    .order-item .order-date {
        font-size: 12px;
        color: #6B7A7B;
    }

    .order-item .order-amount {
        font-weight: 600;
        color: #14532D;
    }

    .order-item .order-right {
        text-align: right;
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

        .chart-card {
            padding: 16px 18px;
        }

        .chart-card .chart-wrapper {
            height: 220px;
        }

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

        .product-item {
            flex-wrap: wrap;
        }

        .product-item .product-stats {
            width: 100%;
            text-align: left;
            padding-left: 36px;
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

        .chart-card .chart-wrapper {
            height: 180px;
        }

        .order-item {
            flex-wrap: wrap;
        }

        .order-item .order-right {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

    <!-- Statistics with A Tags -->
    <div class="stats-grid">
        <a href="orders.php" class="stat-card sdbg">
            <span class="stat-icon business"><i class="fas fa-chart-line"></i></span>
            <div class="stat-number">₹ <?php echo number_format($totalBusiness, 0); ?></div>
            <div class="stat-label">Total Business</div>
            <div class="stat-sub">All orders (except cancelled)</div>
        </a>
        <a href="payments.php" class="stat-card sdbg">
            <span class="stat-icon paid"><i class="fas fa-check-circle"></i></span>
            <div class="stat-number">₹ <?php echo number_format($totalPaid, 0); ?></div>
            <div class="stat-label">Total Paid</div>
            <div class="stat-sub">Confirmed payments</div>
        </a>
        <a href="payments.php" class="stat-card sdbg">
            <span class="stat-icon remaining"><i class="fas fa-clock"></i></span>
            <div class="stat-number <?php echo $remainingAmount <= 0 ? 'remaining-zero' : 'remaining-negative'; ?>">
                ₹ <?php echo number_format($remainingAmount, 0); ?>
            </div>
            <div class="stat-label">Remaining</div>
            <div class="stat-sub">
                <?php if ($remainingAmount <= 0): ?>
                    <span style="color: #16A34A;"><i class="fas fa-check-circle"></i> Fully Paid</span>
                <?php else: ?>
                    <span style="color: #DC2626;">Pending payment</span>
                <?php endif; ?>
            </div>
        </a>
        <a href="orders.php" class="stat-card sdbg">
            <span class="stat-icon orders"><i class="fas fa-shopping-cart"></i></span>
            <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-sub">All orders placed</div>
        </a>
        <a href="orders.php?search=&status=pending" class="stat-card sdbg">
            <span class="stat-icon pending"><i class="fas fa-clock"></i></span>
            <div class="stat-number"><?php echo number_format($pendingOrders); ?></div>
            <div class="stat-label">Pending Orders</div>
            <div class="stat-sub">Awaiting processing</div>
        </a>
        <a href="orders.php?search=&status=delivered" class="stat-card sdbg">
            <span class="stat-icon delivered"><i class="fas fa-truck"></i></span>
            <div class="stat-number"><?php echo number_format($deliveredOrders); ?></div>
            <div class="stat-label">Delivered Orders</div>
            <div class="stat-sub">Total  delivered orders</div>
        </a>
    </div>

    <!-- Order Chart -->
    <div class="chart-card">
        <div class="chart-title">
            <i class="fas fa-chart-bar" style="color: #16A34A;"></i>
            Orders & Revenue Trend (Last 6 Months)
        </div>
        <div class="chart-wrapper">
            <canvas id="orderChart"></canvas>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Most Bought Products -->
        <div class="content-card sdbg">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-trophy" style="color: #EAB308;"></i>
                    Most Bought Products
                </h3>
            </div>
            <?php if (empty($topProducts) || array_sum(array_column($topProducts, 'total_quantity')) == 0): ?>
                <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                    <i class="fas fa-box-open" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    No products purchased yet
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
                    <div class="order-item">
                        <div>
                            <div class="order-number">#<?php echo escapeHtml($order['order_number']); ?></div>
                            <div class="order-date"><?php echo formatDate($order['created_at']); ?></div>
                        </div>
                        <div class="order-right">
                            <div class="order-amount">₹ <?php echo number_format($order['total_amount'], 2); ?></div>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // ORDER & REVENUE CHART
    // ============================================
    const ctx = document.getElementById('orderChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [
                {
                    label: 'Orders',
                    data: <?php echo json_encode($monthlyOrders); ?>,
                    backgroundColor: 'rgba(124, 58, 237, 0.2)',
                    borderColor: '#7C3AED',
                    borderWidth: 2,
                    borderRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($monthlyRevenue); ?>,
                    backgroundColor: 'rgba(22, 163, 74, 0.2)',
                    borderColor: '#16A34A',
                    borderWidth: 2,
                    borderRadius: 4,
                    type: 'line',
                    tension: 0.4,
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
                        stepSize: 1
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        callback: function(value) { return '₹' + value.toLocaleString(); }
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
});
</script>

<?php require_once '../includes/shop_footer.php'; ?>