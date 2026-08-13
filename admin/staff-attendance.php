<?php
/**
 * SAMRIDHI AGRO - Staff Attendance View
 * 
 * This page displays attendance records for a specific staff member.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Staff Attendance';

// Include admin header
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

// Get staff ID from URL
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($staffId <= 0) {
    setFlashMessage('error', 'Invalid staff ID.');
    redirect('admin/staff.php');
    exit;
}

// Get staff details
$sql = "SELECT u.*, sp.department, sp.designation 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ? AND u.role = 'staff'";
$staff = $db->fetchOne($sql, [$staffId]);

if (!$staff) {
    setFlashMessage('error', 'Staff not found.');
    redirect('admin/staff.php');
    exit;
}

// Get attendance records for last 30 days
$sql = "SELECT * FROM attendance WHERE user_id = ? 
        ORDER BY date DESC LIMIT 30";
$attendanceRecords = $db->fetchAll($sql, [$staffId]);

// Calculate statistics
$totalDays = count($attendanceRecords);
$presentDays = 0;
$absentDays = 0;
$halfDays = 0;
$leaves = 0;
$totalOvertime = 0;

foreach ($attendanceRecords as $record) {
    switch ($record['status']) {
        case 'present': $presentDays++; break;
        case 'absent': $absentDays++; break;
        case 'half_day': $halfDays++; break;
        case 'leave': $leaves++; break;
    }
    $totalOvertime += $record['overtime_hours'] ?? 0;
}

// Get attendance settings
$sql = "SELECT setting_key, setting_value FROM attendance_settings";
$settings = $db->fetchAll($sql);
$attSettings = [];
foreach ($settings as $s) {
    $attSettings[$s['setting_key']] = $s['setting_value'];
}

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 16px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .stat-card.present .stat-number { color: #16A34A; }
    .stat-card.absent .stat-number { color: #DC2626; }
    .stat-card.half-day .stat-number { color: #F59E0B; }
    .stat-card.leaves .stat-number { color: #3B82F6; }
    .stat-card.overtime .stat-number { color: #7C3AED; }
    
    .attendance-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .attendance-badge.present { background: #DCFCE7; color: #065F46; }
    .attendance-badge.absent { background: #FEE2E2; color: #991B1B; }
    .attendance-badge.half_day { background: #FEF3C7; color: #92400E; }
    .attendance-badge.leave { background: #DBEAFE; color: #1E40AF; }
    .attendance-badge.holiday { background: #EDE9FE; color: #5B21B6; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-check" style="color: #16A34A;"></i>
            Attendance - <?php echo escapeHtml($staff['full_name']); ?>
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo escapeHtml($staff['department'] ?? 'N/A'); ?>)
            </span>
        </h3>
        <a href="staff.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Staff
        </a>
    </div>
    
    <!-- Staff Info -->
    <div style="background: #F7FCF7; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 16px;">
        <div><strong>Username:</strong> <?php echo escapeHtml($staff['username']); ?></div>
        <div><strong>Email:</strong> <?php echo escapeHtml($staff['email']); ?></div>
        <div><strong>Department:</strong> <?php echo escapeHtml($staff['department'] ?? 'N/A'); ?></div>
        <div><strong>Designation:</strong> <?php echo escapeHtml($staff['designation'] ?? 'N/A'); ?></div>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card present">
            <div class="stat-number"><?php echo $presentDays; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-card absent">
            <div class="stat-number"><?php echo $absentDays; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-card half-day">
            <div class="stat-number"><?php echo $halfDays; ?></div>
            <div class="stat-label">Half Day</div>
        </div>
        <div class="stat-card leaves">
            <div class="stat-number"><?php echo $leaves; ?></div>
            <div class="stat-label">Leaves</div>
        </div>
        <div class="stat-card overtime">
            <div class="stat-number"><?php echo number_format($totalOvertime, 1); ?>h</div>
            <div class="stat-label">Total Overtime</div>
        </div>
    </div>
    
    <!-- Attendance Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Overtime</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendanceRecords)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #6B7A7B;">
                        <i class="fas fa-calendar-day" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        No attendance records found
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($attendanceRecords as $record): ?>
                <tr>
                    <td><?php echo formatDate($record['date']); ?></td>
                    <td>
                        <?php if ($record['check_in_time']): ?>
                            <?php echo date('h:i A', strtotime($record['check_in_time'])); ?>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($record['check_out_time']): ?>
                            <?php echo date('h:i A', strtotime($record['check_out_time'])); ?>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px;">
                        <?php if ($record['check_in_location']): ?>
                            <div><i class="fas fa-map-marker-alt" style="color: #16A34A;"></i> <?php echo escapeHtml($record['check_in_location']); ?></div>
                        <?php endif; ?>
                        <?php if ($record['check_in_lat'] && $record['check_in_lng']): ?>
                            <div style="color: #6B7A7B; font-size: 10px;">
                                <?php echo number_format($record['check_in_lat'], 6); ?>, <?php echo number_format($record['check_in_lng'], 6); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="attendance-badge <?php echo $record['status']; ?>">
                            <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($record['overtime_hours'] > 0): ?>
                            <span style="font-weight: 600; color: #7C3AED;">+<?php echo number_format($record['overtime_hours'], 1); ?>h</span>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 13px; color: #6B7A7B;">
                        <?php echo escapeHtml($record['notes'] ?? ''); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>