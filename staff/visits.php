<?php
/**
 * SAMRIDHI AGRO - Staff Visits
 * 
 * This page displays and manages staff visits.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'My Visits';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login
requireLogin();
requireRole('staff');

// Get database instance
$db = getDB();

// Get staff data
$sql = "SELECT u.*, sp.department, sp.designation 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ?";
$staff = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET VISITS LIST
// ============================================

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["staff_id = ?"];
$params = [$_SESSION['user_id']];

if ($status !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $whereConditions[] = "(purpose LIKE ? OR visit_type LIKE ? OR notes LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM staff_visits $whereClause";
$result = $db->fetchOne($sql, $params);
$totalVisits = $result['total'] ?? 0;

// Get visits
$sql = "SELECT sv.*, 
        a.full_name as agent_name, 
        s.shop_name
        FROM staff_visits sv
        LEFT JOIN agents ag ON sv.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        LEFT JOIN shops s ON sv.shop_id = s.id
        $whereClause
        ORDER BY sv.visit_date DESC, sv.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$visitList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalVisits / $perPage);
$pagination = getPagination($totalVisits, $page, $perPage, 'visits.php?page={page}&status=' . $status . '&search=' . urlencode($search));

// Statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM staff_visits 
        WHERE staff_id = ?";
$visitStats = $db->fetchOne($sql, [$_SESSION['user_id']]);

$csrfToken = generateCsrfToken();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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
    .stat-card.completed .stat-number { color: #16A34A; }
    .stat-card.planned .stat-number { color: #3B82F6; }
    .stat-card.in-progress .stat-number { color: #F59E0B; }
    
    .visit-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .visit-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .visit-card .visit-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .visit-card .visit-type {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
    }
    
    .visit-card .visit-client {
        font-size: 13px;
        color: #6B7A7B;
    }
    
    .visit-card .visit-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 6px;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #F0FDF4;
    }
    
    .visit-card .visit-details .detail-item .detail-label {
        font-size: 10px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .visit-card .visit-details .detail-item .detail-value {
        font-size: 12px;
        font-weight: 500;
        color: #052E16;
    }
    
    .visit-card .visit-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }
    
    .badge-status {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    
    .btn-action {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        font-size: 11px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-start { background: #DCFCE7; color: #16A34A; }
    .btn-complete { background: #EDE9FE; color: #7C3AED; }
    .btn-cancel { background: #FEE2E2; color: #DC2626; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-route" style="color: #16A34A;"></i>
            My Visits
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalVisits); ?>)
            </span>
        </h3>
        <a href="visit-add.php" class="card-action">
            <i class="fas fa-plus"></i> Add Visit
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $visitStats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Visits</div>
        </div>
        <div class="stat-card completed">
            <div class="stat-number"><?php echo $visitStats['completed'] ?? 0; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card planned">
            <div class="stat-number"><?php echo $visitStats['planned'] ?? 0; ?></div>
            <div class="stat-label">Planned</div>
        </div>
        <div class="stat-card in-progress">
            <div class="stat-number"><?php echo $visitStats['in_progress'] ?? 0; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 16px;">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div style="flex: 1; min-width: 160px; position: relative;">
                <input type="text" name="search" placeholder="Search visits..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 8px 12px 8px 32px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            <select name="status" style="padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px; background: white;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="planned" <?php echo $status === 'planned' ? 'selected' : ''; ?>>Planned</option>
                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" style="padding: 8px 20px; background: #14532D; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || $status !== 'all'): ?>
            <a href="visits.php" style="padding: 8px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 8px; text-decoration: none; font-size: 13px;">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Visit List -->
    <?php if (empty($visitList)): ?>
        <div style="text-align: center; padding: 30px; color: #6B7A7B;">
            <i class="fas fa-route" style="font-size: 40px; display: block; margin-bottom: 10px; color: #D1D5DB;"></i>
            <p>No visits found</p>
        </div>
    <?php else: ?>
        <?php foreach ($visitList as $visit): ?>
        <div class="visit-card">
            <div class="visit-header">
                <div>
                    <div class="visit-type">
                        <?php echo str_replace('_', ' ', ucfirst($visit['visit_type'])); ?>
                        <span style="font-size: 13px; color: #6B7A7B; font-weight: 400;">
                            - <?php echo formatDate($visit['visit_date']); ?>
                        </span>
                    </div>
                    <div class="visit-client">
                        <?php if ($visit['agent_name']): ?>
                            <i class="fas fa-user-tie" style="color: #7C3AED;"></i> <?php echo escapeHtml($visit['agent_name']); ?>
                        <?php endif; ?>
                        <?php if ($visit['shop_name']): ?>
                            <i class="fas fa-store" style="color: #16A34A; margin-left: 8px;"></i> <?php echo escapeHtml($visit['shop_name']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php 
                    $statusColors = [
                        'planned' => 'badge-info',
                        'in_progress' => 'badge-warning',
                        'completed' => 'badge-success',
                        'cancelled' => 'badge-danger'
                    ];
                    $color = $statusColors[$visit['status']] ?? 'badge-secondary';
                    ?>
                    <span class="badge-status <?php echo $color; ?>">
                        <?php echo str_replace('_', ' ', ucfirst($visit['status'])); ?>
                    </span>
                </div>
            </div>
            
            <div class="visit-details">
                <div class="detail-item">
                    <div class="detail-label">Purpose</div>
                    <div class="detail-value"><?php echo escapeHtml(truncateText($visit['purpose'], 40)); ?></div>
                </div>
                <?php if ($visit['check_in_time']): ?>
                <div class="detail-item">
                    <div class="detail-label">Check In</div>
                    <div class="detail-value" style="color: #16A34A;"><?php echo date('h:i A', strtotime($visit['check_in_time'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($visit['check_out_time']): ?>
                <div class="detail-item">
                    <div class="detail-label">Check Out</div>
                    <div class="detail-value" style="color: #DC2626;"><?php echo date('h:i A', strtotime($visit['check_out_time'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($visit['rating']): ?>
                <div class="detail-item">
                    <div class="detail-label">Rating</div>
                    <div class="detail-value" style="color: #EAB308;">
                        <?php echo str_repeat('★', $visit['rating']); ?>
                        <?php echo str_repeat('☆', 5 - $visit['rating']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="visit-actions">
                <a href="visit-view.php?id=<?php echo $visit['id']; ?>" class="btn-action btn-view">
                    <i class="fas fa-eye"></i> View
                </a>
                <?php if ($visit['status'] === 'planned'): ?>
                    <a href="visits.php?action=start&id=<?php echo $visit['id']; ?>&csrf=<?php echo $csrfToken; ?>" class="btn-action btn-start" onclick="return confirm('Start this visit?')">
                        <i class="fas fa-play"></i> Start
                    </a>
                    <a href="visits.php?action=cancel&id=<?php echo $visit['id']; ?>&csrf=<?php echo $csrfToken; ?>" class="btn-action btn-cancel" onclick="return confirm('Cancel this visit?')">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
                <?php if ($visit['status'] === 'in_progress'): ?>
                    <a href="visit-complete.php?id=<?php echo $visit['id']; ?>" class="btn-action btn-complete">
                        <i class="fas fa-check"></i> Complete
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 16px;"><?php echo $pagination; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>