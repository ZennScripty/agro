<?php
/**
 * SAMRIDHI AGRO - Product Management
 * 
 * This page displays all products with search, filter,
 * and management capabilities.
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
$pageTitle = 'Product Management';

// Include configuration files (admin_header will handle this)
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('product.view', 'products.php');

// Get database instance
$db = getDB();

// ============================================
// PROCESS ACTIONS
// ============================================

// Handle toggle status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    requirePermission('product.edit');
    
    $productId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get current status
        $sql = "SELECT status, product_name FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$productId]);
        
        if ($product) {
            $newStatus = $product['status'] === 'active' ? 'inactive' : 'active';
            $sql = "UPDATE products SET status = ? WHERE id = ?";
            $db->query($sql, [$newStatus, $productId]);
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'product',
                'Toggled product status to ' . $newStatus . ' for: ' . $product['product_name']
            );
            
            setFlashMessage('success', 'Product status updated successfully.');
        } else {
            setFlashMessage('error', 'Product not found.');
        }
    }
    
    redirect('admin/products.php');
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    requirePermission('product.delete');
    
    $productId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Check if product has orders
        $sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
        $result = $db->fetchOne($sql, [$productId]);
        
        if ($result && $result['count'] > 0) {
            setFlashMessage('error', 'Cannot delete product. It has ' . $result['count'] . ' orders associated with it.');
        } else {
            // Get product name for log
            $sql = "SELECT product_name FROM products WHERE id = ?";
            $product = $db->fetchOne($sql, [$productId]);
            
            if ($product) {
                $sql = "DELETE FROM products WHERE id = ?";
                $db->query($sql, [$productId]);
                
                logActivity(
                    'delete',
                    $_SESSION['user_id'],
                    'product',
                    'Deleted product: ' . $product['product_name']
                );
                
                setFlashMessage('success', 'Product deleted successfully.');
            } else {
                setFlashMessage('error', 'Product not found.');
            }
        }
    }
    
    redirect('admin/products.php');
    exit;
}

// ============================================
// GET PRODUCT LIST
// ============================================

// Search and filter parameters
$search = $_GET['search'] ?? '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

// Search conditions
if (!empty($search)) {
    $whereConditions[] = "(p.product_name LIKE ? OR p.product_slug LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// Category filter
if ($category > 0) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $category;
}

// Status filter
if ($status !== 'all') {
    $whereConditions[] = "p.status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get categories for filter dropdown
$sql = "SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name";
$categories = $db->fetchAll($sql);

// Count total records
$sql = "SELECT COUNT(*) as total FROM products p $whereClause";
$result = $db->fetchOne($sql, $params);
$totalProducts = $result['total'] ?? 0;

// Get product records
$sql = "SELECT p.*, 
        c.category_name,
        u.full_name as created_by_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.created_by = u.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$productList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalProducts / $perPage);
$paginationUrl = 'admin/products.php?page={page}&search=' . urlencode($search) . '&status=' . $status;
if ($category > 0) {
    $paginationUrl .= '&category=' . $category;
}
$pagination = getPagination($totalProducts, $page, $perPage, $paginationUrl);

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .product-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #6B7A7B;
        border: 1px solid #E5EDE7;
        flex-shrink: 0;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
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
    
    .btn-edit { background: #EDE9FE; color: #7C3AED; }
    .btn-edit:hover { background: #DDD6FE; }
    
    .btn-toggle { background: #FEF3C7; color: #D97706; }
    .btn-toggle:hover { background: #FDE68A; }
    
    .btn-delete { background: #FEE2E2; color: #DC2626; }
    .btn-delete:hover { background: #FECACA; }
    
    .stock-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .stock-badge.in-stock { background: #DCFCE7; color: #065F46; }
    .stock-badge.low-stock { background: #FEF3C7; color: #92400E; }
    .stock-badge.out-of-stock { background: #FEE2E2; color: #991B1B; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-box" style="color: #16A34A;"></i>
            All Products
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalProducts); ?>)
            </span>
        </h3>
        <div>
            <a href="product-add.php" class="btn-primary" style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background: linear-gradient(135deg, #14532D, #16A34A);
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            ">
                <i class="fas fa-plus"></i>
                Add Product
            </a>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
        <form method="GET" action="" style="flex: 1; min-width: 200px; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search products..." 
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
            
            <select name="category" style="
                padding: 10px 16px;
                border: 2px solid #E5EDE7;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                background: white;
                cursor: pointer;
                min-width: 150px;
            ">
                <option value="0" <?php echo $category == 0 ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
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
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="out_of_stock" <?php echo $status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
            
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
            
            <?php if (!empty($search) || $category > 0 || $status !== 'all'): ?>
            <a href="admin/products.php" style="
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
    
    <!-- Product Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productList)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #6B7A7B;">
                        <i class="fas fa-box-open" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                        No products found
                        <?php if (!empty($search) || $category > 0 || $status !== 'all'): ?>
                        <br><span style="font-size: 13px;">Try adjusting your search or filters</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($productList as $product): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="product-image">
                                <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                                    <img src="../uploads/products/<?php echo escapeHtml($product['image']); ?>" alt="<?php echo escapeHtml($product['product_name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-box"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #052E16;">
                                    <?php echo escapeHtml($product['product_name']); ?>
                                </div>
                                <?php if (!empty($product['description'])): ?>
                                <div style="font-size: 12px; color: #6B7A7B;">
                                    <?php echo escapeHtml(truncateText($product['description'], 40)); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-size: 13px; font-weight: 600; color: #14532D;">
                            <?php echo escapeHtml($product['sku']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($product['category_name']): ?>
                            <span style="font-size: 13px; background: #F0FDF4; padding: 2px 10px; border-radius: 12px; color: #065F46;">
                                <?php echo escapeHtml($product['category_name']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #6B7A7B; font-size: 13px;">Uncategorized</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #14532D;">
                            ₹ <?php echo number_format($product['price'], 2); ?>
                        </div>
                        <?php if ($product['cost_price'] > 0): ?>
                        <div style="font-size: 11px; color: #6B7A7B;">
                            Cost: ₹ <?php echo number_format($product['cost_price'], 2); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: <?php echo $product['quantity'] <= $product['min_quantity'] ? '#DC2626' : '#052E16'; ?>;">
                            <?php echo number_format($product['quantity']); ?>
                        </div>
                        <?php if ($product['min_quantity'] > 0): ?>
                        <div style="font-size: 11px; color: #6B7A7B;">
                            Min: <?php echo number_format($product['min_quantity']); ?>
                        </div>
                        <?php endif; ?>
                        <?php 
                        $stockStatus = 'in-stock';
                        if ($product['quantity'] <= 0) {
                            $stockStatus = 'out-of-stock';
                        } elseif ($product['quantity'] <= $product['min_quantity']) {
                            $stockStatus = 'low-stock';
                        }
                        ?>
                        <span class="stock-badge <?php echo $stockStatus; ?>">
                            <?php 
                            echo match($stockStatus) {
                                'in-stock' => 'In Stock',
                                'low-stock' => 'Low Stock',
                                'out-of-stock' => 'Out of Stock',
                                default => 'In Stock'
                            };
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $statusColors = [
                            'active' => 'badge-success',
                            'inactive' => 'badge-secondary',
                            'out_of_stock' => 'badge-danger'
                        ];
                        $color = $statusColors[$product['status']] ?? 'badge-secondary';
                        ?>
                        <span class="badge-status <?php echo $color; ?>">
                            <?php echo str_replace('_', ' ', ucfirst($product['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 13px;">
                            <?php echo formatDate($product['created_at']); ?>
                        </div>
                        <?php if ($product['created_by_name']): ?>
                        <div style="font-size: 11px; color: #6B7A7B;">
                            By: <?php echo escapeHtml($product['created_by_name']); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <!-- View -->
                            <a href="product-view.php?id=<?php echo $product['id']; ?>" 
                               class="btn-action btn-view" 
                               title="View Product">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <!-- Edit -->
                            <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                               class="btn-action btn-edit" 
                               title="Edit Product">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <!-- Toggle Status -->
                            <a href="admin/products.php?action=toggle&id=<?php echo $product['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                               class="btn-action btn-toggle" 
                               title="<?php echo $product['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"
                               onclick="return confirm('Are you sure you want to <?php echo $product['status'] === 'active' ? 'deactivate' : 'activate'; ?> this product?')">
                                <i class="fas fa-<?php echo $product['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                            </a>
                            
                            <!-- Delete -->
                            <a href="admin/products.php?action=delete&id=<?php echo $product['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                               class="btn-action btn-delete" 
                               title="Delete Product"
                               onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                <i class="fas fa-trash"></i>
                            </a>
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