<?php
/**
 * SAMRIDHI AGRO - Agent Product View
 * 
 * This page displays detailed information about a specific product.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Product Details';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to products list
if ($productId <= 0) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('agent/products.php');
    exit;
}

// Get product data
$sql = "SELECT p.*, c.category_name, u.full_name as created_by_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = ? AND p.status = 'active'";
$product = $db->fetchOne($sql, [$productId]);

// If product not found, redirect
if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('agent/products.php');
    exit;
}

// Generate CSRF token
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
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
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

        
<div class="content-card" style="border:0.5px solid  #16A34A;">
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
                <div class="placeholder">
                    <i class="fas fa-box"></i>
                </div>
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
            
            <div style="margin-top: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="products.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>