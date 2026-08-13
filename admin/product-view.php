<?php
/**
 * SAMRIDHI AGRO - View Product
 * 
 * This page displays detailed information about a specific product.
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
$pageTitle = 'View Product';

// Include configuration files
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
requireLogin();

// Admin has all access, Staff needs specific permission
if (!isAdmin() && !hasPermission('agent.view')) {
    logActivity('unauthorized_access', $_SESSION['user_id'], 'security', 
                'Attempted to access agents.php without permission');
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
    exit;
}

// Check if user has edit permissions for actions
$canEdit = isAdmin() || hasPermission('agent.edit');
$canDelete = isAdmin() || hasPermission('agent.delete');
$canApprove = isAdmin() || hasPermission('agent.approve');
$canCreate = isAdmin() || hasPermission('agent.create');

// Get database instance
$db = getDB();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to product list
if ($productId <= 0) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('admin/products.php');
    exit;
}

// Get product data with category and creator details
$sql = "SELECT p.*, 
        c.category_name,
        u.full_name as created_by_name,
        u2.full_name as updated_by_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.created_by = u.id
        LEFT JOIN users u2 ON p.updated_by = u2.id
        WHERE p.id = ?";
$product = $db->fetchOne($sql, [$productId]);

// If product not found, redirect
if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('admin/products.php');
    exit;
}

// Get product statistics
// Total orders containing this product
$sql = "SELECT COUNT(DISTINCT order_id) as count FROM order_items WHERE product_id = ?";
$result = $db->fetchOne($sql, [$productId]);
$totalOrders = $result['count'] ?? 0;

// Total quantity sold
$sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM order_items WHERE product_id = ?";
$result = $db->fetchOne($sql, [$productId]);
$totalSold = $result['total'] ?? 0;

// Total revenue from this product
$sql = "SELECT COALESCE(SUM(total), 0) as total FROM order_items WHERE product_id = ?";
$result = $db->fetchOne($sql, [$productId]);
$totalRevenue = $result['total'] ?? 0;

// Get recent orders containing this product
$sql = "SELECT oi.*, o.order_number, o.status as order_status, o.created_at as order_date,
        s.shop_name
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN shops s ON o.shop_id = s.id
        WHERE oi.product_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$productId]);

// Generate CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .product-header {
        display: flex;
        align-items: flex-start;
        gap: 24px;
        padding: 24px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .product-image-large {
        width: 200px;
        height: 200px;
        border-radius: 12px;
        object-fit: cover;
        background: #F3F4F6;
        border: 1px solid #E5EDE7;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #6B7A7B;
    }
    
    .product-image-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
    }
    
    .product-info h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
        margin: 0 0 4px 0;
    }
    
    .product-info .product-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #4A5B5D;
        margin-top: 8px;
    }
    
    .product-info .product-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .product-actions {
        margin-left: auto;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-box {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .stat-box .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-box .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
        margin-top: 4px;
    }
    
    .stat-box .stat-icon {
        font-size: 20px;
        margin-bottom: 8px;
        display: block;
    }
    
    .stat-box.stat-orders .stat-number { color: #7C3AED; }
    .stat-box.stat-sold .stat-number { color: #2563EB; }
    .stat-box.stat-revenue .stat-number { color: #16A34A; }
    
    .detail-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
    }
    
    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
    }
    
    .detail-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
        width: 160px;
        flex-shrink: 0;
    }
    
    .detail-value {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
        flex: 1;
    }
    
    .detail-value .badge-status {
        display: inline-block;
        padding: 2px 12px;
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
    
    .stock-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .stock-badge.in-stock { background: #DCFCE7; color: #065F46; }
    .stock-badge.low-stock { background: #FEF3C7; color: #92400E; }
    .stock-badge.out-of-stock { background: #FEE2E2; color: #991B1B; }
    
    .btn-action-sm {
        padding: 4px 12px;
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
    
    .btn-action-sm:hover {
        transform: translateY(-1px);
    }
    
    .btn-action-sm.btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-action-sm.btn-view:hover { background: #BFDBFE; }
    
    .order-status-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .order-status-badge.pending { background: #FEF3C7; color: #92400E; }
    .order-status-badge.confirmed { background: #DBEAFE; color: #1E40AF; }
    .order-status-badge.processing { background: #EDE9FE; color: #5B21B6; }
    .order-status-badge.shipped { background: #DBEAFE; color: #1E40AF; }
    .order-status-badge.delivered { background: #DCFCE7; color: #065F46; }
    .order-status-badge.cancelled { background: #FEE2E2; color: #991B1B; }
    .order-status-badge.returned { background: #FEF3C7; color: #92400E; }
    
    @media (max-width: 768px) {
        .product-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .product-actions {
            margin-left: 0;
            width: 100%;
            justify-content: center;
        }
        
        .detail-row {
            flex-direction: column;
            padding: 12px 0;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 4px;
        }
        
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .product-image-large {
            width: 150px;
            height: 150px;
        }
    }
    
    @media (max-width: 480px) {
        .stat-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    <!-- Product Header -->
    <div class="product-header">
        <div class="product-image-large">
            <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                <img src="../uploads/products/<?php echo escapeHtml($product['image']); ?>" alt="<?php echo escapeHtml($product['product_name']); ?>">
            <?php else: ?>
                <i class="fas fa-box"></i>
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h2><?php echo escapeHtml($product['product_name']); ?></h2>
            <div class="product-meta">
                <span><i class="fas fa-barcode"></i> SKU: <?php echo escapeHtml($product['sku']); ?></span>
                <span><i class="fas fa-folder"></i> <?php echo escapeHtml($product['category_name'] ?? 'Uncategorized'); ?></span>
                <span>
                    <i class="fas fa-circle" style="color: <?php 
                        echo match($product['status']) {
                            'active' => '#16A34A',
                            'inactive' => '#6B7A7B',
                            'out_of_stock' => '#DC2626',
                            default => '#6B7A7B'
                        };
                    ?>; font-size: 10px;"></i>
                    <?php echo str_replace('_', ' ', ucfirst($product['status'])); ?>
                </span>
                <?php if ($product['is_featured']): ?>
                <span><i class="fas fa-star" style="color: #EAB308;"></i> Featured</span>
                <?php endif; ?>
            </div>
            <div class="product-meta" style="margin-top: 8px; font-size: 18px; font-weight: 600; color: #14532D;">
                ₹ <?php echo number_format($product['price'], 2); ?>
                <?php if ($product['cost_price'] > 0): ?>
                <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; text-decoration: line-through;">
                    ₹ <?php echo number_format($product['cost_price'], 2); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="product-actions">
            <a href="admin/product-edit.php?id=<?php echo $product['id']; ?>" class="btn-action-sm btn-view">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="products.php" class="btn-action-sm btn-view">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stat-grid">
        <div class="stat-box stat-orders">
            <span class="stat-icon"><i class="fas fa-shopping-cart" style="color: #7C3AED;"></i></span>
            <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-box stat-sold">
            <span class="stat-icon"><i class="fas fa-boxes" style="color: #2563EB;"></i></span>
            <div class="stat-number"><?php echo number_format($totalSold); ?></div>
            <div class="stat-label">Units Sold</div>
        </div>
        <div class="stat-box stat-revenue">
            <span class="stat-icon"><i class="fas fa-rupee-sign" style="color: #16A34A;"></i></span>
            <div class="stat-number">₹ <?php echo number_format($totalRevenue, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-box" style="border-color: #EDE9FE;">
            <span class="stat-icon"><i class="fas fa-weight" style="color: #7C3AED;"></i></span>
            <div class="stat-number" style="color: #7C3AED; font-size: 20px;">
                <?php echo number_format($product['quantity']); ?> <?php echo escapeHtml($product['unit']); ?>
            </div>
            <div class="stat-label">Current Stock</div>
        </div>
    </div>
    
    <!-- Product Details -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle" style="color: #16A34A;"></i>
            Product Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Product Name</span>
            <span class="detail-value"><?php echo escapeHtml($product['product_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Slug</span>
            <span class="detail-value">
                <span style="font-family: monospace; color: #6B7A7B;">
                    <?php echo escapeHtml($product['product_slug']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">SKU</span>
            <span class="detail-value">
                <span style="font-family: monospace; font-weight: 600; color: #14532D;">
                    <?php echo escapeHtml($product['sku']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Category</span>
            <span class="detail-value">
                <?php if ($product['category_name']): ?>
                    <span style="background: #F0FDF4; padding: 2px 12px; border-radius: 12px; color: #065F46;">
                        <?php echo escapeHtml($product['category_name']); ?>
                    </span>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Uncategorized</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Unit</span>
            <span class="detail-value"><?php echo escapeHtml(ucfirst($product['unit'])); ?></span>
        </div>
        <?php if (!empty($product['description'])): ?>
        <div class="detail-row">
            <span class="detail-label">Description</span>
            <span class="detail-value" style="white-space: pre-wrap;">
                <?php echo escapeHtml($product['description']); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pricing & Stock -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-coins" style="color: #16A34A;"></i>
            Pricing & Stock Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Selling Price</span>
            <span class="detail-value" style="font-weight: 600; color: #14532D;">
                ₹ <?php echo number_format($product['price'], 2); ?>
            </span>
        </div>
        <?php if ($product['cost_price'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Cost Price</span>
            <span class="detail-value" style="color: #6B7A7B;">
                ₹ <?php echo number_format($product['cost_price'], 2); ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Profit Margin</span>
            <span class="detail-value" style="font-weight: 600; color: #16A34A;">
                <?php 
                $margin = $product['price'] - $product['cost_price'];
                $marginPercent = $product['cost_price'] > 0 ? ($margin / $product['cost_price']) * 100 : 0;
                echo '₹ ' . number_format($margin, 2) . ' (' . number_format($marginPercent, 1) . '%)';
                ?>
            </span>
        </div>
        <?php endif; ?>
        <div class="detail-row">
            <span class="detail-label">Current Stock</span>
            <span class="detail-value">
                <span style="font-weight: 600; color: <?php echo $product['quantity'] <= 0 ? '#DC2626' : '#052E16'; ?>;">
                    <?php echo number_format($product['quantity']); ?> <?php echo escapeHtml($product['unit']); ?>
                </span>
                <?php 
                $stockStatus = 'in-stock';
                if ($product['quantity'] <= 0) {
                    $stockStatus = 'out-of-stock';
                } elseif ($product['quantity'] <= $product['min_quantity']) {
                    $stockStatus = 'low-stock';
                }
                ?>
                <span class="stock-badge <?php echo $stockStatus; ?>" style="margin-left: 8px;">
                    <?php 
                    echo match($stockStatus) {
                        'in-stock' => 'In Stock',
                        'low-stock' => 'Low Stock',
                        'out-of-stock' => 'Out of Stock',
                        default => 'In Stock'
                    };
                    ?>
                </span>
            </span>
        </div>
        <?php if ($product['min_quantity'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Minimum Stock Level</span>
            <span class="detail-value">
                <?php echo number_format($product['min_quantity']); ?> <?php echo escapeHtml($product['unit']); ?>
                <span style="font-size: 12px; color: #6B7A7B; margin-left: 8px;">
                    (Low stock alert threshold)
                </span>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Additional Info -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Additional Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
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
                <?php if ($product['is_featured']): ?>
                <span class="badge-status badge-warning" style="margin-left: 8px;">
                    <i class="fas fa-star" style="color: #EAB308;"></i> Featured
                </span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Created</span>
            <span class="detail-value">
                <?php echo formatDate($product['created_at']); ?>
                <?php if ($product['created_by_name']): ?>
                <span style="color: #6B7A7B; font-size: 13px;">
                    by <?php echo escapeHtml($product['created_by_name']); ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Last Updated</span>
            <span class="detail-value">
                <?php echo formatDate($product['updated_at']); ?>
                <?php if ($product['updated_by_name']): ?>
                <span style="color: #6B7A7B; font-size: 13px;">
                    by <?php echo escapeHtml($product['updated_by_name']); ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Product ID</span>
            <span class="detail-value">
                <span style="font-family: monospace; color: #6B7A7B; font-size: 13px;">
                    #<?php echo $product['id']; ?>
                </span>
            </span>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-shopping-cart" style="color: #16A34A;"></i> Recent Orders</span>
            <a href="orders.php?product=<?php echo $product['id']; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none; font-weight: 500;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-shopping-cart" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No orders for this product yet.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Shop</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo escapeHtml($order['order_number']); ?></strong></td>
                            <td><?php echo escapeHtml($order['shop_name'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($order['quantity']); ?></td>
                            <td>₹ <?php echo number_format($order['total'], 2); ?></td>
                            <td>
                                <?php 
                                $statusColors = [
                                    'pending' => 'pending',
                                    'confirmed' => 'confirmed',
                                    'processing' => 'processing',
                                    'shipped' => 'shipped',
                                    'delivered' => 'delivered',
                                    'cancelled' => 'cancelled',
                                    'returned' => 'returned'
                                ];
                                $color = $statusColors[$order['order_status']] ?? 'pending';
                                ?>
                                <span class="order-status-badge <?php echo $color; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['order_date']); ?></td>
                            <td>
                                <a href="order-view.php?id=<?php echo $order['order_id']; ?>" class="btn-action-sm btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>