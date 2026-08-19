<?php

/**
 * SAMRIDHI AGRO - Shop Orders
 * 
 * This page displays all orders for the shop with search,
 * filter, and tracking capabilities.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'My Orders';

// Include shop header
require_once __DIR__ . '/../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get shop data
$sql = "SELECT s.* FROM shops s WHERE s.user_id = ?";
$shop = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET ORDERS LIST
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = ["o.shop_id = ?"];
$params = [$shop['id']];

if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE ?)";
    $params[] = '%' . $search . '%';
}

if ($status !== 'all') {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM orders o $whereClause";
$result = $db->fetchOne($sql, $params);
$totalOrders = $result['total'] ?? 0;

// Get orders with item count
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
        (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) as total_items
        FROM orders o 
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$orderList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalOrders / $perPage);
$pagination = getPagination($totalOrders, $page, $perPage, 'orders.php?page={page}&search=' . urlencode($search) . '&status=' . $status);

// Order statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as total_revenue
        FROM orders WHERE shop_id = ?";
$orderStats = $db->fetchOne($sql, [$shop['id']]);

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>

<style>
    /* ===== ORDERS PAGE STYLES ===== */

    /* Stats Grid */
    .orders-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    .orders-stat-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        border: 1px solid rgba(20, 83, 45, 0.11);
        border-radius: 10px;
        padding: 10px 14px;
        text-align: center;
       box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        transition: transform 0.3s ease, box-shadow 0.3s ease;

    }

    .orders-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(154, 235, 188, 0.49);
    }

    .orders-stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
    }

    .orders-stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #6B7A7B;
    }

    .orders-stat-card.total .stat-number {
        color: #14532D;
    }

    .orders-stat-card.pending .stat-number {
        color: #F59E0B;
    }

    .orders-stat-card.processing .stat-number {
        color: #8B5CF6;
    }

    .orders-stat-card.delivered .stat-number {
        color: #16A34A;
    }

    .orders-stat-card.cancelled .stat-number {
        color: #DC2626;
    }

    .orders-stat-card.revenue .stat-number {
        color: #7C3AED;
    }

    /* Order Card */
    .orders-order-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        border: 1px solid rgba(20, 83, 45, 0.29);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 2, 1, 0.23);
    }

    .orders-order-card:hover {
        box-shadow: 0 6px 20px rgba(5, 46, 22, 0.10);
        transform: translateY(-2px);
    }

    .orders-order-card .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
    }

    .orders-order-card .order-number {
        font-weight: 600;
        color: #052E16;
        font-size: 16px;
    }

    .orders-order-card .order-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
    }

    .orders-order-card .order-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .orders-order-card .order-details .detail-item .detail-label {
        font-size: 11px;
        color: #6B7A7B;
    }

    .orders-order-card .order-details .detail-item .detail-value {
        font-size: 13px;
        font-weight: 500;
        color: #052E16;
    }

    .orders-order-card .order-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    /* Badges */
    .orders-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .orders-badge-success {
        background: #DCFCE7;
        color: #065F46;
    }

    .orders-badge-warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .orders-badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .orders-badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .orders-badge-primary {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .orders-badge-secondary {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    /* Buttons */
    .orders-btn-action {
        padding: 5px 12px;
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

    .orders-btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
    }

    .orders-btn-view {
        background: #DBEAFE;
        color: #2563EB;
    }

    .orders-btn-cancel {
        background: #FEE2E2;
        color: #DC2626;
    }

    /* Search & Filter */
    .orders-filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }

    .orders-filter-form .search-wrap {
        flex: 1;
        min-width: 180px;
        position: relative;
    }

    .orders-filter-form .search-wrap input {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border: 2px solid rgba(20, 83, 45, 0.12);
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
        transition: border-color 0.3s ease;
    }

    .orders-filter-form .search-wrap input:focus {
        outline: none;
        border-color: #16A34A;
    }

    .orders-filter-form .search-wrap .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7A7B;
    }

    .orders-filter-form select {
        padding: 10px 16px;
        border: 2px solid rgba(20, 83, 45, 0.12);
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
        cursor: pointer;
        transition: border-color 0.3s ease;
        min-width: 140px;
    }

    .orders-filter-form select:focus {
        outline: none;
        border-color: #16A34A;
    }

    .orders-btn-filter {
        padding: 10px 24px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .orders-btn-filter:hover {
        background: #0B2B17;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(5, 46, 22, 0.25);
    }

    .orders-btn-clear {
        padding: 10px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .orders-btn-clear:hover {
        background: #E5E7EB;
        transform: translateY(-2px);
    }

    /* Empty State */
    .orders-empty-state {
        text-align: center;
        padding: 40px;
        color: #6B7A7B;
    }

    .orders-empty-state .empty-icon {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: #D1D5DB;
    }

    /* ===== RESPONSIVE ===== */

 

    @media (max-width: 768px) {
        .orders-stats-grid {
            grid-template-columns: repeat(2, 1fr);
         
        }

        .orders-stat-card {
            padding: 8px 10px;
        }

        .orders-order-card {
            padding: 14px 16px;
        }

        .orders-order-card .order-number {
            font-size: 14px;
        }

  

        .orders-order-card .order-details {
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }

        .orders-filter-form .search-wrap {
            min-width: 100%;
        }

        .orders-filter-form select {
            flex: 1;
            min-width: 120px;
        }

        .orders-btn-filter,
        .orders-btn-clear {
            padding: 10px 18px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
    

        .orders-stat-card {
            padding: 6px 8px;
            border-radius: 8px;
        }


     

        .orders-order-card {
            padding: 12px 14px;
            border-radius: 10px;
        }

        .orders-order-card .order-header {
            gap: 6px;
        }

        .orders-order-card .order-number {
            font-size: 13px;
        }

        .orders-order-card .order-amount {
            font-size: 15px;
        }

        .orders-order-card .order-details {
            /* grid-template-columns: 1fr 1fr; */
            gap: 4px;
            margin-top: 8px;
            padding-top: 8px;
        }

        .orders-order-card .order-details .detail-item .detail-value {
            font-size: 12px;
        }

        .orders-order-card .order-actions {
            flex-direction: column;
            gap: 6px;
            margin-top: 8px;
            padding-top: 8px;
        }

        .orders-order-card .order-actions .orders-btn-action {
            justify-content: center;
            padding: 8px 12px;
            width: 100%;
        }

        .orders-filter-form {
            flex-direction: column;
            gap: 10px;
        }

        .orders-filter-form .search-wrap {
            min-width: 100%;
        }

        .orders-filter-form .search-wrap input {
            padding: 8px 14px 8px 36px;
            font-size: 13px;
        }

        .orders-filter-form select {
            padding: 8px 14px;
            font-size: 13px;
            width: 100%;
        }

        .orders-btn-filter,
        .orders-btn-clear {
            padding: 10px;
            font-size: 13px;
            width: 100%;
            text-align: center;
            justify-content: center;
        }

        .orders-filter-form .filter-row {
            display: grid;
            gap: 10px;
            width: 100%;
            grid-template-columns: 1fr 1fr;
        }

        .orders-filter-form .filter-row select {
            flex: 1;
        }

        .orders-badge {
            font-size: 10px;
            padding: 2px 8px;
        }

        .orders-empty-state {
            padding: 30px 20px;
        }

        .orders-empty-state .empty-icon {
            font-size: 36px;
        }
    }
</style>

<div class="content-card sdbg" style="padding: 20px 24px; border: 1px solid rgba(20, 83, 45, 0.07); border-radius: 12px; background: white; box-shadow: 0 2px 8px rgba(5, 46, 22, 0.06);">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 class="card-title" style="font-family: 'Space Grotesk', sans-serif; font-size: 18px; font-weight: 600; color: #052E16; margin: 0;">
            <i class="fas fa-shopping-cart" style="color: #16A34A;"></i>
            My Orders
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalOrders); ?>)
            </span>
        </h3>
    </div>

    <!-- Statistics -->
    <div class="orders-stats-grid">
        <div class="orders-stat-card sdbg total">
            <div class="stat-number"><?php echo number_format($orderStats['total'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="orders-stat-card sdbg pending">
            <div class="stat-number"><?php echo number_format($orderStats['pending'] ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="orders-stat-card sdbg processing">
            <div class="stat-number"><?php echo number_format(($orderStats['processing'] ?? 0) + ($orderStats['confirmed'] ?? 0) + ($orderStats['shipped'] ?? 0)); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="orders-stat-card sdbg delivered">
            <div class="stat-number"><?php echo number_format($orderStats['delivered'] ?? 0); ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="orders-stat-card sdbg cancelled">
            <div class="stat-number"><?php echo number_format($orderStats['cancelled'] ?? 0); ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
        <div class="orders-stat-card sdbg revenue">
            <div class="stat-number">₹ <?php echo number_format($orderStats['total_revenue'] ?? 0, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- Search and Filter -->
    <form method="GET" action="" class="orders-filter-form">
        <div class="search-wrap">
            <input type="text" name="search" placeholder="Search by order number..." value="<?php echo escapeHtml($search); ?>">
            <i class="fas fa-search search-icon"></i>
        </div>

        <div class="filter-row">
            <select name="status">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <button type="submit" class="orders-btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>

            <?php if (!empty($search) || $status !== 'all'): ?>
                <a href="orders.php" class="orders-btn-clear">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Orders List -->
    <?php if (empty($orderList)): ?>
        <div class="orders-empty-state">
            <i class="fas fa-inbox empty-icon"></i>
            <p>No orders found</p>
        </div>
    <?php else: ?>
        <?php foreach ($orderList as $order): ?>
            <div class="orders-order-card sdbg">
                <div class="order-header">
                    <div>
                        <div class="order-number">
                            #<?php echo escapeHtml($order['order_number']); ?>
                        </div>
                        <div style="font-size: 13px; color: #6B7A7B; margin-top: 2px;">
                            <i class="far fa-calendar"></i> <?php echo formatDate($order['created_at']); ?>
                            <span style="margin-left: 12px;">
                                <i class="fas fa-box"></i> <?php echo $order['item_count']; ?> items
                            </span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="order-amount">₹ <?php echo number_format($order['total_amount'], 2); ?></div>
                        <div>
                            <?php
                            $statusColors = [
                                'pending' => 'orders-badge-warning',
                                'confirmed' => 'orders-badge-info',
                                'processing' => 'orders-badge-primary',
                                'shipped' => 'orders-badge-info',
                                'delivered' => 'orders-badge-success',
                                'cancelled' => 'orders-badge-danger',
                                'returned' => 'orders-badge-warning'
                            ];
                            $color = $statusColors[$order['status']] ?? 'orders-badge-secondary';
                            ?>
                            <span class="orders-badge btsd  <?php echo $color; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="order-details">
                    <?php if ($order['payment_status']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value">
                                <?php
                                $paymentColors = [
                                    'pending' => 'orders-badge-warning',
                                    'paid' => 'orders-badge-success',
                                    'failed' => 'orders-badge-danger',
                                    'refunded' => 'orders-badge-info'
                                ];
                                $pColor = $paymentColors[$order['payment_status']] ?? 'orders-badge-secondary';
                                ?>
                                <span class="orders-badge btsd  <?php echo $pColor; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['payment_method']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo ucfirst($order['payment_method']); ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <div class="detail-label">Total Items</div>
                        <div class="detail-value"><?php echo $order['total_items']; ?></div>
                    </div>
                    <?php if ($order['delivery_notes']): ?>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">Delivery Notes</div>
                            <div class="detail-value" style="font-size: 12px; color: #6B7A7B;"><?php echo escapeHtml($order['delivery_notes']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-actions">
                    <a href="order-view.php?id=<?php echo $order['id']; ?>" class="orders-btn-action orders-btn-view btsd">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    <?php if ($order['status'] === 'pending'): ?>
                        <a href="orders.php?action=cancel&id=<?php echo $order['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                            class="orders-btn-action orders-btn-cancel btsd"
                            onclick="return confirm('Are you sure you want to cancel this order?')">
                            <i class="fas fa-times"></i> Cancel Order
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px;"><?php echo $pagination; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>