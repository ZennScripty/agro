<?php
/**
 * SAMRIDHI AGRO - Staff Leads
 * 
 * This page displays and manages staff leads.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'My Leads';

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
// GET LEADS LIST
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
    $whereConditions[] = "(title LIKE ? OR description LIKE ? OR contact_name LIKE ? OR contact_phone LIKE ? OR contact_email LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM staff_leads $whereClause";
$result = $db->fetchOne($sql, $params);
$totalLeads = $result['total'] ?? 0;

// Get leads
$sql = "SELECT sl.*, 
        a.full_name as agent_name,
        s.shop_name
        FROM staff_leads sl
        LEFT JOIN agents ag ON sl.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        LEFT JOIN shops s ON sl.shop_id = s.id
        $whereClause
        ORDER BY sl.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$leadList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalLeads / $perPage);
$pagination = getPagination($totalLeads, $page, $perPage, 'leads.php?page={page}&status=' . $status . '&search=' . urlencode($search));

// Statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
        SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted,
        SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified,
        SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
        SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost
        FROM staff_leads 
        WHERE staff_id = ?";
$leadStats = $db->fetchOne($sql, [$_SESSION['user_id']]);

$csrfToken = generateCsrfToken();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 8px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 10px 12px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        color: #6B7A7B;
    }
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.new .stat-number { color: #3B82F6; }
    .stat-card.contacted .stat-number { color: #F59E0B; }
    .stat-card.qualified .stat-number { color: #7C3AED; }
    .stat-card.converted .stat-number { color: #16A34A; }
    .stat-card.lost .stat-number { color: #DC2626; }
    
    .lead-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .lead-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .lead-card .lead-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .lead-card .lead-title {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
    }
    
    .lead-card .lead-type {
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .lead-card .lead-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 6px;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #F0FDF4;
    }
    
    .lead-card .lead-details .detail-item .detail-label {
        font-size: 10px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .lead-card .lead-details .detail-item .detail-value {
        font-size: 12px;
        font-weight: 500;
        color: #052E16;
    }
    
    .lead-card .lead-actions {
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
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }
    
    .priority-badge {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .priority-badge.low { background: #F3F4F6; color: #6B7A7B; }
    .priority-badge.medium { background: #DBEAFE; color: #1E40AF; }
    .priority-badge.high { background: #FEF3C7; color: #92400E; }
    .priority-badge.urgent { background: #FEE2E2; color: #991B1B; }
    
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
    .btn-edit { background: #EDE9FE; color: #7C3AED; }
    .btn-contact { background: #DCFCE7; color: #16A34A; }
    .btn-convert { background: #FEF3C7; color: #D97706; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bullhorn" style="color: #16A34A;"></i>
            My Leads
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalLeads); ?>)
            </span>
        </h3>
        <a href="lead-add.php" class="card-action">
            <i class="fas fa-plus"></i> Add Lead
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $leadStats['total'] ?? 0; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card new">
            <div class="stat-number"><?php echo $leadStats['new_leads'] ?? 0; ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="stat-card contacted">
            <div class="stat-number"><?php echo $leadStats['contacted'] ?? 0; ?></div>
            <div class="stat-label">Contacted</div>
        </div>
        <div class="stat-card qualified">
            <div class="stat-number"><?php echo $leadStats['qualified'] ?? 0; ?></div>
            <div class="stat-label">Qualified</div>
        </div>
        <div class="stat-card converted">
            <div class="stat-number"><?php echo $leadStats['converted'] ?? 0; ?></div>
            <div class="stat-label">Converted</div>
        </div>
        <div class="stat-card lost">
            <div class="stat-number"><?php echo $leadStats['lost'] ?? 0; ?></div>
            <div class="stat-label">Lost</div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 16px;">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div style="flex: 1; min-width: 160px; position: relative;">
                <input type="text" name="search" placeholder="Search leads..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 8px 12px 8px 32px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            <select name="status" style="padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px; background: white;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="contacted" <?php echo $status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                <option value="qualified" <?php echo $status === 'qualified' ? 'selected' : ''; ?>>Qualified</option>
                <option value="converted" <?php echo $status === 'converted' ? 'selected' : ''; ?>>Converted</option>
                <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>Lost</option>
            </select>
            <button type="submit" style="padding: 8px 20px; background: #14532D; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || $status !== 'all'): ?>
            <a href="leads.php" style="padding: 8px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 8px; text-decoration: none; font-size: 13px;">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Lead List -->
    <?php if (empty($leadList)): ?>
        <div style="text-align: center; padding: 30px; color: #6B7A7B;">
            <i class="fas fa-bullhorn" style="font-size: 40px; display: block; margin-bottom: 10px; color: #D1D5DB;"></i>
            <p>No leads found</p>
        </div>
    <?php else: ?>
        <?php foreach ($leadList as $lead): ?>
        <div class="lead-card">
            <div class="lead-header">
                <div>
                    <div class="lead-title"><?php echo escapeHtml($lead['title']); ?></div>
                    <div class="lead-type">
                        <span style="background: #F3F4F6; padding: 1px 8px; border-radius: 10px; font-size: 11px;">
                            <?php echo str_replace('_', ' ', ucfirst($lead['lead_type'])); ?>
                        </span>
                        <?php if ($lead['agent_name']): ?>
                            <span style="margin-left: 8px;">
                                <i class="fas fa-user-tie" style="color: #7C3AED;"></i> <?php echo escapeHtml($lead['agent_name']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($lead['shop_name']): ?>
                            <span style="margin-left: 8px;">
                                <i class="fas fa-store" style="color: #16A34A;"></i> <?php echo escapeHtml($lead['shop_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php 
                    $statusColors = [
                        'new' => 'badge-info',
                        'contacted' => 'badge-warning',
                        'qualified' => 'badge-primary',
                        'converted' => 'badge-success',
                        'lost' => 'badge-danger'
                    ];
                    $color = $statusColors[$lead['status']] ?? 'badge-secondary';
                    ?>
                    <span class="badge-status <?php echo $color; ?>">
                        <?php echo ucfirst($lead['status']); ?>
                    </span>
                    <span class="priority-badge <?php echo $lead['priority']; ?>">
                        <?php echo ucfirst($lead['priority']); ?>
                    </span>
                </div>
            </div>
            
            <div class="lead-details">
                <?php if ($lead['contact_name']): ?>
                <div class="detail-item">
                    <div class="detail-label">Contact</div>
                    <div class="detail-value"><?php echo escapeHtml($lead['contact_name']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($lead['contact_phone']): ?>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo escapeHtml($lead['contact_phone']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($lead['follow_up_date']): ?>
                <div class="detail-item">
                    <div class="detail-label">Follow Up</div>
                    <div class="detail-value"><?php echo formatDate($lead['follow_up_date']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($lead['converted_at']): ?>
                <div class="detail-item">
                    <div class="detail-label">Converted</div>
                    <div class="detail-value" style="color: #16A34A;"><?php echo formatDate($lead['converted_at']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($lead['description'])): ?>
            <div style="font-size: 13px; color: #4A5B5D; margin-top: 6px; background: #F7FCF7; padding: 6px 10px; border-radius: 6px;">
                <?php echo escapeHtml(truncateText($lead['description'], 80)); ?>
            </div>
            <?php endif; ?>
            
            <div class="lead-actions">
                <a href="lead-view.php?id=<?php echo $lead['id']; ?>" class="btn-action btn-view">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="lead-edit.php?id=<?php echo $lead['id']; ?>" class="btn-action btn-edit">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php if ($lead['status'] === 'new'): ?>
                    <a href="leads.php?action=contact&id=<?php echo $lead['id']; ?>&csrf=<?php echo $csrfToken; ?>" class="btn-action btn-contact" onclick="return confirm('Mark as contacted?')">
                        <i class="fas fa-phone"></i> Contacted
                    </a>
                <?php endif; ?>
                <?php if ($lead['status'] === 'contacted' || $lead['status'] === 'qualified'): ?>
                    <a href="leads.php?action=convert&id=<?php echo $lead['id']; ?>&csrf=<?php echo $csrfToken; ?>" class="btn-action btn-convert" onclick="return confirm('Convert this lead?')">
                        <i class="fas fa-check"></i> Convert
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