<?php

/**
 * SAMRIDHI AGRO - Attendance Settings
 * 
 * This page allows administrators to manage attendance settings,
 * holiday calendar, and weekly holidays.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 3.0.0
 */

// Set page title
$pageTitle = 'Attendance Settings';

// Include admin header
require_once '../includes/admin_header.php';

requirePermissionOrAdmin('attendance.settings.view', 'attendance-settings.php');


// Get database instance
$db = getDB();

// ============================================
// GET ALL SETTINGS
// ============================================

$sql = "SELECT * FROM attendance_settings";
$allSettings = $db->fetchAll($sql);
$settings = [];
foreach ($allSettings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// ============================================
// HANDLE SETTINGS UPDATE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['holiday_form'])) {
    requirePermission('settings.update');

    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('admin/attendance-settings.php');
        exit;
    }

    // Handle weekly holidays as array
    $weeklyHolidaysArray = isset($_POST['weekly_holidays']) ? $_POST['weekly_holidays'] : [];
    $weeklyHolidaysString = implode(',', $weeklyHolidaysArray);

    $formData = [
        'check_in_start_time' => $_POST['check_in_start_time'] ?? '09:00:00',
        'check_in_end_time' => $_POST['check_in_end_time'] ?? '10:00:00',
        'check_out_start_time' => $_POST['check_out_start_time'] ?? '17:30:00',
        'check_out_end_time' => $_POST['check_out_end_time'] ?? '23:59:00',
        'work_hours' => $_POST['work_hours'] ?? '8.00',
        'allow_geolocation' => isset($_POST['allow_geolocation']) ? '1' : '0',
        'geolocation_radius' => $_POST['geolocation_radius'] ?? '500',
        'office_lat' => $_POST['office_lat'] ?? '28.6139',
        'office_lng' => $_POST['office_lng'] ?? '77.2090',
        'allow_self_checkout' => isset($_POST['allow_self_checkout']) ? '1' : '0',
        'attendance_approval_required' => isset($_POST['attendance_approval_required']) ? '1' : '0',
        'weekly_holidays' => sanitizeInput($weeklyHolidaysString)
    ];

    try {
        foreach ($formData as $key => $value) {
            $sql = "INSERT INTO attendance_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
            $db->query($sql, [$key, $value, $value]);
        }

        logActivity('update', $_SESSION['user_id'], 'settings', 'Updated attendance settings');
        setFlashMessage('success', 'Attendance settings updated successfully!');
        redirect('admin/attendance-settings.php');
        exit;
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to update settings: ' . $e->getMessage());
    }
}

// ============================================
// HOLIDAY MANAGEMENT FUNCTIONS
// ============================================

// Get current month and year for calendar
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Handle holiday actions
if (isset($_GET['holiday_action'])) {
    $holidayId = isset($_GET['holiday_id']) ? (int)$_GET['holiday_id'] : 0;
    $action = $_GET['holiday_action'];

    if (!isset($_GET['csrf']) || !verifyCsrfToken($_GET['csrf'])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('admin/attendance-settings.php');
        exit;
    }

    if ($action === 'delete' && $holidayId > 0) {
        $sql = "DELETE FROM holidays WHERE id = ?";
        $db->query($sql, [$holidayId]);
        logActivity('delete', $_SESSION['user_id'], 'holiday', 'Deleted holiday ID: ' . $holidayId);
        setFlashMessage('success', 'Holiday deleted successfully.');
        redirect('admin/attendance-settings.php');
        exit;
    }

    if ($action === 'toggle' && $holidayId > 0) {
        $sql = "UPDATE holidays SET status = IF(status='active', 'inactive', 'active') WHERE id = ?";
        $db->query($sql, [$holidayId]);
        logActivity('update', $_SESSION['user_id'], 'holiday', 'Toggled holiday status ID: ' . $holidayId);
        setFlashMessage('success', 'Holiday status updated.');
        redirect('admin/attendance-settings.php');
        exit;
    }
}

// Handle holiday add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['holiday_form'])) {
    $holidayId = (int)($_POST['holiday_id'] ?? 0);
    $holidayDate = sanitizeInput($_POST['holiday_date'] ?? '');
    $holidayName = sanitizeInput($_POST['holiday_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $holidayType = sanitizeInput($_POST['holiday_type'] ?? 'public');
    $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurringType = sanitizeInput($_POST['recurring_type'] ?? 'yearly');
    $weeklyHoliday = sanitizeInput($_POST['weekly_holiday'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');

    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('admin/attendance-settings.php');
        exit;
    }

    if (empty($holidayDate) || empty($holidayName)) {
        setFlashMessage('error', 'Holiday date and name are required.');
        redirect('admin/attendance-settings.php');
        exit;
    }

    try {
        if ($holidayId > 0) {
            // Update existing
            $sql = "UPDATE holidays SET 
                    holiday_date = ?,
                    holiday_name = ?,
                    description = ?,
                    holiday_type = ?,
                    is_recurring = ?,
                    recurring_type = ?,
                    weekly_holiday = ?,
                    status = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            $db->query($sql, [
                $holidayDate,
                $holidayName,
                $description,
                $holidayType,
                $isRecurring,
                $recurringType,
                $weeklyHoliday,
                $status,
                $holidayId
            ]);
            logActivity('update', $_SESSION['user_id'], 'holiday', 'Updated holiday: ' . $holidayName);
            setFlashMessage('success', 'Holiday updated successfully.');
        } else {
            // Insert new
            $sql = "INSERT INTO holidays (
                        holiday_date, holiday_name, description, holiday_type, 
                        is_recurring, recurring_type, weekly_holiday, status, 
                        created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $db->query($sql, [
                $holidayDate,
                $holidayName,
                $description,
                $holidayType,
                $isRecurring,
                $recurringType,
                $weeklyHoliday,
                $status,
                $_SESSION['user_id']
            ]);
            logActivity('create', $_SESSION['user_id'], 'holiday', 'Created holiday: ' . $holidayName);
            setFlashMessage('success', 'Holiday added successfully.');
        }
        redirect('admin/attendance-settings.php');
        exit;
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to save holiday: ' . $e->getMessage());
    }
}

// Get holidays for current month
$sql = "SELECT * FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? 
        ORDER BY holiday_date ASC";
$holidays = $db->fetchAll($sql, [$month, $year]);

// Get all holidays for current month (for calendar) - including weekly holidays
$sql = "SELECT holiday_date, holiday_name, holiday_type, status, id, weekly_holiday 
        FROM holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ? AND status = 'active'
        ORDER BY holiday_date ASC";
$holidayCalendar = $db->fetchAll($sql, [$month, $year]);

// Get weekly holidays from settings
$weeklyHolidays = explode(',', $settings['weekly_holidays'] ?? 'Sunday');
$weeklyHolidays = array_map('trim', $weeklyHolidays);
$weeklyHolidayNames = [
    'Monday' => 'Mon',
    'Tuesday' => 'Tue',
    'Wednesday' => 'Wed',
    'Thursday' => 'Thu',
    'Friday' => 'Fri',
    'Saturday' => 'Sat',
    'Sunday' => 'Sun'
];

// Get holiday to edit
$editHoliday = null;
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $editId = (int)$_GET['edit'];
    $sql = "SELECT * FROM holidays WHERE id = ?";
    $editHoliday = $db->fetchOne($sql, [$editId]);
}

$csrfToken = generateCsrfToken();

// Day names for calendar
$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$dayFullNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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

    .settings-card {
        background: #FAFDFA;
        border: 1px solid #E5EDE7;
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.04);
        position: relative;
        overflow: hidden;
    }

    .settings-card::before {
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
        0%, 100% { background-position: -200% 0; }
        50% { background-position: 200% 0; }
    }

    .settings-card:hover {
        border-color: #86EFAC;
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.08);
        transform: translateY(-2px);
    }

    .settings-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 17px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 2px solid #F0FDF4;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .settings-card .card-title .badge-count {
        font-size: 12px;
        font-weight: 500;
        color: #16A34A;
        background: #DCFCE7;
        padding: 2px 12px;
        border-radius: 20px;
        font-family: 'Inter', sans-serif;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #14532D;
        margin-bottom: 6px;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .form-label .required {
        color: #DC2626;
        margin-left: 2px;
    }

    .form-input {
        width: 100%;
        padding: 10px 16px;
        border: 2px solid #E5EDE7;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        background: white;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1), 0 2px 8px rgba(22, 163, 74, 0.05);
    }

    .form-input:hover {
        border-color: #86EFAC;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .form-hint i {
        color: #86EFAC;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        padding: 8px 14px;
        border-radius: 10px;
        background: #F7FCF7;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        margin-bottom: 4px;
    }

    .checkbox-group:hover {
        background: #F0FDF4;
        border-color: #E5EDE7;
    }

    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #16A34A;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* ===== BUTTONS ===== */
    .btn-primary {
        padding: 12px 32px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.25);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        letter-spacing: 0.3px;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 24px rgba(22, 163, 74, 0.35);
        background: linear-gradient(135deg, #0D3B20, #16A34A);
    }

    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2);
    }

    .btn-secondary {
        padding: 10px 24px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: 2px solid #E5EDE7;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-secondary:hover {
        background: #E5E7EB;
        border-color: #D1D5DB;
        transform: translateY(-2px);
    }

    .btn-sm {
        padding: 6px 14px;
        font-size: 12px;
        border-radius: 8px;
    }

    .btn-danger {
        background: linear-gradient(135deg, #DC2626, #B91C1C);
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(220, 38, 38, 0.3);
        background: linear-gradient(135deg, #B91C1C, #991B1B);
    }

    .btn-success-sm {
        background: linear-gradient(135deg, #16A34A, #059669);
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2);
    }

    .btn-success-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.3);
        background: linear-gradient(135deg, #059669, #047857);
    }

    .btn-warning-sm {
        background: linear-gradient(135deg, #F59E0B, #D97706);
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
    }

    .btn-warning-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3);
        background: linear-gradient(135deg, #D97706, #B45309);
    }

    .btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* ===== WEEKLY HOLIDAYS ===== */
    .weekly-holidays-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 12px;
        margin: 10px 0 6px 0;
    }

    .weekly-holiday-checkbox {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 14px 8px;
        background: white;
        border: 2px solid #E5EDE7;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        user-select: none;
        position: relative;
        box-shadow: 0 2px 4px rgba(5, 46, 22, 0.04);
        min-height: 70px;
    }

    .weekly-holiday-checkbox:hover {
        background: #F0FDF4;
        border-color: #86EFAC;
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.12);
    }

    .weekly-holiday-checkbox input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .weekly-holiday-checkbox .day-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        pointer-events: none;
    }

    .weekly-holiday-checkbox .day-short {
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
        line-height: 1.2;
        transition: color 0.3s ease;
    }

    .weekly-holiday-checkbox .day-full {
        font-size: 11px;
        color: #6B7A7B;
        font-weight: 400;
        transition: color 0.3s ease;
    }

    .weekly-holiday-checkbox.selected {
        background: linear-gradient(135deg, #DCFCE7, #BBF7D0);
        border-color: #16A34A;
        box-shadow: 0 4px 20px rgba(22, 163, 74, 0.2), inset 0 0 20px rgba(22, 163, 74, 0.05);
        transform: translateY(-2px);
    }

    .weekly-holiday-checkbox.selected .day-short {
        color: #14532D;
    }

    .weekly-holiday-checkbox.selected .day-full {
        color: #065F46;
    }

    .weekly-holiday-checkbox.selected::after {
        content: '✓';
        position: absolute;
        top: -8px;
        right: -8px;
        background: linear-gradient(135deg, #16A34A, #059669);
        color: white;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
    }

    .weekly-holiday-checkbox .day-number-badge {
        position: absolute;
        top: -6px;
        left: -6px;
        background: #F3F4F6;
        color: #6B7A7B;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 9px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }

    .weekly-holiday-checkbox.selected .day-number-badge {
        background: #DCFCE7;
        color: #14532D;
    }

    /* ===== CALENDAR ===== */
    .calendar-container {
        background: white;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #E5EDE7;
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.04);
        transition: box-shadow 0.3s ease;
    }

    .calendar-container:hover {
        box-shadow: 0 4px 16px rgba(5, 46, 22, 0.08);
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        background: linear-gradient(135deg, #F7FCF7, #F0FDF4);
        border-bottom: 1px solid #E5EDE7;
    }

    .calendar-header .month-year {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 600;
        color: #052E16;
        letter-spacing: 0.5px;
    }

    .calendar-header .nav-btn {
        background: white;
        border: 2px solid #E5EDE7;
        font-size: 16px;
        color: #14532D;
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(5, 46, 22, 0.04);
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .calendar-header .nav-btn:hover {
        background: #16A34A;
        color: white;
        border-color: #16A34A;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.2);
    }

    .calendar-header .nav-btn i {
        font-size: 14px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #E5EDE7;
    }

    .calendar-grid .day-name {
        background: #F7FCF7;
        padding: 12px 8px;
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        color: #6B7A7B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .calendar-grid .day-name.weekly-holiday {
        color: #DC2626;
        background: #FFF5F5;
    }

    .calendar-grid .day-cell {
        background: white;
        min-height: 85px;
        padding: 8px 10px;
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .calendar-grid .day-cell:hover {
        background: #F7FCF7;
        z-index: 2;
    }

    .calendar-grid .day-cell .day-number {
        font-size: 15px;
        font-weight: 500;
        color: #052E16;
        margin-bottom: 4px;
        transition: all 0.3s ease;
    }

    .calendar-grid .day-cell .day-number.other-month {
        color: #D1D5DB;
    }

    .calendar-grid .day-cell .day-number.today {
        background: linear-gradient(135deg, #16A34A, #059669);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 2px 12px rgba(22, 163, 74, 0.3);
    }

    .calendar-grid .day-cell .day-number.weekly-holiday {
        color: #DC2626;
    }

    .calendar-grid .day-cell .holiday-badge {
        font-size: 8px;
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

    .calendar-grid .day-cell .holiday-badge.weekly {
        background: #FEF3C7;
        color: #92400E;
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
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 100;
        min-width: 160px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        font-weight: 400;
    }

    .calendar-grid .day-cell .holiday-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
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

    .calendar-grid .day-cell .weekly-holiday-badge {
        font-size: 7px;
        background: #FEF3C7;
        color: #92400E;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        margin-top: 1px;
        font-weight: 600;
    }

    /* ===== HOLIDAY LIST TABLE ===== */
    .holiday-list-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .holiday-list-table thead th {
        background: #F7FCF7;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: #14532D;
        border-radius: 10px 10px 0 0;
        border-bottom: 2px solid #E5EDE7;
    }

    .holiday-list-table tbody tr {
        background: white;
        transition: all 0.3s ease;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(5, 46, 22, 0.04);
    }

    .holiday-list-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.08);
    }

    .holiday-list-table td {
        padding: 14px 16px;
        font-size: 13px;
        vertical-align: middle;
        background: white;
    }

    .holiday-list-table tbody tr td:first-child {
        border-radius: 10px 0 0 10px;
    }

    .holiday-list-table tbody tr td:last-child {
        border-radius: 0 10px 10px 0;
    }

    .holiday-list-table .badge-status {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .badge-status.active {
        background: #DCFCE7;
        color: #065F46;
    }

    .badge-status.inactive {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    .holiday-type-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .holiday-type-badge.national {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .holiday-type-badge.public {
        background: #DCFCE7;
        color: #065F46;
    }

    .holiday-type-badge.festival {
        background: #FEF3C7;
        color: #92400E;
    }

    .holiday-type-badge.company {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .holiday-type-badge.weekly {
        background: #FEF3C7;
        color: #92400E;
    }

    /* ===== HOLIDAY FORM ===== */
    #holidayFormContainer {
        background: linear-gradient(135deg, #F7FCF7, #F0FDF4);
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 24px;
        border: 2px solid #86EFAC;
        box-shadow: 0 4px 24px rgba(22, 163, 74, 0.08);
        position: relative;
    }

    #holidayFormContainer::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border-radius: 16px;
        background: linear-gradient(135deg, #16A34A, #4ADE80, #16A34A);
        background-size: 200% 200%;
        animation: borderGlow 3s ease-in-out infinite;
        z-index: -1;
        opacity: 0.3;
    }

    @keyframes borderGlow {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    #holidayFormContainer h4 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 17px;
        color: #052E16;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        color: #6B7A7B;
        text-align: center;
        padding: 40px 20px;
        background: #F7FCF7;
        border-radius: 12px;
        border: 2px dashed #E5EDE7;
    }

    .empty-state i {
        font-size: 32px;
        display: block;
        margin-bottom: 12px;
        opacity: 0.4;
        color: #16A34A;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .content-card {
            padding: 20px 16px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .settings-card {
            padding: 18px 16px;
        }

        .weekly-holidays-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .weekly-holiday-checkbox {
            padding: 10px 6px;
            min-height: 60px;
        }

        .weekly-holiday-checkbox .day-short {
            font-size: 16px;
        }

        .weekly-holiday-checkbox .day-full {
            font-size: 9px;
        }

        .calendar-grid .day-cell {
            min-height: 65px;
            padding: 4px 6px;
        }

        .calendar-grid .day-cell .day-number {
            font-size: 13px;
        }

        .calendar-grid .day-cell .day-number.today {
            width: 26px;
            height: 26px;
            font-size: 12px;
        }

        .calendar-grid .day-cell .holiday-badge {
            font-size: 7px;
            padding: 1px 4px;
        }

        .calendar-grid .day-cell .holiday-tooltip {
            font-size: 10px;
            min-width: 120px;
            padding: 6px 12px;
            white-space: normal;
            width: 140px;
        }

        .calendar-header .month-year {
            font-size: 16px;
        }

        .calendar-grid .day-name {
            font-size: 10px;
            padding: 8px 4px;
        }

        .calendar-header {
            padding: 14px 16px;
        }

        .calendar-header .nav-btn {
            padding: 6px 12px;
            font-size: 14px;
        }

        .card-header .card-title {
            font-size: 18px;
        }

        .holiday-list-table td {
            padding: 10px 12px;
            font-size: 12px;
        }

        .holiday-list-table thead th {
            padding: 10px 12px;
            font-size: 11px;
        }

        #holidayFormContainer {
            padding: 18px 16px;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn-group .btn-sm {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .content-card {
            padding: 16px 12px;
        }

        .weekly-holidays-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .weekly-holiday-checkbox {
            padding: 8px 4px;
            min-height: 50px;
        }

        .weekly-holiday-checkbox .day-short {
            font-size: 14px;
        }

        .weekly-holiday-checkbox .day-full {
            font-size: 8px;
        }

        .calendar-grid .day-cell {
            min-height: 55px;
            padding: 2px 4px;
        }

        .calendar-grid .day-cell .day-number {
            font-size: 11px;
        }

        .calendar-grid .day-cell .day-number.today {
            width: 22px;
            height: 22px;
            font-size: 10px;
        }

        .calendar-grid .day-cell .holiday-badge {
            font-size: 6px;
            padding: 1px 3px;
        }

        .settings-card .card-title {
            font-size: 15px;
        }

        .btn-primary {
            padding: 10px 24px;
            font-size: 14px;
            width: 100%;
            justify-content: center;
        }

        .card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .card-header .btn-primary.btn-sm {
            width: 100%;
            justify-content: center;
        }

        .calendar-grid .day-name {
            font-size: 9px;
            padding: 6px 2px;
        }

        .calendar-header {
            padding: 12px 12px;
        }

        .calendar-header .month-year {
            font-size: 14px;
        }

        .calendar-header .nav-btn {
            padding: 4px 10px;
            font-size: 12px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-clock"></i>
            Attendance Settings
        </h3>
    </div>

    <!-- ============================================
    ATTENDANCE SETTINGS FORM
    ============================================ -->
    <form method="POST">
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">

        <!-- Time Settings -->
        <div class="settings-card">
            <div class="card-title">
                ⏰ Time Settings
                <span class="badge-count">Configure</span>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Check-in Start Time</label>
                    <input type="time" name="check_in_start_time" class="form-input"
                        value="<?php echo escapeHtml($settings['check_in_start_time'] ?? '09:00:00'); ?>">
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Office start time for check-in</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Check-in End Time</label>
                    <input type="time" name="check_in_end_time" class="form-input"
                        value="<?php echo escapeHtml($settings['check_in_end_time'] ?? '10:00:00'); ?>">
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Late check-in allowed until this time</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Check-out Start Time</label>
                    <input type="time" name="check_out_start_time" class="form-input"
                        value="<?php echo escapeHtml($settings['check_out_start_time'] ?? '17:30:00'); ?>">
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Earliest check-out time</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Check-out End Time</label>
                    <input type="time" name="check_out_end_time" class="form-input"
                        value="<?php echo escapeHtml($settings['check_out_end_time'] ?? '23:59:00'); ?>">
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Latest check-out time</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Standard Work Hours <span class="required">*</span></label>
                <input type="number" name="work_hours" class="form-input" step="0.5" min="1" max="24"
                    value="<?php echo escapeHtml($settings['work_hours'] ?? '8.00'); ?>">
                <div class="form-hint"><i class="fas fa-info-circle"></i> Number of hours considered as full work day</div>
            </div>
        </div>

        <!-- Geolocation Settings -->
        <div class="settings-card">
            <div class="card-title">
                📍 Geolocation Settings
                <span class="badge-count">Location</span>
            </div>
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="allow_geolocation" value="1"
                        <?php echo ($settings['allow_geolocation'] ?? '1') == '1' ? 'checked' : ''; ?>>
                    <span>Require geolocation for attendance</span>
                </label>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Office Latitude</label>
                    <input type="text" name="office_lat" class="form-input"
                        value="<?php echo escapeHtml($settings['office_lat'] ?? '28.6139'); ?>">
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Office latitude for location validation</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Office Longitude</label>
                    <input type="text" name="office_lng" class="form-input"
                        value="<?php echo escapeHtml($settings['office_lng'] ?? '77.2090'); ?>">
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Office longitude for location validation</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Geolocation Radius <span class="required">*</span></label>
                <input type="number" name="geolocation_radius" class="form-input" min="100" max="5000"
                    value="<?php echo escapeHtml($settings['geolocation_radius'] ?? '500'); ?>">
                <div class="form-hint"><i class="fas fa-info-circle"></i> Allowed radius from office location for check-in (in meters)</div>
            </div>
        </div>

        <!-- Weekly Holidays -->
        <div class="settings-card">
            <div class="card-title">
                📅 Weekly Holidays
                <span class="badge-count"><?php echo count($weeklyHolidays); ?> Selected</span>
            </div>
            <div class="form-group">
                <label class="form-label">Select Weekly Holidays</label>
                <div class="weekly-holidays-grid">
                    <?php 
                    $weeklyHolidays = explode(',', $settings['weekly_holidays'] ?? 'Sunday');
                    $weeklyHolidays = array_map('trim', $weeklyHolidays);
                    $dayFullNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $dayShortNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    ?>
                    <?php foreach ($dayFullNames as $index => $day): ?>
                        <label class="weekly-holiday-checkbox <?php echo in_array($day, $weeklyHolidays) ? 'selected' : ''; ?>">
                            <input type="checkbox" name="weekly_holidays[]" value="<?php echo $day; ?>" 
                                <?php echo in_array($day, $weeklyHolidays) ? 'checked' : ''; ?>
                                onchange="this.parentElement.classList.toggle('selected', this.checked)">
                            <span class="day-number-badge"><?php echo $index + 1; ?></span>
                            <span class="day-label">
                                <span class="day-short"><?php echo $dayShortNames[$index]; ?></span>
                                <span class="day-full"><?php echo $day; ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i> Select days that will be marked as weekly holidays. These days will appear as holidays every week.
                </div>
            </div>
        </div>

        <!-- Other Settings -->
        <div class="settings-card">
            <div class="card-title">
                ⚙️ Other Settings
                <span class="badge-count">Advanced</span>
            </div>
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="allow_self_checkout" value="1"
                        <?php echo ($settings['allow_self_checkout'] ?? '1') == '1' ? 'checked' : ''; ?>>
                    <span>Allow staff to check out without approval</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="attendance_approval_required" value="1"
                        <?php echo ($settings['attendance_approval_required'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <span>Require admin approval for attendance</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn-primary">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </form>
</div>

<!-- ============================================
HOLIDAY MANAGEMENT SECTION
============================================ -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-alt"></i>
            Holiday Management
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (Manage company holidays)
            </span>
        </h3>
        <button class="btn-primary btn-sm" onclick="openHolidayForm()" style="padding: 10px 24px; font-size: 13px;">
            <i class="fas fa-plus"></i> Add Holiday
        </button>
    </div>

    <!-- Holiday Form -->
    <div id="holidayFormContainer" style="display: <?php echo $editHoliday ? 'block' : 'none'; ?>;">
        <h4>
            <?php echo $editHoliday ? '✏️ Edit Holiday' : '➕ Add New Holiday'; ?>
        </h4>
        <form method="POST">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="holiday_form" value="1">
            <?php if ($editHoliday): ?>
                <input type="hidden" name="holiday_id" value="<?php echo $editHoliday['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Holiday Date <span class="required">*</span></label>
                    <input type="date" name="holiday_date" class="form-input" required
                        value="<?php echo $editHoliday ? $editHoliday['holiday_date'] : date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Holiday Name <span class="required">*</span></label>
                    <input type="text" name="holiday_name" class="form-input" required
                        value="<?php echo $editHoliday ? escapeHtml($editHoliday['holiday_name']) : ''; ?>" placeholder="e.g., Diwali">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="2" placeholder="Optional description"><?php echo $editHoliday ? escapeHtml($editHoliday['description']) : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Holiday Type</label>
                    <select name="holiday_type" class="form-input">
                        <option value="public" <?php echo ($editHoliday && $editHoliday['holiday_type'] === 'public') ? 'selected' : ''; ?>>Public</option>
                        <option value="company" <?php echo ($editHoliday && $editHoliday['holiday_type'] === 'company') ? 'selected' : ''; ?>>Company</option>
                        <option value="festival" <?php echo ($editHoliday && $editHoliday['holiday_type'] === 'festival') ? 'selected' : ''; ?>>Festival</option>
                        <option value="national" <?php echo ($editHoliday && $editHoliday['holiday_type'] === 'national') ? 'selected' : ''; ?>>National</option>
                        <option value="weekly" <?php echo ($editHoliday && $editHoliday['holiday_type'] === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active" <?php echo ($editHoliday && $editHoliday['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editHoliday && $editHoliday['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-group">
                        <input type="checkbox" name="is_recurring" value="1"
                            <?php echo ($editHoliday && $editHoliday['is_recurring']) ? 'checked' : ''; ?>>
                        <span>Recurring Holiday (Repeats every year)</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Recurring Type</label>
                    <select name="recurring_type" class="form-input">
                        <option value="yearly" <?php echo ($editHoliday && $editHoliday['recurring_type'] === 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                        <option value="monthly" <?php echo ($editHoliday && $editHoliday['recurring_type'] === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                        <option value="weekly" <?php echo ($editHoliday && $editHoliday['recurring_type'] === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Weekly Holiday (If recurring weekly)</label>
                <select name="weekly_holiday" class="form-input">
                    <option value="">Select Day</option>
                    <?php foreach ($dayFullNames as $day): ?>
                        <option value="<?php echo $day; ?>"
                            <?php echo ($editHoliday && $editHoliday['weekly_holiday'] === $day) ? 'selected' : ''; ?>>
                            <?php echo $day; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i> Select a specific day if this is a weekly recurring holiday.
                </div>
            </div>

            <div class="btn-group" style="margin-top: 12px;">
                <button type="submit" class="btn-primary btn-sm" style="padding: 10px 28px;">
                    <i class="fas fa-save"></i> <?php echo $editHoliday ? 'Update' : 'Add'; ?> Holiday
                </button>
                <button type="button" class="btn-secondary btn-sm" onclick="closeHolidayForm()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Calendar View -->
    <div class="calendar-container">
        <div class="calendar-header">
            <button class="nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i> Prev</button>
            <span class="month-year"><?php echo date('F Y', strtotime("$year-$month-01")); ?></span>
            <button class="nav-btn" onclick="changeMonth(1)">Next <i class="fas fa-chevron-right"></i></button>
        </div>

        <div class="calendar-grid" id="calendarGrid">
            <?php
            $firstDay = date('N', strtotime("$year-$month-01"));
            $daysInMonth = date('t', strtotime("$year-$month-01"));
            $today = date('Y-m-d');
            $holidayDates = [];
            foreach ($holidayCalendar as $h) {
                $holidayDates[$h['holiday_date']] = $h;
            }

            // Check which days are weekly holidays
            $weeklyHolidayDays = [];
            foreach ($weeklyHolidays as $wh) {
                $index = array_search($wh, $dayFullNames);
                if ($index !== false) {
                    $weeklyHolidayDays[] = $index + 1; // 1=Monday, 7=Sunday
                }
            }

            foreach ($dayNames as $index => $name) {
                $isWeeklyHoliday = in_array($index + 1, $weeklyHolidayDays);
                echo '<div class="day-name' . ($isWeeklyHoliday ? ' weekly-holiday' : '') . '">' . $name . '</div>';
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

                // Check if this day is a weekly holiday
                $dayOfWeek = date('N', strtotime($date)); // 1=Monday, 7=Sunday
                $isWeeklyHoliday = in_array($dayOfWeek, $weeklyHolidayDays);

                $class = 'day-cell';
                if ($isHoliday || $isWeeklyHoliday) $class .= ' has-holiday';

                echo '<div class="' . $class . '">';

                $dayNumberClass = '';
                if ($isToday) $dayNumberClass = ' today';
                if ($isHoliday || $isWeeklyHoliday) $dayNumberClass .= ' holiday-number';
                if ($isWeeklyHoliday) $dayNumberClass .= ' weekly-holiday';

                echo '<div class="day-number' . $dayNumberClass . '">' . $day . '</div>';

                if ($isHoliday) {
                    $typeClass = $holiday['holiday_type'] ?? 'public';
                    echo '<span class="holiday-badge ' . $typeClass . '">' . escapeHtml($holiday['holiday_name']) . '</span>';
                    echo '<div class="holiday-tooltip">';
                    echo '<strong>' . escapeHtml($holiday['holiday_name']) . '</strong><br>';
                    echo '<span style="font-size: 11px; opacity: 0.8;">' . ucfirst($holiday['holiday_type']) . ' Holiday</span>';
                    echo '</div>';
                } elseif ($isWeeklyHoliday) {
                    echo '<span class="weekly-holiday-badge">Weekly Off</span>';
                    echo '<div class="holiday-tooltip">';
                    echo '<strong>Weekly Holiday</strong><br>';
                    echo '<span style="font-size: 11px; opacity: 0.8;">' . $dayNames[$dayOfWeek - 1] . ' is a weekly holiday</span>';
                    echo '</div>';
                }

                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Holiday List -->
    <div style="margin-top: 24px; overflow-x: auto;">
        <h4 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; color: #052E16; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-list" style="color: #6B7A7B;"></i> 
            Holiday List (<?php echo date('F Y', strtotime("$year-$month-01")); ?>)
            <span class="badge-count" style="font-size: 12px; font-weight: 500; color: #16A34A; background: #DCFCE7; padding: 2px 12px; border-radius: 20px;">
                <?php echo count($holidays); ?> Holidays
            </span>
        </h4>

        <?php if (empty($holidays)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-day"></i>
                <p style="margin: 0;">No holidays scheduled for this month.</p>
                <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.7;">Click "Add Holiday" to schedule one.</p>
            </div>
        <?php else: ?>
            <table class="holiday-list-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Holiday Name</th>
                        <th>Type</th>
                        <th>Recurring</th>
                        <th>Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holidays as $holiday): ?>
                        <tr>
                            <td><strong><?php echo formatDate($holiday['holiday_date']); ?></strong></td>
                            <td>
                                <strong><?php echo escapeHtml($holiday['holiday_name']); ?></strong>
                                <?php if (!empty($holiday['description'])): ?>
                                    <br><span style="font-size: 11px; color: #6B7A7B;"><?php echo escapeHtml($holiday['description']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="holiday-type-badge <?php echo $holiday['holiday_type']; ?>">
                                    <?php echo ucfirst($holiday['holiday_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($holiday['is_recurring']): ?>
                                    <span style="color: #16A34A; font-size: 12px; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-sync"></i> <?php echo ucfirst($holiday['recurring_type'] ?? 'yearly'); ?>
                                        <?php if ($holiday['weekly_holiday']): ?>
                                            (<?php echo escapeHtml($holiday['weekly_holiday']); ?>)
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6B7A7B; font-size: 12px;">One-time</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?php echo $holiday['status']; ?>">
                                    <?php echo ucfirst($holiday['status']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="btn-group" style="justify-content: center; gap: 4px;">
                                    <a href="?edit=<?php echo $holiday['id']; ?>" class="btn-warning-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?holiday_action=toggle&holiday_id=<?php echo $holiday['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                                        class="<?php echo $holiday['status'] === 'active' ? 'btn-danger' : 'btn-success-sm'; ?>"
                                        onclick="return confirm('Toggle holiday status?')">
                                        <i class="fas fa-<?php echo $holiday['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                    </a>
                                    <a href="?holiday_action=delete&holiday_id=<?php echo $holiday['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                                        class="btn-danger"
                                        onclick="return confirm('Delete this holiday?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
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

        window.location.href = 'attendance-settings.php?month=' + newMonth + '&year=' + newYear;
    }

    function openHolidayForm() {
        document.getElementById('holidayFormContainer').style.display = 'block';
        document.getElementById('holidayFormContainer').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    function closeHolidayForm() {
        document.getElementById('holidayFormContainer').style.display = 'none';
        if (window.history && window.history.pushState) {
            window.history.pushState({}, '', 'attendance-settings.php');
        }
    }

    <?php if ($editHoliday): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('holidayFormContainer').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    <?php endif; ?>
</script>

<?php require_once '../includes/admin_footer.php'; ?>