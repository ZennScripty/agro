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
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
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
    .stat-card.low-stock .stat-number { color: #D97706; }
    .stat-card.out-of-stock .stat-number { color: #DC2626; }
    
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .product-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }
    
    .product-card .product-image {
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
    
    .product-card .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-card .product-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 2px;
        min-height: 40px;
    }
    
    .product-card .product-category {
        font-size: 11px;
        color: #6B7A7B;
        margin-bottom: 6px;
    }
    
    .product-card .product-price {
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
        margin-bottom: 4px;
    }
    
    .product-card .product-stock {
        font-size: 12px;
        margin-bottom: 10px;
    }
    
    .product-card .product-stock .in-stock { color: #16A34A; }
    .product-card .product-stock .low-stock { color: #D97706; }
    .product-card .product-stock .out-of-stock { color: #DC2626; }
    
    .product-card .product-actions {
        margin-top: auto;
        display: flex;
        gap: 6px;
    }
    
    .product-card .product-actions .btn-add-cart {
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
    
    .product-card .product-actions .btn-add-cart:hover {
        background: #052E16;
        transform: translateY(-1px);
    }
    
    .product-card .product-actions .btn-add-cart:disabled {
        background: #E5EDE7;
        color: #6B7A7B;
        cursor: not-allowed;
        transform: none;
    }
    
    .product-card .product-actions .btn-view {
        padding: 8px 12px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .product-card .product-actions .btn-view:hover {
        background: #E5E7EB;
    }
    
    .badge-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .qty-input {
        width: 50px;
        padding: 6px 8px;
        border: 2px solid #E5EDE7;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        text-align: center;
    }
    
    .qty-input:focus {
        outline: none;
        border-color: #16A34A;
    }
    
    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-box" style="color: #16A34A;"></i>
            Available Products
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalProducts); ?>)
            </span>
        </h3>
        <a href="cart.php" class="card-action">
            <i class="fas fa-shopping-bag"></i> View Cart
        </a>
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
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search products..." 
                    value="<?php echo escapeHtml($search); ?>"
                    style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;"
                >
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            
            <select name="category" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer; min-width: 150px;">
                <option value="0" <?php echo $category == 0 ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" style="padding: 10px 24px; background: #14532D; color: white; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $category > 0): ?>
            <a href="products.php" style="padding: 10px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 10px; text-decoration: none;">
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
                        <span style="margin-left: 6px; font-size: 10px; color: #6B7A7B;">
                            SKU: <?php echo escapeHtml($product['sku']); ?>
                        </span>
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
                    
                    <div class="product-actions">
                        <?php if ($product['quantity'] > 0): ?>
                            <input type="number" class="qty-input" id="qty_<?php echo $product['id']; ?>" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                            <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['quantity']; ?>)">
                                <i class="fas fa-cart-plus"></i> Add
                            </button>
                        <?php else: ?>
                            <button class="btn-add-cart" disabled>Out of Stock</button>
                        <?php endif; ?>
                        <a href="product-view.php?id=<?php echo $product['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i>
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

<script>
const csrfToken = '<?php echo $csrfToken; ?>';

function addToCart(productId, productName, price, maxQty) {
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