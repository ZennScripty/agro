<?php
/**
 * SAMRIDHI AGRO - Attendance Settings
 * 
 * This page allows administrators to manage attendance settings.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Attendance Settings';

// Include admin header
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('attendance.settings.view', 'attendance-settings.php');

// Get database instance
$db = getDB();

// Get all attendance settings
$sql = "SELECT * FROM attendance_settings";
$allSettings = $db->fetchAll($sql);
$settings = [];
foreach ($allSettings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('settings.update');
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('admin/attendance-settings.php');
        exit;
    }
    
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
        'attendance_approval_required' => isset($_POST['attendance_approval_required']) ? '1' : '0'
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

$csrfToken = generateCsrfToken();
?>

<style>
    .settings-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }
    
    .settings-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #F0FDF4;
    }
    
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-weight: 600; color: #14532D; margin-bottom: 6px; font-size: 14px; }
    .form-input { width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px; }
    .form-input:focus { outline: none; border-color: #16A34A; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-hint { font-size: 12px; color: #6B7A7B; margin-top: 4px; }
    .checkbox-group { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #16A34A; }
    .btn-primary { padding: 10px 28px; background: linear-gradient(135deg, #14532D, #16A34A); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); }
    
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Attendance Settings
        </h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <!-- Time Settings -->
        <div class="settings-card">
            <div class="card-title">⏰ Time Settings</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Check-in Start Time</label>
                    <input type="time" name="check_in_start_time" class="form-input" 
                           value="<?php echo escapeHtml($settings['check_in_start_time'] ?? '09:00:00'); ?>">
                    <div class="form-hint">Office start time for check-in</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Check-in End Time</label>
                    <input type="time" name="check_in_end_time" class="form-input" 
                           value="<?php echo escapeHtml($settings['check_in_end_time'] ?? '10:00:00'); ?>">
                    <div class="form-hint">Late check-in allowed until this time</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Check-out Start Time</label>
                    <input type="time" name="check_out_start_time" class="form-input" 
                           value="<?php echo escapeHtml($settings['check_out_start_time'] ?? '17:30:00'); ?>">
                    <div class="form-hint">Earliest check-out time</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Check-out End Time</label>
                    <input type="time" name="check_out_end_time" class="form-input" 
                           value="<?php echo escapeHtml($settings['check_out_end_time'] ?? '23:59:00'); ?>">
                    <div class="form-hint">Latest check-out time</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Standard Work Hours (per day)</label>
                <input type="number" name="work_hours" class="form-input" step="0.5" min="1" max="24"
                       value="<?php echo escapeHtml($settings['work_hours'] ?? '8.00'); ?>">
                <div class="form-hint">Number of hours considered as full work day</div>
            </div>
        </div>
        
        <!-- Geolocation Settings -->
        <div class="settings-card">
            <div class="card-title">📍 Geolocation Settings</div>
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
                    <div class="form-hint">Office latitude for location validation</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Office Longitude</label>
                    <input type="text" name="office_lng" class="form-input" 
                           value="<?php echo escapeHtml($settings['office_lng'] ?? '77.2090'); ?>">
                    <div class="form-hint">Office longitude for location validation</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Geolocation Radius (meters)</label>
                <input type="number" name="geolocation_radius" class="form-input" min="100" max="5000"
                       value="<?php echo escapeHtml($settings['geolocation_radius'] ?? '500'); ?>">
                <div class="form-hint">Allowed radius from office location for check-in</div>
            </div>
        </div>
        
        <!-- Other Settings -->
        <div class="settings-card">
            <div class="card-title">⚙️ Other Settings</div>
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

<?php require_once '../includes/admin_footer.php'; ?>