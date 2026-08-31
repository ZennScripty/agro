<?php
/**
 * SAMRIDHI AGRO - Admin Attendance Management
 * 
 * This page allows administrators to view, filter, and manage
 * attendance records of any staff or agent user.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.1.0
 */

$pageTitle = 'Attendance Management';
require_once '../includes/admin_header.php';

requireLogin();
requireRole('admin');
requirePermission('staff.attendance.view');

$db = getDB();

// ============================================
// GET USER ID FROM URL
// ============================================

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    setFlashMessage('error', 'Invalid user ID.');
    redirect('admin/staff.php');
    exit;
}

// ============================================
// GET USER DETAILS
// ============================================

$sql = "SELECT u.*, 
        sp.department, sp.designation,
        a.agent_code, a.commission_rate,
        a.status as agent_status
        FROM users u
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id
        LEFT JOIN agents a ON u.id = a.user_id
        WHERE u.id = ?";
$user = $db->fetchOne($sql, [$userId]);

if (!$user) {
    setFlashMessage('error', 'User not found.');
    redirect('admin/staff.php');
    exit;
}

// ============================================
// GET FILTER PARAMETERS
// ============================================

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// ============================================
// GET ATTENDANCE RECORDS
// ============================================

// Build query for attendance records
$whereConditions = ["user_id = ?"];
$params = [$userId];

if ($status !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

// Date range filter for the selected month
$startDate = date('Y-m-01', strtotime("$year-$month-01"));
$endDate = date('Y-m-t', strtotime("$year-$month-01"));
$whereConditions[] = "date BETWEEN ? AND ?";
$params[] = $startDate;
$params[] = $endDate;

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total records
$sql = "SELECT COUNT(*) as total FROM attendance $whereClause";
$result = $db->fetchOne($sql, $params);
$totalRecords = $result['total'] ?? 0;

// Get attendance records
$sql = "SELECT * FROM attendance 
        $whereClause
        ORDER BY date DESC
        LIMIT ? OFFSET ?";
$queryParams = array_merge($params, [$perPage, $offset]);
$attendanceRecords = $db->fetchAll($sql, $queryParams);

// ============================================
// GET ATTENDANCE STATISTICS
// ============================================
// FIXED: Use backticks for reserved keyword 'leave'
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_day,
        SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as `leave`,
        SUM(CASE WHEN status = 'holiday' THEN 1 ELSE 0 END) as holiday,
        SUM(overtime_hours) as total_overtime
        FROM attendance
        WHERE user_id = ? AND date BETWEEN ? AND ?";
$stats = $db->fetchOne($sql, [$userId, $startDate, $endDate]);

// ============================================
// HANDLE STATUS UPDATE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    requirePermission('staff.attendance.manage');
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token');
        redirect('admin/attendance-manage.php?id=' . $userId . '&month=' . $month . '&year=' . $year);
        exit;
    }
    
    $attendanceId = (int)($_POST['attendance_id'] ?? 0);
    $newStatus = sanitizeInput($_POST['status'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if ($attendanceId <= 0 || empty($newStatus)) {
        setFlashMessage('error', 'Invalid attendance record or status');
    } else {
        $sql = "UPDATE attendance SET status = ?, notes = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $db->query($sql, [$newStatus, $notes, $attendanceId, $userId]);
        
        logActivity('update', $_SESSION['user_id'], 'attendance', 'Updated attendance status for user: ' . $user['full_name']);
        setFlashMessage('success', 'Attendance status updated successfully');
    }
    redirect('admin/attendance-manage.php?id=' . $userId . '&month=' . $month . '&year=' . $year);
    exit;
}

// ============================================
// PAGINATION
// ============================================

$totalPages = ceil($totalRecords / $perPage);
$paginationUrl = 'attendance-manage.php?id=' . $userId . '&page={page}&month=' . $month . '&year=' . $year . '&status=' . $status;
$pagination = getPagination($totalRecords, $page, $perPage, $paginationUrl);

$csrfToken = generateCsrfToken();

// User role label
$roleLabels = [
    'admin' => 'Administrator',
    'staff' => 'Staff',
    'agent' => 'Agent',
    'shop' => 'Shop'
];

$statusColors = [
    'present' => 'badge-success',
    'absent' => 'badge-danger',
    'half_day' => 'badge-warning',
    'leave' => 'badge-info',
    'holiday' => 'badge-primary'
];
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .user-header {
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 16px;
        padding: 20px 24px;
        margin-bottom: 24px;
        border: 1px solid #E5EDE7;
    }
    
    .user-header .user-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #052E16;
    }
    
    .user-header .user-details {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 8px;
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .user-header .user-details span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
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
        padding: 12px 14px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
    }
    
    .stat-card .stat-label {
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.present .stat-number { color: #16A34A; }
    .stat-card.absent .stat-number { color: #DC2626; }
    .stat-card.half-day .stat-number { color: #D97706; }
    .stat-card.leave .stat-number { color: #3B82F6; }
    .stat-card.overtime .stat-number { color: #7C3AED; font-size: 16px; }
    
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
    }
    
    .filter-bar select {
        padding: 8px 12px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: white;
        min-height: 38px;
    }
    
    .filter-bar .btn-filter {
        padding: 8px 20px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        min-height: 38px;
    }
    
    .filter-bar .btn-filter:hover {
        background: #052E16;
    }
    
    .attendance-row {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 8px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
    }
    
    .attendance-row:hover {
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.06);
        border-color: #DCFCE7;
    }
    
    .attendance-row .row-date {
        font-weight: 600;
        color: #052E16;
        min-width: 110px;
    }
    
    .attendance-row .row-status {
        min-width: 100px;
    }
    
    .attendance-row .row-time {
        font-size: 13px;
        color: #6B7A7B;
        min-width: 140px;
    }
    
    .attendance-row .row-location {
        font-size: 12px;
        color: #6B7A7B;
        flex: 1;
        min-width: 150px;
    }
    
    .attendance-row .row-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .badge-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    
    .btn-action {
        padding: 4px 10px;
        border-radius: 4px;
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
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-edit { background: #EDE9FE; color: #7C3AED; }
    .btn-edit:hover { background: #DDD6FE; }
    
    .pagination-wrapper {
        margin-top: 20px;
        display: flex;
        justify-content: center;
    }
    
    @media (max-width: 768px) {
        .attendance-row {
            flex-direction: column;
            align-items: stretch;
        }
        .attendance-row .row-actions {
            justify-content: flex-start;
        }
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .user-header .user-name {
            font-size: 18px;
        }
    }
</style>

<!-- ============================================
USER HEADER
============================================ -->
<div class="user-header">
    <div class="user-name">
        <?php echo escapeHtml($user['full_name']); ?>
        <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
            (<?php echo $roleLabels[$user['role']] ?? ucfirst($user['role']); ?>)
        </span>
        <?php if ($user['role'] === 'agent' && $user['agent_code']): ?>
            <span style="font-size: 13px; font-weight: 500; color: #7C3AED; background: #EDE9FE; padding: 2px 12px; border-radius: 12px;">
                <?php echo escapeHtml($user['agent_code']); ?>
            </span>
        <?php endif; ?>
        <?php if ($user['role'] === 'staff' && $user['department']): ?>
            <span style="font-size: 13px; font-weight: 500; color: #16A34A; background: #DCFCE7; padding: 2px 12px; border-radius: 12px;">
                <?php echo escapeHtml($user['department']); ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="user-details">
        <span><i class="fas fa-envelope"></i> <?php echo escapeHtml($user['email']); ?></span>
        <?php if ($user['phone']): ?>
        <span><i class="fas fa-phone"></i> <?php echo escapeHtml($user['phone']); ?></span>
        <?php endif; ?>
        <span><i class="fas fa-user-tag"></i> <?php echo ucfirst($user['username']); ?></span>
        <span><i class="fas fa-calendar-plus"></i> Joined: <?php echo formatDate($user['created_at']); ?></span>
        <span>
            <i class="fas fa-circle" style="color: <?php echo $user['status'] === 'active' ? '#16A34A' : '#DC2626'; ?>; font-size: 10px;"></i>
            <?php echo ucfirst($user['status']); ?>
        </span>
    </div>
    <div style="margin-top: 10px;">
        <a href="<?php echo $user['role'] === 'staff' ? 'staff.php' : 'agents.php'; ?>" class="btn-action btn-edit" style="padding: 6px 16px;">
            <i class="fas fa-arrow-left"></i> Back to <?php echo ucfirst($user['role']); ?> List
        </a>
    </div>
</div>

<!-- ============================================
STATISTICS
============================================ -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
        <div class="stat-label">Total Days</div>
    </div>
    <div class="stat-card present">
        <div class="stat-number"><?php echo $stats['present'] ?? 0; ?></div>
        <div class="stat-label">Present</div>
    </div>
    <div class="stat-card absent">
        <div class="stat-number"><?php echo $stats['absent'] ?? 0; ?></div>
        <div class="stat-label">Absent</div>
    </div>
    <div class="stat-card half-day">
        <div class="stat-number"><?php echo $stats['half_day'] ?? 0; ?></div>
        <div class="stat-label">Half Day</div>
    </div>
    <div class="stat-card leave">
        <div class="stat-number"><?php echo $stats['leave'] ?? 0; ?></div>
        <div class="stat-label">Leaves</div>
    </div>
    <div class="stat-card overtime">
        <div class="stat-number"><?php echo number_format($stats['total_overtime'] ?? 0, 1); ?>h</div>
        <div class="stat-label">Total Overtime</div>
    </div>
</div>

<!-- ============================================
FILTER BAR
============================================ -->
<div class="filter-bar">
    <form method="GET" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; width: 100%;">
        <input type="hidden" name="id" value="<?php echo $userId; ?>">
        
        <label style="font-weight: 500; color: #14532D; font-size: 13px;">Month:</label>
        <select name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                </option>
            <?php endfor; ?>
        </select>
        
        <label style="font-weight: 500; color: #14532D; font-size: 13px;">Year:</label>
        <select name="year">
            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                    <?php echo $y; ?>
                </option>
            <?php endfor; ?>
        </select>
        
        <label style="font-weight: 500; color: #14532D; font-size: 13px;">Status:</label>
        <select name="status">
            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
            <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
            <option value="half_day" <?php echo $status === 'half_day' ? 'selected' : ''; ?>>Half Day</option>
            <option value="leave" <?php echo $status === 'leave' ? 'selected' : ''; ?>>Leave</option>
            <option value="holiday" <?php echo $status === 'holiday' ? 'selected' : ''; ?>>Holiday</option>
        </select>
        
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply</button>
        <a href="attendance-manage.php?id=<?php echo $userId; ?>" class="btn-action" style="background: #F3F4F6; color: #4A5B5D; padding: 8px 16px;">
            <i class="fas fa-times"></i> Reset
        </a>
    </form>
</div>

<!-- ============================================
ATTENDANCE RECORDS
============================================ -->
<div style="margin-bottom: 12px;">
    <span style="font-size: 13px; color: #6B7A7B;">
        Showing <?php echo count($attendanceRecords); ?> of <?php echo $totalRecords; ?> records for 
        <?php echo date('F Y', strtotime("$year-$month-01")); ?>
    </span>
</div>

<?php if (empty($attendanceRecords)): ?>
    <div style="text-align: center; padding: 40px 20px; color: #6B7A7B;">
        <i class="fas fa-calendar-day" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
        <p>No attendance records found for this period</p>
    </div>
<?php else: ?>
    <?php foreach ($attendanceRecords as $record): ?>
    <div class="attendance-row">
        <div class="row-date">
            <i class="far fa-calendar"></i> <?php echo formatDate($record['date']); ?>
        </div>
        
        <div class="row-time">
            <?php if ($record['check_in_time']): ?>
                <i class="fas fa-sign-in-alt" style="color: #16A34A;"></i> <?php echo date('h:i A', strtotime($record['check_in_time'])); ?>
            <?php else: ?>
                <span style="color: #6B7A7B;">—</span>
            <?php endif; ?>
            <?php if ($record['check_out_time']): ?>
                <br><i class="fas fa-sign-out-alt" style="color: #DC2626;"></i> <?php echo date('h:i A', strtotime($record['check_out_time'])); ?>
            <?php endif; ?>
        </div>
        
        <div class="row-status">
            <span class="badge-status <?php echo $statusColors[$record['status']] ?? 'badge-secondary'; ?>">
                <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
            </span>
            <?php if ($record['overtime_hours'] > 0): ?>
                <span style="font-size: 11px; color: #7C3AED; font-weight: 600;">+<?php echo number_format($record['overtime_hours'], 1); ?>h</span>
            <?php endif; ?>
        </div>
        
        <div class="row-location">
            <?php if ($record['check_in_location']): ?>
                <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
                <?php echo escapeHtml($record['check_in_location']); ?>
                <?php if ($record['check_in_lat'] && $record['check_in_lng']): ?>
                    <br><span style="font-size: 10px; color: #6B7A7B; font-family: monospace;">
                        <?php echo number_format($record['check_in_lat'], 6); ?>, <?php echo number_format($record['check_in_lng'], 6); ?>
                    </span>
                <?php endif; ?>
            <?php else: ?>
                <span style="color: #6B7A7B;">N/A</span>
            <?php endif; ?>
        </div>
        
        <div class="row-actions">
            <?php if (hasPermission('staff.attendance.manage')): ?>
                <button class="btn-action btn-edit" onclick="openEditModal(<?php echo $record['id']; ?>, '<?php echo $record['status']; ?>', '<?php echo addslashes($record['notes'] ?? ''); ?>')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <?php echo $pagination; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ============================================
EDIT MODAL
============================================ -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(5,46,22,0.4); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:16px; padding:28px; max-width:450px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(5,46,22,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-family:'Space Grotesk',sans-serif; font-size:18px; color:#052E16; margin:0;">
                <i class="fas fa-edit" style="color:#16A34A;"></i> Edit Attendance
            </h3>
            <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; color:#6B7A7B; cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="update_attendance" value="1">
            <input type="hidden" name="attendance_id" id="editAttendanceId" value="">
            
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="display:block; font-weight:600; color:#14532D; margin-bottom:4px;">Status</label>
                <select name="status" id="editStatus" class="form-input" style="width:100%; padding:10px 14px; border:2px solid #E5EDE7; border-radius:8px; font-size:14px;">
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="half_day">Half Day</option>
                    <option value="leave">Leave</option>
                    <option value="holiday">Holiday</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="display:block; font-weight:600; color:#14532D; margin-bottom:4px;">Notes</label>
                <textarea name="notes" id="editNotes" class="form-input" rows="3" style="width:100%; padding:10px 14px; border:2px solid #E5EDE7; border-radius:8px; font-size:14px; font-family:'Inter',sans-serif;" placeholder="Add notes..."></textarea>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="btn-primary" style="padding:10px 24px; background:linear-gradient(135deg,#14532D,#16A34A); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; flex:1;">
                    <i class="fas fa-save"></i> Update
                </button>
                <button type="button" onclick="closeEditModal()" style="padding:10px 24px; background:#F3F4F6; color:#4A5B5D; border:none; border-radius:8px; font-weight:500; cursor:pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, status, notes) {
    document.getElementById('editAttendanceId').value = id;
    document.getElementById('editStatus').value = status;
    document.getElementById('editNotes').value = notes || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>