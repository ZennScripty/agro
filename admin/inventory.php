<?php
/**
 * SAMRIDHI AGRO - Inventory Management
 * 
 * This page displays all products with inventory levels,
 * allows stock adjustments, and tracks inventory changes.
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
$pageTitle = 'Inventory Management';

// Include configuration files (admin_header will include these)
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Require admin login and permission
requireLogin();
requireRole('admin');
requirePermission('inventory.view');

// Get database instance
$db = getDB();

// ============================================
// HANDLE INVENTORY ACTIONS
// ============================================

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    requirePermission('inventory.update');
    
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/inventory.php');
        exit;
    }
    
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $adjustmentType = sanitizeInput($_POST['adjustment_type'] ?? 'adjustment');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if ($productId <= 0) {
        setFlashMessage('error', 'Invalid product.');
        redirect('admin/inventory.php');
        exit;
    }
    
    // Get current product data
    $sql = "SELECT product_name, quantity FROM products WHERE id = ?";
    $product = $db->fetchOne($sql, [$productId]);
    
    if (!$product) {
        setFlashMessage('error', 'Product not found.');
        redirect('admin/inventory.php');
        exit;
    }
    
    $newQuantity = $product['quantity'] + $quantity;
    
    // Update product quantity
    $sql = "UPDATE products SET quantity = ?, updated_at = NOW() WHERE id = ?";
    $db->query($sql, [$newQuantity, $productId]);
    
    // Log inventory change
    $sql = "INSERT INTO inventory_log (
                product_id, quantity_change, previous_quantity, new_quantity,
                reference_type, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $db->query($sql, [
        $productId,
        $quantity,
        $product['quantity'],
        $newQuantity,
        $adjustmentType,
        $notes,
        $_SESSION['user_id']
    ]);
    
    logActivity(
        'update',
        $_SESSION['user_id'],
        'inventory',
        'Adjusted stock for ' . $product['product_name'] . 
        ': ' . ($quantity > 0 ? '+' : '') . $quantity . 
        ' (New: ' . $newQuantity . ')'
    );
    
    setFlashMessage('success', 'Stock updated successfully!');
    redirect('admin/inventory.php');
    exit;
}

// ============================================
// GET INVENTORY LIST
// ============================================

$search = $_GET['search'] ?? '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stockStatus = $_GET['stock_status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

// Search conditions
if (!empty($search)) {
    $whereConditions[] = "(p.product_name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Category filter
if ($category > 0) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $category;
}

// Stock status filter
if ($stockStatus !== 'all') {
    if ($stockStatus === 'in_stock') {
        $whereConditions[] = "p.quantity > p.min_quantity";
    } elseif ($stockStatus === 'low_stock') {
        $whereConditions[] = "p.quantity <= p.min_quantity AND p.quantity > 0";
    } elseif ($stockStatus === 'out_of_stock') {
        $whereConditions[] = "p.quantity <= 0";
    }
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get categories for filter dropdown
$sql = "SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name";
$categories = $db->fetchAll($sql);

// Count total records
$sql = "SELECT COUNT(*) as total FROM products p $whereClause";
$result = $db->fetchOne($sql, $params);
$totalProducts = $result['total'] ?? 0;

// Get product records with inventory info
$sql = "SELECT p.*, 
        c.category_name,
        u.full_name as created_by_name,
        (SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_log WHERE product_id = p.id) as total_in,
        (SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_log WHERE product_id = p.id AND quantity_change < 0) as total_out
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.created_by = u.id
        $whereClause
        ORDER BY p.quantity ASC, p.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$productList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalProducts / $perPage);
$paginationUrl = 'inventory.php?page={page}&search=' . urlencode($search) . '&category=' . $category . '&stock_status=' . $stockStatus;
$pagination = getPagination($totalProducts, $page, $perPage, $paginationUrl);

// Get inventory statistics
$sql = "SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN quantity > min_quantity THEN 1 ELSE 0 END) as in_stock,
        SUM(CASE WHEN quantity <= min_quantity AND quantity > 0 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
        COALESCE(SUM(quantity), 0) as total_quantity,
        COALESCE(SUM(quantity * price), 0) as total_value
        FROM products";
$inventoryStats = $db->fetchOne($sql);

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px 18px;
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
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .stat-card .stat-icon {
        font-size: 18px;
        display: block;
        margin-bottom: 4px;
    }
    
    .stat-card.in-stock .stat-number { color: #16A34A; }
    .stat-card.in-stock .stat-icon { color: #16A34A; }
    .stat-card.low-stock .stat-number { color: #D97706; }
    .stat-card.low-stock .stat-icon { color: #D97706; }
    .stat-card.out-of-stock .stat-number { color: #DC2626; }
    .stat-card.out-of-stock .stat-icon { color: #DC2626; }
    .stat-card.value .stat-number { color: #7C3AED; }
    .stat-card.value .stat-icon { color: #7C3AED; }
    
    /* Product Cards */
    .product-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .product-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-color: #16A34A;
    }
    
    .product-card .product-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .product-card .product-name {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
    }
    
    .product-card .product-sku {
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .product-card .product-category {
        font-size: 12px;
        color: #16A34A;
        background: #DCFCE7;
        padding: 2px 10px;
        border-radius: 12px;
        display: inline-block;
    }
    
    .product-card .stock-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }
    
    .product-card .stock-info .stock-item .stock-label {
        font-size: 10px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .product-card .stock-info .stock-item .stock-value {
        font-size: 14px;
        font-weight: 600;
        color: #052E16;
    }
    
    .product-card .stock-info .stock-item .stock-value.in-stock { color: #16A34A; }
    .product-card .stock-info .stock-item .stock-value.low-stock { color: #D97706; }
    .product-card .stock-info .stock-item .stock-value.out-of-stock { color: #DC2626; }
    
    .product-card .product-actions {
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
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    
    .stock-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .stock-badge.in-stock { background: #DCFCE7; color: #065F46; }
    .stock-badge.low-stock { background: #FEF3C7; color: #92400E; }
    .stock-badge.out-of-stock { background: #FEE2E2; color: #991B1B; }
    
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
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-adjust { background: #EDE9FE; color: #7C3AED; }
    .btn-adjust:hover { background: #DDD6FE; }
    .btn-edit { background: #DBEAFE; color: #2563EB; }
    .btn-edit:hover { background: #BFDBFE; }
    .btn-log { background: #F3F4F6; color: #4A5B5D; }
    .btn-log:hover { background: #E5E7EB; }
    
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
    
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .product-card .stock-info {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .product-card .stock-info {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-warehouse" style="color: #16A34A;"></i>
            Inventory Management
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalProducts); ?> products)
            </span>
        </h3>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card in-stock">
            <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
            <div class="stat-number"><?php echo number_format($inventoryStats['in_stock'] ?? 0); ?></div>
            <div class="stat-label">In Stock</div>
        </div>
        <div class="stat-card low-stock">
            <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="stat-number"><?php echo number_format($inventoryStats['low_stock'] ?? 0); ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="stat-card out-of-stock">
            <span class="stat-icon"><i class="fas fa-times-circle"></i></span>
            <div class="stat-number"><?php echo number_format($inventoryStats['out_of_stock'] ?? 0); ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
        <div class="stat-card value">
            <span class="stat-icon"><i class="fas fa-rupee-sign"></i></span>
            <div class="stat-number">₹ <?php echo number_format($inventoryStats['total_value'] ?? 0, 0); ?></div>
            <div class="stat-label">Total Inventory Value</div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; width: 100%;">
            <div style="flex: 1; min-width: 160px; position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search products..." 
                    value="<?php echo escapeHtml($search); ?>"
                    style="width: 100%; padding: 8px 12px 8px 32px;"
                >
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6B7A7B; font-size: 13px;"></i>
            </div>
            
            <select name="category">
                <option value="0" <?php echo $category == 0 ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="stock_status">
                <option value="all" <?php echo $stockStatus === 'all' ? 'selected' : ''; ?>>All Stock</option>
                <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                <option value="low_stock" <?php echo $stockStatus === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="out_of_stock" <?php echo $stockStatus === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
            
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $category > 0 || $stockStatus !== 'all'): ?>
            <a href="inventory.php" class="btn-clear">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Product List -->
    <?php if (empty($productList)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>No products found in inventory</p>
            <?php if (!empty($search) || $category > 0 || $stockStatus !== 'all'): ?>
                <p style="font-size: 13px;">Try adjusting your search or filters</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($productList as $product): ?>
        <div class="product-card">
            <div class="product-header">
                <div>
                    <div class="product-name">
                        <?php echo escapeHtml($product['product_name']); ?>
                        <?php if ($product['is_featured']): ?>
                            <span style="color: #EAB308; font-size: 12px;">
                                <i class="fas fa-star"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="product-sku">
                        SKU: <?php echo escapeHtml($product['sku']); ?>
                        <span class="product-category" style="margin-left: 8px;">
                            <?php echo escapeHtml($product['category_name'] ?? 'Uncategorized'); ?>
                        </span>
                    </div>
                </div>
                <div>
                    <span class="stock-badge <?php 
                        echo $product['quantity'] > $product['min_quantity'] ? 'in-stock' : 
                            ($product['quantity'] > 0 ? 'low-stock' : 'out-of-stock'); 
                    ?>">
                        <?php 
                        echo $product['quantity'] > $product['min_quantity'] ? 'In Stock' : 
                            ($product['quantity'] > 0 ? 'Low Stock' : 'Out of Stock'); 
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="stock-info">
                <div class="stock-item">
                    <div class="stock-label">Current Stock</div>
                    <div class="stock-value <?php 
                        echo $product['quantity'] > $product['min_quantity'] ? 'in-stock' : 
                            ($product['quantity'] > 0 ? 'low-stock' : 'out-of-stock'); 
                    ?>">
                        <?php echo number_format($product['quantity']); ?>
                    </div>
                </div>
                <div class="stock-item">
                    <div class="stock-label">Min Stock Level</div>
                    <div class="stock-value"><?php echo number_format($product['min_quantity']); ?></div>
                </div>
                <div class="stock-item">
                    <div class="stock-label">Unit Price</div>
                    <div class="stock-value">₹ <?php echo number_format($product['price'], 2); ?></div>
                </div>
                <div class="stock-item">
                    <div class="stock-label">Stock Value</div>
                    <div class="stock-value">₹ <?php echo number_format($product['quantity'] * $product['price'], 0); ?></div>
                </div>
            </div>
            
            <div class="product-actions">
                <button class="btn-action btn-adjust" onclick="openAdjustModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['quantity']; ?>)">
                    <i class="fas fa-edit"></i> Adjust Stock
                </button>
                <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn-action btn-edit">
                    <i class="fas fa-edit"></i> Edit Product
                </a>
                <a href="inventory-log.php?product=<?php echo $product['id']; ?>" class="btn-action btn-log">
                    <i class="fas fa-history"></i> View Log
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

<!-- Adjust Stock Modal -->
<div id="adjustModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 20px; color: #052E16; margin-bottom: 20px;">
            <i class="fas fa-edit" style="color: #7C3AED;"></i> Adjust Stock
        </h3>
        
        <form method="POST" action="" id="adjustForm">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="adjust_stock">
            <input type="hidden" name="product_id" id="adjust_product_id">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Product
                </label>
                <div style="font-size: 16px; font-weight: 600; color: #052E16;" id="adjust_product_name"></div>
                <div style="font-size: 13px; color: #6B7A7B; margin-top: 4px;">
                    Current Stock: <strong id="adjust_current_stock"></strong>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Adjustment Type <span style="color: #DC2626;">*</span>
                </label>
                <select name="adjustment_type" class="form-input" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
                    <option value="adjustment">Manual Adjustment</option>
                    <option value="purchase">Purchase (Add Stock)</option>
                    <option value="sale">Sale (Remove Stock)</option>
                    <option value="return">Return (Add Stock)</option>
                    <option value="damage">Damage (Remove Stock)</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Quantity Change <span style="color: #DC2626;">*</span>
                </label>
                <input type="number" name="quantity" id="adjust_quantity" class="form-input" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;" placeholder="+10 or -5" required>
                <div style="font-size: 12px; color: #6B7A7B; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i> Use + for adding stock, - for removing stock
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Notes (Optional)
                </label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Reason for adjustment" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn-action" style="padding: 10px 24px; background: #7C3AED; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save"></i> Update Stock
                </button>
                <button type="button" onclick="closeAdjustModal()" style="padding: 10px 24px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjustModal(productId, productName, currentStock) {
    document.getElementById('adjust_product_id').value = productId;
    document.getElementById('adjust_product_name').textContent = productName;
    document.getElementById('adjust_current_stock').textContent = currentStock;
    document.getElementById('adjust_quantity').value = '';
    document.getElementById('adjust_quantity').focus();
    document.getElementById('adjustModal').style.display = 'flex';
}

function closeAdjustModal() {
    document.getElementById('adjustModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('adjustModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdjustModal();
    }
});

// Auto-focus on quantity input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('adjust_quantity').addEventListener('focus', function() {
        this.select();
    });
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>