<?php
/**
 * SAMRIDHI AGRO - Agent Management
 * 
 * This page displays all agents with search, filter,
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
$pageTitle = 'Agent Management';

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Require admin login and permission
// requireLogin();
// requireRole('admin');
// requirePermission('agent.view');

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

// Get database instance
$db = getDB();

// ============================================
// PROCESS ACTIONS
// ============================================

// Handle approve/reject
if (isset($_GET['action']) && in_array($_GET['action'], ['approve', 'reject']) && isset($_GET['id'])) {
    requirePermission('agent.approve');
    
    $agentId = (int)$_GET['id'];
    $action = $_GET['action'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get agent data
        $sql = "SELECT a.*, u.full_name FROM agents a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.id = ?";
        $agent = $db->fetchOne($sql, [$agentId]);
        
        if ($agent) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $statusMessage = $action === 'approve' ? 'approved' : 'rejected';
            
            // Update agent status
            $sql = "UPDATE agents SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?";
            $db->query($sql, [$newStatus, $_SESSION['user_id'], $agentId]);
            
            // Update user status (activate if approved)
            if ($action === 'approve') {
                $sql = "UPDATE users SET status = 'active' WHERE id = ?";
                $db->query($sql, [$agent['user_id']]);
            }
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'agent',
                'Agent ' . $statusMessage . ': ' . $agent['full_name']
            );
            
            setFlashMessage('success', 'Agent ' . $statusMessage . ' successfully.');
        } else {
            setFlashMessage('error', 'Agent not found.');
        }
    }
    
    redirect('agents.php');
    exit;
}

// Handle toggle status (activate/deactivate)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    requirePermission('agent.edit');
    
    $agentId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get agent data
        $sql = "SELECT a.*, u.full_name, u.id as user_id FROM agents a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.id = ?";
        $agent = $db->fetchOne($sql, [$agentId]);
        
        if ($agent) {
            $newStatus = $agent['status'] === 'approved' ? 'suspended' : 'approved';
            $statusMessage = $agent['status'] === 'approved' ? 'suspended' : 'activated';
            
            // Update agent status
            $sql = "UPDATE agents SET status = ? WHERE id = ?";
            $db->query($sql, [$newStatus, $agentId]);
            
            // Update user status
            $userStatus = $newStatus === 'approved' ? 'active' : 'suspended';
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $db->query($sql, [$userStatus, $agent['user_id']]);
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'agent',
                'Agent ' . $statusMessage . ': ' . $agent['full_name']
            );
            
            setFlashMessage('success', 'Agent ' . $statusMessage . ' successfully.');
        } else {
            setFlashMessage('error', 'Agent not found.');
        }
    }
    
    redirect('agents.php');
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    requirePermission('agent.delete');
    
    $agentId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get agent data
        $sql = "SELECT a.*, u.full_name FROM agents a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.id = ?";
        $agent = $db->fetchOne($sql, [$agentId]);
        
        if ($agent) {
            // Soft delete - update status
            $sql = "UPDATE agents SET status = 'suspended' WHERE id = ?";
            $db->query($sql, [$agentId]);
            
            // Suspend user
            $sql = "UPDATE users SET status = 'suspended' WHERE id = ?";
            $db->query($sql, [$agent['user_id']]);
            
            logActivity(
                'delete',
                $_SESSION['user_id'],
                'agent',
                'Deleted agent: ' . $agent['full_name']
            );
            
            setFlashMessage('success', 'Agent deleted successfully.');
        } else {
            setFlashMessage('error', 'Agent not found.');
        }
    }
    
    redirect('agents.php');
    exit;
}

// ============================================
// GET AGENT LIST
// ============================================

// Search and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

// Search conditions
if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.phone LIKE ? OR a.agent_code LIKE ? OR a.company_name LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Status filter
if ($status !== 'all') {
    $whereConditions[] = "a.status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count total records
$sql = "SELECT COUNT(*) as total 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalAgents = $result['total'] ?? 0;

// Get agent records
$sql = "SELECT a.*, u.full_name, u.username, u.email, u.phone, u.status as user_status,
        u.created_at as user_created_at, u.last_login,
        u2.full_name as approved_by_name
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN users u2 ON a.approved_by = u2.id
        $whereClause
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$agentList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalAgents / $perPage);
$pagination = getPagination($totalAgents, $page, $perPage, 'agents.php?page={page}&search=' . urlencode($search) . '&status=' . $status);

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .agent-avatar {
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
    
    .agent-avatar.active { background: #16A34A; }
    .agent-avatar.pending { background: #F59E0B; }
    .agent-avatar.rejected { background: #DC2626; }
    .agent-avatar.suspended { background: #6B7A7B; }
    .agent-avatar.approved { background: #16A34A; }
    
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
            <i class="fas fa-user-tie" style="color: #16A34A;"></i>
            All Agents
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalAgents); ?>)
            </span>
        </h3>
        <div>
            <a href="agent-add.php" class="btn-primary" style="
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
                Add Agent
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
                    placeholder="Search by name, email, company, code..." 
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
            <?php if (!empty($search) || $status !== 'all'): ?>
            <a href="agents.php" style="
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
    
    <!-- Agent Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Code</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Commission</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agentList)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #6B7A7B;">
                        <i class="fas fa-user-slash" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                        No agents found
                        <?php if (!empty($search) || $status !== 'all'): ?>
                        <br><span style="font-size: 13px;">Try adjusting your search or filters</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($agentList as $agent): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="agent-avatar <?php echo $agent['status']; ?>">
                                <?php echo strtoupper(substr($agent['full_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($agent['full_name']); ?></div>
                                <div style="font-size: 12px; color: #6B7A7B;"><?php echo escapeHtml($agent['username']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-size: 13px; font-weight: 600; color: #14532D;">
                            <?php echo escapeHtml($agent['agent_code']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($agent['company_name'])): ?>
                            <?php echo escapeHtml($agent['company_name']); ?>
                        <?php else: ?>
                            <span style="color: #6B7A7B; font-size: 13px;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size: 13px;">
                            <?php if (!empty($agent['email'])): ?>
                                <div><i class="fas fa-envelope" style="color: #6B7A7B; width: 14px;"></i> <?php echo escapeHtml($agent['email']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($agent['phone'])): ?>
                                <div><i class="fas fa-phone" style="color: #6B7A7B; width: 14px;"></i> <?php echo escapeHtml($agent['phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #14532D;">
                            <?php echo $agent['commission_rate'] > 0 ? number_format($agent['commission_rate'], 2) . '%' : 'N/A'; ?>
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
                        $color = $statusColors[$agent['status']] ?? 'badge-secondary';
                        ?>
                        <span class="badge-status <?php echo $color; ?>">
                            <?php echo ucfirst($agent['status']); ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($agent['created_at']); ?></td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                            <!-- View Details -->
                            <a href="agent-view.php?id=<?php echo $agent['id']; ?>" 
                               class="btn-action btn-view" 
                               title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <!-- Edit -->
                            <a href="agent-edit.php?id=<?php echo $agent['id']; ?>" 
                               class="btn-action btn-edit" 
                               title="Edit Agent">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if ($agent['status'] === 'pending'): ?>
                                <!-- Approve -->
                                <a href="agents.php?action=approve&id=<?php echo $agent['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                                   class="btn-action btn-approve" 
                                   title="Approve Agent"
                                   onclick="return confirm('Are you sure you want to approve this agent?')">
                                    <i class="fas fa-check"></i>
                                </a>
                                
                                <!-- Reject -->
                                <a href="agents.php?action=reject&id=<?php echo $agent['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                                   class="btn-action btn-reject" 
                                   title="Reject Agent"
                                   onclick="return confirm('Are you sure you want to reject this agent?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php else: ?>
                                <!-- Toggle Status (Activate/Deactivate) -->
                                <a href="agents.php?action=toggle&id=<?php echo $agent['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                                   class="btn-action btn-toggle" 
                                   title="<?php echo $agent['status'] === 'approved' ? 'Suspend' : 'Activate'; ?>"
                                   onclick="return confirm('Are you sure you want to <?php echo $agent['status'] === 'approved' ? 'suspend' : 'activate'; ?> this agent?')">
                                    <i class="fas fa-<?php echo $agent['status'] === 'approved' ? 'pause' : 'play'; ?>"></i>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Delete -->
                            <a href="agents.php?action=delete&id=<?php echo $agent['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                               class="btn-action btn-delete" 
                               title="Delete Agent"
                               onclick="return confirm('Are you sure you want to delete this agent? This action cannot be undone.')">
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