<?php
/**
 * SAMRIDHI AGRO - View Shop
 * 
 * This page displays detailed information about a specific shop.
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
$pageTitle = 'View Shop';

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
requirePermissionOrAdmin('shop.view', 'shop-view.php');

// Get database instance
$db = getDB();

// Get shop ID from URL
$shopId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to shop list
if ($shopId <= 0) {
    setFlashMessage('error', 'Invalid shop ID.');
    redirect('admin/shops.php');
    exit;
}

// Get shop data with user and agent details
$sql = "SELECT s.*, u.full_name as owner_name, u.username, u.email, u.phone, u.status as user_status,
        u.created_at as user_created_at, u.last_login, u.last_ip,
        u2.full_name as approved_by_name,
        a.full_name as agent_name, a.agent_code
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN users u2 ON s.approved_by = u2.id
        LEFT JOIN (
            SELECT a.id, u.full_name, a.agent_code 
            FROM agents a 
            JOIN users u ON a.user_id = u.id
        ) a ON s.agent_id = a.id
        WHERE s.id = ?";
$shop = $db->fetchOne($sql, [$shopId]);

// If shop not found, redirect
if (!$shop) {
    setFlashMessage('error', 'Shop not found.');
    redirect('shops.php');
    exit;
}

// Get shop statistics
// Total orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE shop_id = ?";
$result = $db->fetchOne($sql, [$shopId]);
$totalOrders = $result['count'] ?? 0;

// Total revenue
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM orders 
        WHERE shop_id = ? AND status = 'delivered'";
$result = $db->fetchOne($sql, [$shopId]);
$totalRevenue = $result['total'] ?? 0;

// Order status breakdown
$sql = "SELECT status, COUNT(*) as count 
        FROM orders 
        WHERE shop_id = ? 
        GROUP BY status";
$orderStatuses = $db->fetchAll($sql, [$shopId]);

// Recent orders
$sql = "SELECT id, order_number, total_amount, status, created_at 
        FROM orders 
        WHERE shop_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$shopId]);

// Generate CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .profile-header {
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 24px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
    }
    
    .profile-avatar.pending { background: #F59E0B; }
    .profile-avatar.approved { background: #16A34A; }
    .profile-avatar.rejected { background: #DC2626; }
    .profile-avatar.suspended { background: #6B7A7B; }
    
    .profile-info h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
        margin: 0 0 4px 0;
    }
    
    .profile-info .profile-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .profile-info .profile-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .profile-actions {
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
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-actions {
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
    }
    
    @media (max-width: 480px) {
        .stat-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar <?php echo $shop['status']; ?>">
            <?php echo strtoupper(substr($shop['shop_name'], 0, 2)); ?>
        </div>
        <div class="profile-info">
            <h2><?php echo escapeHtml($shop['shop_name']); ?></h2>
            <div class="profile-meta">
                <span><i class="fas fa-id-badge"></i> <?php echo escapeHtml($shop['shop_code']); ?></span>
                <span><i class="fas fa-user"></i> <?php echo escapeHtml($shop['owner_name']); ?></span>
                <span><i class="fas fa-envelope"></i> <?php echo escapeHtml($shop['email']); ?></span>
                <span>
                    <i class="fas fa-circle" style="color: <?php 
                        echo match($shop['status']) {
                            'pending' => '#F59E0B',
                            'approved' => '#16A34A',
                            'rejected' => '#DC2626',
                            'suspended' => '#6B7A7B',
                            default => '#6B7A7B'
                        };
                    ?>; font-size: 10px;"></i>
                    <?php echo ucfirst($shop['status']); ?>
                </span>
            </div>
        </div>
        <div class="profile-actions">
            <a href="shop-edit.php?id=<?php echo $shop['id']; ?>" class="btn-action-sm btn-view">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="shops.php" class="btn-action-sm btn-view">
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
        <div class="stat-box stat-revenue">
            <span class="stat-icon"><i class="fas fa-rupee-sign" style="color: #16A34A;"></i></span>
            <div class="stat-number">₹ <?php echo number_format($totalRevenue, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-box" style="border-color: #EDE9FE;">
            <span class="stat-icon"><i class="fas fa-tag" style="color: #7C3AED;"></i></span>
            <div class="stat-number" style="color: #7C3AED;">
                <?php 
                $typeLabels = [
                    'retail' => 'Retail',
                    'wholesale' => 'Wholesale',
                    'both' => 'Both'
                ];
                echo $typeLabels[$shop['shop_type']] ?? $shop['shop_type'];
                ?>
            </div>
            <div class="stat-label">Shop Type</div>
        </div>
        <div class="stat-box" style="border-color: #DBEAFE;">
            <span class="stat-icon"><i class="fas fa-user-tie" style="color: #2563EB;"></i></span>
            <div class="stat-number" style="color: #2563EB; font-size: 18px;">
                <?php echo $shop['agent_name'] ? escapeHtml($shop['agent_name']) : 'Not Assigned'; ?>
            </div>
            <div class="stat-label">Assigned Agent</div>
        </div>
    </div>
    
    <!-- Shop Details -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-store" style="color: #16A34A;"></i>
            Shop Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Name</span>
            <span class="detail-value"><?php echo escapeHtml($shop['shop_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Code</span>
            <span class="detail-value">
                <span style="font-family: monospace; font-weight: 600; color: #14532D;">
                    <?php echo escapeHtml($shop['shop_code']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Type</span>
            <span class="detail-value">
                <span style="background: #F0FDF4; padding: 2px 12px; border-radius: 12px; color: #065F46;">
                    <?php echo $typeLabels[$shop['shop_type']] ?? $shop['shop_type']; ?>
                </span>
            </span>
        </div>
        <?php if (!empty($shop['gst_number'])): ?>
        <div class="detail-row">
            <span class="detail-label">GST Number</span>
            <span class="detail-value"><?php echo escapeHtml($shop['gst_number']); ?></span>
        </div>
        <?php endif; ?>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                <?php 
                $statusColors = [
                    'pending' => 'badge-warning',
                    'approved' => 'badge-success',
                    'rejected' => 'badge-danger',
                    'suspended' => 'badge-secondary'
                ];
                $color = $statusColors[$shop['status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $color; ?>">
                    <?php echo ucfirst($shop['status']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Assigned Agent</span>
            <span class="detail-value">
                <?php if ($shop['agent_name']): ?>
                    <a href="agent-view.php?id=<?php echo $shop['agent_id']; ?>" style="color: #16A34A; text-decoration: none;">
                        <?php echo escapeHtml($shop['agent_name']); ?> 
                        <span style="color: #6B7A7B; font-size: 12px;">(<?php echo escapeHtml($shop['agent_code']); ?>)</span>
                        <i class="fas fa-external-link-alt" style="font-size: 12px; margin-left: 4px;"></i>
                    </a>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Not assigned</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if (!empty($shop['address'])): ?>
        <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value">
                <?php echo escapeHtml($shop['address']); ?>
                <?php if (!empty($shop['city']) || !empty($shop['state']) || !empty($shop['pincode'])): ?>
                    <br>
                    <?php 
                    $locationParts = [];
                    if (!empty($shop['city'])) $locationParts[] = $shop['city'];
                    if (!empty($shop['state'])) $locationParts[] = $shop['state'];
                    if (!empty($shop['pincode'])) $locationParts[] = $shop['pincode'];
                    echo escapeHtml(implode(', ', $locationParts));
                    ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Owner Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-user-circle" style="color: #16A34A;"></i>
            Owner Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Owner Name</span>
            <span class="detail-value"><?php echo escapeHtml($shop['owner_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Username</span>
            <span class="detail-value"><?php echo escapeHtml($shop['username']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo escapeHtml($shop['email']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?php echo !empty($shop['phone']) ? escapeHtml($shop['phone']) : '<span style="color: #6B7A7B;">Not provided</span>'; ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Joined Date</span>
            <span class="detail-value"><?php echo formatDate($shop['created_at']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Last Login</span>
            <span class="detail-value">
                <?php if ($shop['last_login']): ?>
                    <?php echo formatDate($shop['last_login']) . ' (' . timeAgo($shop['last_login']) . ')'; ?>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Never logged in</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($shop['approved_by_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Approved By</span>
            <span class="detail-value"><?php echo escapeHtml($shop['approved_by_name']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($shop['approved_at']): ?>
        <div class="detail-row">
            <span class="detail-label">Approved At</span>
            <span class="detail-value"><?php echo formatDate($shop['approved_at']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Order Status Breakdown -->
    <?php if (!empty($orderStatuses)): ?>
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-chart-pie" style="color: #16A34A;"></i>
            Order Status Breakdown
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px;">
            <?php foreach ($orderStatuses as $status): ?>
            <div style="background: #F7FCF7; border-radius: 8px; padding: 12px; text-align: center;">
                <div style="font-size: 20px; font-weight: 700; color: #052E16;"><?php echo $status['count']; ?></div>
                <div>
                    <span class="order-status-badge <?php echo $status['status']; ?>">
                        <?php echo ucfirst($status['status']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Orders -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-shopping-cart" style="color: #16A34A;"></i> Recent Orders</span>
            <a href="orders.php?shop=<?php echo $shop['id']; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none; font-weight: 500;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-shopping-cart" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No orders from this shop yet.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo escapeHtml($order['order_number']); ?></strong></td>
                            <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
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
                                $color = $statusColors[$order['status']] ?? 'pending';
                                ?>
                                <span class="order-status-badge <?php echo $color; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn-action-sm btn-view">
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