<?php
/**
 * SAMRIDHI AGRO - Admin Attendance List
 * 
 * This page displays all staff and agents with their monthly attendance summary.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

$pageTitle = 'Attendance List';
require_once '../includes/admin_header.php';

requireLogin();
requireRole('admin');
requirePermission('staff.attendance.view');

$db = getDB();

// ============================================
// GET FILTER PARAMETERS
// ============================================

$role = $_GET['role'] ?? 'all';
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// ============================================
// GET HOLIDAYS FOR THE MONTH
// ============================================

$startDate = date('Y-m-01', strtotime("$year-$month-01"));
$endDate = date('Y-m-t', strtotime("$year-$month-01"));

// Get all holidays for the month
$sql = "SELECT holiday_date FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? AND status = 'active'";
$holidays = $db->fetchAll($sql, [$month, $year]);
$holidayDates = array_column($holidays, 'holiday_date');

// Get weekly holidays from settings
$sql = "SELECT setting_value FROM attendance_settings WHERE setting_key = 'weekly_holidays'";
$result = $db->fetchOne($sql);
$weeklyHolidays = [];
if ($result && !empty($result['setting_value'])) {
    $weeklyHolidays = explode(',', $result['setting_value']);
    $weeklyHolidays = array_map('trim', $weeklyHolidays);
}
$dayFullNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Calculate total working days for the month
$totalDays = date('t', strtotime("$year-$month-01"));
$workingDays = 0;
for ($day = 1; $day <= $totalDays; $day++) {
    $date = date('Y-m-d', strtotime("$year-$month-$day"));
    $dayOfWeek = date('N', strtotime($date));
    $dayName = $dayFullNames[$dayOfWeek - 1];
    
    // Check if it's a holiday or weekly holiday
    if (in_array($date, $holidayDates) || in_array($dayName, $weeklyHolidays)) {
        continue;
    }
    $workingDays++;
}

// ============================================
// BUILD USER QUERY
// ============================================

$whereConditions = ["u.role IN ('staff', 'agent')"];
$params = [];

if ($role !== 'all') {
    $whereConditions[] = "u.role = ?";
    $params[] = $role;
}

if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total users
$sql = "SELECT COUNT(*) as total FROM users u $whereClause";
$result = $db->fetchOne($sql, $params);
$totalUsers = $result['total'] ?? 0;

// Get users with attendance summary for the month
$sql = "SELECT u.id, u.full_name, u.username, u.email, u.role, u.status,
        sp.department, sp.designation,
        a.agent_code, a.commission_rate,
        (SELECT COUNT(*) FROM attendance 
         WHERE user_id = u.id 
         AND status = 'present' 
         AND date BETWEEN ? AND ?) as present_days,
        (SELECT COUNT(*) FROM attendance 
         WHERE user_id = u.id 
         AND status = 'absent' 
         AND date BETWEEN ? AND ?) as absent_days,
        (SELECT COUNT(*) FROM attendance 
         WHERE user_id = u.id 
         AND status = 'half_day' 
         AND date BETWEEN ? AND ?) as half_days,
        (SELECT COUNT(*) FROM attendance 
         WHERE user_id = u.id 
         AND status = 'leave' 
         AND date BETWEEN ? AND ?) as leave_days,
        (SELECT COALESCE(SUM(overtime_hours), 0) FROM attendance 
         WHERE user_id = u.id 
         AND date BETWEEN ? AND ?) as total_overtime,
        (SELECT check_in_time FROM attendance 
         WHERE user_id = u.id 
         AND date = CURDATE() 
         ORDER BY id DESC LIMIT 1) as today_check_in,
        (SELECT check_out_time FROM attendance 
         WHERE user_id = u.id 
         AND date = CURDATE() 
         ORDER BY id DESC LIMIT 1) as today_check_out,
        (SELECT status FROM attendance 
         WHERE user_id = u.id 
         AND date = CURDATE() 
         ORDER BY id DESC LIMIT 1) as today_status
        FROM users u
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id
        LEFT JOIN agents a ON u.id = a.user_id
        $whereClause
        ORDER BY u.full_name ASC
        LIMIT ? OFFSET ?";

$queryParams = array_merge(
    [$startDate, $endDate], // present
    [$startDate, $endDate], // absent
    [$startDate, $endDate], // half_day
    [$startDate, $endDate], // leave
    [$startDate, $endDate], // overtime
    $params,
    [$perPage, $offset]
);

$userList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalUsers / $perPage);
$paginationUrl = 'attendance-list.php?page={page}&role=' . $role . '&month=' . $month . '&year=' . $year . '&search=' . urlencode($search);
$pagination = getPagination($totalUsers, $page, $perPage, $paginationUrl);

$csrfToken = generateCsrfToken();

// Role labels
$roleLabels = [
    'staff' => 'Staff',
    'agent' => 'Agent'
];

$roleColors = [
    'staff' => '#16A34A',
    'agent' => '#7C3AED'
];

$statusColors = [
    'active' => 'badge-success',
    'inactive' => 'badge-warning',
    'suspended' => 'badge-danger'
];
?>

<style>
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .stat-box {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 16px;
        text-align: center;
    }
    
    .stat-box .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-box .stat-label {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        padding: 14px 18px;
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
    }
    
    .filter-bar select,
    .filter-bar input {
        padding: 8px 12px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: white;
        min-height: 38px;
    }
    
    .filter-bar select:focus,
    .filter-bar input:focus {
        outline: none;
        border-color: #16A34A;
    }
    
    .filter-bar .btn-filter {
        padding: 8px 24px;
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
    
    .filter-bar .btn-clear {
        padding: 8px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .filter-bar .btn-clear:hover {
        background: #E5E7EB;
    }
    
    .user-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }
    
    .user-card:hover {
        box-shadow: 0 4px 16px rgba(5, 46, 22, 0.08);
        border-color: #DCFCE7;
        transform: translateY(-2px);
    }
    
    .user-card .user-info {
        flex: 1;
        min-width: 180px;
    }
    
    .user-card .user-info .user-name {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .user-card .user-info .user-details {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .user-card .user-info .user-details span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-right: 12px;
    }
    
    .user-card .attendance-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 16px;
        align-items: center;
        min-width: 200px;
    }
    
    .user-card .attendance-summary .att-item {
        text-align: center;
    }
    
    .user-card .attendance-summary .att-item .att-number {
        font-weight: 700;
        font-size: 16px;
        color: #052E16;
    }
    
    .user-card .attendance-summary .att-item .att-label {
        font-size: 10px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .user-card .attendance-summary .att-item .att-number.present { color: #16A34A; }
    .user-card .attendance-summary .att-item .att-number.absent { color: #DC2626; }
    .user-card .attendance-summary .att-item .att-number.half { color: #D97706; }
    .user-card .attendance-summary .att-item .att-number.leave { color: #3B82F6; }
    
    .user-card .attendance-percentage {
        min-width: 80px;
        text-align: center;
    }
    
    .user-card .attendance-percentage .percent {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
    }
    
    .user-card .attendance-percentage .percent.high { color: #16A34A; }
    .user-card .attendance-percentage .percent.medium { color: #D97706; }
    .user-card .attendance-percentage .percent.low { color: #DC2626; }
    
    .user-card .attendance-percentage .label {
        font-size: 10px;
        color: #6B7A7B;
        text-transform: uppercase;
    }
    
    .user-card .user-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .role-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .role-badge.staff {
        background: #DCFCE7;
        color: #065F46;
    }
    
    .role-badge.agent {
        background: #EDE9FE;
        color: #5B21B6;
    }
    
    .badge-status {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .today-status {
        font-size: 11px;
        font-weight: 500;
        padding: 2px 10px;
        border-radius: 12px;
    }
    
    .today-status.checked-in {
        background: #DCFCE7;
        color: #065F46;
    }
    
    .today-status.checked-out {
        background: #F3F4F6;
        color: #6B7A7B;
    }
    
    .today-status.absent {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    .today-status.not-started {
        background: #FEF3C7;
        color: #92400E;
    }
    
    .btn-action {
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
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-view:hover { background: #BFDBFE; }
    
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-bar select,
        .filter-bar input {
            width: 100%;
        }
        .user-card {
            flex-direction: column;
            align-items: stretch;
        }
        .user-card .attendance-summary {
            justify-content: space-around;
        }
        .user-card .attendance-percentage {
            text-align: left;
        }
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
        .user-card .attendance-summary {
            flex-wrap: wrap;
        }
        .user-card .attendance-summary .att-item {
            flex: 1;
            min-width: 50px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-check" style="color: #16A34A;"></i>
            Attendance List
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo $totalUsers; ?> users)
            </span>
        </h3>
        <span style="font-size: 13px; color: #6B7A7B;">
            <i class="fas fa-calendar-alt"></i>
            <?php echo date('F Y', strtotime("$year-$month-01")); ?>
            | Working Days: <strong><?php echo $workingDays; ?></strong>
        </span>
    </div>
    
    <!-- Stats Row -->
    <?php
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalHalf = 0;
    $totalLeave = 0;
    $totalOvertime = 0;
    
    foreach ($userList as $user) {
        $totalPresent += $user['present_days'] ?? 0;
        $totalAbsent += $user['absent_days'] ?? 0;
        $totalHalf += $user['half_days'] ?? 0;
        $totalLeave += $user['leave_days'] ?? 0;
        $totalOvertime += $user['total_overtime'] ?? 0;
    }
    ?>
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-number" style="color: #14532D;"><?php echo count($userList); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #16A34A;"><?php echo $totalPresent; ?></div>
            <div class="stat-label">Present Days</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #DC2626;"><?php echo $totalAbsent; ?></div>
            <div class="stat-label">Absent Days</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #D97706;"><?php echo $totalHalf; ?></div>
            <div class="stat-label">Half Days</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #3B82F6;"><?php echo $totalLeave; ?></div>
            <div class="stat-label">Leaves</div>
        </div>
        <!-- <div class="stat-box">
            <div class="stat-number" style="color: #7C3AED;"><?php echo number_format($totalOvertime, 1); ?>h</div>
            <div class="stat-label">Total Overtime</div>
        </div> -->
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; width: 100%;">
            <input type="text" name="search" placeholder="🔍 Search by name..." value="<?php echo escapeHtml($search); ?>" style="flex: 1; min-width: 150px;">
            
            <select name="role">
                <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>👤 All Roles</option>
                <option value="staff" <?php echo $role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                <option value="agent" <?php echo $role === 'agent' ? 'selected' : ''; ?>>Agent</option>
            </select>
            
            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            
            <select name="year">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
            
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            
            <?php if (!empty($search) || $role !== 'all'): ?>
                <a href="attendance-list.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn-clear">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Info -->
    <div style="margin-bottom: 12px; font-size: 13px; color: #6B7A7B; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between;">
        <span>
            <i class="fas fa-info-circle"></i>
            Showing <?php echo count($userList); ?> of <?php echo $totalUsers; ?> users
            <?php if (!empty($search)): ?>
                (search: "<?php echo escapeHtml($search); ?>")
            <?php endif; ?>
        </span>
        <span>
            <i class="fas fa-calendar-day"></i>
            Working Days: <strong><?php echo $workingDays; ?></strong>
            (Total: <?php echo $totalDays; ?> days)
        </span>
    </div>
    
    <!-- User Cards -->
    <?php if (empty($userList)): ?>
        <div style="text-align: center; padding: 40px 20px; color: #6B7A7B;">
            <i class="fas fa-users-slash" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
            <p>No users found matching your criteria</p>
        </div>
    <?php else: ?>
        <?php foreach ($userList as $user): 
            $attPercent = $workingDays > 0 ? round(($user['present_days'] ?? 0) / $workingDays * 100) : 0;
            $percentClass = $attPercent >= 80 ? 'high' : ($attPercent >= 50 ? 'medium' : 'low');
        ?>
        <div class="user-card">
            <div class="user-info">
                <div class="user-name">
                    <?php echo escapeHtml($user['full_name']); ?>
                    <span class="role-badge <?php echo $user['role']; ?>">
                        <?php echo $roleLabels[$user['role']] ?? ucfirst($user['role']); ?>
                    </span>
                    <?php if ($user['role'] === 'agent' && $user['agent_code']): ?>
                        <span style="font-size: 11px; color: #7C3AED; background: #EDE9FE; padding: 1px 8px; border-radius: 8px;">
                            <?php echo escapeHtml($user['agent_code']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'staff' && $user['department']): ?>
                        <span style="font-size: 11px; color: #16A34A; background: #DCFCE7; padding: 1px 8px; border-radius: 8px;">
                            <?php echo escapeHtml($user['department']); ?>
                        </span>
                    <?php endif; ?>
                    <span class="badge-status <?php echo $statusColors[$user['status']] ?? 'badge-secondary'; ?>">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </div>
                <div class="user-details">
                    <span><i class="fas fa-user-tag"></i> <?php echo escapeHtml($user['username']); ?></span>
                    <span><i class="fas fa-envelope"></i> <?php echo escapeHtml($user['email']); ?></span>
                    <?php if ($user['today_status']): ?>
                        <span>
                            <i class="fas fa-circle" style="color: <?php echo $user['today_status'] === 'present' ? '#16A34A' : ($user['today_status'] === 'absent' ? '#DC2626' : '#D97706'); ?>; font-size: 8px;"></i>
                            Today: 
                            <?php if ($user['today_check_in'] && $user['today_check_out']): ?>
                                <span class="today-status checked-out">Checked Out</span>
                            <?php elseif ($user['today_check_in']): ?>
                                <span class="today-status checked-in">Checked In</span>
                            <?php elseif ($user['today_status'] === 'absent'): ?>
                                <span class="today-status absent">Absent</span>
                            <?php elseif ($user['today_status'] === 'leave'): ?>
                                <span class="today-status not-started">Leave</span>
                            <?php else: ?>
                                <span class="today-status not-started">Not Started</span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="attendance-summary">
                <div class="att-item">
                    <div class="att-number present"><?php echo $user['present_days'] ?? 0; ?></div>
                    <div class="att-label">Present</div>
                </div>
                <div class="att-item">
                    <div class="att-number absent"><?php echo $user['absent_days'] ?? 0; ?></div>
                    <div class="att-label">Absent</div>
                </div>
                <div class="att-item">
                    <div class="att-number half"><?php echo $user['half_days'] ?? 0; ?></div>
                    <div class="att-label">Half</div>
                </div>
                <div class="att-item">
                    <div class="att-number leave"><?php echo $user['leave_days'] ?? 0; ?></div>
                    <div class="att-label">Leave</div>
                </div>
                <!-- <div class="att-item">
                    <div class="att-number" style="color: #7C3AED; font-size: 14px;"><?php echo number_format($user['total_overtime'] ?? 0, 1); ?>h</div>
                    <div class="att-label">OT</div>
                </div> -->
            </div>
            
            <div class="attendance-percentage">
                <div class="percent <?php echo $percentClass; ?>"><?php echo $attPercent; ?>%</div>
                <div class="label">Attendance</div>
            </div>
            
            <div class="user-actions">
                <a href="attendance-manage.php?id=<?php echo $user['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="btn-action btn-view" title="View Details">
                    <i class="fas fa-eye"></i> Details
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px; display: flex; justify-content: center;">
                <?php echo $pagination; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>