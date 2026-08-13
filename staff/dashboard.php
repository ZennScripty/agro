<?php
/**
 * SAMRIDHI AGRO - Staff Dashboard
 * 
 * This is the staff dashboard displaying key metrics,
 * recent activities, and system statistics based on permissions.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Dashboard';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login
requireLogin();
requireRole('staff');

// Get database instance
$db = getDB();

// Get staff data
$sql = "SELECT u.*, sp.department, sp.designation, sp.joining_date 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ?";
$staff = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// GET DASHBOARD DATA BASED ON PERMISSIONS
// ============================================

$widgets = [];

// 1. Staff Attendance Widget
if (hasPermission('staff.attendance.view')) {
    $sql = "SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
            FROM attendance 
            WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $attendance = $db->fetchOne($sql, [$_SESSION['user_id']]);
    
    // Today's status
    $sql = "SELECT status, check_in_time, check_out_time 
            FROM attendance 
            WHERE user_id = ? AND date = CURDATE()";
    $today = $db->fetchOne($sql, [$_SESSION['user_id']]);
    
    $widgets['attendance'] = [
        'title' => 'My Attendance',
        'icon' => 'fa-calendar-check',
        'color' => 'icon-blue',
        'total' => $attendance['total_days'] ?? 0,
        'present' => $attendance['present_days'] ?? 0,
        'percentage' => ($attendance['total_days'] ?? 0) > 0 ? 
            round(($attendance['present_days'] ?? 0) / ($attendance['total_days'] ?? 0) * 100) : 0,
        'today_status' => $today['status'] ?? 'absent',
        'check_in' => $today['check_in_time'] ?? null,
        'check_out' => $today['check_out_time'] ?? null
    ];
}

// 2. Staff Visits Widget
if (hasPermission('staff.visits.view')) {
    $sql = "SELECT COUNT(*) as total 
            FROM staff_visits 
            WHERE staff_id = ? AND status = 'completed'";
    $result = $db->fetchOne($sql, [$_SESSION['user_id']]);
    $completedVisits = $result['total'] ?? 0;
    
    $sql = "SELECT COUNT(*) as total 
            FROM staff_visits 
            WHERE staff_id = ? AND status = 'planned' AND visit_date >= CURDATE()";
    $result = $db->fetchOne($sql, [$_SESSION['user_id']]);
    $plannedVisits = $result['total'] ?? 0;
    
    $widgets['visits'] = [
        'title' => 'My Visits',
        'icon' => 'fa-route',
        'color' => 'icon-orange',
        'completed' => $completedVisits,
        'planned' => $plannedVisits
    ];
}

// 3. Staff Leads Widget
if (hasPermission('staff.leads.view')) {
    $sql = "SELECT 
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
            SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_leads,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads
            FROM staff_leads 
            WHERE staff_id = ?";
    $leads = $db->fetchOne($sql, [$_SESSION['user_id']]);
    
    $widgets['leads'] = [
        'title' => 'My Leads',
        'icon' => 'fa-bullhorn',
        'color' => 'icon-green',
        'new' => $leads['new_leads'] ?? 0,
        'contacted' => $leads['contacted_leads'] ?? 0,
        'converted' => $leads['converted_leads'] ?? 0
    ];
}

// 4. Recent Activities
$sql = "SELECT al.*, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.user_id = ? OR al.module IN ('staff', 'attendance', 'visits', 'leads')
        ORDER BY al.created_at DESC 
        LIMIT 10";
$recentActivities = $db->fetchAll($sql, [$_SESSION['user_id']]);
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 18px 20px;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .stat-card .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    
    .stat-card .stat-title {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
    }
    
    .stat-card .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
    }
    
    .stat-card .stat-detail {
        font-size: 13px;
        color: #4A5B5D;
        margin-top: 4px;
    }
    
    .icon-blue { background: #DBEAFE; color: #2563EB; }
    .icon-orange { background: #FEF3C7; color: #D97706; }
    .icon-green { background: #DCFCE7; color: #16A34A; }
    .icon-purple { background: #EDE9FE; color: #7C3AED; }
    
    .attendance-status {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .attendance-status.present { background: #DCFCE7; color: #065F46; }
    .attendance-status.absent { background: #FEE2E2; color: #991B1B; }
    .attendance-status.half_day { background: #FEF3C7; color: #92400E; }
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .content-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
    }
    
    .content-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .content-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
    }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-item .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #F0FDF4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #16A34A;
        flex-shrink: 0;
    }
    
    .activity-item .activity-content {
        flex: 1;
    }
    
    .activity-item .activity-content .activity-text {
        font-size: 14px;
        color: #052E16;
    }
    
    .activity-item .activity-content .activity-text strong {
        font-weight: 600;
    }
    
    .activity-item .activity-content .activity-time {
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #14532D 0%, #16A34A 100%);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        color: white;
    }
    
    .welcome-section h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        margin: 0;
    }
    
    .welcome-section p {
        opacity: 0.8;
        margin: 4px 0 0 0;
        font-size: 14px;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h2>Welcome back, <?php echo escapeHtml($staff['full_name'] ?? 'Staff'); ?>! 👋</h2>
        <p>
            Department: <?php echo escapeHtml($staff['department'] ?? 'N/A'); ?> 
            | Designation: <?php echo escapeHtml($staff['designation'] ?? 'N/A'); ?>
            | Joined: <?php echo $staff['joining_date'] ? formatDate($staff['joining_date']) : 'N/A'; ?>
        </p>
    </div>
    
    <!-- Widgets -->
    <div class="stats-grid">
        <?php if (isset($widgets['attendance'])): ?>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?php echo $widgets['attendance']['title']; ?></span>
                <div class="stat-icon <?php echo $widgets['attendance']['color']; ?>">
                    <i class="fas <?php echo $widgets['attendance']['icon']; ?>"></i>
                </div>
            </div>
            <div class="stat-number">
                <?php echo $widgets['attendance']['present'] . '/' . $widgets['attendance']['total']; ?>
                <span style="font-size: 14px; color: #6B7A7B; margin-left: 8px;">
                    (<?php echo $widgets['attendance']['percentage']; ?>%)
                </span>
            </div>
            <div class="stat-label">Last 30 Days</div>
            <div class="stat-detail">
                Today: 
                <span class="attendance-status <?php echo $widgets['attendance']['today_status']; ?>">
                    <?php echo ucfirst($widgets['attendance']['today_status']); ?>
                </span>
                <?php if ($widgets['attendance']['check_in']): ?>
                    | In: <?php echo date('h:i A', strtotime($widgets['attendance']['check_in'])); ?>
                <?php endif; ?>
                <?php if ($widgets['attendance']['check_out']): ?>
                    | Out: <?php echo date('h:i A', strtotime($widgets['attendance']['check_out'])); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['visits'])): ?>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?php echo $widgets['visits']['title']; ?></span>
                <div class="stat-icon <?php echo $widgets['visits']['color']; ?>">
                    <i class="fas <?php echo $widgets['visits']['icon']; ?>"></i>
                </div>
            </div>
            <div class="stat-number"><?php echo $widgets['visits']['completed']; ?></div>
            <div class="stat-label">Completed Visits</div>
            <div class="stat-detail">
                Planned: <?php echo $widgets['visits']['planned']; ?> visits
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['leads'])): ?>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?php echo $widgets['leads']['title']; ?></span>
                <div class="stat-icon <?php echo $widgets['leads']['color']; ?>">
                    <i class="fas <?php echo $widgets['leads']['icon']; ?>"></i>
                </div>
            </div>
            <div class="stat-number"><?php echo $widgets['leads']['new']; ?></div>
            <div class="stat-label">New Leads</div>
            <div class="stat-detail">
                Contacted: <?php echo $widgets['leads']['contacted']; ?> | 
                Converted: <?php echo $widgets['leads']['converted']; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Activities -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history" style="color: #16A34A;"></i>
                Recent Activities
            </h3>
        </div>
        
        <?php if (empty($recentActivities)): ?>
            <p style="color: #6B7A7B; text-align: center; padding: 20px 0;">
                <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                No activities yet
            </p>
        <?php else: ?>
            <?php foreach ($recentActivities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-<?php 
                        echo match($activity['action']) {
                            'create' => 'plus',
                            'update' => 'edit',
                            'delete' => 'trash',
                            'login' => 'sign-in-alt',
                            'logout' => 'sign-out-alt',
                            default => 'circle'
                        };
                    ?>"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">
                        <?php if ($activity['full_name']): ?>
                            <strong><?php echo escapeHtml($activity['full_name']); ?></strong>
                        <?php endif; ?>
                        <?php echo escapeHtml($activity['description'] ?? $activity['action']); ?>
                    </div>
                    <div class="activity-time">
                        <i class="far fa-clock"></i> <?php echo timeAgo($activity['created_at']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>