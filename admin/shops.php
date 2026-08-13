<?php
/**
 * SAMRIDHI AGRO - Shop Management
 * 
 * This page displays all shops with search, filter,
 * approval, and management capabilities.
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
$pageTitle = 'Shop Management';

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

// ============================================
// PROCESS ACTIONS
// ============================================

// Handle approve/reject
if (isset($_GET['action']) && in_array($_GET['action'], ['approve', 'reject']) && isset($_GET['id'])) {
    requirePermission('shop.approve');
    
    $shopId = (int)$_GET['id'];
    $action = $_GET['action'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get shop data
        $sql = "SELECT s.*, u.full_name FROM shops s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.id = ?";
        $shop = $db->fetchOne($sql, [$shopId]);
        
        if ($shop) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $statusMessage = $action === 'approve' ? 'approved' : 'rejected';
            
            // Update shop status
            $sql = "UPDATE shops SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?";
            $db->query($sql, [$newStatus, $_SESSION['user_id'], $shopId]);
            
            // Update user status (activate if approved)
            if ($action === 'approve') {
                $sql = "UPDATE users SET status = 'active' WHERE id = ?";
                $db->query($sql, [$shop['user_id']]);
            }
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'shop',
                'Shop ' . $statusMessage . ': ' . $shop['shop_name']
            );
            
            setFlashMessage('success', 'Shop ' . $statusMessage . ' successfully.');
        } else {
            setFlashMessage('error', 'Shop not found.');
        }
    }
    
    redirect('admin/shops.php');
    exit;
}

// Handle toggle status (activate/deactivate)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    requirePermission('shop.edit');
    
    $shopId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get shop data
        $sql = "SELECT s.*, u.full_name, u.id as user_id FROM shops s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.id = ?";
        $shop = $db->fetchOne($sql, [$shopId]);
        
        if ($shop) {
            $newStatus = $shop['status'] === 'approved' ? 'suspended' : 'approved';
            $statusMessage = $shop['status'] === 'approved' ? 'suspended' : 'activated';
            
            // Update shop status
            $sql = "UPDATE shops SET status = ? WHERE id = ?";
            $db->query($sql, [$newStatus, $shopId]);
            
            // Update user status
            $userStatus = $newStatus === 'approved' ? 'active' : 'suspended';
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $db->query($sql, [$userStatus, $shop['user_id']]);
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'shop',
                'Shop ' . $statusMessage . ': ' . $shop['shop_name']
            );
            
            setFlashMessage('success', 'Shop ' . $statusMessage . ' successfully.');
        } else {
            setFlashMessage('error', 'Shop not found.');
        }
    }
    
    redirect('admin/shops.php');
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    requirePermission('shop.delete');
    
    $shopId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get shop data
        $sql = "SELECT s.*, u.full_name FROM shops s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.id = ?";
        $shop = $db->fetchOne($sql, [$shopId]);
        
        if ($shop) {
            // Soft delete - update status
            $sql = "UPDATE shops SET status = 'suspended' WHERE id = ?";
            $db->query($sql, [$shopId]);
            
            // Suspend user
            $sql = "UPDATE users SET status = 'suspended' WHERE id = ?";
            $db->query($sql, [$shop['user_id']]);
            
            logActivity(
                'delete',
                $_SESSION['user_id'],
                'shop',
                'Deleted shop: ' . $shop['shop_name']
            );
            
            setFlashMessage('success', 'Shop deleted successfully.');
        } else {
            setFlashMessage('error', 'Shop not found.');
        }
    }
    
    redirect('admin/shops.php');
    exit;
}

// ============================================
// GET SHOP LIST
// ============================================

// Search and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$agentFilter = isset($_GET['agent']) ? (int)$_GET['agent'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

// Search conditions
if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.phone LIKE ? OR s.shop_name LIKE ? OR s.shop_code LIKE ? OR s.owner_name LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Status filter
if ($status !== 'all') {
    $whereConditions[] = "s.status = ?";
    $params[] = $status;
}

// Agent filter
if ($agentFilter > 0) {
    $whereConditions[] = "s.agent_id = ?";
    $params[] = $agentFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get agents for filter dropdown
$sql = "SELECT a.id, u.full_name FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.status = 'approved' 
        ORDER BY u.full_name";
$agentList = $db->fetchAll($sql);

// Count total records
$sql = "SELECT COUNT(*) as total 
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalShops = $result['total'] ?? 0;

// Get shop records
$sql = "SELECT s.*, u.full_name, u.username, u.email, u.phone, u.status as user_status,
        u.created_at as user_created_at, u.last_login,
        u2.full_name as approved_by_name,
        a.full_name as agent_name
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN users u2 ON s.approved_by = u2.id
        LEFT JOIN (
            SELECT a.id, u.full_name 
            FROM agents a 
            JOIN users u ON a.user_id = u.id
        ) a ON s.agent_id = a.id
        $whereClause
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$shopList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalShops / $perPage);
$paginationUrl = 'admin/shops.php?page={page}&search=' . urlencode($search) . '&status=' . $status;
if ($agentFilter > 0) {
    $paginationUrl .= '&agent=' . $agentFilter;
}
$pagination = getPagination($totalShops, $page, $perPage, $paginationUrl);

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .shop-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 16px;
        color: white;
        flex-shrink: 0;
    }
    
    .shop-avatar.active { background: #16A34A; }
    .shop-avatar.pending { background: #F59E0B; }
    .shop-avatar.rejected { background: #DC2626; }
    .shop-avatar.suspended { background: #6B7A7B; }
    .shop-avatar.approved { background: #16A34A; }
    
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
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
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
    
    .btn-approve { background: #DCFCE7; color: #16A34A; }
    .btn-approve:hover { background: #BBF7D0; }
    
    .btn-reject { background: #FEE2E2; color: #DC2626; }
    .btn-reject:hover { background: #FECACA; }
    
    .btn-toggle { background: #FEF3C7; color: #D97706; }
    .btn-toggle:hover { background: #FDE68A; }
    
    .btn-delete { background: #FEE2E2; color: #DC2626; }
    .btn-delete:hover { background: #FECACA; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-store" style="color: #16A34A;"></i>
            All Shops
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalShops); ?>)
            </span>
        </h3>
        <div>
            <a href="shop-add.php" class="btn-primary" style="
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
                Add Shop
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
                    placeholder="Search by name, shop, code, owner..." 
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
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
            <select name="agent" style="
                padding: 10px 16px;
                border: 2px solid #E5EDE7;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                background: white;
                cursor: pointer;
                min-width: 150px;
            ">
                <option value="0" <?php echo $agentFilter == 0 ? 'selected' : ''; ?>>All Agents</option>
                <?php foreach ($agentList as $agent): ?>
                    <option value="<?php echo $agent['id']; ?>" <?php echo $agentFilter == $agent['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($agent['full_name']); ?>
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
            <?php if (!empty($search) || $status !== 'all' || $agentFilter > 0): ?>
            <a href="admin/shops.php" style="
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
    
    <!-- Shop Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Shop</th>
                    <th>Code</th>
                    <th>Owner</th>
                    <th>Agent</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shopList)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #6B7A7B;">
                        <i class="fas fa-store-slash" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                        No shops found
                        <?php if (!empty($search) || $status !== 'all' || $agentFilter > 0): ?>
                        <br><span style="font-size: 13px;">Try adjusting your search or filters</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($shopList as $shop): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="shop-avatar <?php echo $shop['status']; ?>">
                                <?php echo strtoupper(substr($shop['shop_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($shop['shop_name']); ?></div>
                                <div style="font-size: 12px; color: #6B7A7B;">
                                    <?php echo escapeHtml($shop['city'] ?? 'N/A'); ?>
                                    <?php if (!empty($shop['city']) && !empty($shop['state'])): ?>
                                    , <?php echo escapeHtml($shop['state']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-size: 13px; font-weight: 600; color: #14532D;">
                            <?php echo escapeHtml($shop['shop_code']); ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 13px;">
                            <div><i class="fas fa-user" style="color: #6B7A7B; width: 14px;"></i> <?php echo escapeHtml($shop['owner_name'] ?? 'N/A'); ?></div>
                            <?php if (!empty($shop['phone'])): ?>
                            <div><i class="fas fa-phone" style="color: #6B7A7B; width: 14px;"></i> <?php echo escapeHtml($shop['phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($shop['agent_name']): ?>
                            <span style="font-size: 13px;"><?php echo escapeHtml($shop['agent_name']); ?></span>
                        <?php else: ?>
                            <span style="color: #6B7A7B; font-size: 13px;">Not assigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $typeLabels = [
                            'retail' => 'Retail',
                            'wholesale' => 'Wholesale',
                            'both' => 'Both'
                        ];
                        $type = $typeLabels[$shop['shop_type']] ?? $shop['shop_type'];
                        ?>
                        <span style="font-size: 13px; background: #F0FDF4; padding: 2px 10px; border-radius: 12px; color: #065F46;">
                            <?php echo escapeHtml($type); ?>
                        </span>
                    </td>
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
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                            <!-- View Details -->
                            <a href="shop-view.php?id=<?php echo $shop['id']; ?>" 
                               class="btn-action btn-view" 
                               title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <!-- Edit -->
                            <a href="shop-edit.php?id=<?php echo $shop['id']; ?>" 
                               class="btn-action btn-edit" 
                               title="Edit Shop">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if ($shop['status'] === 'pending'): ?>
                                <!-- Approve -->
                                <a href="shops.php?action=approve&id=<?php echo $shop['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                                   class="btn-action btn-approve" 
                                   title="Approve Shop"
                                   onclick="return confirm('Are you sure you want to approve this shop?')">
                                    <i class="fas fa-check"></i>
                                </a>
                                
                                <!-- Reject -->
                                <a href="shops.php?action=reject&id=<?php echo $shop['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                                   class="btn-action btn-reject" 
                                   title="Reject Shop"
                                   onclick="return confirm('Are you sure you want to reject this shop?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php else: ?>
                                <!-- Toggle Status (Activate/Deactivate) -->
                                <a href="shops.php?action=toggle&id=<?php echo $shop['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                                   class="btn-action btn-toggle" 
                                   title="<?php echo $shop['status'] === 'approved' ? 'Suspend' : 'Activate'; ?>"
                                   onclick="return confirm('Are you sure you want to <?php echo $shop['status'] === 'approved' ? 'suspend' : 'activate'; ?> this shop?')">
                                    <i class="fas fa-<?php echo $shop['status'] === 'approved' ? 'pause' : 'play'; ?>"></i>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Delete -->
                            <a href="shops.php?action=delete&id=<?php echo $shop['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                               class="btn-action btn-delete" 
                               title="Delete Shop"
                               onclick="return confirm('Are you sure you want to delete this shop? This action cannot be undone.')">
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