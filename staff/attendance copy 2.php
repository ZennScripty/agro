<?php
/**
 * SAMRIDHI AGRO - Staff Attendance
 * 
 * This page allows staff to check-in/out with geofence validation
 * and view attendance history with holiday calendar.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'My Attendance';

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

// Get today's attendance
$today = date('Y-m-d');
$sql = "SELECT * FROM attendance WHERE user_id = ? AND date = ?";
$todayAttendance = $db->fetchOne($sql, [$_SESSION['user_id'], $today]);

// Get attendance history (last 30 days)
$sql = "SELECT * FROM attendance 
        WHERE user_id = ? 
        ORDER BY date DESC 
        LIMIT 30";
$attendanceHistory = $db->fetchAll($sql, [$_SESSION['user_id']]);

// ============================================
// HOLIDAY CALENDAR DATA
// ============================================

// Get current month and year for calendar
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get holidays for current month
$sql = "SELECT holiday_date, holiday_name, holiday_type, description 
        FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? AND status = 'active'
        ORDER BY holiday_date ASC";
$holidays = $db->fetchAll($sql, [$month, $year]);

// Get all holidays for current month (for calendar)
$sql = "SELECT holiday_date, holiday_name, holiday_type 
        FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? AND status = 'active'";
$holidayCalendar = $db->fetchAll($sql, [$month, $year]);

// ============================================
// HANDLE CHECK-IN/OUT VIA AJAX
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $location = $_POST['location'] ?? null;
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
    if ($action === 'check_in') {
        // Staff must be within geofence
        $result = recordAttendanceCheckIn($_SESSION['user_id'], $location, $lat, $lng);
        echo json_encode($result);
        exit;
    } elseif ($action === 'check_out') {
        $result = recordAttendanceCheckOut($_SESSION['user_id'], $location, $lat, $lng);
        echo json_encode($result);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Calculate statistics
$totalDays = count($attendanceHistory);
$presentDays = 0;
$absentDays = 0;
$halfDays = 0;
$leaveDays = 0;
foreach ($attendanceHistory as $record) {
    switch ($record['status']) {
        case 'present': $presentDays++; break;
        case 'absent': $absentDays++; break;
        case 'half_day': $halfDays++; break;
        case 'leave': $leaveDays++; break;
    }
}
$attendancePercentage = $totalDays > 0 ? round($presentDays / $totalDays * 100) : 0;

// Convert holidays to associative array for calendar
$holidayDates = [];
foreach ($holidayCalendar as $h) {
    $holidayDates[$h['holiday_date']] = $h;
}
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .attendance-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 16px;
    }
    
    .attendance-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #E5EDE7;
    }
    
    .attendance-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
    }
    
    .attendance-status-large {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px;
        border-radius: 12px;
        text-align: center;
    }
    
    .attendance-status-large .status-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }
    
    .attendance-status-large .status-text {
        font-size: 20px;
        font-weight: 700;
    }
    
    .attendance-status-large .status-time {
        font-size: 14px;
        color: #6B7A7B;
        margin-top: 4px;
    }
    
    .attendance-status-large.checked-in {
        background: #DCFCE7;
        color: #065F46;
    }
    
    .attendance-status-large.checked-out {
        background: #F3F4F6;
        color: #6B7A7B;
    }
    
    .attendance-status-large.absent {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    .btn-checkin {
        padding: 12px 32px;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 16px;
    }
    
    .btn-checkin.check-in {
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
    }
    
    .btn-checkin.check-in:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }
    
    .btn-checkin.check-out {
        background: #DC2626;
        color: white;
    }
    
    .btn-checkin.check-out:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    
    .btn-checkin:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .stats-grid-attendance {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 16px;
    }
    
    .stat-item {
        text-align: center;
        padding: 12px;
        border-radius: 8px;
        background: #F7FCF7;
    }
    
    .stat-item .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-item .stat-label {
        font-size: 12px;
        color: #6B7A7B;
    }
    
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
    
    /* Calendar Styles */
    .calendar-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #E5EDE7;
        margin-bottom: 20px;
    }
    
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #F7FCF7;
        border-bottom: 1px solid #E5EDE7;
    }
    
    .calendar-header .month-year {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
    }
    
    .calendar-header .nav-btn {
        background: none;
        border: none;
        font-size: 16px;
        color: #14532D;
        cursor: pointer;
        padding: 4px 10px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .calendar-header .nav-btn:hover {
        background: #E5EDE7;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #E5EDE7;
    }
    
    .calendar-grid .day-name {
        background: #F7FCF7;
        padding: 8px;
        text-align: center;
        font-weight: 600;
        font-size: 12px;
        color: #6B7A7B;
        text-transform: uppercase;
    }
    
    .calendar-grid .day-cell {
        background: white;
        min-height: 70px;
        padding: 4px 6px;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .calendar-grid .day-cell:hover {
        background: #F0FDF4;
    }
    
    .calendar-grid .day-cell .day-number {
        font-size: 13px;
        font-weight: 500;
        color: #052E16;
        margin-bottom: 2px;
    }
    
    .calendar-grid .day-cell .day-number.other-month {
        color: #D1D5DB;
    }
    
    .calendar-grid .day-cell .day-number.today {
        background: #16A34A;
        color: white;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
    
    .calendar-grid .day-cell .day-number.attendance-present {
        color: #16A34A;
    }
    
    .calendar-grid .day-cell .day-number.attendance-absent {
        color: #DC2626;
    }
    
    .calendar-grid .day-cell .day-number.attendance-half {
        color: #D97706;
    }
    
    .calendar-grid .day-cell .holiday-badge {
        font-size: 7px;
        background: #FEE2E2;
        color: #991B1B;
        padding: 1px 4px;
        border-radius: 3px;
        display: inline-block;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        font-weight: 500;
    }
    
    .calendar-grid .day-cell .holiday-badge.festival {
        background: #FEF3C7;
        color: #92400E;
    }
    
    .calendar-grid .day-cell .holiday-badge.national {
        background: #DBEAFE;
        color: #1E40AF;
    }
    
    .calendar-grid .day-cell .holiday-badge.company {
        background: #EDE9FE;
        color: #5B21B6;
    }
    
    .calendar-grid .day-cell .holiday-badge.public {
        background: #DCFCE7;
        color: #065F46;
    }
    
    .calendar-grid .day-cell .holiday-tooltip {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #052E16;
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 11px;
        white-space: nowrap;
        z-index: 100;
        min-width: 120px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .calendar-grid .day-cell .holiday-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #052E16;
    }
    
    .calendar-grid .day-cell:hover .holiday-tooltip {
        display: block;
    }
    
    .calendar-grid .day-cell.has-holiday {
        background: #FFF5F5;
    }
    
    .calendar-grid .day-cell.has-holiday:hover {
        background: #FFEBEB;
    }
    
    .calendar-grid .day-cell .day-number.holiday-number {
        color: #DC2626;
    }
    
    .calendar-grid .day-cell .attendance-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
        margin-left: 4px;
    }
    
    .attendance-dot.present { background: #16A34A; }
    .attendance-dot.absent { background: #DC2626; }
    .attendance-dot.half { background: #D97706; }
    .attendance-dot.leave { background: #3B82F6; }
    
    .holiday-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 16px;
        background: #F7FCF7;
        border-top: 1px solid #E5EDE7;
        font-size: 11px;
    }
    
    .holiday-legend .legend-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .holiday-legend .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 3px;
    }
    
    .legend-dot.public { background: #DCFCE7; }
    .legend-dot.national { background: #DBEAFE; }
    .legend-dot.festival { background: #FEF3C7; }
    .legend-dot.company { background: #EDE9FE; }
    
    @media (max-width: 768px) {
        .attendance-container {
            grid-template-columns: 1fr;
        }
        .stats-grid-attendance {
            grid-template-columns: repeat(3, 1fr);
        }
        .calendar-grid .day-cell {
            min-height: 55px;
            padding: 3px 4px;
        }
        .calendar-grid .day-cell .day-number {
            font-size: 11px;
        }
        .calendar-grid .day-cell .day-number.today {
            width: 22px;
            height: 22px;
            font-size: 11px;
        }
        .calendar-grid .day-cell .holiday-badge {
            font-size: 6px;
            padding: 1px 3px;
        }
        .calendar-grid .day-cell .holiday-tooltip {
            font-size: 10px;
            min-width: 100px;
            padding: 5px 8px;
            white-space: normal;
            width: 120px;
        }
        .calendar-header .month-year {
            font-size: 14px;
        }
        .calendar-grid .day-name {
            font-size: 10px;
            padding: 4px;
        }
    }
    
    @media (max-width: 480px) {
        .calendar-grid .day-cell {
            min-height: 45px;
            padding: 2px 2px;
        }
        .calendar-grid .day-cell .day-number {
            font-size: 9px;
        }
        .calendar-grid .day-cell .day-number.today {
            width: 18px;
            height: 18px;
            font-size: 9px;
        }
        .calendar-grid .day-cell .holiday-badge {
            font-size: 5px;
            padding: 1px 2px;
        }
        .holiday-legend {
            font-size: 9px;
            gap: 6px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-check" style="color: #16A34A;"></i>
            My Attendance
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo $attendancePercentage; ?>% attendance - Last 30 days)
            </span>
        </h3>
    </div>
    
    <div class="attendance-container">
        <!-- Check-in/Out Section -->
        <div class="attendance-card">
            <div class="card-title">Today's Attendance</div>
            
            <?php if ($todayAttendance): ?>
                <?php if ($todayAttendance['check_out_time']): ?>
                    <div class="attendance-status-large checked-out">
                        <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="status-text">Checked Out</div>
                        <div class="status-time">
                            Checked In: <?php echo date('h:i A', strtotime($todayAttendance['check_in_time'])); ?>
                            <br>
                            Checked Out: <?php echo date('h:i A', strtotime($todayAttendance['check_out_time'])); ?>
                        </div>
                    </div>
                    <button class="btn-checkin check-out" disabled>Already Checked Out</button>
                <?php else: ?>
                    <div class="attendance-status-large checked-in">
                        <div class="status-icon"><i class="fas fa-clock"></i></div>
                        <div class="status-text">Checked In</div>
                        <div class="status-time">
                            Checked In: <?php echo date('h:i A', strtotime($todayAttendance['check_in_time'])); ?>
                            <br>
                            Location: <?php echo escapeHtml($todayAttendance['check_in_location'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <button class="btn-checkin check-out" id="checkOutBtn">
                        <i class="fas fa-sign-out-alt"></i> Check Out
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <div class="attendance-status-large absent">
                    <div class="status-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="status-text">Not Checked In</div>
                    <div class="status-time">Please check in to start your work day</div>
                </div>
                <button class="btn-checkin check-in" id="checkInBtn">
                    <i class="fas fa-sign-in-alt"></i> Check In
                </button>
            <?php endif; ?>
            
            <div style="margin-top: 12px; font-size: 13px; color: #6B7A7B; text-align: center;">
                <i class="fas fa-info-circle"></i> 
                <?php if (hasRole('staff')): ?>
                    You must be within the office geofence to check-in.
                <?php else: ?>
                    You can check-in from anywhere as an agent.
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics Section -->
        <div class="attendance-card">
            <div class="card-title">Attendance Statistics</div>
            
            <div class="stats-grid-attendance">
                <div class="stat-item">
                    <div class="stat-number" style="color: #16A34A;"><?php echo $presentDays; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: #DC2626;"><?php echo $absentDays; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: #D97706;"><?php echo $halfDays; ?></div>
                    <div class="stat-label">Half Day</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: #3B82F6;"><?php echo $leaveDays; ?></div>
                    <div class="stat-label">Leaves</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: #14532D;"><?php echo $totalDays; ?></div>
                    <div class="stat-label">Total Days</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: <?php echo $attendancePercentage >= 80 ? '#16A34A' : ($attendancePercentage >= 50 ? '#D97706' : '#DC2626'); ?>;">
                        <?php echo $attendancePercentage; ?>%
                    </div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>
            
            <div style="margin-top: 12px; background: #F7FCF7; border-radius: 8px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; font-size: 14px;">
                    <span style="color: #6B7A7B;">Attendance Rate</span>
                    <span style="font-weight: 700; color: <?php echo $attendancePercentage >= 80 ? '#16A34A' : ($attendancePercentage >= 50 ? '#D97706' : '#DC2626'); ?>;">
                        <?php echo $attendancePercentage; ?>%
                    </span>
                </div>
                <div style="width: 100%; height: 6px; background: #E5EDE7; border-radius: 4px; margin-top: 4px; overflow: hidden;">
                    <div style="width: <?php echo $attendancePercentage; ?>%; height: 100%; background: <?php echo $attendancePercentage >= 80 ? '#16A34A' : ($attendancePercentage >= 50 ? '#D97706' : '#DC2626'); ?>; border-radius: 4px; transition: width 0.5s ease;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Holiday Calendar -->
    <div style="margin-top: 24px;">
        <div class="calendar-container">
            <div class="calendar-header">
                <button class="nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                <span class="month-year"><?php echo date('F Y', strtotime("$year-$month-01")); ?></span>
                <button class="nav-btn" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
            
            <div class="calendar-grid">
                <?php
                $firstDay = date('N', strtotime("$year-$month-01"));
                $daysInMonth = date('t', strtotime("$year-$month-01"));
                $today = date('Y-m-d');
                
                // Get attendance status for each day
                $attendanceStatus = [];
                foreach ($attendanceHistory as $record) {
                    $attendanceStatus[$record['date']] = $record['status'];
                }
                
                $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                foreach ($dayNames as $name) {
                    echo '<div class="day-name">' . $name . '</div>';
                }
                
                // Empty cells before first day
                for ($i = 1; $i < $firstDay; $i++) {
                    echo '<div class="day-cell"></div>';
                }
                
                // Days of month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = date('Y-m-d', strtotime("$year-$month-$day"));
                    $isToday = $date === $today;
                    $isHoliday = isset($holidayDates[$date]);
                    $holiday = $isHoliday ? $holidayDates[$date] : null;
                    $attStatus = $attendanceStatus[$date] ?? null;
                    
                    $class = 'day-cell';
                    if ($isHoliday) $class .= ' has-holiday';
                    
                    $numberClass = '';
                    if ($isToday) $numberClass = 'today';
                    if ($isHoliday) $numberClass .= ' holiday-number';
                    if ($attStatus === 'present') $numberClass .= ' attendance-present';
                    if ($attStatus === 'absent') $numberClass .= ' attendance-absent';
                    if ($attStatus === 'half_day') $numberClass .= ' attendance-half';
                    
                    echo '<div class="' . $class . '">';
                    echo '<div class="day-number ' . trim($numberClass) . '">' . $day;
                    
                    // Attendance dot
                    if ($attStatus) {
                        $dotClass = $attStatus === 'present' ? 'present' : ($attStatus === 'absent' ? 'absent' : ($attStatus === 'half_day' ? 'half' : 'leave'));
                        echo '<span class="attendance-dot ' . $dotClass . '"></span>';
                    }
                    
                    echo '</div>';
                    
                    if ($isHoliday) {
                        $typeClass = $holiday['holiday_type'] ?? 'public';
                        echo '<span class="holiday-badge ' . $typeClass . '">' . escapeHtml($holiday['holiday_name']) . '</span>';
                        echo '<div class="holiday-tooltip">';
                        echo '<strong>' . escapeHtml($holiday['holiday_name']) . '</strong><br>';
                        if (!empty($holiday['description'])) {
                            echo '<span style="font-size: 10px; opacity: 0.8;">' . escapeHtml($holiday['description']) . '</span><br>';
                        }
                        echo '<span style="font-size: 10px; opacity: 0.8;">' . ucfirst($holiday['holiday_type']) . ' Holiday</span>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Legend -->
            <div class="holiday-legend">
                <span style="font-weight: 600; color: #052E16;">Legend:</span>
                <span class="legend-item">
                    <span class="legend-dot public"></span> Public
                </span>
                <span class="legend-item">
                    <span class="legend-dot national"></span> National
                </span>
                <span class="legend-item">
                    <span class="legend-dot festival"></span> Festival
                </span>
                <span class="legend-item">
                    <span class="legend-dot company"></span> Company
                </span>
                <span class="legend-item">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #16A34A;"></span> Present
                </span>
                <span class="legend-item">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #DC2626;"></span> Absent
                </span>
                <span class="legend-item">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #D97706;"></span> Half Day
                </span>
                <span class="legend-item">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #3B82F6;"></span> Leave
                </span>
            </div>
        </div>
    </div>
    
    <!-- History Table -->
    <div style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h4 style="font-family: 'Space Grotesk', sans-serif; font-size: 14px; font-weight: 600; color: #052E16; margin: 0;">
                <i class="fas fa-history" style="color: #6B7A7B;"></i> Recent Attendance History
            </h4>
        </div>
        
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceHistory)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #6B7A7B;">
                            <i class="fas fa-calendar-day" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                            No attendance records found
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($attendanceHistory as $record): ?>
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
                                <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
                                <?php echo escapeHtml($record['check_in_location']); ?>
                            <?php else: ?>
                                <span style="color: #6B7A7B;">N/A</span>
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
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo $csrfToken; ?>';

// ============================================
// CALENDAR NAVIGATION
// ============================================

function changeMonth(delta) {
    const currentMonth = <?php echo $month; ?>;
    const currentYear = <?php echo $year; ?>;
    let newMonth = currentMonth + delta;
    let newYear = currentYear;
    
    if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    } else if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    }
    
    window.location.href = 'attendance.php?month=' + newMonth + '&year=' + newYear;
}

// ============================================
// CHECK IN
// ============================================

const checkInBtn = document.getElementById('checkInBtn');
if (checkInBtn) {
    checkInBtn.addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    checkIn(lat, lng);
                },
                function(error) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Unavailable',
                        text: 'Could not get your location. Check-in without location?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, proceed',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            checkIn(null, null);
                        }
                    });
                }
            );
        } else {
            checkIn(null, null);
        }
    });
}

function checkIn(lat, lng) {
    const formData = new FormData();
    formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);
    formData.append('action', 'check_in');
    formData.append('lat', lat);
    formData.append('lng', lng);
    
    Swal.fire({
        title: 'Checking In...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Checked In!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Check-in Failed',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}

// ============================================
// CHECK OUT
// ============================================

const checkOutBtn = document.getElementById('checkOutBtn');
if (checkOutBtn) {
    checkOutBtn.addEventListener('click', function() {
        Swal.fire({
            title: 'Check Out?',
            text: 'Are you sure you want to check out?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, check out',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);
                formData.append('action', 'check_out');
                
                Swal.fire({
                    title: 'Checking Out...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Checked Out!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Check-out Failed',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong. Please try again.'
                    });
                });
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>