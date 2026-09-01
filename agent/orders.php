<?php

/**
 * SAMRIDHI AGRO - Agent Orders
 * 
 * This page displays orders from shops assigned to the agent.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 3.0.0
 */

// Set page title
$pageTitle = 'Orders';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get agent data
$sql = "SELECT a.* FROM agents a WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET ORDERS LIST
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$shopFilter = isset($_GET['shop']) ? (int)$_GET['shop'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["s.agent_id = ?"];
$params = [$agent['id']];

if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE ? OR s.shop_name LIKE ? OR s.shop_code LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

if ($shopFilter > 0) {
    $whereConditions[] = "o.shop_id = ?";
    $params[] = $shopFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalOrders = $result['total'] ?? 0;

// Get orders - REMOVED payment fields
$sql = "SELECT o.*, s.shop_name, s.shop_code, u.full_name as shop_owner
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        JOIN users u ON s.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$orderList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalOrders / $perPage);
$paginationUrl = 'orders.php?page={page}&search=' . urlencode($search) . '&status=' . $status;
if ($shopFilter > 0) $paginationUrl .= '&shop=' . $shopFilter;
$pagination = getPagination($totalOrders, $page, $perPage, $paginationUrl);

// Get shops for filter
$sql = "SELECT id, shop_name FROM shops WHERE agent_id = ? ORDER BY shop_name";
$shops = $db->fetchAll($sql, [$agent['id']]);

// Get order statistics - REMOVED total_revenue
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN o.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN o.status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN o.status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ?";
$orderStats = $db->fetchOne($sql, [$agent['id']]);

$csrfToken = generateCsrfToken();
?>

<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 10px 14px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        border-color: #16A34A;
    }

    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
    }

    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        color: #6B7A7B;
    }

    .stat-card .stat-icon {
        font-size: 16px;
        display: block;
        margin-bottom: 2px;
    }

    .stat-card.total .stat-number {
        color: #14532D;
    }
    .stat-card.total .stat-icon {
        color: #14532D;
    }

    .stat-card.pending .stat-number {
        color: #F59E0B;
    }
    .stat-card.pending .stat-icon {
        color: #F59E0B;
    }

    .stat-card.processing .stat-number {
        color: #8B5CF6;
    }
    .stat-card.processing .stat-icon {
        color: #8B5CF6;
    }

    .stat-card.delivered .stat-number {
        color: #16A34A;
    }
    .stat-card.delivered .stat-icon {
        color: #16A34A;
    }

    .stat-card.cancelled .stat-number {
        color: #DC2626;
    }
    .stat-card.cancelled .stat-icon {
        color: #DC2626;
    }

    /* Order Cards */
    .order-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .order-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        border-color: #16A34A;
    }

    .order-card .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }

    .order-card .order-number {
        font-weight: 600;
        color: #14532D;
        font-size: 15px;
        text-decoration: none;
    }

    .order-card .order-number:hover {
        color: #16A34A;
    }

    .order-card .order-shop {
        font-size: 13px;
        color: #6B7A7B;
    }

    .order-card .order-shop i {
        color: #16A34A;
    }

    .order-card .order-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
    }

    .order-card .order-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 6px;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #F0FDF4;
    }

    .order-card .order-details .detail-item .detail-label {
        font-size: 10px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .order-card .order-details .detail-item .detail-value {
        font-size: 12px;
        font-weight: 500;
        color: #052E16;
    }

    .order-card .order-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .badge-status {
        display: inline-block;
        padding: 2px 10px;
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
    .badge-status.badge-secondary {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    .btn-action {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        font-size: 11px;
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
    .btn-view:hover {
        background: #BFDBFE;
    }

    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 16px;
        padding: 12px 16px;
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        border: 1px solid #E5EDE7;
        border-radius: 10px;
    }

    .filter-bar input,
    .filter-bar select {
        padding: 8px 12px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: white;
        transition: all 0.3s ease;
    }

    .filter-bar input:focus,
    .filter-bar select:focus {
        outline: none;
        border-color: #16A34A;
    }

    .filter-bar .btn-filter {
        padding: 8px 20px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-bar .btn-filter:hover {
        background: #052E16;
    }

    .filter-bar .btn-clear {
        padding: 8px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .filter-bar .btn-clear:hover {
        background: #E5E7EB;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6B7A7B;
    }

    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: #D1D5DB;
    }

    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-bar form {
            flex-direction: column !important;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .order-card .order-details {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .order-card .order-details {
            grid-template-columns: 1fr;
        }
        .order-card .order-actions {
            flex-direction: column;
        }
        .order-card .order-actions .btn-action {
            justify-content: center;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shopping-cart" style="color: #16A34A;"></i>
            Orders
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalOrders); ?>)
            </span>
        </h3>
    </div>

    <!-- Statistics - REMOVED Revenue Card -->
    <div class="stats-grid">
        <div class="stat-card total">
            <span class="stat-icon"><i class="fas fa-list"></i></span>
            <div class="stat-number"><?php echo number_format($orderStats['total'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card pending">
            <span class="stat-icon"><i class="fas fa-clock"></i></span>
            <div class="stat-number"><?php echo number_format($orderStats['pending'] ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card processing">
            <span class="stat-icon"><i class="fas fa-spinner"></i></span>
            <div class="stat-number"><?php echo number_format(($orderStats['processing'] ?? 0) + ($orderStats['confirmed'] ?? 0) + ($orderStats['shipped'] ?? 0)); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card delivered">
            <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
            <div class="stat-number"><?php echo number_format($orderStats['delivered'] ?? 0); ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card cancelled">
            <span class="stat-icon"><i class="fas fa-times-circle"></i></span>
            <div class="stat-number"><?php echo number_format($orderStats['cancelled'] ?? 0); ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; width: 100%;">
            <div style="flex: 1; min-width: 160px; position: relative;">
                <input
                    type="text"
                    name="search"
                    placeholder="Search order #, shop..."
                    value="<?php echo escapeHtml($search); ?>"
                    style="width: 100%; padding: 8px 12px 8px 32px;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6B7A7B; font-size: 13px;"></i>
            </div>

            <select name="status">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <?php foreach (ORDER_STATUSES as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>

            <select name="shop" style="min-width: 130px;">
                <option value="0" <?php echo $shopFilter == 0 ? 'selected' : ''; ?>>All Shops</option>
                <?php foreach ($shops as $shop): ?>
                    <option value="<?php echo $shop['id']; ?>" <?php echo $shopFilter == $shop['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($shop['shop_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>

            <?php if (!empty($search) || $status !== 'all' || $shopFilter > 0): ?>
                <a href="orders.php" class="btn-clear">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders List -->
    <?php if (empty($orderList)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No orders found</p>
            <?php if (!empty($search) || $status !== 'all' || $shopFilter > 0): ?>
                <p style="font-size: 13px;">Try adjusting your search or filters</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($orderList as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <a href="order-view.php?id=<?php echo $order['id']; ?>" class="order-number">
                            #<?php echo escapeHtml($order['order_number']); ?>
                        </a>
                        <div class="order-shop">
                            <i class="fas fa-store"></i> <?php echo escapeHtml($order['shop_name'] ?? 'N/A'); ?>
                            <span style="color: #6B7A7B; font-size: 12px;">
                                (<?php echo escapeHtml($order['shop_code']); ?>)
                            </span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="order-amount">₹ <?php echo number_format($order['total_amount'], 2); ?></div>
                        <div>
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
                </div>

                <!-- REMOVED: Payment Status, Remaining, Paid, Collection Date, Admin Confirmed -->
                <div class="order-details">
                    <div class="detail-item">
                        <div class="detail-label">Order Date</div>
                        <div class="detail-value"><?php echo formatDate($order['created_at']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Shop Owner</div>
                        <div class="detail-value"><?php echo escapeHtml($order['shop_owner'] ?? 'N/A'); ?></div>
                    </div>
                </div>

                <div class="order-actions">
                    <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view" title="View Order">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 16px;">
                <?php echo $pagination; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>