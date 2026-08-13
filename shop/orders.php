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
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 10px 14px;
        text-align: center;
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
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.pending .stat-number { color: #F59E0B; }
    .stat-card.processing .stat-number { color: #8B5CF6; }
    .stat-card.delivered .stat-number { color: #16A34A; }
    .stat-card.cancelled .stat-number { color: #DC2626; }
    .stat-card.revenue .stat-number { color: #7C3AED; font-size: 16px; }
    
    .order-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }
    
    .order-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .order-card .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .order-card .order-number {
        font-weight: 600;
        color: #052E16;
        font-size: 16px;
    }
    
    .order-card .order-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
    }
    
    .order-card .order-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }
    
    .order-card .order-details .detail-item .detail-label {
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .order-card .order-details .detail-item .detail-value {
        font-size: 13px;
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
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }
    
    .btn-action {
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
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-cancel { background: #FEE2E2; color: #DC2626; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shopping-cart" style="color: #16A34A;"></i>
            My Orders
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalOrders); ?>)
            </span>
        </h3>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo number_format($orderStats['total'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo number_format($orderStats['pending'] ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card processing">
            <div class="stat-number"><?php echo number_format(($orderStats['processing'] ?? 0) + ($orderStats['confirmed'] ?? 0) + ($orderStats['shipped'] ?? 0)); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card delivered">
            <div class="stat-number"><?php echo number_format($orderStats['delivered'] ?? 0); ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-number"><?php echo number_format($orderStats['cancelled'] ?? 0); ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-number">₹ <?php echo number_format($orderStats['total_revenue'] ?? 0, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input type="text" name="search" placeholder="Search by order number..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            
            <select name="status" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            
            <button type="submit" style="padding: 10px 24px; background: #14532D; color: white; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $status !== 'all'): ?>
            <a href="orders.php" style="padding: 10px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 10px; text-decoration: none;">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Orders List -->
    <?php if (empty($orderList)): ?>
        <div style="text-align: center; padding: 40px; color: #6B7A7B;">
            <i class="fas fa-inbox" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
            <p>No orders found</p>
        </div>
    <?php else: ?>
        <?php foreach ($orderList as $order): ?>
        <div class="order-card">
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
            
            <div class="order-details">
                <?php if ($order['payment_status']): ?>
                <div class="detail-item">
                    <div class="detail-label">Payment Status</div>
                    <div class="detail-value">
                        <?php 
                        $paymentColors = [
                            'pending' => 'badge-warning',
                            'paid' => 'badge-success',
                            'failed' => 'badge-danger',
                            'refunded' => 'badge-info'
                        ];
                        $pColor = $paymentColors[$order['payment_status']] ?? 'badge-secondary';
                        ?>
                        <span class="badge-status <?php echo $pColor; ?>">
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
                <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <?php if ($order['status'] === 'pending'): ?>
                <a href="orders.php?action=cancel&id=<?php echo $order['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                   class="btn-action btn-cancel" 
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