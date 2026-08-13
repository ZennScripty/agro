<?php
/**
 * SAMRIDHI AGRO - Staff Permissions Management
 * 
 * This page allows administrators to manage individual staff permissions
 * with bulk select feature for granting/revoking multiple permissions at once.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// ============================================
// STEP 1: All PHP logic FIRST (no HTML output)
// ============================================

// Set page title
$pageTitle = 'Staff Permissions';

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Require admin login and permission
requireLogin();
requireRole('admin');
requirePermission('staff.edit');

// Get database instance
$db = getDB();

// Get staff ID from URL
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to staff list
if ($staffId <= 0) {
    setFlashMessage('error', 'Invalid staff ID.');
    redirect('admin/staff.php');
    exit;
}

// Get staff data
$sql = "SELECT u.*, sp.department, sp.designation 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ? AND u.role = 'staff'";
$staff = $db->fetchOne($sql, [$staffId]);

// If staff not found, redirect
if (!$staff) {
    setFlashMessage('error', 'Staff member not found.');
    redirect('admin/staff.php');
    exit;
}

// Cannot manage permissions for self
if ($staffId == $_SESSION['user_id']) {
    setFlashMessage('error', 'You cannot manage permissions for your own account.');
    redirect('admin/staff.php');
    exit;
}

// ============================================
// HANDLE BULK PERMISSION UPDATES
// ============================================

// Handle bulk update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
    
    $bulkAction = $_POST['bulk_action'] ?? '';
    $selectedPermissions = isset($_POST['selected_permissions']) ? (array)$_POST['selected_permissions'] : [];
    
    if (empty($selectedPermissions)) {
        setFlashMessage('error', 'Please select at least one permission.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
    
    if (!in_array($bulkAction, ['grant', 'revoke'])) {
        setFlashMessage('error', 'Invalid action.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
    
    try {
        $db->beginTransaction();
        $count = 0;
        $permissionNames = [];
        
        foreach ($selectedPermissions as $permissionId) {
            $permissionId = (int)$permissionId;
            
            // Get permission name for log
            $sql = "SELECT permission_name FROM permissions WHERE id = ?";
            $perm = $db->fetchOne($sql, [$permissionId]);
            $permName = $perm['permission_name'] ?? 'Unknown';
            
            if ($bulkAction === 'grant') {
                // Check if permission already exists
                $sql = "SELECT id FROM user_permissions WHERE user_id = ? AND permission_id = ?";
                $existing = $db->fetchOne($sql, [$staffId, $permissionId]);
                
                if (!$existing) {
                    $sql = "INSERT INTO user_permissions (user_id, permission_id, created_at) 
                            VALUES (?, ?, NOW())";
                    $db->query($sql, [$staffId, $permissionId]);
                    $count++;
                    $permissionNames[] = $permName;
                }
            } else {
                // Revoke permission
                $sql = "DELETE FROM user_permissions WHERE user_id = ? AND permission_id = ?";
                $db->query($sql, [$staffId, $permissionId]);
                $count++;
                $permissionNames[] = $permName;
            }
        }
        
        $db->commit();
        
        logActivity(
            'update',
            $_SESSION['user_id'],
            'staff',
            ucfirst($bulkAction) . 'ed ' . $count . ' permissions for staff: ' . $staff['full_name'] . 
            ' (' . implode(', ', array_slice($permissionNames, 0, 5)) . ($count > 5 ? ' and ' . ($count - 5) . ' more' : '') . ')'
        );
        
        setFlashMessage('success', ucfirst($bulkAction) . 'ed ' . $count . ' permissions successfully!');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log('Bulk permission update error: ' . $e->getMessage());
        setFlashMessage('error', 'Failed to update permissions. Please try again.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
}

// Handle single permission update (existing code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'bulk') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
    
    $action = $_POST['action'];
    $permissionId = isset($_POST['permission_id']) ? (int)$_POST['permission_id'] : 0;
    
    if ($permissionId <= 0) {
        setFlashMessage('error', 'Invalid permission.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
    
    try {
        if ($action === 'add') {
            $sql = "INSERT INTO user_permissions (user_id, permission_id, created_at) 
                    VALUES (?, ?, NOW())";
            $db->query($sql, [$staffId, $permissionId]);
            
            $sql = "SELECT permission_name FROM permissions WHERE id = ?";
            $perm = $db->fetchOne($sql, [$permissionId]);
            $permName = $perm['permission_name'] ?? 'Unknown';
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'staff',
                'Added permission "' . $permName . '" to staff: ' . $staff['full_name']
            );
            
            setFlashMessage('success', 'Permission added successfully!');
            
        } elseif ($action === 'remove') {
            $sql = "DELETE FROM user_permissions WHERE user_id = ? AND permission_id = ?";
            $db->query($sql, [$staffId, $permissionId]);
            
            $sql = "SELECT permission_name FROM permissions WHERE id = ?";
            $perm = $db->fetchOne($sql, [$permissionId]);
            $permName = $perm['permission_name'] ?? 'Unknown';
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'staff',
                'Removed permission "' . $permName . '" from staff: ' . $staff['full_name']
            );
            
            setFlashMessage('success', 'Permission removed successfully!');
        }
        
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
        
    } catch (Exception $e) {
        error_log('Permission update error: ' . $e->getMessage());
        setFlashMessage('error', 'Failed to update permission. Please try again.');
        redirect('admin/staff-permissions.php?id=' . $staffId);
        exit;
    }
}

// Get all available permissions grouped by module
$sql = "SELECT * FROM permissions ORDER BY module, permission_name";
$allPermissions = $db->fetchAll($sql);

// Get user's current permissions
$sql = "SELECT permission_id FROM user_permissions WHERE user_id = ?";
$userPermissions = $db->fetchAll($sql, [$staffId]);
$userPermissionIds = array_column($userPermissions, 'permission_id');

// Group permissions by module
$groupedPermissions = [];
foreach ($allPermissions as $perm) {
    $module = $perm['module'] ?? 'General';
    if (!isset($groupedPermissions[$module])) {
        $groupedPermissions[$module] = [];
    }
    $perm['has_permission'] = in_array($perm['id'], $userPermissionIds);
    $groupedPermissions[$module][] = $perm;
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .permission-group {
        margin-bottom: 24px;
    }
    
    .permission-group-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        padding-bottom: 8px;
        border-bottom: 2px solid #E5EDE7;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .permission-group-title .badge {
        background: #DCFCE7;
        color: #065F46;
        font-size: 12px;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: 500;
    }
    
    .permission-group-title .select-all-btn {
        margin-left: auto;
        font-size: 12px;
        color: #16A34A;
        cursor: pointer;
        background: none;
        border: none;
        font-weight: 500;
        padding: 4px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .permission-group-title .select-all-btn:hover {
        background: #F0FDF4;
    }
    
    .permission-item {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 8px;
        background: #F7FCF7;
        margin-bottom: 6px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        gap: 12px;
    }
    
    .permission-item:hover {
        background: #F0FDF4;
        border-color: #DCFCE7;
    }
    
    .permission-item .permission-checkbox {
        flex-shrink: 0;
    }
    
    .permission-item .permission-checkbox input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #16A34A;
        cursor: pointer;
    }
    
    .permission-item .permission-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        cursor: pointer;
    }
    
    .permission-item .permission-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: #E5EDE7;
        color: #4A5B5D;
        flex-shrink: 0;
    }
    
    .permission-item .permission-icon.has-permission {
        background: #DCFCE7;
        color: #16A34A;
    }
    
    .permission-item .permission-name {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #052E16;
    }
    
    .permission-item .permission-slug {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
        font-weight: 400;
        margin-left: 8px;
    }
    
    .permission-item .permission-description {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #6B7A7B;
        flex: 1;
        margin-left: 8px;
    }
    
    .permission-item .permission-status {
        flex-shrink: 0;
        font-size: 12px;
        font-weight: 600;
    }
    
    .permission-item .permission-status.granted {
        color: #16A34A;
    }
    
    .permission-item .permission-status.revoked {
        color: #6B7A7B;
    }
    
    .permission-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .stat-box {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 18px;
        text-align: center;
    }
    
    .stat-box .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #052E16;
    }
    
    .stat-box .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .stat-box.stat-total .stat-number { color: #14532D; }
    .stat-box.stat-granted .stat-number { color: #16A34A; }
    .stat-box.stat-revoked .stat-number { color: #DC2626; }
    
    .bulk-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        padding: 12px 16px;
        background: #F7FCF7;
        border-radius: 10px;
        margin-bottom: 16px;
        border: 1px solid #E5EDE7;
    }
    
    .bulk-actions .selected-count {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #052E16;
    }
    
    .bulk-actions .btn-bulk {
        padding: 6px 16px;
        border: none;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .bulk-actions .btn-bulk.btn-grant {
        background: #16A34A;
        color: white;
    }
    
    .bulk-actions .btn-bulk.btn-grant:hover {
        background: #14532D;
    }
    
    .bulk-actions .btn-bulk.btn-revoke {
        background: #DC2626;
        color: white;
    }
    
    .bulk-actions .btn-bulk.btn-revoke:hover {
        background: #991B1B;
    }
    
    .bulk-actions .btn-bulk:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .bulk-actions .btn-bulk.btn-clear {
        background: #F3F4F6;
        color: #4A5B5D;
    }
    
    .bulk-actions .btn-bulk.btn-clear:hover {
        background: #E5E7EB;
    }
    
    .btn-toggle {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-toggle.btn-grant {
        background: #DCFCE7;
        color: #16A34A;
    }
    
    .btn-toggle.btn-grant:hover {
        background: #BBF7D0;
    }
    
    .btn-toggle.btn-revoke {
        background: #FEE2E2;
        color: #DC2626;
    }
    
    .btn-toggle.btn-revoke:hover {
        background: #FECACA;
    }
    
    @media (max-width: 768px) {
        .permission-item {
            flex-wrap: wrap;
        }
        
        .permission-item .permission-info {
            flex-wrap: wrap;
            width: 100%;
        }
        
        .permission-item .permission-description {
            width: 100%;
            margin-left: 44px;
        }
        
        .permission-item .permission-actions {
            margin-left: 44px;
        }
        
        .bulk-actions {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-lock" style="color: #16A34A;"></i>
            Manage Permissions
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $staff['id']; ?> - <?php echo escapeHtml($staff['full_name']); ?>
            </span>
        </h3>
        <a href="admin/staff.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Staff List
        </a>
    </div>
    
    <!-- Staff Info -->
    <div style="background: #F7FCF7; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
        <div>
            <span style="font-size: 13px; color: #6B7A7B;">Username</span>
            <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($staff['username']); ?></div>
        </div>
        <div>
            <span style="font-size: 13px; color: #6B7A7B;">Email</span>
            <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($staff['email']); ?></div>
        </div>
        <div>
            <span style="font-size: 13px; color: #6B7A7B;">Department</span>
            <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($staff['department'] ?? 'N/A'); ?></div>
        </div>
        <div>
            <span style="font-size: 13px; color: #6B7A7B;">Designation</span>
            <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($staff['designation'] ?? 'N/A'); ?></div>
        </div>
        <div style="margin-left: auto;">
            <span style="font-size: 13px; color: #6B7A7B;">Status</span>
            <div>
                <?php 
                $statusColors = [
                    'active' => 'badge-success',
                    'inactive' => 'badge-warning',
                    'suspended' => 'badge-danger'
                ];
                $color = $statusColors[$staff['status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $color; ?>">
                    <?php echo ucfirst($staff['status']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Permission Statistics -->
    <?php
    $totalPermissions = count($allPermissions);
    $grantedPermissions = count($userPermissionIds);
    $revokedPermissions = $totalPermissions - $grantedPermissions;
    ?>
    <div class="permission-stats">
        <div class="stat-box stat-total">
            <div class="stat-number"><?php echo $totalPermissions; ?></div>
            <div class="stat-label">Total Permissions</div>
        </div>
        <div class="stat-box stat-granted">
            <div class="stat-number"><?php echo $grantedPermissions; ?></div>
            <div class="stat-label">Granted</div>
        </div>
        <div class="stat-box stat-revoked">
            <div class="stat-number"><?php echo $revokedPermissions; ?></div>
            <div class="stat-label">Revoked</div>
        </div>
    </div>
    
    <!-- Bulk Actions Bar -->
    <div class="bulk-actions" id="bulkActions">
        <span class="selected-count" id="selectedCount">0 permissions selected</span>
        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-left: auto;">
            <button type="button" class="btn-bulk btn-grant" id="bulkGrantBtn" disabled onclick="submitBulkAction('grant')">
                <i class="fas fa-check"></i> Grant Selected
            </button>
            <button type="button" class="btn-bulk btn-revoke" id="bulkRevokeBtn" disabled onclick="submitBulkAction('revoke')">
                <i class="fas fa-times"></i> Revoke Selected
            </button>
            <button type="button" class="btn-bulk btn-clear" onclick="clearAllSelections()">
                <i class="fas fa-times-circle"></i> Clear All
            </button>
        </div>
    </div>
    
    <!-- Permissions List -->
    <form method="POST" action="" id="permissionForm">
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="bulk_action" id="bulkAction" value="">
        
        <?php if (empty($groupedPermissions)): ?>
            <div style="text-align: center; padding: 40px; color: #6B7A7B;">
                <i class="fas fa-lock" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                No permissions found in the system.
            </div>
        <?php else: ?>
            <?php foreach ($groupedPermissions as $module => $permissions): ?>
                <div class="permission-group">
                    <div class="permission-group-title">
                        <i class="fas fa-<?php echo getModuleIcon($module); ?>" style="color: #16A34A;"></i>
                        <?php echo escapeHtml(ucfirst($module)); ?>
                        <span class="badge">
                            <?php 
                            $grantedCount = count(array_filter($permissions, function($p) { return $p['has_permission']; }));
                            echo $grantedCount . '/' . count($permissions);
                            ?>
                        </span>
                        <button type="button" class="select-all-btn" onclick="toggleGroupSelect(this, '<?php echo $module; ?>')">
                            <i class="fas fa-check-double"></i> Select All
                        </button>
                    </div>
                    
                    <?php foreach ($permissions as $perm): ?>
                        <div class="permission-item" data-module="<?php echo $module; ?>">
                            <div class="permission-checkbox">
                                <input type="checkbox" name="selected_permissions[]" value="<?php echo $perm['id']; ?>" 
                                       class="permission-checkbox-input" onchange="updateSelectionCount()">
                            </div>
                            <div class="permission-info" onclick="toggleCheckbox(this)">
                                <div class="permission-icon <?php echo $perm['has_permission'] ? 'has-permission' : ''; ?>">
                                    <i class="fas fa-<?php echo getPermissionIcon($perm['permission_slug']); ?>"></i>
                                </div>
                                <div>
                                    <span class="permission-name"><?php echo escapeHtml($perm['permission_name']); ?></span>
                                    <span class="permission-slug">(<?php echo escapeHtml($perm['permission_slug']); ?>)</span>
                                </div>
                                <?php if (!empty($perm['description'])): ?>
                                    <span class="permission-description">
                                        <i class="fas fa-info-circle" style="color: #6B7A7B; font-size: 12px;"></i>
                                        <?php echo escapeHtml($perm['description']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="permission-status <?php echo $perm['has_permission'] ? 'granted' : 'revoked'; ?>">
                                <?php if ($perm['has_permission']): ?>
                                    <i class="fas fa-check-circle" style="color: #16A34A;"></i> Granted
                                <?php else: ?>
                                    <i class="fas fa-circle" style="color: #D1D5DB;"></i> Revoked
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </form>
</div>

<script>
// ============================================
// BULK SELECT FUNCTIONS
// ============================================

function updateSelectionCount() {
    const checkboxes = document.querySelectorAll('.permission-checkbox-input:checked');
    const count = checkboxes.length;
    const selectedCount = document.getElementById('selectedCount');
    const grantBtn = document.getElementById('bulkGrantBtn');
    const revokeBtn = document.getElementById('bulkRevokeBtn');
    
    selectedCount.textContent = count + ' permission' + (count !== 1 ? 's' : '') + ' selected';
    grantBtn.disabled = count === 0;
    revokeBtn.disabled = count === 0;
}

function toggleCheckbox(element) {
    const checkbox = element.closest('.permission-item').querySelector('.permission-checkbox-input');
    checkbox.checked = !checkbox.checked;
    updateSelectionCount();
}

function toggleGroupSelect(button, module) {
    const items = document.querySelectorAll('.permission-item[data-module="' + module + '"]');
    const checkboxes = document.querySelectorAll('.permission-item[data-module="' + module + '"] .permission-checkbox-input');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
    
    button.innerHTML = allChecked ? '<i class="fas fa-check-double"></i> Select All' : '<i class="fas fa-check-double"></i> Deselect All';
    updateSelectionCount();
}

function clearAllSelections() {
    document.querySelectorAll('.permission-checkbox-input').forEach(cb => {
        cb.checked = false;
    });
    updateSelectionCount();
    
    // Reset all "Select All" buttons
    document.querySelectorAll('.select-all-btn').forEach(btn => {
        btn.innerHTML = '<i class="fas fa-check-double"></i> Select All';
    });
}

function submitBulkAction(action) {
    const selected = document.querySelectorAll('.permission-checkbox-input:checked');
    if (selected.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Permissions Selected',
            text: 'Please select at least one permission.',
            confirmButtonColor: '#F59E0B'
        });
        return;
    }
    
    const actionText = action === 'grant' ? 'Grant' : 'Revoke';
    const actionColor = action === 'grant' ? '#16A34A' : '#DC2626';
    
    Swal.fire({
        title: `${actionText} Permissions?`,
        html: `
            <p style="font-size: 16px; margin-bottom: 10px;">
                Are you sure you want to <strong>${actionText.toLowerCase()}</strong> 
                <strong>${selected.length}</strong> permission${selected.length !== 1 ? 's' : ''}?
            </p>
            <div style="background: #F7FCF7; padding: 12px; border-radius: 8px; margin-top: 10px; max-height: 150px; overflow-y: auto;">
                ${Array.from(selected).map(cb => {
                    const item = cb.closest('.permission-item');
                    const name = item.querySelector('.permission-name').textContent;
                    return `<div style="padding: 2px 0; font-size: 13px;">• ${escapeHtml(name)}</div>`;
                }).join('')}
            </div>
            <p style="font-size: 14px; color: #6B7A7B; margin-top: 12px;">
                This will ${action === 'grant' ? 'enable' : 'disable'} these permissions for 
                <strong><?php echo escapeHtml($staff['full_name']); ?></strong>.
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#6B7A7B',
        confirmButtonText: `Yes, ${actionText} Selected`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('bulkAction').value = action;
            document.getElementById('permissionForm').submit();
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// HELPER FUNCTIONS FOR ICONS
// ============================================

function getModuleIcon(module) {
    const icons = {
        'dashboard': 'th-large',
        'staff': 'users',
        'agent': 'user-tie',
        'shop': 'store',
        'product': 'box',
        'category': 'tags',
        'order': 'shopping-cart',
        'inventory': 'warehouse',
        'report': 'chart-bar',
        'settings': 'cog',
        'security': 'shield-alt',
        'general': 'circle'
    };
    return icons[module] || 'circle';
}

function getPermissionIcon(slug) {
    if (slug.indexOf('view') !== -1) return 'eye';
    if (slug.indexOf('create') !== -1) return 'plus-circle';
    if (slug.indexOf('edit') !== -1) return 'edit';
    if (slug.indexOf('delete') !== -1) return 'trash';
    if (slug.indexOf('approve') !== -1) return 'check-double';
    if (slug.indexOf('update') !== -1) return 'sync';
    if (slug.indexOf('cancel') !== -1) return 'times-circle';
    return 'circle';
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>