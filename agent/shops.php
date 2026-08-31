<?php

/**
 * SAMRIDHI AGRO - Agent Shops Management
 * 
 * This page displays all shops assigned to the agent with full management
 * including shop details, orders, payments, and history.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.1
 */

// Set page title
$pageTitle = 'My Shops';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get agent data with company name
$sql = "SELECT a.*, u.full_name FROM agents a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);



// ============================================
// GET SHOPS LIST WITH COMPANY NAME
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["s.agent_id = ?"];
$params = [$agent['id']];

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR s.owner_name LIKE ? OR s.city LIKE ? OR a.company_name LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "s.status = ?";
    $params[] = $status;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM shops s $whereClause";
$result = $db->fetchOne($sql, $params);
$totalShops = $result['total'] ?? 0;

// Get shops with statistics and company name from agent
$sql = "SELECT s.*, 
        u.full_name as owner_name, u.email, u.phone,
        a.company_name as agent_company_name,

        (SELECT COUNT(*) 
         FROM orders 
         WHERE shop_id = s.id) as order_count,

        (SELECT COALESCE(SUM(total_amount), 0) 
         FROM orders 
         WHERE shop_id = s.id 
         AND status = 'delivered') as total_revenue,

        (SELECT COALESCE(SUM(total_amount * ? / 100), 0) 
         FROM orders o 
         WHERE o.shop_id = s.id 
         AND o.status = 'delivered') as commission_earned,

        (SELECT COUNT(*) 
         FROM orders 
         WHERE shop_id = s.id 
         AND status = 'pending') as pending_orders,

        (SELECT COUNT(*) 
         FROM shop_payments 
         WHERE shop_id = s.id 
         AND status IN ('pending', 'collected', 'submitted')) as pending_payments

        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN agents a ON s.agent_id = a.id

        $whereClause

        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge(
    [$agent['commission_rate']], // ?1
    $params,                     // ?2 = agent_id
    [$perPage, $offset]          // ?3, ?4
);

$shopList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalShops / $perPage);
$pagination = getPagination($totalShops, $page, $perPage, 'shops.php?page={page}&search=' . urlencode($search) . '&status=' . $status);

// Shop Statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
        FROM shops WHERE agent_id = ?";
$shopStats = $db->fetchOne($sql, [$agent['id']]);

$csrfToken = generateCsrfToken();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 12px 16px;
        text-align: center;
         background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
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

    .stat-card.total .stat-number {
        color: #14532D;
    }

    .stat-card.approved .stat-number {
        color: #16A34A;
    }

    .stat-card.pending .stat-number {
        color: #F59E0B;
    }

    .stat-card.suspended .stat-number {
        color: #DC2626;
    }

    .shop-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
    }

    .shop-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .shop-card .shop-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
    }

    .shop-card .shop-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 600;
        color: #052E16;
    }

    .shop-card .shop-code {
        font-size: 13px;
        color: #6B7A7B;
    }

    .shop-card .shop-company {
        font-size: 13px;
        color: #7C3AED;
        font-weight: 500;
        margin-top: 2px;
    }

    .shop-card .shop-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .shop-card .shop-stats .stat-item {
        text-align: center;
    }

    .shop-card .shop-stats .stat-item .stat-value {
        font-weight: 600;
        color: #14532D;
    }

    .shop-card .shop-stats .stat-item .stat-label {
        font-size: 11px;
        color: #6B7A7B;
    }

    .shop-card .shop-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 10px;
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

    .badge-status.badge-secondary {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    .btn-action {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-action:hover {
        transform: translateY(-1px);
    }

    .btn-view {
        background: #DBEAFE;
        color: #2563EB;
    }

    .btn-orders {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .btn-payments {
        background: #DCFCE7;
        color: #16A34A;
    }

    .btn-history {
        background: #FEF3C7;
        color: #D97706;
    }

    .btn-toggle {
        background: #F3F4F6;
        color: #4A5B5D;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-store" style="color: #16A34A;"></i>
            My Shops
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">(<?php echo number_format($totalShops); ?>)</span>
        </h3>
        <a href="shop-create.php" class="card-action">
            <i class="fas fa-plus"></i> Create Shop
        </a>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $shopStats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Shops</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-number"><?php echo $shopStats['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo $shopStats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card suspended">
            <div class="stat-number"><?php echo $shopStats['suspended'] ?? 0; ?></div>
            <div class="stat-label">Suspended</div>
        </div>
    </div>

    <!-- Search -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input type="text" name="search" placeholder="Search shops, company..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            <select name="status" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
            <button type="submit" style="padding: 10px 24px; background: #14532D; color: white; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || $status !== 'all'): ?>
                <a href="shops.php" style="padding: 10px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 10px; text-decoration: none;">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Shop List -->
    <?php if (empty($shopList)): ?>
        <div style="text-align: center; padding: 40px; color: #6B7A7B;">
            <i class="fas fa-store-slash" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
            <p>No shops found</p>
        </div>
    <?php else: ?>
        <?php foreach ($shopList as $shop): ?>
            <div class="shop-card">
                <div class="shop-header">
                    <div>
                        <div class="shop-name"><?php echo escapeHtml($shop['shop_name']); ?></div>
                        <div class="shop-code">
                            Code: <?php echo escapeHtml($shop['shop_code']); ?>
                            | Owner: <?php echo escapeHtml($shop['owner_name']); ?>
                            | <?php echo escapeHtml($shop['city'] ?? 'N/A'); ?>
                        </div>
                        <?php if (!empty($shop['agent_company_name'])): ?>
                            <div class="shop-company">
                                <i class="fas fa-building"></i> Company: <?php echo escapeHtml($shop['agent_company_name']); ?>
                            </div>
                        <?php endif; ?>
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
                        <span class="badge-status <?php echo $color; ?>"><?php echo ucfirst($shop['status']); ?></span>
                        <?php if ($shop['delivery_available']): ?>
                            <span class="badge-status" style="background: #DBEAFE; color: #1E40AF;">🚚 Delivery</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="shop-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($shop['order_count'] ?? 0); ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹ <?php echo number_format($shop['total_revenue'] ?? 0, 0); ?></div>
                        <div class="stat-label">Revenue</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹ <?php echo number_format($shop['commission_earned'] ?? 0, 0); ?></div>
                        <div class="stat-label">Commission</div>
                    </div>
                    <?php if (($shop['pending_orders'] ?? 0) > 0): ?>
                        <div class="stat-item" style="border-left: 2px solid #F59E0B;">
                            <div class="stat-value" style="color: #F59E0B;"><?php echo $shop['pending_orders']; ?></div>
                            <div class="stat-label">Pending Orders</div>
                        </div>
                    <?php endif; ?>
                    <?php if (($shop['pending_payments'] ?? 0) > 0): ?>
                        <div class="stat-item" style="border-left: 2px solid #DC2626;">
                            <div class="stat-value" style="color: #DC2626;"><?php echo $shop['pending_payments']; ?></div>
                            <div class="stat-label">Pending Payments</div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($shop['working_hours_start']) && !empty($shop['working_hours_end'])): ?>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo date('h:i A', strtotime($shop['working_hours_start'])); ?> - <?php echo date('h:i A', strtotime($shop['working_hours_end'])); ?></div>
                            <div class="stat-label">Working Hours</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="shop-actions">
                    <a href="shop-view.php?id=<?php echo $shop['id']; ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a>
                    <a href="orders.php?shop=<?php echo $shop['id']; ?>" class="btn-action btn-orders"><i class="fas fa-shopping-cart"></i> Orders</a>
                    <a href="shop-payments.php?shop=<?php echo $shop['id']; ?>" class="btn-action btn-payments"><i class="fas fa-rupee-sign"></i> Payments</a>
                    <a href="shop-history.php?shop=<?php echo $shop['id']; ?>" class="btn-action btn-history"><i class="fas fa-history"></i> History</a>
                    <?php if ($shop['status'] === 'approved'): ?>
                        <a href="shops.php?action=toggle&id=<?php echo $shop['id']; ?>&csrf=<?php echo $csrfToken; ?>" class="btn-action btn-toggle" onclick="return confirm('Toggle shop status?')"><i class="fas fa-pause"></i> Suspend</a>
                    <?php elseif ($shop['status'] === 'suspended'): ?>
                        <a href="shops.php?action=toggle&id=<?php echo $shop['id']; ?>&csrf=<?php echo $csrfToken; ?>" class="btn-action btn-toggle" onclick="return confirm('Activate this shop?')"><i class="fas fa-play"></i> Activate</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px;"><?php echo $pagination; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>