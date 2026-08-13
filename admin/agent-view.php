<?php
/**
 * SAMRIDHI AGRO - View Agent
 * 
 * This page displays detailed information about a specific agent.
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
$pageTitle = 'View Agent';

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
requirePermissionOrAdmin('agent.view', 'agent-view.php');

// Get database instance
$db = getDB();

// Get agent ID from URL
$agentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to agent list
if ($agentId <= 0) {
    setFlashMessage('error', 'Invalid agent ID.');
    redirect('agents.php');
    exit;
}

// Get agent data with user details
$sql = "SELECT a.*, u.full_name, u.username, u.email, u.phone, u.status as user_status,
        u.created_at as user_created_at, u.last_login, u.last_ip,
        u2.full_name as approved_by_name
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN users u2 ON a.approved_by = u2.id
        WHERE a.id = ?";
$agent = $db->fetchOne($sql, [$agentId]);

// If agent not found, redirect
if (!$agent) {
    setFlashMessage('error', 'Agent not found.');
    redirect('agents.php');
    exit;
}

// Get agent statistics
// Total shops under this agent
$sql = "SELECT COUNT(*) as count FROM shops WHERE agent_id = ?";
$result = $db->fetchOne($sql, [$agentId]);
$totalShops = $result['count'] ?? 0;

// Total orders from agent's shops
$sql = "SELECT COUNT(*) as count FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ?";
$result = $db->fetchOne($sql, [$agentId]);
$totalOrders = $result['count'] ?? 0;

// Total revenue from agent's shops
$sql = "SELECT COALESCE(SUM(o.total_amount), 0) as total 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ? AND o.status = 'delivered'";
$result = $db->fetchOne($sql, [$agentId]);
$totalRevenue = $result['total'] ?? 0;

// Recent shops under this agent
$sql = "SELECT id, shop_name, shop_code, city, status, created_at 
        FROM shops 
        WHERE agent_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentShops = $db->fetchAll($sql, [$agentId]);

// Recent orders from agent's shops
$sql = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, s.shop_name 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE s.agent_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$recentOrders = $db->fetchAll($sql, [$agentId]);

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
    
    .stat-box.stat-shops .stat-number { color: #2563EB; }
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
        <div class="profile-avatar <?php echo $agent['status']; ?>">
            <?php echo strtoupper(substr($agent['full_name'], 0, 2)); ?>
        </div>
        <div class="profile-info">
            <h2><?php echo escapeHtml($agent['full_name']); ?></h2>
            <div class="profile-meta">
                <span><i class="fas fa-user-tag"></i> <?php echo escapeHtml($agent['username']); ?></span>
                <span><i class="fas fa-envelope"></i> <?php echo escapeHtml($agent['email']); ?></span>
                <span><i class="fas fa-id-badge"></i> <?php echo escapeHtml($agent['agent_code']); ?></span>
                <span>
                    <i class="fas fa-circle" style="color: <?php 
                        echo match($agent['status']) {
                            'pending' => '#F59E0B',
                            'approved' => '#16A34A',
                            'rejected' => '#DC2626',
                            'suspended' => '#6B7A7B',
                            default => '#6B7A7B'
                        };
                    ?>; font-size: 10px;"></i>
                    <?php echo ucfirst($agent['status']); ?>
                </span>
            </div>
        </div>
        <div class="profile-actions">
            <a href="agent-edit.php?id=<?php echo $agent['id']; ?>" class="btn-action-sm btn-view">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="agents.php" class="btn-action-sm btn-view">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stat-grid">
        <div class="stat-box stat-shops">
            <span class="stat-icon"><i class="fas fa-store" style="color: #2563EB;"></i></span>
            <div class="stat-number"><?php echo number_format($totalShops); ?></div>
            <div class="stat-label">Total Shops</div>
        </div>
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
            <span class="stat-icon"><i class="fas fa-percentage" style="color: #7C3AED;"></i></span>
            <div class="stat-number" style="color: #7C3AED;"><?php echo number_format($agent['commission_rate'], 2); ?>%</div>
            <div class="stat-label">Commission Rate</div>
        </div>
    </div>
    
    <!-- Agent Details -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-user-circle" style="color: #16A34A;"></i>
            Personal Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Full Name</span>
            <span class="detail-value"><?php echo escapeHtml($agent['full_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Username</span>
            <span class="detail-value"><?php echo escapeHtml($agent['username']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo escapeHtml($agent['email']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?php echo !empty($agent['phone']) ? escapeHtml($agent['phone']) : '<span style="color: #6B7A7B;">Not provided</span>'; ?></span>
        </div>
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
                $color = $statusColors[$agent['status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $color; ?>">
                    <?php echo ucfirst($agent['status']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Joined Date</span>
            <span class="detail-value"><?php echo formatDate($agent['created_at']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Last Login</span>
            <span class="detail-value">
                <?php if ($agent['last_login']): ?>
                    <?php echo formatDate($agent['last_login']) . ' (' . timeAgo($agent['last_login']) . ')'; ?>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Never logged in</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($agent['approved_by_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Approved By</span>
            <span class="detail-value"><?php echo escapeHtml($agent['approved_by_name']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($agent['approved_at']): ?>
        <div class="detail-row">
            <span class="detail-label">Approved At</span>
            <span class="detail-value"><?php echo formatDate($agent['approved_at']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Company Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-building" style="color: #16A34A;"></i>
            Company Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Agent Code</span>
            <span class="detail-value">
                <span style="font-family: monospace; font-weight: 600; color: #14532D;">
                    <?php echo escapeHtml($agent['agent_code']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Company Name</span>
            <span class="detail-value"><?php echo !empty($agent['company_name']) ? escapeHtml($agent['company_name']) : '<span style="color: #6B7A7B;">Not provided</span>'; ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">GST Number</span>
            <span class="detail-value"><?php echo !empty($agent['gst_number']) ? escapeHtml($agent['gst_number']) : '<span style="color: #6B7A7B;">Not provided</span>'; ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Commission Rate</span>
            <span class="detail-value">
                <span style="font-weight: 600; color: #7C3AED;"><?php echo number_format($agent['commission_rate'], 2); ?>%</span>
            </span>
        </div>
        <?php if (!empty($agent['address'])): ?>
        <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value">
                <?php echo escapeHtml($agent['address']); ?>
                <?php if (!empty($agent['city']) || !empty($agent['state']) || !empty($agent['pincode'])): ?>
                    <br>
                    <?php 
                    $locationParts = [];
                    if (!empty($agent['city'])) $locationParts[] = $agent['city'];
                    if (!empty($agent['state'])) $locationParts[] = $agent['state'];
                    if (!empty($agent['pincode'])) $locationParts[] = $agent['pincode'];
                    echo escapeHtml(implode(', ', $locationParts));
                    ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Shops -->
    <div class="detail-section">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-store" style="color: #16A34A;"></i> Recent Shops</span>
            <a href="shops.php?agent=<?php echo $agent['id']; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none; font-weight: 500;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($recentShops)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-store" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No shops associated with this agent yet.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Shop Name</th>
                            <th>Code</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentShops as $shop): ?>
                        <tr>
                            <td><strong><?php echo escapeHtml($shop['shop_name']); ?></strong></td>
                            <td><?php echo escapeHtml($shop['shop_code']); ?></td>
                            <td><?php echo escapeHtml($shop['city'] ?? 'N/A'); ?></td>
                            <td>
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
                            </td>
                            <td><?php echo formatDate($shop['created_at']); ?></td>
                            <td>
                                <a href="shop-view.php?id=<?php echo $shop['id']; ?>" class="btn-action-sm btn-view">
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
    
    <!-- Recent Orders -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-shopping-cart" style="color: #16A34A;"></i> Recent Orders</span>
            <a href="orders.php?agent=<?php echo $agent['id']; ?>" style="font-size: 13px; color: #16A34A; text-decoration: none; font-weight: 500;">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-shopping-cart" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No orders from this agent's shops yet.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Shop</th>
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
                            <td><?php echo escapeHtml($order['shop_name']); ?></td>
                            <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php 
                                $statusColors = [
                                    'pending' => 'badge-warning',
                                    'confirmed' => 'badge-info',
                                    'processing' => 'badge-primary',
                                    'shipped' => 'badge-info',
                                    'delivered' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                    'returned' => 'badge-warning'
                                ];
                                $color = $statusColors[$order['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge-status <?php echo $color; ?>">
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