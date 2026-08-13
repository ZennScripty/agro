<?php
/**
 * SAMRIDHI AGRO - Order Management
 * 
 * This page displays all orders with search, filter,
 * and management capabilities.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: Set page title and include admin header
// ============================================

// Set page title
$pageTitle = 'Order Management';

// Include admin header (which already includes all configs)
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('order.view', 'orders.php');

// Get database instance
$db = getDB();

// ============================================
// PROCESS ACTIONS
// ============================================

// Handle order status update
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['order_id'])) {
    requirePermission('order.update');
    
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitizeInput($_POST['status'] ?? '');
    $csrfToken = $_POST['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } elseif (empty($newStatus) || !array_key_exists($newStatus, ORDER_STATUSES)) {
        setFlashMessage('error', 'Invalid status value.');
    } else {
        // Get order details
        $sql = "SELECT o.*, s.shop_name FROM orders o 
                LEFT JOIN shops s ON o.shop_id = s.id 
                WHERE o.id = ?";
        $order = $db->fetchOne($sql, [$orderId]);
        
        if ($order) {
            $oldStatus = $order['status'];
            
            // Update order status
            $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$newStatus, $orderId]);
            
            // If status is delivered, update inventory
            if ($newStatus === 'delivered') {
                $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                $items = $db->fetchAll($sql, [$orderId]);
                foreach ($items as $item) {
                    $sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                    $db->query($sql, [$item['quantity'], $item['product_id']]);
                }
            }
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'order',
                'Updated order status from ' . $oldStatus . ' to ' . $newStatus . ' for order #' . $order['order_number']
            );
            
            setFlashMessage('success', 'Order status updated successfully.');
        } else {
            setFlashMessage('error', 'Order not found.');
        }
    }
    
    redirect('admin/orders.php');
    exit;
}

// Handle order cancellation
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    requirePermission('order.cancel');
    
    $orderId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        $sql = "SELECT o.*, s.shop_name FROM orders o 
                LEFT JOIN shops s ON o.shop_id = s.id 
                WHERE o.id = ?";
        $order = $db->fetchOne($sql, [$orderId]);
        
        if ($order && $order['status'] === 'pending') {
            $sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$orderId]);
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'order',
                'Cancelled order #' . $order['order_number'] . ' for shop: ' . $order['shop_name']
            );
            
            setFlashMessage('success', 'Order cancelled successfully.');
        } else {
            setFlashMessage('error', 'Order not found or cannot be cancelled.');
        }
    }
    
    redirect('admin/orders.php');
    exit;
}

// ============================================
// GET ORDER LIST
// ============================================

// Search and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

// Search conditions
if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE ? OR s.shop_name LIKE ? OR s.shop_code LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Status filter
if ($status !== 'all') {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

// Date range filter
if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $whereConditions[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count total records
$sql = "SELECT COUNT(*) as total 
        FROM orders o 
        LEFT JOIN shops s ON o.shop_id = s.id 
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalOrders = $result['total'] ?? 0;

// Get order records
$sql = "SELECT o.*, 
        s.shop_name, s.shop_code,
        u.full_name as shop_owner,
        u2.full_name as approved_by_name
        FROM orders o 
        LEFT JOIN shops s ON o.shop_id = s.id 
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN users u2 ON o.approved_by = u2.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$orderList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalOrders / $perPage);
$paginationUrl = 'orders.php?page={page}&search=' . urlencode($search) . '&status=' . $status;
if (!empty($dateFrom)) $paginationUrl .= '&date_from=' . urlencode($dateFrom);
if (!empty($dateTo)) $paginationUrl .= '&date_to=' . urlencode($dateTo);
$pagination = getPagination($totalOrders, $page, $perPage, $paginationUrl);

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// HTML CONTENT (already started by admin_header.php)
// ============================================
?>

<!-- Rest of the HTML content remains exactly the same -->
<style>
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
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 13px;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
    }
    
    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-view:hover { background: #BFDBFE; }
    
    .btn-cancel { background: #FEE2E2; color: #DC2626; }
    .btn-cancel:hover { background: #FECACA; }
    
    .status-dropdown {
        padding: 4px 8px;
        border: 2px solid #E5EDE7;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .status-dropdown:focus {
        outline: none;
        border-color: #16A34A;
    }
    
    .order-amount {
        font-weight: 600;
        color: #14532D;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shopping-cart" style="color: #16A34A;"></i>
            All Orders
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalOrders); ?>)
            </span>
        </h3>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
        <form method="GET" action="" style="flex: 1; min-width: 200px; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by order #, shop..." 
                    value="<?php echo escapeHtml($search); ?>"
                    style="
                        width: 100%;
                        padding: 10px 16px 10px 40px;
                        border: 2px solid #E5EDE7;
                        border-radius: 10px;
                        font-family: 'Inter', sans-serif;
                        font-size: 14px;
                        transition: all 0.3s ease;
                        background: white;
                    "
                >
                <i class="fas fa-search" style="
                    position: absolute;
                    left: 14px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #6B7A7B;
                "></i>
            </div>
            
            <select name="status" style="
                padding: 10px 16px;
                border: 2px solid #E5EDE7;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                background: white;
                cursor: pointer;
            ">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <?php foreach (ORDER_STATUSES as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input 
                type="date" 
                name="date_from" 
                value="<?php echo escapeHtml($dateFrom); ?>"
                style="
                    padding: 10px 16px;
                    border: 2px solid #E5EDE7;
                    border-radius: 10px;
                    font-family: 'Inter', sans-serif;
                    font-size: 14px;
                    background: white;
                "
                placeholder="From Date"
            >
            
            <input 
                type="date" 
                name="date_to" 
                value="<?php echo escapeHtml($dateTo); ?>"
                style="
                    padding: 10px 16px;
                    border: 2px solid #E5EDE7;
                    border-radius: 10px;
                    font-family: 'Inter', sans-serif;
                    font-size: 14px;
                    background: white;
                "
                placeholder="To Date"
            >
            
            <button type="submit" style="
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
            ">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $status !== 'all' || !empty($dateFrom) || !empty($dateTo)): ?>
            <a href="orders.php" style="
                padding: 10px 16px;
                background: #F3F4F6;
                color: #4A5B5D;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                text-decoration: none;
                transition: all 0.3s ease;
            ">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Order Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Shop</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orderList)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #6B7A7B;">
                        <i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                        No orders found
                        <?php if (!empty($search) || $status !== 'all' || !empty($dateFrom) || !empty($dateTo)): ?>
                        <br><span style="font-size: 13px;">Try adjusting your search or filters</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($orderList as $order): ?>
                <tr>
                    <td>
                        <a href="order-view.php?id=<?php echo $order['id']; ?>" style="font-weight: 600; color: #14532D; text-decoration: none;">
                            #<?php echo escapeHtml($order['order_number']); ?>
                        </a>
                    </td>
                    <td>
                        <div>
                            <div style="font-weight: 500;"><?php echo escapeHtml($order['shop_name'] ?? 'N/A'); ?></div>
                            <div style="font-size: 12px; color: #6B7A7B;"><?php echo escapeHtml($order['shop_owner'] ?? ''); ?></div>
                        </div>
                    </td>
                    <td>
                        <span class="order-amount">₹ <?php echo number_format($order['total_amount'], 2); ?></span>
                    </td>
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
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="status-dropdown" onchange="this.form.submit()" style="border-color: <?php 
                                echo match($order['status']) {
                                    'pending' => '#F59E0B',
                                    'confirmed' => '#3B82F6',
                                    'processing' => '#8B5CF6',
                                    'shipped' => '#06B6D4',
                                    'delivered' => '#22C55E',
                                    'cancelled' => '#EF4444',
                                    'returned' => '#F59E0B',
                                    default => '#6B7280'
                                };
                            ?>;">
                                <?php foreach (ORDER_STATUSES as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $order['status'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
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
                    </td>
                    <td>
                        <div style="font-size: 13px;"><?php echo formatDate($order['created_at']); ?></div>
                        <div style="font-size: 11px; color: #6B7A7B;"><?php echo timeAgo($order['created_at']); ?></div>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <a href="order-view.php?id=<?php echo $order['id']; ?>" 
                               class="btn-action btn-view" 
                               title="View Order">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <?php if ($order['status'] === 'pending'): ?>
                            <a href="orders.php?action=cancel&id=<?php echo $order['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                               class="btn-action btn-cancel" 
                               title="Cancel Order"
                               onclick="return confirm('Are you sure you want to cancel this order?')">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="margin-top: 20px;">
        <?php echo $pagination; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>