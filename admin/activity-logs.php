<?php
/**
 * SAMRIDHI AGRO - Activity Logs
 * 
 * This page displays all system activities with search,
 * filter, and pagination capabilities for audit purposes.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: Set page title and include admin header
// ============================================

// Set page title
$pageTitle = 'Activity Logs';

// Include admin header (which already includes all configs)
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requireLogin();

// Admin has all access, Staff needs specific permission
if (!isAdmin() && !hasPermission('agent.view')) {
    logActivity('unauthorized_access', $_SESSION['user_id'], 'security', 
                'Attempted to access agents.php without permission');
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('staff/dashboard.php');
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
// GET FILTER PARAMETERS
// ============================================

$search = $_GET['search'] ?? '';
$module = $_GET['module'] ?? 'all';
$action = $_GET['action'] ?? 'all';
$user = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// ============================================
// GET USERS FOR FILTER DROPDOWN
// ============================================

$sql = "SELECT id, full_name, username FROM users ORDER BY full_name";
$userList = $db->fetchAll($sql);

// ============================================
// GET MODULES FOR FILTER DROPDOWN
// ============================================

$sql = "SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL AND module != '' ORDER BY module";
$moduleList = $db->fetchAll($sql);

// ============================================
// GET ACTIONS FOR FILTER DROPDOWN
// ============================================

$sql = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
$actionList = $db->fetchAll($sql);

// ============================================
// BUILD QUERY
// ============================================

$whereConditions = [];
$params = [];

// Search conditions (search in description)
if (!empty($search)) {
    $whereConditions[] = "(al.description LIKE ? OR al.action LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam]);
}

// Module filter
if ($module !== 'all' && !empty($module)) {
    $whereConditions[] = "al.module = ?";
    $params[] = $module;
}

// Action filter
if ($action !== 'all' && !empty($action)) {
    $whereConditions[] = "al.action = ?";
    $params[] = $action;
}

// User filter
if ($user > 0) {
    $whereConditions[] = "al.user_id = ?";
    $params[] = $user;
}

// Date range filter
if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $whereConditions[] = "DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count total records
$sql = "SELECT COUNT(*) as total FROM activity_logs al $whereClause";
$result = $db->fetchOne($sql, $params);
$totalLogs = $result['total'] ?? 0;

// Get activity logs
$sql = "SELECT al.*, 
        u.full_name as user_name, 
        u.username,
        u.email as user_email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$activityLogs = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalLogs / $perPage);
$paginationUrl = 'activity-logs.php?page={page}&search=' . urlencode($search) . '&module=' . $module . '&action=' . $action . '&user=' . $user;
if (!empty($dateFrom)) $paginationUrl .= '&date_from=' . urlencode($dateFrom);
if (!empty($dateTo)) $paginationUrl .= '&date_to=' . urlencode($dateTo);
$pagination = getPagination($totalLogs, $page, $perPage, $paginationUrl);

// ============================================
// GET SUMMARY STATISTICS
// ============================================

// Total activities
$sql = "SELECT COUNT(*) as total FROM activity_logs";
$result = $db->fetchOne($sql);
$totalActivities = $result['total'] ?? 0;

// Activities today
$sql = "SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()";
$result = $db->fetchOne($sql);
$todayActivities = $result['total'] ?? 0;

// Activities this week
$sql = "SELECT COUNT(*) as total FROM activity_logs WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())";
$result = $db->fetchOne($sql);
$weekActivities = $result['total'] ?? 0;

// Top module
$sql = "SELECT module, COUNT(*) as count FROM activity_logs WHERE module IS NOT NULL AND module != '' GROUP BY module ORDER BY count DESC LIMIT 1";
$result = $db->fetchOne($sql);
$topModule = $result['module'] ?? 'N/A';

// ============================================
// HTML CONTENT
// ============================================
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 18px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .stat-card .stat-icon {
        font-size: 18px;
        margin-bottom: 4px;
        display: block;
    }
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.today .stat-number { color: #16A34A; }
    .stat-card.week .stat-number { color: #7C3AED; }
    .stat-card.top-module .stat-number { color: #D97706; font-size: 18px; }
    
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        padding: 16px 20px;
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
    }
    
    .filter-bar label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 500;
        color: #14532D;
        white-space: nowrap;
    }
    
    .filter-bar select,
    .filter-bar input {
        padding: 8px 12px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: white;
        transition: all 0.3s ease;
        min-width: 120px;
    }
    
    .filter-bar select:focus,
    .filter-bar input:focus {
        outline: none;
        border-color: #16A34A;
    }
    
    .filter-bar .btn-filter {
        padding: 8px 20px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-bar .btn-filter:hover {
        background: #052E16;
    }
    
    .filter-bar .btn-clear {
        padding: 8px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .filter-bar .btn-clear:hover {
        background: #E5E7EB;
    }
    
    .activity-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .activity-badge.create { background: #DCFCE7; color: #065F46; }
    .activity-badge.update { background: #DBEAFE; color: #1E40AF; }
    .activity-badge.delete { background: #FEE2E2; color: #991B1B; }
    .activity-badge.login { background: #EDE9FE; color: #5B21B6; }
    .activity-badge.logout { background: #FEF3C7; color: #92400E; }
    .activity-badge.unauthorized_access { background: #FEE2E2; color: #991B1B; }
    .activity-badge.default { background: #F3F4F6; color: #6B7A7B; }
    
    .log-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 12px 16px;
        border-bottom: 1px solid #F7FCF7;
        transition: all 0.3s ease;
    }
    
    .log-item:hover {
        background: #F7FCF7;
    }
    
    .log-item .log-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #F0FDF4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #16A34A;
        flex-shrink: 0;
        font-size: 14px;
    }
    
    .log-item .log-content {
        flex: 1;
    }
    
    .log-item .log-content .log-text {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
    }
    
    .log-item .log-content .log-text strong {
        font-weight: 600;
    }
    
    .log-item .log-content .log-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 4px;
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .log-item .log-content .log-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .log-item .log-time {
        font-size: 12px;
        color: #6B7A7B;
        white-space: nowrap;
        flex-shrink: 0;
        padding-top: 2px;
    }
    
    .module-badge {
        display: inline-block;
        padding: 1px 10px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 500;
        background: #F0FDF4;
        color: #065F46;
        text-transform: capitalize;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6B7A7B;
    }
    
    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: #D1D5DB;
    }
    
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-bar label {
            margin-top: 4px;
        }
        
        .log-item {
            flex-wrap: wrap;
        }
        
        .log-item .log-time {
            width: 100%;
            padding-left: 50px;
        }
    }
</style>

<!-- ============================================
HTML CONTENT
============================================ -->

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; color: #052E16; margin: 0;">
            <i class="fas fa-history" style="color: #16A34A;"></i>
            Activity Logs
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalLogs); ?> records)
            </span>
        </h2>
        <a href="activity-logs.php" class="btn-action-sm" style="
            padding: 6px 16px;
            background: #F3F4F6;
            color: #4A5B5D;
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        ">
            <i class="fas fa-sync"></i> Refresh
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <span class="stat-icon"><i class="fas fa-list" style="color: #14532D;"></i></span>
            <div class="stat-number"><?php echo number_format($totalActivities); ?></div>
            <div class="stat-label">Total Activities</div>
        </div>
        <div class="stat-card today">
            <span class="stat-icon"><i class="fas fa-calendar-day" style="color: #16A34A;"></i></span>
            <div class="stat-number"><?php echo number_format($todayActivities); ?></div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card week">
            <span class="stat-icon"><i class="fas fa-calendar-week" style="color: #7C3AED;"></i></span>
            <div class="stat-number"><?php echo number_format($weekActivities); ?></div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card top-module">
            <span class="stat-icon"><i class="fas fa-cube" style="color: #D97706;"></i></span>
            <div class="stat-number"><?php echo escapeHtml(ucfirst($topModule)); ?></div>
            <div class="stat-label">Top Module</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; width: 100%;">
            <div style="flex: 1; min-width: 150px; position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search activities..." 
                    value="<?php echo escapeHtml($search); ?>"
                    style="
                        width: 100%;
                        padding: 8px 12px 8px 32px;
                        border: 2px solid #E5EDE7;
                        border-radius: 8px;
                        font-family: 'Inter', sans-serif;
                        font-size: 13px;
                        transition: all 0.3s ease;
                        background: white;
                    "
                >
                <i class="fas fa-search" style="
                    position: absolute;
                    left: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #6B7A7B;
                    font-size: 13px;
                "></i>
            </div>
            
            <label for="module">Module:</label>
            <select id="module" name="module">
                <option value="all" <?php echo $module === 'all' ? 'selected' : ''; ?>>All Modules</option>
                <?php foreach ($moduleList as $m): ?>
                    <?php if (!empty($m['module'])): ?>
                    <option value="<?php echo escapeHtml($m['module']); ?>" 
                        <?php echo $module === $m['module'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml(ucfirst($m['module'])); ?>
                    </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <label for="action">Action:</label>
            <select id="action" name="action">
                <option value="all" <?php echo $action === 'all' ? 'selected' : ''; ?>>All Actions</option>
                <?php foreach ($actionList as $a): ?>
                    <?php if (!empty($a['action'])): ?>
                    <option value="<?php echo escapeHtml($a['action']); ?>" 
                        <?php echo $action === $a['action'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml(ucfirst(str_replace('_', ' ', $a['action']))); ?>
                    </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <label for="user">User:</label>
            <select id="user" name="user">
                <option value="0" <?php echo $user == 0 ? 'selected' : ''; ?>>All Users</option>
                <?php foreach ($userList as $u): ?>
                    <option value="<?php echo $u['id']; ?>" 
                        <?php echo $user == $u['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($u['full_name'] ?? $u['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input 
                type="date" 
                name="date_from" 
                value="<?php echo escapeHtml($dateFrom); ?>"
                placeholder="From"
                style="padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 13px; background: white;"
            >
            
            <input 
                type="date" 
                name="date_to" 
                value="<?php echo escapeHtml($dateTo); ?>"
                placeholder="To"
                style="padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 13px; background: white;"
            >
            
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $module !== 'all' || $action !== 'all' || $user > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
            <a href="activity-logs.php" class="btn-clear">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Activity Logs List -->
    <div style="background: white; border: 1px solid #E5EDE7; border-radius: 12px; overflow: hidden;">
        <?php if (empty($activityLogs)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No activities found matching your criteria.</p>
                <?php if (!empty($search) || $module !== 'all' || $action !== 'all' || $user > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
                    <p style="font-size: 13px;">Try adjusting your filters.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($activityLogs as $log): ?>
            <div class="log-item">
                <div class="log-icon">
                    <i class="fas fa-<?php 
                        echo match($log['action']) {
                            'create' => 'plus',
                            'update' => 'edit',
                            'delete' => 'trash',
                            'login' => 'sign-in-alt',
                            'logout' => 'sign-out-alt',
                            'unauthorized_access' => 'exclamation-triangle',
                            default => 'circle'
                        };
                    ?>"></i>
                </div>
                <div class="log-content">
                    <div class="log-text">
                        <?php if ($log['user_name']): ?>
                            <strong><?php echo escapeHtml($log['user_name']); ?></strong>
                        <?php else: ?>
                            <strong>System</strong>
                        <?php endif; ?>
                        <?php echo escapeHtml($log['description'] ?? $log['action']); ?>
                        <?php if (!empty($log['module'])): ?>
                            <span class="module-badge"><?php echo escapeHtml($log['module']); ?></span>
                        <?php endif; ?>
                        <span class="activity-badge <?php echo in_array($log['action'], ['create', 'update', 'delete', 'login', 'logout', 'unauthorized_access']) ? $log['action'] : 'default'; ?>">
                            <?php echo escapeHtml(str_replace('_', ' ', ucfirst($log['action'] ?? 'unknown'))); ?>
                        </span>
                    </div>
                    <div class="log-meta">
                        <?php if ($log['user_name']): ?>
                            <span><i class="fas fa-user"></i> <?php echo escapeHtml($log['username'] ?? ''); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($log['ip_address'])): ?>
                            <span><i class="fas fa-network-wired"></i> <?php echo escapeHtml($log['ip_address']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($log['module'])): ?>
                            <span><i class="fas fa-cube"></i> <?php echo escapeHtml(ucfirst($log['module'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="log-time">
                    <span title="<?php echo formatDate($log['created_at']); ?>">
                        <?php echo timeAgo($log['created_at']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div style="padding: 16px 20px; border-top: 1px solid #E5EDE7;">
                <?php echo $pagination; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>