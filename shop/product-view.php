<?php
/**
 * SAMRIDHI AGRO - Shop Product View
 * 
 * This page displays detailed information about a specific product.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Product Details';

// Include shop header
require_once __DIR__ . '/../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('shop/products.php');
    exit;
}

// Get product data
$sql = "SELECT p.*, c.category_name, u.full_name as created_by_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = ? AND p.status = 'active'";
$product = $db->fetchOne($sql, [$productId]);

if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('shop/products.php');
    exit;
}

$csrfToken = generateCsrfToken();
?>

<style>
    .product-detail-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .product-detail-image {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
    }
    
    .product-detail-image img {
        max-width: 100%;
        max-height: 400px;
        object-fit: contain;
    }
    
    .product-detail-image .placeholder {
        font-size: 80px;
        color: #D1D5DB;
    }
    
    .product-detail-info {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .product-detail-info .product-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #052E16;
        margin: 0;
    }
    
    .product-detail-info .product-sku {
        font-size: 14px;
        color: #6B7A7B;
    }
    
    .product-detail-info .product-price {
        font-size: 24px;
        font-weight: 700;
        color: #14532D;
    }
    
    .product-detail-info .product-description {
        font-family: 'Inter', sans-serif;
        font-size: 15px;
        color: #4A5B5D;
        line-height: 1.6;
    }
    
    .product-detail-info .product-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 8px;
    }
    
    .product-detail-info .meta-item {
        background: #F7FCF7;
        padding: 12px 16px;
        border-radius: 8px;
    }
    
    .product-detail-info .meta-item .meta-label {
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .product-detail-info .meta-item .meta-value {
        font-weight: 600;
        color: #052E16;
    }
    
    .badge-status {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .qty-input-large {
        width: 80px;
        padding: 10px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        text-align: center;
    }
    
    .qty-input-large:focus {
        outline: none;
        border-color: #16A34A;
    }
    
    .btn-add-cart-large {
        padding: 12px 32px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-add-cart-large:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }
    
    .btn-add-cart-large:disabled {
        background: #E5EDE7;
        color: #6B7A7B;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .btn-back {
        padding: 10px 24px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-back:hover {
        background: #E5E7EB;
    }
    
    @media (max-width: 768px) {
        .product-detail-container {
            grid-template-columns: 1fr;
        }
        .product-detail-image {
            min-height: 200px;
        }
        .product-detail-info .product-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-box" style="color: #16A34A;"></i>
            Product Details
        </h3>
        <a href="products.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
    
    <div class="product-detail-container">
        <!-- Image -->
        <div class="product-detail-image">
            <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                <img src="../uploads/products/<?php echo escapeHtml($product['image']); ?>" alt="<?php echo escapeHtml($product['product_name']); ?>">
            <?php else: ?>
                <div class="placeholder"><i class="fas fa-box"></i></div>
            <?php endif; ?>
        </div>
        
        <!-- Info -->
        <div class="product-detail-info">
            <h1 class="product-name"><?php echo escapeHtml($product['product_name']); ?></h1>
            
            <div class="product-sku">
                <i class="fas fa-barcode"></i> SKU: <?php echo escapeHtml($product['sku']); ?>
                <?php if ($product['category_name']): ?>
                    <span style="margin-left: 16px;">
                        <i class="fas fa-tag"></i> <?php echo escapeHtml($product['category_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="product-price">₹ <?php echo number_format($product['price'], 2); ?></div>
            
            <?php if (!empty($product['description'])): ?>
                <div class="product-description">
                    <?php echo nl2br(escapeHtml($product['description'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="product-meta">
                <div class="meta-item">
                    <div class="meta-label">Stock Status</div>
                    <div class="meta-value">
                        <?php if ($product['quantity'] > $product['min_quantity']): ?>
                            <span class="badge-status badge-success">In Stock</span>
                            <span style="font-size: 13px; color: #6B7A7B; margin-left: 8px;">
                                (<?php echo number_format($product['quantity']); ?> units)
                            </span>
                        <?php elseif ($product['quantity'] > 0): ?>
                            <span class="badge-status badge-warning">Low Stock</span>
                            <span style="font-size: 13px; color: #6B7A7B; margin-left: 8px;">
                                (<?php echo number_format($product['quantity']); ?> units)
                            </span>
                        <?php else: ?>
                            <span class="badge-status badge-danger">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Unit</div>
                    <div class="meta-value"><?php echo escapeHtml(ucfirst($product['unit'])); ?></div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Minimum Stock Level</div>
                    <div class="meta-value"><?php echo number_format($product['min_quantity']); ?> units</div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span class="badge-status badge-success">Active</span>
                        <?php if ($product['is_featured']): ?>
                            <span class="badge-status" style="background: #FEF3C7; color: #92400E;">
                                <i class="fas fa-star" style="color: #EAB308;"></i> Featured
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($product['quantity'] > 0): ?>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 8px;">
                    <input type="number" class="qty-input-large" id="qtyInput" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                    <button class="btn-add-cart-large" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['quantity']; ?>)">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            <?php else: ?>
                <button class="btn-add-cart-large" disabled>
                    <i class="fas fa-times-circle"></i> Out of Stock
                </button>
            <?php endif; ?>
            
            <div style="margin-top: 8px;">
                <a href="products.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo $csrfToken; ?>';

function addToCart(productId, productName, price, maxQty) {
    const qtyInput = document.getElementById('qtyInput');
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