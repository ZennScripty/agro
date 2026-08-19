<?php

/**
 * SAMRIDHI AGRO - Shop Products
 * 
 * This page displays all available products with search,
 * filter by category, and add to cart capabilities.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Products';

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
// GET PRODUCTS LIST
// ============================================

$search = $_GET['search'] ?? '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; // Products per page (grid view)
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.product_name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

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

// Get product statistics
$sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$result = $db->fetchOne($sql);
$totalActiveProducts = $result['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= min_quantity AND quantity > 0";
$result = $db->fetchOne($sql);
$lowStockProducts = $result['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= 0";
$result = $db->fetchOne($sql);
$outOfStockProducts = $result['total'] ?? 0;

// CSRF token for cart actions
$csrfToken = generateCsrfToken();
?>

<style>
    /* ===== PRODUCTS PAGE STYLES ===== */

    /* Stats Grid */
    .prod-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    .prod-stat-card {
        background: white;
        border: 1px solid rgba(20, 83, 45, 0.08);
        border-radius: 10px;
        padding: 10px 14px;
        text-align: center;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .prod-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(5, 46, 22, 0.10);
    }

    .prod-stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
    }

    .prod-stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }

    .prod-stat-card.total .stat-number {
        color: #14532D;
    }

    .prod-stat-card.low-stock .stat-number {
        color: #D97706;
    }

    .prod-stat-card.out-of-stock .stat-number {
        color: #DC2626;
    }

    /* Product Grid */
    .prod-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }

    .prod-card {
        border: 1px solid rgba(20, 83, 45, 0.07);
        border-radius: 12px;
        padding: 14px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        cursor: pointer;
        position: relative;
        background: linear-gradient(59deg, #d4d1c924 0%, rgb(253 253 165 / 34%) 100%, rgba(189, 188, 184, 0.18) 49%);
    }

    .prod-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.12);
    }

    .prod-card .product-image {
        width: 100%;
        height: 160px;
        border-radius: 8px;
        object-fit: cover;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #6B7A7B;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .prod-card .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .prod-card .product-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 2px;
        min-height: 40px;
        line-height: 1.3;
    }

    .prod-card .product-category {
        font-size: 11px;
        color: #6B7A7B;
        margin-bottom: 6px;
    }

    .prod-card .product-price {
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
        margin-bottom: 4px;
    }

    .prod-card .product-stock {
        font-size: 12px;
        margin-bottom: 10px;
    }

    .prod-card .product-stock .in-stock {
        color: #16A34A;
    }

    .prod-card .product-stock .low-stock {
        color: #D97706;
    }

    .prod-card .product-stock .out-of-stock {
        color: #DC2626;
    }

    .prod-card .product-actions {
        margin-top: auto;
        display: flex;
        gap: 6px;
    }

    .prod-card .product-actions .btn-add-cart {
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
        transition: all 0.3s ease;
    }

    .prod-card .product-actions .btn-add-cart:hover {
        background: #052E16;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.25);
    }

    .prod-card .product-actions .btn-add-cart:disabled {
        background: #E5EDE7;
        color: #6B7A7B;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .prod-card .qty-input {
        width: 50px;
        padding: 6px 8px;
        border: 2px solid rgba(20, 83, 45, 0.15);
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        text-align: center;
        transition: border-color 0.3s ease;
    }

    .prod-card .qty-input:focus {
        outline: none;
        border-color: #16A34A;
    }

    /* Badge */
    .prod-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }

    .prod-badge-success {
        background: #DCFCE7;
        color: #065F46;
    }

    .prod-badge-warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .prod-badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* Search & Filter */
    .prod-filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }

    .prod-filter-form .search-wrap {
        flex: 1;
        min-width: 180px;
        position: relative;
    }

    .prod-filter-form .search-wrap input {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border: 2px solid rgba(20, 83, 45, 0.12);
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
        transition: border-color 0.3s ease;
    }

    .prod-filter-form .search-wrap input:focus {
        outline: none;
        border-color: #16A34A;
    }

    .prod-filter-form .search-wrap .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7A7B;
    }

    .prod-filter-form select {
        padding: 10px 16px;
        border: 2px solid rgba(20, 83, 45, 0.12);
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
        cursor: pointer;
        transition: border-color 0.3s ease;
        min-width: 150px;
    }

    .prod-filter-form select:focus {
        outline: none;
        border-color: #16A34A;
    }

    .prod-btn-filter {
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

    .prod-btn-filter:hover {
        background: #0B2B17;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(5, 46, 22, 0.25);
    }

    .prod-btn-clear {
        padding: 10px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .prod-btn-clear:hover {
        background: #E5E7EB;
        transform: translateY(-2px);
    }

    /* Empty State */
    .prod-empty-state {
        text-align: center;
        padding: 40px;
        color: #6B7A7B;
    }

    .prod-empty-state .empty-icon {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: #D1D5DB;
    }

    .prod-empty-state .sub-text {
        font-size: 13px;
        color: #6B7A7B;
    }

    /* ===== PAGINATION ===== */
    .prod-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 24px;
        flex-wrap: wrap;
        padding: 8px 0;
    }

    .prod-pagination .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid rgba(20, 83, 45, 0.12);
        background: white;
        color: #4A5B5D;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.25s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .prod-pagination .page-link:hover {
        background: #F0FDF4;
        border-color: #16A34A;
        color: #14532D;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5, 46, 22, 0.10);
    }

    .prod-pagination .page-link.active {
        background: #14532D;
        border-color: #14532D;
        color: white;
        box-shadow: 0 4px 12px rgba(5, 46, 22, 0.20);
    }

    .prod-pagination .page-link.active:hover {
        background: #0B2B17;
        border-color: #0B2B17;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(5, 46, 22, 0.25);
    }

    .prod-pagination .page-link.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }

    .prod-pagination .page-link .page-dots {
        color: #6B7A7B;
        letter-spacing: 1px;
    }

    .prod-pagination .page-info {
        font-size: 13px;
        color: #6B7A7B;
        padding: 0 8px;
    }

    .prod-pagination .page-link .page-icon {
        font-size: 12px;
    }

    /* ===== RESPONSIVE ===== */

    @media (max-width: 1024px) {
        .prod-stat-card .stat-number {
            font-size: 18px;
        }
    }

    @media (max-width: 768px) {
        .prod-stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .prod-stat-card {
            padding: 8px 10px;
        }

        .prod-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }

        .prod-card .product-name {
            font-size: 14px;
            min-height: 36px;
        }

        .prod-card .product-price {
            font-size: 16px;
        }

        .prod-filter-form .search-wrap {
            min-width: 100%;
        }

        .prod-filter-form select {
            flex: 1;
            min-width: 120px;
        }

        .prod-btn-filter,
        .prod-btn-clear {
            padding: 10px 18px;
            font-size: 13px;
        }

        .prod-pagination .page-link {
            min-width: 34px;
            height: 34px;
            font-size: 13px;
            padding: 0 10px;
        }
    }

    @media (max-width: 480px) {
        .prod-stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }

        .prod-stat-card {
            padding: 6px 8px;
            border-radius: 8px;
        }


        .prod-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .prod-card {
            padding: 10px;
            border-radius: 10px;
        }

        .prod-card .product-image {
            height: 120px;
            font-size: 30px;
        }

        .prod-card .product-name {
            font-size: 13px;
            min-height: 32px;
        }

        .prod-card .product-category {
            font-size: 10px;
        }

        .prod-card .product-price {
            font-size: 15px;
        }

        .prod-card .product-stock {
            font-size: 11px;
        }

        .prod-card .product-actions {
            flex-wrap: wrap;
        }

        .prod-card .product-actions .qty-input {
            width: 42px;
            padding: 4px 6px;
            font-size: 12px;
        }

        .prod-card .product-actions .btn-add-cart {
            font-size: 12px;
            padding: 6px 8px;
            flex: 1;
            min-width: 60px;
        }

        .prod-filter-form {
            flex-direction: column;
            gap: 10px;
        }

        .prod-filter-form .search-wrap {
            min-width: 100%;
        }

        .prod-filter-form .search-wrap input {
            padding: 8px 14px 8px 36px;
            font-size: 13px;
        }

        .prod-filter-form select {
            padding: 8px 14px;
            font-size: 13px;
            width: 100%;
        }

        .prod-btn-filter,
        .prod-btn-clear {
            padding: 10px;
            font-size: 13px;
            width: 100%;
            text-align: center;
            justify-content: center;
        }

        .prod-filter-form .filter-row {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .prod-filter-form .filter-row select {
            flex: 1;
        }

        .prod-empty-state {
            padding: 30px 20px;
        }

        .prod-empty-state .empty-icon {
            font-size: 36px;
        }

        .prod-pagination {
            gap: 4px;
        }

        .prod-pagination .page-link {
            min-width: 30px;
            height: 30px;
            font-size: 12px;
            padding: 0 8px;
            border-radius: 6px;
        }

        .prod-pagination .page-link .page-icon {
            font-size: 10px;
        }

        .prod-pagination .page-info {
            font-size: 11px;
            padding: 0 4px;
        }
    }
</style>


<div class="content-card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 class="card-title" style="font-family: 'Space Grotesk', sans-serif; font-size: 18px; font-weight: 600; color: #052E16; margin: 0;">
            <i class="fas fa-box" style="color: #16A34A;"></i>
            Available Products
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalProducts); ?>)
            </span>
        </h3>
        <a href="cart.php" class="card-action" style="font-size: 14px; color: #16A34A; text-decoration: none; font-weight: 500; transition: color 0.2s ease;">
            <i class="fas fa-shopping-bag"></i> View Cart
        </a>
    </div>

    <!-- Statistics -->
    <div class="prod-stats-grid">
        <div class="prod-stat-card total">
            <div class="stat-number"><?php echo number_format($totalActiveProducts); ?></div>
            <div class="stat-label">Active Products</div>
        </div>
        <div class="prod-stat-card low-stock">
            <div class="stat-number"><?php echo number_format($lowStockProducts); ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="prod-stat-card out-of-stock">
            <div class="stat-number"><?php echo number_format($outOfStockProducts); ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
</div>
<div>

    <!-- Search and Filter -->
    <form method="GET" action="" class="prod-filter-form">
        <div class="search-wrap">
            <input type="text" name="search" placeholder="Search products..." value="<?php echo escapeHtml($search); ?>">
            <i class="fas fa-search search-icon"></i>
        </div>

        <div class="filter-row">
            <select name="category">
                <option value="0" <?php echo $category == 0 ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="prod-btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>

            <?php if (!empty($search) || $category > 0): ?>
                <a href="products.php" class="prod-btn-clear">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Products Grid -->
    <?php if (empty($productList)): ?>
        <div class="prod-empty-state">
            <i class="fas fa-box-open empty-icon"></i>
            <p>No products found</p>
            <?php if (!empty($search) || $category > 0): ?>
                <p class="sub-text">Try adjusting your search or filters</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="prod-grid">
            <?php foreach ($productList as $product): ?>
                <div class="prod-card" onclick="window.location.href='product-view.php?id=<?php echo $product['id']; ?>'">
                    <div class="product-image">
                        <?php if (!empty($product['image']) && file_exists('../uploads/products/thumbs/' . $product['image'])): ?>
                            <!-- Thumbnail image -->
                            <img src="../uploads/products/thumbs/<?php echo escapeHtml($product['image']); ?>" alt="<?php echo escapeHtml($product['product_name']); ?>">
                        <?php elseif (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                            <!-- Fallback to original if thumbnail not found -->
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
                    </div>

                    <div class="product-price">₹ <?php echo number_format($product['price'], 2); ?></div>

                    <div class="product-stock">
                        <?php if ($product['quantity'] > $product['min_quantity']): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock</span>
                            <span style="font-size: 11px; color: #6B7A7B; margin-left: 4px;">
                                (<?php echo number_format($product['quantity']); ?> units)
                            </span>
                        <?php elseif ($product['quantity'] > 0): ?>
                            <span class="low-stock"><i class="fas fa-exclamation-triangle"></i> Low Stock</span>
                            <span style="font-size: 11px; color: #6B7A7B; margin-left: 4px;">
                                (<?php echo number_format($product['quantity']); ?> units)
                            </span>
                        <?php else: ?>
                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-actions" onclick="event.stopPropagation();">
                        <?php if ($product['quantity'] > 0): ?>
                            <input type="number" class="qty-input" id="qty_<?php echo $product['id']; ?>" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                            <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['quantity']; ?>)">
                                <i class="fas fa-cart-plus"></i> Add
                            </button>
                        <?php else: ?>
                            <button class="btn-add-cart" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="prod-pagination">
                <?php echo $pagination; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    const csrfToken = '<?php echo $csrfToken; ?>';

    function addToCart(productId, productName, price, maxQty) {
        // Stop event propagation to prevent card click
        event.stopPropagation();

        const qtyInput = document.getElementById('qty_' + productId);
        let quantity = parseInt(qtyInput.value) || 1;

        if (quantity < 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Quantity',
                text: 'Please enter a valid quantity (1 or more)'
            });
            return;
        }

        if (quantity > maxQty) {
            Swal.fire({
                icon: 'warning',
                title: 'Not Enough Stock',
                text: 'Only ' + maxQty + ' units available'
            });
            return;
        }

        // Show loading
        Swal.fire({
            title: 'Adding to Cart...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('../shop/cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'product_id': productId,
                    'quantity': quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Added to Cart!',
                        html: `
                    <p><strong>${escapeHtml(productName)}</strong></p>
                    <p>Quantity: ${quantity} | Price: ₹ ${price.toFixed(2)} each</p>
                    <p style="color: #16A34A; font-weight: 600;">Total: ₹ ${(price * quantity).toFixed(2)}</p>
                `,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Update cart count if needed
                        if (data.cart_count) {
                            const cartBadge = document.querySelector('.cart-badge');
                            if (cartBadge) {
                                cartBadge.textContent = data.cart_count;
                                cartBadge.style.display = data.cart_count > 0 ? 'inline' : 'none';
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to add to cart'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.'
                });
                console.error('Error:', error);
            });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>