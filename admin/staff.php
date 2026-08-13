<?php

/**
 * SAMRIDHI AGRO - Staff Management
 * 
 * This page displays all staff members with search, filter,
 * and management capabilities.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.1.0
 */

// ============================================
// STEP 1: All PHP logic FIRST (no HTML output)
// ============================================

// Set page title
$pageTitle = 'Staff Management';

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requireLogin();

// Admin has all access, Staff needs specific permission
if (!isAdmin() && !hasPermission('agent.view')) {
    logActivity(
        'unauthorized_access',
        $_SESSION['user_id'],
        'security',
        'Attempted to access agents.php without permission'
    );
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

// ============================================
// PROCESS ACTIONS
// ============================================

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    requirePermission('staff.edit');

    $staffId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get current status
        $sql = "SELECT status FROM users WHERE id = ? AND role = 'staff'";
        $user = $db->fetchOne($sql, [$staffId]);

        if ($user) {
            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $db->query($sql, [$newStatus, $staffId]);

            logActivity(
                'update',
                $_SESSION['user_id'],
                'staff',
                'Toggled staff status to ' . $newStatus . ' for user ID: ' . $staffId
            );

            setFlashMessage('success', 'Staff status updated successfully.');
        } else {
            setFlashMessage('error', 'Staff member not found.');
        }
    }

    redirect('staff.php');
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    requirePermission('staff.delete');

    $staffId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Check if trying to delete self
        if ($staffId == $_SESSION['user_id']) {
            setFlashMessage('error', 'You cannot delete your own account.');
        } else {
            // Check if staff exists
            $sql = "SELECT id, full_name FROM users WHERE id = ? AND role = 'staff'";
            $user = $db->fetchOne($sql, [$staffId]);

            if ($user) {
                // Soft delete - set status to suspended
                $sql = "UPDATE users SET status = 'suspended' WHERE id = ?";
                $db->query($sql, [$staffId]);

                logActivity(
                    'delete',
                    $_SESSION['user_id'],
                    'staff',
                    'Deleted staff: ' . $user['full_name'] . ' (ID: ' . $staffId . ')'
                );

                setFlashMessage('success', 'Staff member deleted successfully.');
            } else {
                setFlashMessage('error', 'Staff member not found.');
            }
        }
    }

    redirect('staff.php');
    exit;
}

// ============================================
// GET STAFF LIST
// ============================================

// Search and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = ["u.role = 'staff'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "u.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $whereConditions);

// Count total records
$sql = "SELECT COUNT(*) as total FROM users u WHERE $whereClause";
$result = $db->fetchOne($sql, $params);
$totalStaff = $result['total'] ?? 0;

// Get staff records
$sql = "SELECT u.*, 
        sp.department, sp.designation, sp.joining_date
        FROM users u
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id
        WHERE $whereClause
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$staffList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalStaff / $perPage);
$pagination = getPagination($totalStaff, $page, $perPage, 'staff.php?page={page}&search=' . urlencode($search) . '&status=' . $status);

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Staff Management Content -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users" style="color: #16A34A;"></i>
            All Staff Members
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalStaff); ?>)
            </span>
        </h3>
        <div>
            <a href="staff-add.php" class="btn-primary" style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background: linear-gradient(135deg, #14532D, #16A34A);
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            ">
                <i class="fas fa-plus"></i>
                Add Staff
            </a>
        </div>
    </div>

    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
        <form method="GET" action="" style="flex: 1; min-width: 200px; display: flex; gap: 12px;">
            <div style="flex: 1; position: relative;">
                <input
                    type="text"
                    name="search"
                    placeholder="Search by name, email, username..."
                    value="<?php echo escapeHtml($search); ?>"
                    style="
                        width: 100%;
                        padding: 10px 16px 10px 40px;
                        border: 2px solid #E5EDE7;
                        border-radius: 10px;
                        font-family: 'Inter', sans-serif;
                        font-size: 14px;
                        transition: all 0.3s ease;
                    ">
                <i class="fas fa-search" style="
                    position: absolute;
                    left: 14px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #6B7A7B;
                "></i>
            </div>
            <select name="status" style="
                padding: 10px 16px;
                border: 2px solid #E5EDE7;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                background: white;
                cursor: pointer;
            ">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
            <button type="submit" style="
                padding: 10px 24px;
                background: #14532D;
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            ">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || $status !== 'all'): ?>
                <a href="staff.php" style="
                padding: 10px 16px;
                background: #F3F4F6;
                color: #4A5B5D;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                text-decoration: none;
                transition: all 0.3s ease;
            ">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Staff Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staffList)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6B7A7B;">
                            <i class="fas fa-user-slash" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                            No staff members found
                            <?php if (!empty($search) || $status !== 'all'): ?>
                                <br><span style="font-size: 13px;">Try adjusting your search or filters</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staffList as $staff): ?>
                        <tr>
                            <td><strong>#<?php echo $staff['id']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="
                                width: 36px;
                                height: 36px;
                                border-radius: 50%;
                                background: <?php echo $staff['status'] === 'active' ? '#DCFCE7' : '#F3F4F6'; ?>;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: <?php echo $staff['status'] === 'active' ? '#16A34A' : '#6B7A7B'; ?>;
                                font-weight: 600;
                                font-size: 14px;
                            ">
                                        <?php echo strtoupper(substr($staff['full_name'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #052E16;"><?php echo escapeHtml($staff['full_name']); ?></div>
                                        <?php if ($staff['designation']): ?>
                                            <div style="font-size: 12px; color: #6B7A7B;"><?php echo escapeHtml($staff['designation']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo escapeHtml($staff['username']); ?></td>
                            <td><?php echo escapeHtml($staff['email']); ?></td>
                            <td><?php echo escapeHtml($staff['department'] ?? 'N/A'); ?></td>
                            <td>
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
                            </td>
                            <td><?php echo formatDate($staff['created_at']); ?></td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                    <!-- Edit Button -->
                                    <a href="staff-edit.php?id=<?php echo $staff['id']; ?>"
                                        class="btn-action btn-edit"
                                        title="Edit Staff"
                                        style="
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 8px;
                                    border: none;
                                    background: #DBEAFE;
                                    color: #2563EB;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    text-decoration: none;
                                    transition: all 0.3s ease;
                                    cursor: pointer;
                               ">
                                        <i class="fas fa-edit" style="font-size: 13px;"></i>
                                    </a>
                                    <!-- View Attendance -->
                                    <a href="staff-attendance.php?id=<?php echo $staff['id']; ?>"
                                        class="btn-action btn-attendance"
                                        title="View Attendance"
                                        style="
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #DBEAFE;
        color: #2563EB;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
   ">
                                        <i class="fas fa-calendar-check" style="font-size: 13px;"></i>
                                    </a>

                                    <!-- View Visits -->
                                    <a href="staff-visits.php?id=<?php echo $staff['id']; ?>"
                                        class="btn-action btn-visits"
                                        title="View Visits"
                                        style="
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #EDE9FE;
        color: #7C3AED;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
   ">
                                        <i class="fas fa-route" style="font-size: 13px;"></i>
                                    </a>

                                    <!-- View Leads -->
                                    <a href="staff-leads.php?id=<?php echo $staff['id']; ?>"
                                        class="btn-action btn-leads"
                                        title="View Leads"
                                        style="
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #FEF3C7;
        color: #D97706;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
   ">
                                        <i class="fas fa-bullhorn" style="font-size: 13px;"></i>
                                    </a>
                                    <!-- Permissions Button -->
                                    <a href="staff-permissions.php?id=<?php echo $staff['id']; ?>"
                                        class="btn-action btn-permissions"
                                        title="Manage Permissions"
                                        style="
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 8px;
                                    border: none;
                                    background: #EDE9FE;
                                    color: #7C3AED;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    text-decoration: none;
                                    transition: all 0.3s ease;
                                    cursor: pointer;
                               ">
                                        <i class="fas fa-lock" style="font-size: 13px;"></i>
                                    </a>

                                    <!-- Toggle Status Button -->
                                    <button onclick="toggleStaffStatus(<?php echo $staff['id']; ?>, '<?php echo $staff['status']; ?>', '<?php echo addslashes($staff['full_name']); ?>')"
                                        class="btn-action btn-toggle"
                                        title="<?php echo $staff['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"
                                        style="
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 8px;
                                    border: none;
                                    background: <?php echo $staff['status'] === 'active' ? '#FEF3C7' : '#DCFCE7'; ?>;
                                    color: <?php echo $staff['status'] === 'active' ? '#D97706' : '#16A34A'; ?>;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    text-decoration: none;
                                    transition: all 0.3s ease;
                                    cursor: pointer;
                               ">
                                        <i class="fas fa-<?php echo $staff['status'] === 'active' ? 'pause' : 'play'; ?>" style="font-size: 13px;"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <?php if ($staff['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo addslashes($staff['full_name']); ?>')"
                                            class="btn-action btn-delete"
                                            title="Delete Staff"
                                            style="
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 8px;
                                    border: none;
                                    background: #FEE2E2;
                                    color: #DC2626;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    text-decoration: none;
                                    transition: all 0.3s ease;
                                    cursor: pointer;
                               ">
                                            <i class="fas fa-trash" style="font-size: 13px;"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px;">
            <?php echo $pagination; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // CSRF Token for AJAX requests
    const csrfToken = '<?php echo $csrfToken; ?>';

    /**
     * Toggle Staff Status with SweetAlert
     */
    function toggleStaffStatus(staffId, currentStatus, staffName) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const actionText = newStatus === 'active' ? 'activate' : 'deactivate';
        const emoji = newStatus === 'active' ? '✅' : '⛔';
        const color = newStatus === 'active' ? '#16A34A' : '#D97706';

        Swal.fire({
            title: `${emoji} ${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Staff Member?`,
            html: `
            <p style="font-size: 16px; margin-bottom: 10px;">
                Are you sure you want to <strong>${actionText}</strong> this staff member?
            </p>
            <div style="background: #F7FCF7; padding: 12px; border-radius: 8px; margin-top: 10px;">
                <strong style="color: #052E16;">${escapeHtml(staffName)}</strong>
            </div>
            <p style="font-size: 14px; color: #6B7A7B; margin-top: 12px;">
                This will ${actionText} their account access.
            </p>
        `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#6B7A7B',
            confirmButtonText: `Yes, ${actionText} it!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to toggle action
                window.location.href = `staff.php?action=toggle&id=${staffId}&csrf=${csrfToken}`;
            }
        });
    }

    /**
     * Delete Staff with SweetAlert
     */
    function deleteStaff(staffId, staffName) {
        Swal.fire({
            title: '🗑️ Delete Staff Member?',
            html: `
            <p style="font-size: 16px; margin-bottom: 10px; color: #DC2626;">
                <strong>Warning:</strong> This action cannot be undone!
            </p>
            <p style="font-size: 16px; margin-bottom: 10px;">
                Are you sure you want to delete this staff member?
            </p>
            <div style="background: #FEE2E2; padding: 12px; border-radius: 8px; margin-top: 10px; border: 1px solid #FECACA;">
                <strong style="color: #991B1B;">${escapeHtml(staffName)}</strong>
            </div>
            <p style="font-size: 14px; color: #6B7A7B; margin-top: 12px;">
                This will permanently suspend their account.
            </p>
        `,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7A7B',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to delete action
                window.location.href = `staff.php?action=delete&id=${staffId}&csrf=${csrfToken}`;
            }
        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-close flash messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        if (alert.parentElement) {
                            alert.remove();
                        }
                    }, 500);
                }
            }, 5000);
        });
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>