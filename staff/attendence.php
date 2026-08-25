<?php

/**
 * SAMRIDHI AGRO - Staff Attendance
 *
 * Staff check-in/check-out with office geofence validation (driven by
 * admin attendance settings), reverse-geocoded location save (same
 * pattern as agent/attendence.php), and a holiday calendar that
 * follows the admin-configured weekly holidays.
 *
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 3.1.0
 */

$pageTitle = 'My Attendance';

// ============================================
// LOAD BACKEND FILES ONLY
// ============================================

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// CHECK LOGIN
// ============================================

if (!isset($_SESSION['user_id'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => false,
            'message' => 'Session expired. Please login again.'
        ]);

        exit;
    }

    header('');
    exit;
}

// ============================================
// CHECK ROLE (staff only) - before any HTML output
// ============================================

requireLogin();
requireRole('staff');

// Get database instance
$db = getDB();

// ============================================
// LOAD ADMIN ATTENDANCE SETTINGS
// (same settings table admin/attendance-settings.php writes to)
// ============================================

$sql = "SELECT * FROM attendance_settings";
$allSettings = $db->fetchAll($sql);
$settings = [];
foreach ($allSettings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$checkInStartTime   = $settings['check_in_start_time'] ?? '09:00:00';
$checkInEndTime     = $settings['check_in_end_time'] ?? '10:00:00';
$checkOutStartTime  = $settings['check_out_start_time'] ?? '17:30:00';
$checkOutEndTime    = $settings['check_out_end_time'] ?? '23:59:00';
$workHours          = $settings['work_hours'] ?? '8.00';
$requireGeolocation = ($settings['allow_geolocation'] ?? '1') == '1';
$geoRadius          = (int)($settings['geolocation_radius'] ?? 500);
$officeLat          = isset($settings['office_lat']) ? (float)$settings['office_lat'] : 28.6139;
$officeLng          = isset($settings['office_lng']) ? (float)$settings['office_lng'] : 77.2090;

// Weekly holidays configured by admin (e.g. "Sunday" or "Sunday,Saturday")
$weeklyHolidays = [];
if (!empty($settings['weekly_holidays'])) {
    $weeklyHolidays = array_map('trim', explode(',', $settings['weekly_holidays']));
}
$dayFullNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/**
 * Haversine distance between two lat/lng points, in meters.
 * Used to enforce the admin-configured office geofence for staff check-in.
 */
if (!function_exists('calculateDistanceMeters')) {
    function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad((float)$lat1);
        $lon1 = deg2rad((float)$lon1);
        $lat2 = deg2rad((float)$lat2);
        $lon2 = deg2rad((float)$lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

// ============================================
// HANDLE CHECK-IN / CHECK-OUT AJAX
// IMPORTANT: THIS MUST COME BEFORE HTML
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';

    $lat = isset($_POST['lat']) && $_POST['lat'] !== ''
        ? (float)$_POST['lat']
        : null;

    $lng = isset($_POST['lng']) && $_POST['lng'] !== ''
        ? (float)$_POST['lng']
        : null;

    $location = isset($_POST['location'])
        ? trim($_POST['location'])
        : null;

    // CSRF validation
    if (
        !isset($_POST[CSRF_TOKEN_NAME]) ||
        !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid security token.'
        ]);

        exit;
    }

    // ============================================
    // ENFORCE OFFICE GEOFENCE FOR CHECK-IN
    // (server-side — the real source of truth; the client-side
    // check in JS below is only for a fast, friendly warning)
    // ============================================
    if ($action === 'check_in' && $requireGeolocation) {

        if ($lat === null || $lng === null) {
            echo json_encode([
                'success' => false,
                'message' => 'Location is required to check-in. Please allow location access and try again.'
            ]);
            exit;
        }

        $distance = calculateDistanceMeters($officeLat, $officeLng, $lat, $lng);

        if ($distance > $geoRadius) {
            echo json_encode([
                'success' => false,
                'message' => 'You are ' . round($distance) . 'm away from the office. Check-in is only allowed within ' . $geoRadius . 'm of the office location.'
            ]);
            exit;
        }
    }

    // CHECK IN
    if ($action === 'check_in') {

        // ============================================
        // ENFORCE CHECK-IN TIME WINDOW
        // ============================================
        $currentTime = date('H:i:s');

        if ($currentTime < $checkInStartTime || $currentTime > $checkInEndTime) {
            echo json_encode([
                'success' => false,
                'message' => 'Check-in is only allowed between ' .
                    date('h:i A', strtotime($checkInStartTime)) . ' and ' .
                    date('h:i A', strtotime($checkInEndTime)) . '.'
            ]);
            exit;
        }

        $result = recordAttendanceCheckIn(
            $_SESSION['user_id'],
            $location,
            $lat,
            $lng
        );

        echo json_encode(
            $result,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    // CHECK OUT
    if ($action === 'check_out') {

        // ============================================
        // ENFORCE CHECK-OUT TIME WINDOW
        // (mirrors the admin-configured check-in window above —
        // this was previously validated only for check-in)
        // ============================================
        $currentTime = date('H:i:s');

        if ($currentTime < $checkOutStartTime || $currentTime > $checkOutEndTime) {
            echo json_encode([
                'success' => false,
                'message' => 'Check-out is only allowed between ' .
                    date('h:i A', strtotime($checkOutStartTime)) . ' and ' .
                    date('h:i A', strtotime($checkOutEndTime)) .
                    '.'
            ]);
            exit;
        }

        $result = recordAttendanceCheckOut(
            $_SESSION['user_id'],
            $location,
            $lat,
            $lng
        );

        echo json_encode(
            $result,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid attendance action.'
    ]);

    exit;
}

// ============================================
// NORMAL PAGE REQUEST ONLY
// ============================================

// Now it is safe to load the HTML header
require_once __DIR__ . '/../includes/staff_header.php';

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

// Validate month and year
if ($month < 1 || $month > 12) {
    $month = date('m');
}
if ($year < 2000 || $year > 2100) {
    $year = date('Y');
}

// Get holidays for current month
$sql = "SELECT holiday_date, holiday_name, holiday_type, description 
        FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? AND status = 'active'
        ORDER BY holiday_date ASC";
$holidays = $db->fetchAll($sql, [$month, $year]);

// Get all holidays for current month (for calendar)
$sql = "SELECT holiday_date, holiday_name, holiday_type, description 
        FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? AND status = 'active'";
$holidayCalendar = $db->fetchAll($sql, [$month, $year]);

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
        case 'present':
            $presentDays++;
            break;
        case 'absent':
            $absentDays++;
            break;
        case 'half_day':
            $halfDays++;
            break;
        case 'leave':
            $leaveDays++;
            break;
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
    /* ===== BASE STYLES ===== */
    body {
        background: #F0F7F2;
    }

    .content-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(5, 46, 22, 0.08);
        padding: 28px 32px;
        margin-bottom: 24px;
        transition: box-shadow 0.3s ease;
        border: 1px solid rgba(229, 237, 231, 0.5);
    }

    .content-card:hover {
        box-shadow: 0 12px 48px rgba(5, 46, 22, 0.12);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #F0FDF4;
        flex-wrap: wrap;
        gap: 12px;
    }

    .card-header .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #052E16;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header .card-title i {
        color: #16A34A;
    }

    /* ===== ATTENDANCE CONTAINER ===== */
    .attendance-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 16px;
    }

    .attendance-card {
        background: #FAFDFA;
        border-radius: 14px;
        padding: 24px 28px;
        border: 1px solid #E5EDE7;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.04);
        position: relative;
        overflow: hidden;
    }

    .attendance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #16A34A, #4ADE80, #16A34A);
        background-size: 200% 100%;
        animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {

        0%,
        100% {
            background-position: -200% 0;
        }

        50% {
            background-position: 200% 0;
        }
    }

    .attendance-card:hover {
        border-color: #86EFAC;
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.08);
        transform: translateY(-2px);
    }

    .attendance-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 17px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 2px solid #F0FDF4;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .attendance-card .card-title .badge-count {
        font-size: 12px;
        font-weight: 500;
        color: #16A34A;
        background: #DCFCE7;
        padding: 2px 12px;
        border-radius: 20px;
        font-family: 'Inter', sans-serif;
    }

    .attendance-status-large {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 28px 20px;
        border-radius: 12px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .attendance-status-large .status-icon {
        font-size: 52px;
        margin-bottom: 12px;
    }

    .attendance-status-large .status-text {
        font-size: 22px;
        font-weight: 700;
    }

    .attendance-status-large .status-time {
        font-size: 14px;
        margin-top: 6px;
        line-height: 1.6;
        opacity: 0.85;
    }

    .attendance-status-large.checked-in {
        background: linear-gradient(135deg, #DCFCE7, #BBF7D0);
        color: #065F46;
        border: 2px solid #86EFAC;
        box-shadow: 0 4px 20px rgba(22, 163, 74, 0.12);
    }

    .attendance-status-large.checked-out {
        background: linear-gradient(135deg, #F3F4F6, #E5E7EB);
        color: #4A5B5D;
        border: 2px solid #E5EDE7;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    .attendance-status-large.absent {
        background: linear-gradient(135deg, #FFF7ED, #FFEDD5);
        color: #9A3412;
        border: 2px solid #FDBA74;
        box-shadow: 0 4px 20px rgba(234, 88, 12, 0.10);
    }

    /* ===== BUTTONS ===== */
    .btn-checkin {
        padding: 14px 32px;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 16px;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-checkin.check-in {
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.25);
    }

    .btn-checkin.check-in:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 24px rgba(22, 163, 74, 0.35);
        background: linear-gradient(135deg, #0D3B20, #16A34A);
    }

    .btn-checkin.check-in:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2);
    }

    .btn-checkin.check-out {
        background: linear-gradient(135deg, #DC2626, #B91C1C);
        color: white;
        box-shadow: 0 4px 16px rgba(220, 38, 38, 0.25);
    }

    .btn-checkin.check-out:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 24px rgba(220, 38, 38, 0.35);
        background: linear-gradient(135deg, #B91C1C, #991B1B);
    }

    .btn-checkin.check-out:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
    }

    .btn-checkin:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    /* ===== STATISTICS ===== */
    .stats-grid-attendance {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 16px;
    }

    .stat-item {
        text-align: center;
        padding: 14px 8px;
        border-radius: 10px;
        background: white;
        border: 1px solid #E5EDE7;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(5, 46, 22, 0.04);
    }

    .stat-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(5, 46, 22, 0.08);
        border-color: #86EFAC;
    }

    .stat-item .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
        display: block;
        line-height: 1.2;
    }

    .stat-item .stat-label {
        font-size: 12px;
        color: #6B7A7B;
        font-weight: 500;
        margin-top: 2px;
    }

    /* Progress Bar */
    .progress-container {
        margin-top: 16px;
        background: white;
        border-radius: 10px;
        padding: 14px 18px;
        border: 1px solid #E5EDE7;
        box-shadow: 0 1px 3px rgba(5, 46, 22, 0.04);
    }

    .progress-container .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        margin-bottom: 6px;
    }

    .progress-container .progress-label span:first-child {
        color: #6B7A7B;
        font-weight: 500;
    }

    .progress-container .progress-label span:last-child {
        font-weight: 700;
    }

    .progress-bar-track {
        width: 100%;
        height: 8px;
        background: #E5EDE7;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 1s ease;
        background: linear-gradient(90deg, #16A34A, #4ADE80);
    }

    .progress-bar-fill.low {
        background: linear-gradient(90deg, #DC2626, #F87171);
    }

    .progress-bar-fill.medium {
        background: linear-gradient(90deg, #D97706, #FBBF24);
    }

    /* ===== ATTENDANCE BADGE ===== */
    .attendance-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }

    .attendance-badge.present {
        background: #DCFCE7;
        color: #065F46;
    }

    .attendance-badge.absent {
        background: #FEE2E2;
        color: #991B1B;
    }

    .attendance-badge.half_day {
        background: #FEF3C7;
        color: #92400E;
    }

    .attendance-badge.leave {
        background: #DBEAFE;
        color: #1E40AF;
    }

    /* ===== CALENDAR ===== */
    .calendar-container {
        background: white;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #E5EDE7;
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.04);
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .calendar-container:hover {
        box-shadow: 0 4px 16px rgba(5, 46, 22, 0.08);
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
        background: linear-gradient(135deg, #F7FCF7, #F0FDF4);
        border-bottom: 1px solid #E5EDE7;
    }

    .calendar-header .month-year {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 17px;
        font-weight: 600;
        color: #052E16;
        letter-spacing: 0.5px;
    }

    .calendar-header .nav-btn {
        background: white;
        border: 2px solid #E5EDE7;
        font-size: 14px;
        color: #14532D;
        cursor: pointer;
        padding: 6px 14px;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(5, 46, 22, 0.04);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
    }

    .calendar-header .nav-btn:hover {
        background: #16A34A;
        color: white;
        border-color: #16A34A;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.2);
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #E5EDE7;
    }

    .calendar-grid .day-name {
        background: #F7FCF7;
        padding: 10px 6px;
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .calendar-grid .day-name.weekly-holiday {
        color: #1E40AF;
        background: #EFF6FF;
    }

    .calendar-grid .day-cell {
        background: white;
        min-height: 80px;
        padding: 6px 8px;
        position: relative;
        transition: all 0.3s ease;
    }

    .calendar-grid .day-cell.has-detail {
        cursor: pointer;
    }

    .calendar-grid .day-cell:hover {
        background: #F7FCF7;
        z-index: 2;
    }

    .calendar-grid .day-cell.day-active {
        background: #F0FDF4;
        box-shadow: inset 0 0 0 2px #16A34A;
        z-index: 3;
    }

    .calendar-grid .day-cell .day-number {
        font-size: 14px;
        font-weight: 500;
        color: #052E16;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .calendar-grid .day-cell .day-number.today {
        background: linear-gradient(135deg, #16A34A, #059669);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 2px 12px rgba(22, 163, 74, 0.3);
    }

    .calendar-grid .day-cell .day-number.attendance-present {
        color: #fefffe;
        font-weight: 700;
    }

    .calendar-grid .day-cell .day-number.attendance-absent {
        color: #DC2626;
        font-weight: 700;
    }

    .calendar-grid .day-cell .day-number.attendance-half {
        color: #D97706;
        font-weight: 700;
    }

    .calendar-grid .day-cell .day-number.weekly-holiday {
        color: #1E40AF;
    }

    .calendar-grid .day-cell .day-number.holiday-number {
        color: #DC2626;
    }

    .calendar-grid .day-cell .day-number.weekly-holiday.holiday-number {
        color: #DC2626;
    }

    /* Attendance Status Text Badge */
    .attendance-status-text {
        font-size: 9px;
        font-weight: 700;
        display: inline-block;
        padding: 1px 5px;
        border-radius: 3px;
        letter-spacing: 0.5px;
        position: absolute;
        right: 5%;
        top: 10%;
        border: 0.5px solid #0000004d;
    }

    .attendance-status-text.present {
        background: #DCFCE7;
        color: #065F46;
    }

    .attendance-status-text.absent {
        background: #FEE2E2;
        color: #991B1B;
    }

    .attendance-status-text.half {
        background: #FEF3C7;
        color: #92400E;
    }

    .attendance-status-text.leave {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .calendar-grid .day-cell .holiday-badge {
        font-size: 9px;
        background: #FEE2E2;
        color: #991B1B;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        font-weight: 600;
        letter-spacing: 0.2px;
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

    .calendar-grid .day-cell .holiday-badge.weekly {
        background: #FEF3C7;
        color: #92400E;
    }

    .calendar-grid .day-cell.has-holiday {
        background: #FFF5F5;
    }

    .calendar-grid .day-cell.has-holiday:hover {
        background: #FFEBEB;
    }

    .calendar-grid .day-cell .weekly-holiday-badge {
        font-size: 9px;
        background: #DBEAFE;
        color: #1E40AF;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        margin-top: 2px;
        font-weight: 600;
    }

    /* ===== DATE DETAIL POPOVER =====
       Rendered once, appended to <body>, and positioned with JS using
       position:fixed so it can never be clipped by the calendar
       card's overflow:hidden — and clamped to the viewport so it never
       runs off-screen on the left/right edge dates or on mobile. */
    .day-popover-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(5, 46, 22, 0.15);
        z-index: 1000;
    }

    .day-popover-backdrop.open {
        display: block;
    }

    .day-popover {
        display: none;
        position: fixed;
        z-index: 1001;
        max-width: 260px;
        width: max-content;
        background: #052E16;
        color: white;
        border-radius: 12px;
        padding: 14px 16px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.28);
        font-size: 12px;
        line-height: 1.5;
    }

    .day-popover.open {
        display: block;
        animation: popoverIn 0.15s ease;
    }

    @keyframes popoverIn {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .day-popover .day-popover-close {
        position: absolute;
        top: 6px;
        right: 8px;
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        padding: 4px;
    }

    .day-popover .day-popover-close:hover {
        color: white;
    }

    .day-popover .day-popover-date {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #86EFAC;
        font-weight: 700;
        margin-bottom: 6px;
        padding-right: 16px;
    }

    .day-popover .day-popover-row {
        margin-bottom: 6px;
    }

    .day-popover .day-popover-row:last-child {
        margin-bottom: 0;
    }

    .day-popover .day-popover-title {
        font-weight: 700;
        font-size: 13px;
    }

    .day-popover .day-popover-sub {
        opacity: 0.8;
        font-size: 11px;
        margin-top: 2px;
    }

    .day-popover .day-popover-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-top: 2px;
    }

    .day-popover .day-popover-tag.present {
        background: #DCFCE7;
        color: #065F46;
    }

    .day-popover .day-popover-tag.absent {
        background: #FEE2E2;
        color: #991B1B;
    }

    .day-popover .day-popover-tag.half {
        background: #FEF3C7;
        color: #92400E;
    }

    .day-popover .day-popover-tag.leave,
    .day-popover .day-popover-tag.weekly {
        background: #DBEAFE;
        color: #1E40AF;
    }

    /* ===== LEGEND ===== */
    .holiday-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        padding: 12px 20px;
        background: #F7FCF7;
        border-top: 1px solid #E5EDE7;
        font-size: 11px;
        font-weight: 500;
    }

    .holiday-legend .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #4A5B5D;
    }

    .holiday-legend .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 4px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .legend-dot.public {
        background: #DCFCE7;
    }

    .legend-dot.national {
        background: #DBEAFE;
    }

    .legend-dot.festival {
        background: #FEF3C7;
    }

    .legend-dot.company {
        background: #EDE9FE;
    }

    .legend-dot.weekly {
        background: #FEF3C7;
    }

    .legend-text-badge {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: 700;
    }

    .legend-text-badge.present {
        background: #DCFCE7;
        color: #065F46;
    }

    .legend-text-badge.absent {
        background: #FEE2E2;
        color: #991B1B;
    }

    .legend-text-badge.half {
        background: #FEF3C7;
        color: #92400E;
    }

    .legend-text-badge.leave {
        background: #DBEAFE;
        color: #1E40AF;
    }

    /* ===== TABLE ===== */
    .table-wrapper {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #E5EDE7;
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.04);
        transition: all 0.3s ease;
    }

    .table-wrapper:hover {
        box-shadow: 0 4px 16px rgba(5, 46, 22, 0.08);
    }

    .table-custom {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-custom thead th {
        background: linear-gradient(135deg, #F7FCF7, #F0FDF4);
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: #14532D;
        border-bottom: 2px solid #E5EDE7;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .table-custom tbody tr {
        transition: all 0.3s ease;
    }

    .table-custom tbody tr:hover {
        background: #F7FCF7;
        transform: scale(1.01);
    }

    .table-custom td {
        padding: 14px 16px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid #F0FDF4;
        color: #052E16;
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    .table-custom .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6B7A7B;
    }

    .table-custom .empty-state i {
        font-size: 32px;
        display: block;
        margin-bottom: 12px;
        opacity: 0.4;
        color: #16A34A;
    }

    /* ===== INFO TEXT ===== */
    .info-text {
        margin-top: 12px;
        font-size: 13px;
        color: #6B7A7B;
        text-align: center;
        background: #F7FCF7;
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid #E5EDE7;
    }

    .info-text i {
        color: #16A34A;
    }

    .info-text.geo-rule {
        color: #92400E;
        background: #FFFBEB;
        border-color: #FDE68A;
        text-align: left;
    }

    .info-text.geo-rule i {
        color: #D97706;
    }

    .info-text.checkout-rule {
        color: #1E40AF;
        background: #EFF6FF;
        border-color: #BFDBFE;
    }

    .info-text.checkout-rule i {
        color: #2563EB;
    }

    /* ===== LOCATION STATUS (below Check In/Out button) ===== */
    .location-status {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 8px;
        background: #F7FCF7;
        border: 1px solid #E5EDE7;
        text-align: left;
    }

    .location-status.success {
        color: #065F46;
        background: #F0FDF4;
        border-color: #86EFAC;
    }

    .location-status.error {
        color: #991B1B;
        background: #FEF2F2;
        border-color: #FCA5A5;
    }

    .location-status.warning {
        color: #92400E;
        background: #FFFBEB;
        border-color: #FDE68A;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .content-card {
            padding: 20px 16px;
        }

        .attendance-container {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .attendance-card {
            padding: 18px 16px;
        }

        .stats-grid-attendance {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .stat-item .stat-number {
            font-size: 22px;
        }

        .calendar-grid .day-cell {
            min-height: 58px;
            padding: 4px 4px;
        }

        .calendar-grid .day-cell .day-number {
            font-size: 13px;
        }

        .calendar-grid .day-cell .day-number.today {
            width: 24px;
            height: 24px;
            font-size: 12px;
        }

        .calendar-grid .day-cell .holiday-badge,
        .calendar-grid .day-cell .weekly-holiday-badge {
            font-size: 8px;
            padding: 1px 4px;
        }

        .day-popover {
            max-width: calc(100vw - 24px);
            font-size: 12px;
        }

        .calendar-header .month-year {
            font-size: 15px;
        }

        .calendar-grid .day-name {
            font-size: 10px;
            padding: 7px 3px;
        }

        .calendar-header {
            padding: 12px 14px;
        }

        .calendar-header .nav-btn {
            padding: 6px 10px;
            font-size: 12px;
        }

        .card-header .card-title {
            font-size: 20px;
        }

        .attendance-status-large .status-icon {
            font-size: 44px;
        }

        .attendance-status-large .status-text {
            font-size: 20px;
        }

        .holiday-legend {
            font-size: 11px;
            gap: 8px;
            padding: 10px 14px;
        }

        .table-custom thead th,
        .table-custom td {
            padding: 12px 14px;
            font-size: 14px;
        }

        .btn-checkin {
            font-size: 16px;
            padding: 14px 28px;
        }

        .progress-container {
            padding: 14px 16px;
        }

        .attendance-status-text {
            font-size: 9px;
            padding: 1px 5px;
            top: 4px;
            right: 4px;
        }
    }

    @media (max-width: 480px) {
        .content-card {
            padding: 16px 12px;
        }

        .stats-grid-attendance {
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }

        .stat-item {
            padding: 12px 6px;
        }

        .stat-item .stat-number {
            font-size: 20px;
        }

        .stat-item .stat-label {
            font-size: 11px;
        }

        .calendar-grid {
            gap: 1px;
        }

        .calendar-grid .day-cell {
            min-height: 48px;
            padding: 3px 2px;
        }

        .calendar-grid .day-cell .day-number {
            font-size: 12px;
            gap: 0;
        }

        .calendar-grid .day-cell .day-number.today {
            width: 20px;
            height: 20px;
            font-size: 11px;
        }

        .calendar-grid .day-cell .holiday-badge,
        .calendar-grid .day-cell .weekly-holiday-badge {
            font-size: 0;
            padding: 0;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-top: 3px;
            overflow: visible;
        }

        .calendar-grid .day-name {
            font-size: 9px;
            padding: 6px 1px;
        }

        .holiday-legend {
            font-size: 10px;
            gap: 6px;
            padding: 10px 10px;
        }

        .holiday-legend .legend-dot {
            width: 10px;
            height: 10px;
        }

        .attendance-status-large {
            padding: 22px 18px;
        }

        .attendance-status-large .status-icon {
            font-size: 36px;
        }

        .attendance-status-large .status-text {
            font-size: 18px;
        }

        .attendance-status-large .status-time {
            font-size: 14px;
        }

        .table-custom thead th,
        .table-custom td {
            padding: 10px 10px;
            font-size: 14px;
        }

        .btn-checkin {
            font-size: 15px;
            padding: 12px 22px;
        }

        .card-header .card-title {
            font-size: 18px;
        }

        .attendance-card .card-title {
            font-size: 17px;
        }

        .info-text {
            font-size: 14px;
            padding: 10px 14px;
        }

        .attendance-status-text {
            top: 2px;
            right: 2px;
            font-size: 8px;
            padding: 1px 4px;
        }
    }

    /* ===== HISTORY: TABLE -> CARD LIST ON MOBILE ===== */
    @media (max-width: 640px) {
        .table-wrapper {
            overflow-x: visible;
            border: none;
            box-shadow: none;
            border-radius: 0;
        }

        .table-custom thead {
            display: none;
        }

        .table-custom,
        .table-custom tbody,
        .table-custom tr,
        .table-custom td {
            display: block;
            width: 100%;
        }

        .table-custom tbody tr {
            background: white;
            border: 1px solid #E5EDE7;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(5, 46, 22, 0.05);
        }

        .table-custom tbody tr:hover {
            transform: none;
            background: white;
        }

        .table-custom tbody tr:last-child {
            margin-bottom: 0;
        }

        .table-custom td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #F0FDF4;
            text-align: right;
            font-size: 15px;
        }

        .table-custom tbody tr td:last-child {
            border-bottom: none;
        }

        .table-custom td::before {
            content: attr(data-label);
            font-size: 13px;
            font-weight: 700;
            color: #6B7A7B;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .table-custom td.history-date-cell {
            padding-top: 0;
            font-size: 16px;
        }

        .table-custom td.history-date-cell::before {
            content: none;
        }

        .table-custom td.history-date-cell strong {
            font-size: 17px;
        }

        .table-custom td.empty-state {
            display: block;
            text-align: center;
        }

        .table-custom td.empty-state::before {
            content: none;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-check"></i>
            Attendance
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo $attendancePercentage; ?>% attendance - Last 30 days)
            </span>
        </h3>
    </div>

    <div class="attendance-container">
        <!-- Check-in/Out Section -->
        <div class="attendance-card">
            <div class="card-title">
                <i class="fas fa-clock" style="color: #16A34A;"></i>
                Today's Attendance
                <span class="badge-count"><?php echo date('d M Y'); ?></span>
            </div>

            <?php if ($todayAttendance): ?>
                <?php if ($todayAttendance['check_out_time']): ?>
                    <div class="attendance-status-large checked-out">
                        <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="status-text">Checked Out</div>
                        <div class="status-time">
                            <i class="fas fa-sign-in-alt" style="width:14px;"></i> In: <?php echo date('h:i A', strtotime($todayAttendance['check_in_time'])); ?>
                            <br>
                            <i class="fas fa-sign-out-alt" style="width:14px;"></i> Out: <?php echo date('h:i A', strtotime($todayAttendance['check_out_time'])); ?>
                            <?php if ($todayAttendance['check_in_location']): ?>
                                <br><i class="fas fa-map-marker-alt" style="width:14px;"></i> <?php echo escapeHtml($todayAttendance['check_in_location']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="btn-checkin check-out" disabled><i class="fas fa-check-circle"></i> Already Checked Out</button>
                <?php else: ?>
                    <div class="attendance-status-large checked-in">
                        <div class="status-icon"><i class="fas fa-clock"></i></div>
                        <div class="status-text">Checked In</div>
                        <div class="status-time">
                            <i class="fas fa-sign-in-alt" style="width:14px;"></i> In: <?php echo date('h:i A', strtotime($todayAttendance['check_in_time'])); ?>
                            <?php if ($todayAttendance['check_in_location']): ?>
                                <br><i class="fas fa-map-marker-alt" style="width:14px;"></i> <?php echo escapeHtml($todayAttendance['check_in_location']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="btn-checkin check-out" id="checkOutBtn">
                        <i class="fas fa-sign-out-alt"></i> Check Out
                    </button>
                    <div class="location-status" id="checkOutLocationStatus" style="display:none;"></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="attendance-status-large absent">
                    <div class="status-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="status-text">Not Checked In</div>
                    <div class="status-time">Please check in to start your work day</div>
                </div>
                <button class="btn-checkin check-in" id="checkInBtn">
                    <i class="fas fa-sign-in-alt"></i> Check In
                </button>
                <div class="location-status" id="checkInLocationStatus" style="display:none;"></div>
            <?php endif; ?>

            <?php if (!$todayAttendance): ?>

                <!-- CHECK-IN WINDOW -->
                <div class="info-text">
                    <i class="fas fa-clock"></i>
                    Check-in window:
                    <?php echo date('h:i A', strtotime($checkInStartTime)); ?>
                    -
                    <?php echo date('h:i A', strtotime($checkInEndTime)); ?>
                </div>

            <?php elseif (!$todayAttendance['check_out_time']): ?>

                <!-- CHECK-OUT WINDOW -->
                <div class="info-text checkout-rule">
                    <i class="fas fa-clock"></i>
                    Check-out window:
                    <?php echo date('h:i A', strtotime($checkOutStartTime)); ?>
                    -
                    <?php echo date('h:i A', strtotime($checkOutEndTime)); ?>
                </div>

            <?php endif; ?>

            <?php if ($requireGeolocation): ?>
                <div class="info-text geo-rule">
                    <i class="fas fa-map-marker-alt"></i>
                    You must be within <strong><?php echo $geoRadius; ?>m</strong> of the office location to check-in.
                </div>
            <?php else: ?>
                <div class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Office geofence is currently not enforced by admin settings.
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Section -->
        <div class="attendance-card">
            <div class="card-title">
                <i class="fas fa-chart-bar" style="color: #16A34A;"></i>
                Attendance Statistics
                <span class="badge-count">Last 30 Days</span>
            </div>

            <div class="stats-grid-attendance">
                <div class="stat-item">
                    <span class="stat-number" style="color: #16A34A;"><?php echo $presentDays; ?></span>
                    <span class="stat-label">Present</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: #DC2626;"><?php echo $absentDays; ?></span>
                    <span class="stat-label">Absent</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: #D97706;"><?php echo $halfDays; ?></span>
                    <span class="stat-label">Half Day</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: #3B82F6;"><?php echo $leaveDays; ?></span>
                    <span class="stat-label">Leaves</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: #14532D;"><?php echo $totalDays; ?></span>
                    <span class="stat-label">Total Days</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: <?php echo $attendancePercentage >= 80 ? '#16A34A' : ($attendancePercentage >= 50 ? '#D97706' : '#DC2626'); ?>;">
                        <?php echo $attendancePercentage; ?>%
                    </span>
                    <span class="stat-label">Attendance Rate</span>
                </div>
            </div>

            <div class="progress-container">
                <div class="progress-label">
                    <span>Attendance Rate</span>
                    <span style="color: <?php echo $attendancePercentage >= 80 ? '#16A34A' : ($attendancePercentage >= 50 ? '#D97706' : '#DC2626'); ?>;">
                        <?php echo $attendancePercentage; ?>%
                    </span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill <?php echo $attendancePercentage < 50 ? 'low' : ($attendancePercentage < 80 ? 'medium' : ''); ?>"
                        style="width: <?php echo $attendancePercentage; ?>%;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Holiday Calendar -->
    <div style="margin-top: 24px;">
        <div class="calendar-container">
            <div class="calendar-header">
                <button class="nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i> Prev</button>
                <span class="month-year"><i class="far fa-calendar-alt" style="margin-right:6px;"></i><?php echo date('F Y', strtotime("$year-$month-01")); ?></span>
                <button class="nav-btn" onclick="changeMonth(1)">Next <i class="fas fa-chevron-right"></i></button>
            </div>

            <div class="calendar-grid" id="calendarGrid">
                <?php
                $firstDay = date('N', strtotime("$year-$month-01"));
                $daysInMonth = date('t', strtotime("$year-$month-01"));
                $today = date('Y-m-d');

                // Get attendance status for each day
                $attendanceStatus = [];
                foreach ($attendanceHistory as $record) {
                    $attendanceStatus[$record['date']] = $record['status'];
                }

                // Display day names with admin-configured weekly holiday highlighting
                foreach ($dayFullNames as $index => $dayName) {
                    $isWeeklyHoliday = in_array($dayName, $weeklyHolidays);
                    echo '<div class="day-name' . ($isWeeklyHoliday ? ' weekly-holiday' : '') . '">' . substr($dayName, 0, 3) . '</div>';
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

                    // Check if this day is a weekly holiday (from admin settings)
                    $dayOfWeek = date('N', strtotime($date)); // 1=Monday, 7=Sunday
                    $isWeeklyHoliday = in_array($dayFullNames[$dayOfWeek - 1], $weeklyHolidays);

                    $class = 'day-cell';
                    if ($isHoliday || $isWeeklyHoliday) $class .= ' has-holiday';

                    // Any cell that has something worth showing detail for becomes clickable
                    $hasDetail = $isHoliday || $isWeeklyHoliday || $attStatus;
                    if ($hasDetail) $class .= ' has-detail';

                    $numberClass = '';
                    if ($isToday) $numberClass = 'today';
                    if ($isHoliday || $isWeeklyHoliday) $numberClass .= ' holiday-number';
                    if ($isWeeklyHoliday) $numberClass .= ' weekly-holiday';
                    if ($attStatus === 'present') $numberClass .= ' attendance-present';
                    if ($attStatus === 'absent') $numberClass .= ' attendance-absent';
                    if ($attStatus === 'half_day') $numberClass .= ' attendance-half';

                    // Build data attributes consumed by the JS popover — this
                    // replaces the old CSS :hover tooltip so detail can never
                    // get clipped on the left/right edge dates or on mobile.
                    $dataAttrs = ' data-date="' . escapeHtml(date('d M Y', strtotime($date))) . '"';

                    if ($isHoliday) {
                        $dataAttrs .= ' data-holiday-name="' . escapeHtml($holiday['holiday_name']) . '"';
                        $dataAttrs .= ' data-holiday-type="' . escapeHtml(ucfirst($holiday['holiday_type'] ?? 'public')) . '"';
                        if (!empty($holiday['description'])) {
                            $dataAttrs .= ' data-holiday-desc="' . escapeHtml($holiday['description']) . '"';
                        }
                    } elseif ($isWeeklyHoliday) {
                        $dataAttrs .= ' data-weekly="' . escapeHtml($dayFullNames[$dayOfWeek - 1]) . '"';
                    }

                    if ($attStatus) {
                        $dataAttrs .= ' data-attendance-status="' . escapeHtml($attStatus) . '"';
                    } elseif ($isWeeklyHoliday && !$isHoliday) {
                        $dataAttrs .= ' data-attendance-status="leave"';
                    }

                    echo '<div class="' . $class . '"' . $dataAttrs . '>';
                    echo '<div class="day-number ' . trim($numberClass) . '">' . $day;
                    echo '</div>';

                    // Display attendance status as a small text badge
                    if ($attStatus) {
                        $statusLabels = [
                            'present' => 'P',
                            'absent' => 'A',
                            'half_day' => 'H',
                            'leave' => 'L'
                        ];
                        $statusClasses = [
                            'present' => 'present',
                            'absent' => 'absent',
                            'half_day' => 'half',
                            'leave' => 'leave'
                        ];
                        $label = $statusLabels[$attStatus] ?? strtoupper(substr($attStatus, 0, 1));
                        $badgeClass = $statusClasses[$attStatus] ?? '';
                        echo '<span class="attendance-status-text ' . $badgeClass . '">' . $label . '</span>';
                    } elseif ($isWeeklyHoliday && !$isHoliday) {
                        // No attendance record on a weekly-off day - show it as a Leave badge
                        echo '<span class="attendance-status-text leave">L</span>';
                    }

                    if ($isHoliday) {
                        $typeClass = $holiday['holiday_type'] ?? 'public';
                        echo '<span class="holiday-badge ' . $typeClass . '">' . escapeHtml($holiday['holiday_name']) . '</span>';
                    } elseif ($isWeeklyHoliday) {
                        echo '<span class="weekly-holiday-badge">Weekly Off</span>';
                    }

                    echo '</div>';
                }
                ?>
            </div>

            <!-- Legend -->
            <div class="holiday-legend">
                <span style="font-weight: 700; color: #052E16;">Legend:</span>
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
                    <span class="legend-dot weekly"></span> Weekly
                </span>
                <span class="legend-item">
                    <span class="legend-text-badge present">P</span> Present
                </span>
                <span class="legend-item">
                    <span class="legend-text-badge absent">A</span> Absent
                </span>
                <span class="legend-item">
                    <span class="legend-text-badge half">H</span> Half Day
                </span>
                <span class="legend-item">
                    <span class="legend-text-badge leave">L</span> Leave
                </span>
                <span class="legend-item" style="margin-left:auto; opacity:0.75;">
                    <i class="fas fa-hand-pointer"></i> Tap a date for details
                </span>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h4 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 600; color: #052E16; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-history" style="color: #16A34A;"></i>
                Recent Attendance History
                <span style="font-size: 12px; font-weight: 500; color: #16A34A; background: #DCFCE7; padding: 2px 12px; border-radius: 20px;">
                    <?php echo count($attendanceHistory); ?> Records
                </span>
            </h4>
        </div>

        <div class="table-wrapper">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th><i class="far fa-calendar-alt"></i> Date</th>
                        <th><i class="fas fa-sign-in-alt"></i> Check In</th>
                        <th><i class="fas fa-sign-out-alt"></i> Check Out</th>
                        <th><i class="fas fa-map-marker-alt"></i> Location</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-stopwatch"></i> Overtime</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceHistory)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-calendar-day"></i>
                                No attendance records found
                                <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.7;">Check in today to start tracking your attendance</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendanceHistory as $record): ?>
                            <tr>
                                <td class="history-date-cell" data-label="Date"><strong><?php echo formatDate($record['date']); ?></strong></td>
                                <td data-label="Check In">
                                    <?php if ($record['check_in_time']): ?>
                                        <span style="font-weight: 500;"><?php echo date('h:i A', strtotime($record['check_in_time'])); ?></span>
                                    <?php else: ?>
                                        <span style="color: #6B7A7B;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Check Out">
                                    <?php if ($record['check_out_time']): ?>
                                        <span style="font-weight: 500;"><?php echo date('h:i A', strtotime($record['check_out_time'])); ?></span>
                                    <?php else: ?>
                                        <span style="color: #6B7A7B;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Location" style="font-size: 12px;">
                                    <?php if ($record['check_in_location']): ?>
                                        <span style="max-width: 60%;">
                                            <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
                                            <?php echo escapeHtml($record['check_in_location']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6B7A7B;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <span class="attendance-badge <?php echo $record['status']; ?>">
                                        <?php
                                        $statusLabels = [
                                            'present' => '<i class="fas fa-check-circle"></i> Present',
                                            'absent' => '<i class="fas fa-times-circle"></i> Absent',
                                            'half_day' => '<i class="fas fa-adjust"></i> Half Day',
                                            'leave' => '<i class="fas fa-calendar-minus"></i> Leave'
                                        ];
                                        echo $statusLabels[$record['status']] ?? str_replace('_', ' ', ucfirst($record['status']));
                                        ?>
                                    </span>
                                </td>
                                <td data-label="Overtime">
                                    <?php if ($record['overtime_hours'] > 0): ?>
                                        <span style="font-weight: 700; color: #7C3AED;">+<?php echo number_format($record['overtime_hours'], 1); ?>h</span>
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

<!-- Shared date-detail popover (positioned dynamically by JS, see below) -->
<div class="day-popover-backdrop" id="dayPopoverBackdrop"></div>
<div class="day-popover" id="dayPopover" role="dialog" aria-modal="true">
    <button type="button" class="day-popover-close" id="dayPopoverClose" aria-label="Close">&times;</button>
    <div class="day-popover-date" id="dayPopoverDate"></div>
    <div id="dayPopoverBody"></div>
</div>

<script>
    const csrfToken = '<?php echo $csrfToken; ?>';

    // Admin-configured geofence settings, passed from PHP
    const requireGeolocation = <?php echo $requireGeolocation ? 'true' : 'false'; ?>;
    const geoRadius = <?php echo (int)$geoRadius; ?>;
    const officeLat = <?php echo (float)$officeLat; ?>;
    const officeLng = <?php echo (float)$officeLng; ?>;

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

        window.location.href = 'attendence.php?month=' + newMonth + '&year=' + newYear;
    }

    // ============================================
    // DATE DETAIL POPOVER
    // Tap/click a date cell to see its detail. Uses position:fixed +
    // viewport clamping so it always stays fully visible, even for
    // dates in the first/last column of the calendar grid or on
    // small mobile screens.
    // ============================================

    const dayPopover = document.getElementById('dayPopover');
    const dayPopoverBackdrop = document.getElementById('dayPopoverBackdrop');
    const dayPopoverClose = document.getElementById('dayPopoverClose');
    const dayPopoverDate = document.getElementById('dayPopoverDate');
    const dayPopoverBody = document.getElementById('dayPopoverBody');
    let activeDayCell = null;

    const ATTENDANCE_LABELS = {
        present: {
            label: 'Present',
            icon: 'fa-check-circle'
        },
        absent: {
            label: 'Absent',
            icon: 'fa-times-circle'
        },
        half_day: {
            label: 'Half Day',
            icon: 'fa-adjust'
        },
        leave: {
            label: 'Leave',
            icon: 'fa-calendar-minus'
        }
    };

    const ATTENDANCE_TAG_CLASS = {
        present: 'present',
        absent: 'absent',
        half_day: 'half',
        leave: 'leave'
    };

    function buildPopoverContent(cell) {
        const holidayName = cell.dataset.holidayName;
        const holidayType = cell.dataset.holidayType;
        const holidayDesc = cell.dataset.holidayDesc;
        const weekly = cell.dataset.weekly;
        const attStatus = cell.dataset.attendanceStatus;

        let html = '';

        if (holidayName) {
            html += '<div class="day-popover-row">';
            html += '<div class="day-popover-title"><i class="fas fa-umbrella-beach" style="margin-right:6px;"></i>' + holidayName + '</div>';
            if (holidayDesc) {
                html += '<div class="day-popover-sub">' + holidayDesc + '</div>';
            }
            if (holidayType) {
                html += '<span class="day-popover-tag weekly">' + holidayType + ' Holiday</span>';
            }
            html += '</div>';
        } else if (weekly) {
            html += '<div class="day-popover-row">';
            html += '<div class="day-popover-title"><i class="fas fa-calendar-week" style="margin-right:6px;"></i>Weekly Holiday</div>';
            html += '<div class="day-popover-sub">' + weekly + ' is a weekly off day</div>';
            html += '</div>';
        }

        if (attStatus && ATTENDANCE_LABELS[attStatus]) {
            const info = ATTENDANCE_LABELS[attStatus];
            const tagClass = ATTENDANCE_TAG_CLASS[attStatus] || '';
            html += '<div class="day-popover-row">';
            html += '<span class="day-popover-tag ' + tagClass + '"><i class="fas ' + info.icon + '" style="margin-right:4px;"></i>' + info.label + '</span>';
            html += '</div>';
        }

        if (!html) {
            html = '<div class="day-popover-row day-popover-sub">No details for this date.</div>';
        }

        return html;
    }

    function positionPopover(cell) {
        const rect = cell.getBoundingClientRect();
        const margin = 10;

        // Measure the popover (it's already display:block by the time this runs)
        const popRect = dayPopover.getBoundingClientRect();
        const popWidth = popRect.width;
        const popHeight = popRect.height;

        // Preferred position: centered under the cell
        let left = rect.left + rect.width / 2 - popWidth / 2;
        let top = rect.bottom + 8;

        // Clamp horizontally so it never runs off the left/right edge —
        // this is what fixes the previous "first/last column date" cutoff.
        const maxLeft = window.innerWidth - popWidth - margin;
        if (left < margin) left = margin;
        if (left > maxLeft) left = Math.max(margin, maxLeft);

        // If there isn't room below, flip above the cell
        if (top + popHeight > window.innerHeight - margin) {
            const above = rect.top - popHeight - 8;
            top = above > margin ? above : Math.max(margin, window.innerHeight - popHeight - margin);
        }

        dayPopover.style.left = left + 'px';
        dayPopover.style.top = top + 'px';
    }

    function openPopoverForCell(cell) {
        if (activeDayCell) {
            activeDayCell.classList.remove('day-active');
        }
        activeDayCell = cell;
        cell.classList.add('day-active');

        dayPopoverDate.textContent = cell.dataset.date || '';
        dayPopoverBody.innerHTML = buildPopoverContent(cell);

        dayPopover.classList.add('open');
        dayPopoverBackdrop.classList.add('open');

        // Position after content is rendered so measurements are accurate
        positionPopover(cell);
    }

    function closePopover() {
        dayPopover.classList.remove('open');
        dayPopoverBackdrop.classList.remove('open');
        if (activeDayCell) {
            activeDayCell.classList.remove('day-active');
            activeDayCell = null;
        }
    }

    document.getElementById('calendarGrid').addEventListener('click', function(e) {
        const cell = e.target.closest('.day-cell.has-detail');
        if (!cell) return;

        if (activeDayCell === cell && dayPopover.classList.contains('open')) {
            closePopover();
            return;
        }

        openPopoverForCell(cell);
    });

    dayPopoverClose.addEventListener('click', closePopover);
    dayPopoverBackdrop.addEventListener('click', closePopover);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePopover();
    });

    window.addEventListener('resize', function() {
        if (activeDayCell) positionPopover(activeDayCell);
    });

    window.addEventListener('scroll', function() {
        if (activeDayCell) positionPopover(activeDayCell);
    }, true);

    // ============================================
    // GEOFENCE HELPER
    // ============================================

    function haversineDistanceMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000; // meters
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    // ============================================
    // LOCATION HELPERS (GPS + reverse geocode + confirm,
    // same pattern as agent/attendence.php)
    // ============================================

    function reverseGeocode(lat, lng) {
        return fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng +
                '&zoom=18&addressdetails=1')
            .then(function(response) {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(function(data) {
                return (data && data.display_name) ? data.display_name : (lat.toFixed(6) + ', ' + lng.toFixed(6));
            })
            .catch(function() {
                return lat.toFixed(6) + ', ' + lng.toFixed(6);
            });
    }

    function fetchLocationForAttendance(actionType, btn, statusEl, onConfirm) {
        const isIn = actionType === 'in';
        const accentColor = isIn ? '#16A34A' : '#DC2626';
        const actionLabel = isIn ? 'Check In' : 'Check Out';
        const originalBtnHtml = btn.innerHTML;

        // Staff check-in is geofenced by admin settings - location can't be skipped in that case
        const geofenceRequired = isIn && requireGeolocation;

        function resetButton() {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }

        function proceedWithoutLocation(reasonText) {
            if (geofenceRequired) {
                resetButton();
                statusEl.style.display = 'flex';
                statusEl.className = 'location-status error';
                statusEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + reasonText;

                Swal.fire({
                    icon: 'error',
                    title: 'Location Required',
                    text: reasonText + ' Location access is required to check-in (office geofence is enforced). Please enable location and try again.',
                    confirmButtonColor: accentColor
                });
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Location Unavailable',
                text: reasonText + ' Proceed with ' + actionLabel + ' without location?',
                showCancelButton: true,
                confirmButtonText: 'Proceed',
                cancelButtonText: 'Cancel',
                confirmButtonColor: accentColor,
                cancelButtonColor: '#6B7A7B'
            }).then(function(result) {
                resetButton();
                if (result.isConfirmed) {
                    onConfirm(null, null, null);
                }
            });
        }

        function askConfirm(lat, lng, address) {
            resetButton();

            // Client-side geofence check for a fast, friendly warning.
            // The server re-validates this distance regardless.
            if (geofenceRequired) {
                const distance = haversineDistanceMeters(officeLat, officeLng, lat, lng);
                if (distance > geoRadius) {
                    statusEl.style.display = 'flex';
                    statusEl.className = 'location-status error';
                    statusEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> You are ' + Math.round(distance) + 'm away from the office (allowed: ' + geoRadius + 'm).';

                    Swal.fire({
                        icon: 'error',
                        title: 'Outside Office Range',
                        html: 'You are <strong>' + Math.round(distance) + 'm</strong> away from the office.<br>Check-in is only allowed within <strong>' + geoRadius + 'm</strong> of the office location.',
                        confirmButtonColor: accentColor
                    });
                    return;
                }
            }

            Swal.fire({
                icon: 'info',
                title: 'Confirm ' + actionLabel + ' Location',
                html: '<div style="text-align:left; background:#F7FCF7; border:1px solid #E5EDE7; border-radius:10px; padding:12px 14px; font-size:13px; color:#052E16;">' +
                    '<div style="margin-bottom:6px;"><strong>Address:</strong><br>' + address + '</div>' +
                    '<div style="font-size:11px; color:#6B7A7B;">Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) +
                    '</div>' +
                    '</div>',
                showCancelButton: true,
                confirmButtonText: 'Confirm ' + actionLabel,
                cancelButtonText: 'Cancel',
                confirmButtonColor: accentColor,
                cancelButtonColor: '#6B7A7B'
            }).then(function(result) {
                if (result.isConfirmed) {
                    onConfirm(lat, lng, address);
                }
            });
        }

        // Check if geolocation is supported
        if (!navigator.geolocation) {
            statusEl.style.display = 'flex';
            statusEl.className = 'location-status error';
            statusEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Your browser does not support GPS location.';
            proceedWithoutLocation('GPS is not supported on this device.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
        statusEl.style.display = 'flex';
        statusEl.className = 'location-status';
        statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching GPS coordinates...';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                reverseGeocode(lat, lng)
                    .then(function(address) {
                        statusEl.className = 'location-status success';
                        statusEl.innerHTML = '<i class="fas fa-check-circle"></i> Location captured: ' + address;
                        askConfirm(lat, lng, address);
                    })
                    .catch(function() {
                        const address = lat.toFixed(6) + ', ' + lng.toFixed(6);
                        statusEl.className = 'location-status warning';
                        statusEl.innerHTML =
                            '<i class="fas fa-exclamation-triangle"></i> Address lookup failed, using coordinates.';
                        askConfirm(lat, lng, address);
                    });
            },
            function(error) {
                let reason = '';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        reason = 'Location permission denied. Please allow location access for this site in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        reason = 'GPS signal unavailable right now.';
                        break;
                    case error.TIMEOUT:
                        reason = 'Getting your location took too long.';
                        break;
                    default:
                        reason = 'An error occurred while fetching location.';
                        break;
                }
                statusEl.className = 'location-status error';
                statusEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + reason;
                proceedWithoutLocation(reason);
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    // ============================================
    // CHECK IN
    // ============================================

    const checkInBtn = document.getElementById('checkInBtn');
    const checkInLocationStatus = document.getElementById('checkInLocationStatus');
    if (checkInBtn) {
        checkInBtn.addEventListener('click', function() {
            fetchLocationForAttendance('in', checkInBtn, checkInLocationStatus, function(lat, lng, location) {
                checkIn(lat, lng, location);
            });
        });
    }

    function checkIn(lat, lng, location) {
        const formData = new FormData();
        formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);
        formData.append('action', 'check_in');

        if (lat !== null && lat !== undefined) {
            formData.append('lat', lat);
        }
        if (lng !== null && lng !== undefined) {
            formData.append('lng', lng);
        }
        if (location !== null && location !== undefined) {
            formData.append('location', location);
        }

        Swal.fire({
            title: '⏳ Checking In...',
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
                        text: data.message,
                        confirmButtonColor: '#16A34A'
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#16A34A'
                });
            });
    }

    // ============================================
    // CHECK OUT
    // ============================================

    const checkOutBtn = document.getElementById('checkOutBtn');
    const checkOutLocationStatus = document.getElementById('checkOutLocationStatus');
    if (checkOutBtn) {
        checkOutBtn.addEventListener('click', function() {
            fetchLocationForAttendance('out', checkOutBtn, checkOutLocationStatus, function(lat, lng, location) {
                checkOut(lat, lng, location);
            });
        });
    }

    function checkOut(lat, lng, location) {
        const formData = new FormData();
        formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);
        formData.append('action', 'check_out');

        if (lat !== null && lat !== undefined) {
            formData.append('lat', lat);
        }
        if (lng !== null && lng !== undefined) {
            formData.append('lng', lng);
        }
        if (location !== null && location !== undefined) {
            formData.append('location', location);
        }

        Swal.fire({
            title: '⏳ Checking Out...',
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
                        text: data.message,
                        confirmButtonColor: '#DC2626'
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#DC2626'
                });
            });
    }
</script>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>