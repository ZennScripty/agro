<?php

/**
 * SAMRIDHI AGRO - Agent Products
 * 
 * This page displays all available products with search,
 * filter by category, and view capabilities.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Products';

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
// GET PRODUCTS LIST
// ============================================

// Search and filter parameters
$search = $_GET['search'] ?? '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = ["p.status = 'active'"];
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

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get categories for filter dropdown
$sql = "SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name";
$categories = $db->fetchAll($sql);

// Count total records
$sql = "SELECT COUNT(*) as total FROM products p $whereClause";
$result = $db->fetchOne($sql, $params);
$totalProducts = $result['total'] ?? 0;

// Get product records
$sql = "SELECT p.*, c.category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$productList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalProducts / $perPage);
$paginationUrl = 'products.php?page={page}&search=' . urlencode($search);
if ($category > 0) $paginationUrl .= '&category=' . $category;
$pagination = getPagination($totalProducts, $page, $perPage, $paginationUrl);

// ============================================
// GET PRODUCT STATISTICS
// ============================================

$sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$result = $db->fetchOne($sql);
$totalActiveProducts = $result['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= min_quantity AND quantity > 0";
$result = $db->fetchOne($sql);
$lowStockProducts = $result['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= 0";
$result = $db->fetchOne($sql);
$outOfStockProducts = $result['total'] ?? 0;

// Generate CSRF token
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

    .stat-card.low-stock .stat-number {
        color: #D97706;
    }

    .stat-card.out-of-stock .stat-number {
        color: #DC2626;
    }

    .product-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    }

    .product-card .product-image {
        width: 100%;
        height: 180px;
        border-radius: 8px;
        object-fit: cover;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #6B7A7B;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .product-card .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-card .product-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 4px;
    }

    .product-card .product-category {
        font-size: 12px;
        color: #6B7A7B;
        margin-bottom: 8px;
    }

    .product-card .product-price {
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
        margin-bottom: 4px;
    }

    .product-card .product-stock {
        font-size: 13px;
        margin-bottom: 12px;
    }

    .product-card .product-stock .in-stock {
        color: #16A34A;
    }

    .product-card .product-stock .low-stock {
        color: #D97706;
    }

    .product-card .product-stock .out-of-stock {
        color: #DC2626;
    }

    .product-card .product-actions {
        margin-top: auto;
        display: flex;
        gap: 8px;
    }

    .product-card .product-actions .btn-view {
        flex: 1;
        padding: 8px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
    }

    .product-card .product-actions .btn-view:hover {
        background: #052E16;
        transform: translateY(-1px);
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .badge-status {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
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

    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
           .product-card .product-price, .product-card .product-name{
            font-size: 12px;
        }
        .product-card .product-stock,  .product-category {
            font-size: 8px;
        }
    }

    @media (max-width: 480px) {
        .product-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-box" style="color: #16A34A;"></i>
            Available Products
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalProducts); ?>)
            </span>
        </h3>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo number_format($totalActiveProducts); ?></div>
            <div class="stat-label">Active Products</div>
        </div>
        <div class="stat-card low-stock">
            <div class="stat-number"><?php echo number_format($lowStockProducts); ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="stat-card out-of-stock">
            <div class="stat-number"><?php echo number_format($outOfStockProducts); ?></div>
            <div class="stat-label">Out of Stock</div>
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
                    ">
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

            <?php if (!empty($search) || $category > 0): ?>
                <a href="products.php" style="
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

    <!-- Products Grid -->
    <?php if (empty($productList)): ?>
        <div style="text-align: center; padding: 40px; color: #6B7A7B;">
            <i class="fas fa-box-open" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
            <p>No products found</p>
            <?php if (!empty($search) || $category > 0): ?>
                <p style="font-size: 13px;">Try adjusting your search or filters</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($productList as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                            <img src="../uploads/products/<?php echo escapeHtml($product['image']); ?>" alt="<?php echo escapeHtml($product['product_name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-box"></i>
                        <?php endif; ?>
                    </div>

                    <div class="product-name"><?php echo escapeHtml($product['product_name']); ?></div>
                    <div class="product-category">
                        <?php if ($product['category_name']): ?>
                            <i class="fas fa-tag" style="font-size: 10px;"></i> <?php echo escapeHtml($product['category_name']); ?>
                        <?php else: ?>
                            <span style="color: #D1D5DB;">Uncategorized</span>
                        <?php endif; ?>
                        <span style="margin-left: 8px; font-size: 11px; color: #6B7A7B;">
                            SKU: <?php echo escapeHtml($product['sku']); ?>
                        </span>
                    </div>

                    <div class="product-price">₹ <?php echo number_format($product['price'], 2); ?></div>

                    <div class="product-stock">
                        <?php if ($product['quantity'] > $product['min_quantity']): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock</span>
                            <span style="font-size: 12px; color: #6B7A7B; margin-left: 8px;">
                                (<?php echo number_format($product['quantity']); ?> units)
                            </span>
                        <?php elseif ($product['quantity'] > 0): ?>
                            <span class="low-stock"><i class="fas fa-exclamation-triangle"></i> Low Stock</span>
                            <span style="font-size: 12px; color: #6B7A7B; margin-left: 8px;">
                                (<?php echo number_format($product['quantity']); ?> units)
                            </span>
                        <?php else: ?>
                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-actions">
                        <a href="product-view.php?id=<?php echo $product['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px;">
                <?php echo $pagination; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>