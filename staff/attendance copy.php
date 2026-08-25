<?php
/**
 * SAMRIDHI AGRO - Staff Attendance
 * 
 * This page allows staff to check-in/out with geolocation validation.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
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

// Get attendance settings
$sql = "SELECT setting_key, setting_value FROM attendance_settings";
$settings = $db->fetchAll($sql);
$attSettings = [];
foreach ($settings as $s) {
    $attSettings[$s['setting_key']] = $s['setting_value'];
}

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

// Calculate statistics
$totalDays = count($attendanceHistory);
$presentDays = 0;
$absentDays = 0;
$halfDays = 0;
$totalOvertime = 0;

foreach ($attendanceHistory as $record) {
    switch ($record['status']) {
        case 'present': $presentDays++; break;
        case 'absent': $absentDays++; break;
        case 'half_day': $halfDays++; break;
    }
    $totalOvertime += $record['overtime_hours'] ?? 0;
}

$attendancePercentage = $totalDays > 0 ? round($presentDays / $totalDays * 100) : 0;

// Handle check-in/out via AJAX
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
        // Check if user is within office geofence (for staff)
        if ($lat && $lng) {
            $locationCheck = canCheckInFromLocation($_SESSION['user_id'], $lat, $lng);
            if (!$locationCheck['allowed']) {
                echo json_encode(['success' => false, 'message' => $locationCheck['message']]);
                exit;
            }
        }
        
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

$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .attendance-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 16px;
    }
    
    .attendance-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
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
    
    .attendance-status-large .status-location {
        font-size: 13px;
        color: #4A5B5D;
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
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
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
    
    .stat-item .stat-number.present { color: #16A34A; }
    .stat-item .stat-number.absent { color: #DC2626; }
    .stat-item .stat-number.half-day { color: #F59E0B; }
    .stat-item .stat-number.overtime { color: #7C3AED; }
    
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
    
    .geofence-info {
        background: #F0FDF4;
        border: 1px solid #DCFCE7;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        color: #065F46;
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .geofence-info i {
        font-size: 16px;
    }
    
    @media (max-width: 768px) {
        .attendance-container {
            grid-template-columns: 1fr;
        }
        .stats-grid-attendance {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid-attendance {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-check" style="color: #16A34A;"></i>
            My Attendance
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo $attendancePercentage; ?>% - Last 30 days)
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
                        <?php if ($todayAttendance['check_in_location']): ?>
                        <div class="status-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo escapeHtml($todayAttendance['check_in_location']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn-checkin check-out" disabled>Already Checked Out</button>
                <?php else: ?>
                    <div class="attendance-status-large checked-in">
                        <div class="status-icon"><i class="fas fa-clock"></i></div>
                        <div class="status-text">Checked In</div>
                        <div class="status-time">
                            Checked In: <?php echo date('h:i A', strtotime($todayAttendance['check_in_time'])); ?>
                        </div>
                        <?php if ($todayAttendance['check_in_location']): ?>
                        <div class="status-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo escapeHtml($todayAttendance['check_in_location']); ?>
                        </div>
                        <?php endif; ?>
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
            
            <div class="geofence-info">
                <i class="fas fa-map-pin"></i>
                <span>You must be within the office geofence to check-in.</span>
            </div>
        </div>
        
        <!-- Statistics Section -->
        <div class="attendance-card">
            <div class="card-title">Attendance Statistics</div>
            
            <div class="stats-grid-attendance">
                <div class="stat-item">
                    <div class="stat-number present"><?php echo $presentDays; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number absent"><?php echo $absentDays; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number half-day"><?php echo $halfDays; ?></div>
                    <div class="stat-label">Half Day</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number overtime"><?php echo number_format($totalOvertime, 1); ?>h</div>
                    <div class="stat-label">Overtime</div>
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
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo $csrfToken; ?>';
    
    // Check In
    const checkInBtn = document.getElementById('checkInBtn');
    if (checkInBtn) {
        checkInBtn.addEventListener('click', function() {
            // Get location
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
                            title: 'Location Required',
                            text: 'Please enable location services to check in.',
                            confirmButtonColor: '#16A34A'
                        });
                    }
                );
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Location Not Supported',
                    text: 'Your browser does not support geolocation.',
                    confirmButtonColor: '#DC2626'
                });
            }
        });
    }
    
    function checkIn(lat, lng) {
        Swal.fire({
            title: 'Checking In...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        const formData = new FormData();
        formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);
        formData.append('action', 'check_in');
        formData.append('lat', lat);
        formData.append('lng', lng);
        formData.append('location', 'Office Location');
        
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
                }).then(() => window.location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Check-in Failed',
                    text: data.message,
                    confirmButtonColor: '#DC2626'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.',
                confirmButtonColor: '#DC2626'
            });
        });
    }
    
    // Check Out
    const checkOutBtn = document.getElementById('checkOutBtn');
    if (checkOutBtn) {
        checkOutBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Check Out?',
                text: 'Are you sure you want to check out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#6B7A7B',
                confirmButtonText: 'Yes, check out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);
                    formData.append('action', 'check_out');
                    formData.append('location', 'Office Location');
                    
                    Swal.fire({
                        title: 'Checking Out...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
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
                            }).then(() => window.location.reload());
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong. Please try again.',
                            confirmButtonColor: '#DC2626'
                        });
                    });
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>