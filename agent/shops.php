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
 * @version 2.1.0
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

// Get agent data
$sql = "SELECT a.*, u.full_name FROM agents a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET SHOPS LIST WITH FINANCIAL DATA
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["s.agent_id = ?"];
$params = [$agent['id']];

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR s.owner_name LIKE ? OR s.city LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
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

// Get shops with financial statistics
$sql = "SELECT s.*, 
        u.full_name as owner_name, u.email, u.phone,

        /* Total Business = Sum of all orders (non-cancelled) */
        (
            SELECT COALESCE(SUM(total_amount), 0) 
            FROM orders 
            WHERE shop_id = s.id AND status != 'cancelled'
        ) as total_business,

        /* Paid Amount = Sum of confirmed payments */
        (
            SELECT COALESCE(SUM(amount), 0) 
            FROM payments 
            WHERE shop_id = s.id AND status = 'confirmed'
        ) as paid_amount,

        /* Remaining Amount = Total Business - Paid Amount */
        (
            SELECT COALESCE(SUM(total_amount), 0) 
            FROM orders 
            WHERE shop_id = s.id AND status != 'cancelled'
        ) - (
            SELECT COALESCE(SUM(amount), 0) 
            FROM payments 
            WHERE shop_id = s.id AND status = 'confirmed'
        ) as remaining_amount,

        /* Order count */
        (SELECT COUNT(*) 
         FROM orders 
         WHERE shop_id = s.id) as order_count,

        /* Pending orders */
        (SELECT COUNT(*) 
         FROM orders 
         WHERE shop_id = s.id 
         AND status = 'pending') as pending_orders,

        /* Pending payments */
        (SELECT COUNT(*) 
         FROM shop_payments 
         WHERE shop_id = s.id 
         AND status IN ('pending', 'collected', 'submitted')) as pending_payments

        FROM shops s 
        JOIN users u ON s.user_id = u.id 

        $whereClause

        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$shopList = $db->fetchAll($sql, $queryParams);

// Calculate remaining amount in PHP (for safety)
foreach ($shopList as &$shop) {
    $shop['remaining_amount'] = max(0, ($shop['total_business'] ?? 0) - ($shop['paid_amount'] ?? 0));
}

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

    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.approved .stat-number { color: #16A34A; }
    .stat-card.pending .stat-number { color: #F59E0B; }
    .stat-card.suspended .stat-number { color: #DC2626; }

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

    .shop-card .shop-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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

    .shop-card .shop-stats .stat-item .stat-value.zero {
        color: #16A34A;
    }

    .shop-card .shop-stats .stat-item .stat-value.negative {
        color: #DC2626;
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

    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }

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

    .btn-action:hover { transform: translateY(-1px); }

    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-orders { background: #EDE9FE; color: #7C3AED; }
    .btn-payments { background: #DCFCE7; color: #16A34A; }
    .btn-history { background: #FEF3C7; color: #D97706; }
    .btn-toggle { background: #F3F4F6; color: #4A5B5D; }

    .financial-amount {
        font-weight: 700;
        font-size: 16px;
    }

    .financial-amount.positive { color: #14532D; }
    .financial-amount.zero { color: #16A34A; }
    .financial-amount.negative { color: #DC2626; }

    .financial-detail {
        font-size: 10px;
        color: #6B7A7B;
        display: block;
        margin-top: 2px;
    }

    /* ===== MOBILE RESPONSIVE IMPROVEMENTS ===== */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .stat-card {
            padding: 10px 12px;
        }

        .stat-card .stat-number {
            font-size: 18px;
        }

        .stat-card .stat-label {
            font-size: 11px;
        }

        .shop-card {
            padding: 14px 16px;
            border-radius: 10px;
        }

        .shop-card .shop-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .shop-card .shop-name {
            font-size: 16px;
            word-break: break-word;
        }

        .shop-card .shop-code {
            font-size: 12px;
            word-break: break-word;
        }

        .shop-card .shop-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 8px;
            padding-top: 8px;
        }

        .shop-card .shop-stats .stat-item {
            padding: 6px 4px;
            background: rgba(255,255,255,0.5);
            border-radius: 6px;
        }

        .shop-card .shop-stats .stat-item .stat-value {
            font-size: 14px;
        }

        .shop-card .shop-stats .stat-item .stat-label {
            font-size: 10px;
        }

        .financial-amount {
            font-size: 14px;
        }

        .shop-card .shop-actions {
            gap: 4px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #F0FDF4;
        }

        .btn-action {
            padding: 6px 10px;
            font-size: 11px;
            flex: 1;
            justify-content: center;
            min-width: 60px;
        }

        .badge-status {
            font-size: 10px;
            padding: 2px 8px;
        }

        .filter-row {
            flex-direction: column;
        }

        .filter-row input,
        .filter-row select,
        .filter-row button {
            width: 100%;
        }

        /* Search bar mobile */
        .search-wrap {
            width: 100%;
        }

        .search-wrap input {
            font-size: 13px !important;
            padding: 8px 12px 8px 36px !important;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .stat-card {
            padding: 8px 10px;
            border-radius: 8px;
        }

        .stat-card .stat-number {
            font-size: 16px;
        }

        .stat-card .stat-label {
            font-size: 10px;
        }

        .shop-card {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .shop-card .shop-name {
            font-size: 15px;
        }

        .shop-card .shop-code {
            font-size: 11px;
        }

        .shop-card .shop-stats {
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .shop-card .shop-stats .stat-item {
            padding: 4px 6px;
        }

        .shop-card .shop-stats .stat-item .stat-value {
            font-size: 13px;
        }

        .shop-card .shop-stats .stat-item .stat-label {
            font-size: 9px;
        }

        .financial-amount {
            font-size: 13px;
        }

        .shop-card .shop-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
        }

        .btn-action {
            padding: 8px 6px;
            font-size: 10px;
            flex: none;
            width: 100%;
            justify-content: center;
        }

        .badge-status {
            font-size: 9px;
            padding: 2px 6px;
        }

        .shop-card .shop-header .badge-status {
            margin-top: 4px;
        }
    }

    /* Very small screens */
    @media (max-width: 360px) {
        .shop-card .shop-stats {
            grid-template-columns: 1fr 1fr;
            gap: 4px;
        }

        .shop-card .shop-stats .stat-item .stat-value {
            font-size: 12px;
        }

        .btn-action {
            font-size: 9px;
            padding: 6px 4px;
        }

        .shop-card .shop-name {
            font-size: 14px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-store" style="color: #16A34A;"></i>
            My Shops
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">(<?php echo number_format($totalShops); ?>)</span>
        </h3>
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
            <div style="flex: 1; min-width: 180px; position: relative;" class="search-wrap">
                <input type="text" name="search" placeholder="Search shops by name, code, owner, city..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
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

                <!-- Financial Stats -->
                <div class="shop-stats">
                    <!-- Total Business -->
                    <div class="stat-item">
                        <div class="stat-value financial-amount positive">
                            ₹ <?php echo number_format($shop['total_business'] ?? 0, 0); ?>
                        </div>
                        <div class="stat-label">Total Business</div>
                    </div>

                    <!-- Paid Amount -->
                    <div class="stat-item">
                        <div class="stat-value financial-amount positive">
                            ₹ <?php echo number_format($shop['paid_amount'] ?? 0, 0); ?>
                        </div>
                        <div class="stat-label">Paid</div>
                    </div>

                    <!-- Remaining Amount -->
                    <div class="stat-item">
                        <?php 
                        $remaining = $shop['remaining_amount'] ?? 0;
                        $class = $remaining <= 0 ? 'zero' : 'negative';
                        ?>
                        <div class="stat-value financial-amount <?php echo $class; ?>">
                            ₹ <?php echo number_format($remaining, 0); ?>
                        </div>
                        <div class="stat-label">Remaining</div>
                        <?php if ($remaining <= 0): ?>
                            <span style="font-size: 9px; color: #16A34A;">
                                <i class="fas fa-check-circle"></i> Fully Paid
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Orders -->
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($shop['order_count'] ?? 0); ?></div>
                        <div class="stat-label">Total Orders</div>
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
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px;"><?php echo $pagination; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>