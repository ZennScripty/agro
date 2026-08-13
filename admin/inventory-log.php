<?php
/**
 * SAMRIDHI AGRO - Inventory Log
 * 
 * This page displays detailed inventory history for a specific product.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: All PHP logic FIRST (no HTML output)
// ============================================

// Set page title
$pageTitle = 'Inventory Log';

 require_once __DIR__ . '/../includes/admin_header.php'; 


// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('inventory.view', 'inventory-log.php');

// Get database instance
$db = getDB();

// Get product ID from URL
$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// If no product ID, redirect to inventory
if ($productId <= 0) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('admin/inventory.php');
    exit;
}

// Get product details
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";
$product = $db->fetchOne($sql, [$productId]);

// If product not found, redirect
if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('admin/inventory.php');
    exit;
}

// ============================================
// GET INVENTORY LOGS
// ============================================

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = ["product_id = ?"];
$params = [$productId];

if (!empty($search)) {
    $whereConditions[] = "(notes LIKE ? OR reference_type LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if ($type !== 'all') {
    $whereConditions[] = "reference_type = ?";
    $params[] = $type;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total records
$sql = "SELECT COUNT(*) as total FROM inventory_log $whereClause";
$result = $db->fetchOne($sql, $params);
$totalLogs = $result['total'] ?? 0;

// Get logs
$sql = "SELECT il.*, u.full_name as user_name
        FROM inventory_log il
        LEFT JOIN users u ON il.created_by = u.id
        $whereClause
        ORDER BY il.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$logList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalLogs / $perPage);
$paginationUrl = 'inventory-log.php?product=' . $productId . '&page={page}&search=' . urlencode($search) . '&type=' . $type;
$pagination = getPagination($totalLogs, $page, $perPage, $paginationUrl);

// Get statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as total_in,
        SUM(CASE WHEN quantity_change < 0 THEN quantity_change ELSE 0 END) as total_out,
        COUNT(CASE WHEN quantity_change > 0 THEN 1 END) as in_count,
        COUNT(CASE WHEN quantity_change < 0 THEN 1 END) as out_count
        FROM inventory_log 
        WHERE product_id = ?";
$stats = $db->fetchOne($sql, [$productId]);

// Get summary by type
$sql = "SELECT 
        reference_type,
        COUNT(*) as count,
        SUM(quantity_change) as total_change
        FROM inventory_log 
        WHERE product_id = ?
        GROUP BY reference_type
        ORDER BY count DESC";
$typeSummary = $db->fetchAll($sql, [$productId]);

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 12px 16px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.in .stat-number { color: #16A34A; }
    .stat-card.in .stat-icon { color: #16A34A; }
    .stat-card.out .stat-number { color: #DC2626; }
    .stat-card.out .stat-icon { color: #DC2626; }
    
    /* Product Header */
    .product-header {
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid #E5EDE7;
    }
    
    .product-header .product-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
    }
    
    .product-header .product-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-size: 14px;
        color: #4A5B5D;
        margin-top: 4px;
    }
    
    .product-header .product-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .product-header .current-stock {
        margin-top: 8px;
        font-size: 15px;
    }
    
    .product-header .current-stock .stock-value {
        font-weight: 700;
        font-size: 18px;
    }
    
    .product-header .current-stock .stock-value.in-stock { color: #16A34A; }
    .product-header .current-stock .stock-value.low-stock { color: #D97706; }
    .product-header .current-stock .stock-value.out-of-stock { color: #DC2626; }
    
    /* Log Items */
    .log-item {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .log-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-color: #16A34A;
    }
    
    .log-item .log-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 14px;
    }
    
    .log-item .log-icon.in {
        background: #DCFCE7;
        color: #16A34A;
    }
    
    .log-item .log-icon.out {
        background: #FEE2E2;
        color: #DC2626;
    }
    
    .log-item .log-content {
        flex: 1;
        min-width: 150px;
    }
    
    .log-item .log-content .log-text {
        font-size: 14px;
        color: #052E16;
    }
    
    .log-item .log-content .log-text .change {
        font-weight: 700;
    }
    
    .log-item .log-content .log-text .change.positive {
        color: #16A34A;
    }
    
    .log-item .log-content .log-text .change.negative {
        color: #DC2626;
    }
    
    .log-item .log-content .log-meta {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .log-item .log-content .log-meta span {
        margin-right: 12px;
    }
    
    .log-item .log-amount {
        text-align: right;
        flex-shrink: 0;
    }
    
    .log-item .log-amount .old-stock {
        font-size: 12px;
        color: #6B7A7B;
        text-decoration: line-through;
    }
    
    .log-item .log-amount .new-stock {
        font-size: 16px;
        font-weight: 700;
        color: #052E16;
    }
    
    .badge-type {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-type.purchase { background: #DCFCE7; color: #065F46; }
    .badge-type.sale { background: #FEE2E2; color: #991B1B; }
    .badge-type.adjustment { background: #FEF3C7; color: #92400E; }
    .badge-type.return { background: #DBEAFE; color: #1E40AF; }
    .badge-type.damage { background: #F3F4F6; color: #6B7A7B; }
    
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 16px;
        padding: 12px 16px;
        background: white;
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
    
    .btn-back {
        padding: 6px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: #E5E7EB;
    }
    
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .log-item {
            flex-direction: column;
            align-items: stretch;
        }
        .log-item .log-amount {
            text-align: left;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history" style="color: #16A34A;"></i>
            Inventory Log
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalLogs); ?> records)
            </span>
        </h3>
        <a href="inventory.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
    </div>
    
    <!-- Product Header -->
    <div class="product-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
            <div>
                <div class="product-name">
                    <?php echo escapeHtml($product['product_name']); ?>
                    <?php if ($product['is_featured']): ?>
                        <span style="color: #EAB308; font-size: 14px;">
                            <i class="fas fa-star"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="product-meta">
                    <span><i class="fas fa-barcode"></i> SKU: <?php echo escapeHtml($product['sku']); ?></span>
                    <span><i class="fas fa-tag"></i> <?php echo escapeHtml($product['category_name'] ?? 'Uncategorized'); ?></span>
                    <span><i class="fas fa-rupee-sign"></i> ₹ <?php echo number_format($product['price'], 2); ?></span>
                </div>
            </div>
            <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn-back" style="background: #DBEAFE; color: #2563EB;">
                <i class="fas fa-edit"></i> Edit Product
            </a>
        </div>
        <div class="current-stock">
            Current Stock: 
            <span class="stock-value <?php 
                echo $product['quantity'] > $product['min_quantity'] ? 'in-stock' : 
                    ($product['quantity'] > 0 ? 'low-stock' : 'out-of-stock'); 
            ?>">
                <?php echo number_format($product['quantity']); ?>
            </span>
            <?php if ($product['min_quantity'] > 0): ?>
                <span style="font-size: 13px; color: #6B7A7B; margin-left: 8px;">
                    (Min: <?php echo number_format($product['min_quantity']); ?>)
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
            <div class="stat-label">Total Adjustments</div>
        </div>
        <div class="stat-card in">
            <span class="stat-icon"><i class="fas fa-arrow-up"></i></span>
            <div class="stat-number">+<?php echo number_format($stats['total_in'] ?? 0); ?></div>
            <div class="stat-label">Stock Added (<?php echo $stats['in_count'] ?? 0; ?> times)</div>
        </div>
        <div class="stat-card out">
            <span class="stat-icon"><i class="fas fa-arrow-down"></i></span>
            <div class="stat-number"><?php echo number_format($stats['total_out'] ?? 0); ?></div>
            <div class="stat-label">Stock Removed (<?php echo $stats['out_count'] ?? 0; ?> times)</div>
        </div>
        <div class="stat-card" style="border-color: #EDE9FE;">
            <div class="stat-number" style="color: #7C3AED; font-size: 18px;">
                <?php echo number_format($stats['total'] ?? 0); ?>
            </div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>
    
    <!-- Type Summary -->
    <?php if (!empty($typeSummary)): ?>
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
        <?php foreach ($typeSummary as $item): ?>
        <span style="background: #F7FCF7; border-radius: 20px; padding: 4px 14px; font-size: 13px; border: 1px solid #E5EDE7;">
            <strong><?php echo ucfirst($item['reference_type']); ?></strong>
            : <?php echo $item['count']; ?> transactions
            <?php if ($item['total_change'] != 0): ?>
                (<?php echo $item['total_change'] > 0 ? '+' : ''; ?><?php echo $item['total_change']; ?>)
            <?php endif; ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Search and Filter -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; width: 100%;">
            <input type="hidden" name="product" value="<?php echo $productId; ?>">
            
            <div style="flex: 1; min-width: 160px; position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search notes..." 
                    value="<?php echo escapeHtml($search); ?>"
                    style="width: 100%; padding: 8px 12px 8px 32px;"
                >
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6B7A7B; font-size: 13px;"></i>
            </div>
            
            <select name="type">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="adjustment" <?php echo $type === 'adjustment' ? 'selected' : ''; ?>>Manual Adjustment</option>
                <option value="purchase" <?php echo $type === 'purchase' ? 'selected' : ''; ?>>Purchase</option>
                <option value="sale" <?php echo $type === 'sale' ? 'selected' : ''; ?>>Sale</option>
                <option value="return" <?php echo $type === 'return' ? 'selected' : ''; ?>>Return</option>
                <option value="damage" <?php echo $type === 'damage' ? 'selected' : ''; ?>>Damage</option>
            </select>
            
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $type !== 'all'): ?>
            <a href="inventory-log.php?product=<?php echo $productId; ?>" class="btn-clear">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Log List -->
    <?php if (empty($logList)): ?>
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <p>No inventory logs found</p>
            <?php if (!empty($search) || $type !== 'all'): ?>
                <p style="font-size: 13px;">Try adjusting your search or filters</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($logList as $log): ?>
        <div class="log-item">
            <div class="log-icon <?php echo $log['quantity_change'] > 0 ? 'in' : 'out'; ?>">
                <i class="fas fa-<?php echo $log['quantity_change'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
            </div>
            
            <div class="log-content">
                <div class="log-text">
                    <span class="change <?php echo $log['quantity_change'] > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $log['quantity_change'] > 0 ? '+' : ''; ?><?php echo $log['quantity_change']; ?>
                    </span>
                    units 
                    <span class="badge-type <?php echo $log['reference_type']; ?>">
                        <?php echo ucfirst($log['reference_type']); ?>
                    </span>
                    <?php if (!empty($log['notes'])): ?>
                        <span style="font-size: 13px; color: #6B7A7B; margin-left: 4px;">
                            <i class="fas fa-sticky-note"></i> <?php echo escapeHtml($log['notes']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="log-meta">
                    <span><i class="fas fa-user"></i> <?php echo escapeHtml($log['user_name'] ?? 'System'); ?></span>
                    <span><i class="far fa-clock"></i> <?php echo formatDate($log['created_at']); ?></span>
                    <span><i class="far fa-calendar"></i> <?php echo timeAgo($log['created_at']); ?></span>
                </div>
            </div>
            
            <div class="log-amount">
                <div class="old-stock"><?php echo number_format($log['previous_quantity']); ?></div>
                <div class="new-stock">→ <?php echo number_format($log['new_quantity']); ?></div>
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

<?php require_once '../includes/admin_footer.php'; ?>