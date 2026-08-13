<?php
/**
 * SAMRIDHI AGRO - Edit Staff
 * 
 * This page allows administrators to update existing staff member details.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: All PHP logic FIRST (no HTML output)
// ============================================

// Set page title
$pageTitle = 'Edit Staff';

// Include configuration files (these don't output HTML)
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Require admin login and permission (BEFORE any HTML output)
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
    redirect('staff.php');
    exit; // Always exit after redirect
}

// Get staff data
$sql = "SELECT u.*, sp.department, sp.designation, sp.joining_date 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ? AND u.role = 'staff'";
$staff = $db->fetchOne($sql, [$staffId]);

// If staff not found, redirect
if (!$staff) {
    setFlashMessage('error', 'Staff member not found.');
    redirect('staff.php');
    exit;
}

// Cannot edit self through this page
if ($staffId == $_SESSION['user_id']) {
    setFlashMessage('error', 'You cannot edit your own account through this page.');
    redirect('staff.php');
    exit;
}

// Initialize variables
$errors = [];
$formData = [
    'full_name' => $staff['full_name'],
    'username' => $staff['username'],
    'email' => $staff['email'],
    'phone' => $staff['phone'] ?? '',
    'department' => $staff['department'] ?? '',
    'designation' => $staff['designation'] ?? '',
    'status' => $staff['status']
];

// Get departments for dropdown
$departments = [
    'Management',
    'Sales',
    'Operations',
    'Logistics',
    'Procurement',
    'Quality Control',
    'Customer Service',
    'Finance',
    'Human Resources',
    'IT'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('staff-edit.php?id=' . $staffId);
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'department' => sanitizeInput($_POST['department'] ?? ''),
        'designation' => sanitizeInput($_POST['designation'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? 'active')
    ];
    
    $change_password = isset($_POST['change_password']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $hasErrors = false;
    
    // Full name - required
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required';
        $hasErrors = true;
    } elseif (strlen($formData['full_name']) < 3) {
        $errors['full_name'] = 'Full name must be at least 3 characters';
        $hasErrors = true;
    }
    
    // Username - required, unique (except current user)
    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required';
        $hasErrors = true;
    } elseif (strlen($formData['username']) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
        $hasErrors = true;
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
        $errors['username'] = 'Username can only contain letters, numbers and underscore';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['username'], $staffId]);
        if ($existing) {
            $errors['username'] = 'Username already exists. Please choose another.';
            $hasErrors = true;
        }
    }
    
    // Email - required, valid, unique (except current user)
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required';
        $hasErrors = true;
    } elseif (!isValidEmail($formData['email'])) {
        $errors['email'] = 'Please enter a valid email address';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['email'], $staffId]);
        if ($existing) {
            $errors['email'] = 'Email already exists. Please use another.';
            $hasErrors = true;
        }
    }
    
    // Phone - optional, valid if provided
    if (!empty($formData['phone']) && !isValidPhone($formData['phone'])) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
        $hasErrors = true;
    }
    
    // Password - required if change password is checked
    if ($change_password) {
        if (empty($password)) {
            $errors['password'] = 'Password is required';
            $hasErrors = true;
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
            $hasErrors = true;
        } elseif ($password !== $confirm_password) {
            $errors['password'] = 'Passwords do not match';
            $hasErrors = true;
        } else {
            $validation = validatePassword($password);
            if (!$validation['valid']) {
                $errors['password'] = implode(' ', $validation['errors']);
                $hasErrors = true;
            }
        }
    }
    
    // Department - required
    if (empty($formData['department'])) {
        $errors['department'] = 'Department is required';
        $hasErrors = true;
    }
    
    // If no errors, update staff
    if (!$hasErrors) {
        try {
            $db->beginTransaction();
            
            $sql = "UPDATE users SET 
                    username = ?,
                    email = ?,
                    full_name = ?,
                    phone = ?,
                    status = ?
                    WHERE id = ?";
            
            $params = [
                $formData['username'],
                $formData['email'],
                $formData['full_name'],
                $formData['phone'],
                $formData['status'],
                $staffId
            ];
            
            if ($change_password && !empty($password)) {
                $hashedPassword = hashPassword($password);
                $sql = "UPDATE users SET 
                        username = ?,
                        email = ?,
                        full_name = ?,
                        phone = ?,
                        status = ?,
                        password_hash = ?
                        WHERE id = ?";
                $params = [
                    $formData['username'],
                    $formData['email'],
                    $formData['full_name'],
                    $formData['phone'],
                    $formData['status'],
                    $hashedPassword,
                    $staffId
                ];
            }
            
            $db->query($sql, $params);
            
            $sql = "UPDATE staff_profiles SET 
                    department = ?,
                    designation = ?
                    WHERE user_id = ?";
            $db->query($sql, [
                $formData['department'],
                $formData['designation'],
                $staffId
            ]);
            
            $db->commit();
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'staff',
                'Updated staff: ' . $formData['full_name'] . ' (' . $formData['username'] . ')'
            );
            
            setFlashMessage('success', 'Staff member updated successfully!');
            redirect('staff.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Staff update error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to update staff member. Please try again.');
            redirect('staff-edit.php?id=' . $staffId);
            exit;
        }
    }
}

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-edit" style="color: #16A34A;"></i>
            Edit Staff Member
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $staff['id']; ?> - <?php echo escapeHtml($staff['full_name']); ?>
            </span>
        </h3>
        <a href="staff.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Staff List
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <p style="color: #991B1B; font-weight: 600; margin-bottom: 8px;">
            <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
        </p>
        <ul style="margin: 0; padding-left: 20px; color: #991B1B;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo escapeHtml($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="staffForm" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Left Column -->
            <div>
                <!-- Full Name -->
                <div class="form-group">
                    <label class="form-label" for="full_name">
                        <i class="fas fa-user" style="color: #16A34A;"></i>
                        Full Name <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input <?php echo isset($errors['full_name']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['full_name']); ?>"
                        placeholder="Enter full name"
                        required
                    >
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['full_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Username -->
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user-tag" style="color: #16A34A;"></i>
                        Username <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input <?php echo isset($errors['username']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['username']); ?>"
                        placeholder="Enter username (letters, numbers, underscore)"
                        required
                    >
                    <?php if (isset($errors['username'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['username']); ?></div>
                    <?php endif; ?>
                    <div style="font-size: 12px; color: #6B7A7B; margin-top: 4px;">
                        <i class="fas fa-info-circle"></i> 3-50 characters, letters, numbers and underscore only
                    </div>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fas fa-envelope" style="color: #16A34A;"></i>
                        Email Address <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['email']); ?>"
                        placeholder="Enter email address"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['email']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <label class="form-label" for="phone">
                        <i class="fas fa-phone" style="color: #16A34A;"></i>
                        Phone Number
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-input <?php echo isset($errors['phone']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['phone']); ?>"
                        placeholder="Enter 10-digit phone number"
                    >
                    <?php if (isset($errors['phone'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['phone']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Joining Date (Read Only) -->
                <div class="form-group">
                    <label class="form-label" for="joining_date">
                        <i class="fas fa-calendar-plus" style="color: #16A34A;"></i>
                        Joining Date
                    </label>
                    <input 
                        type="text" 
                        id="joining_date" 
                        class="form-input"
                        value="<?php echo $staff['joining_date'] ? formatDate($staff['joining_date']) : 'N/A'; ?>"
                        disabled
                        style="background: #F3F4F6; cursor: not-allowed;"
                    >
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Department -->
                <div class="form-group">
                    <label class="form-label" for="department">
                        <i class="fas fa-building" style="color: #16A34A;"></i>
                        Department <span style="color: #DC2626;">*</span>
                    </label>
                    <select 
                        id="department" 
                        name="department" 
                        class="form-input <?php echo isset($errors['department']) ? 'error' : ''; ?>"
                        required
                    >
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo escapeHtml($dept); ?>" 
                                <?php echo $formData['department'] === $dept ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['department'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['department']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Designation -->
                <div class="form-group">
                    <label class="form-label" for="designation">
                        <i class="fas fa-briefcase" style="color: #16A34A;"></i>
                        Designation
                    </label>
                    <input 
                        type="text" 
                        id="designation" 
                        name="designation" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['designation']); ?>"
                        placeholder="Enter job title (e.g., Manager, Executive)"
                    >
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label" for="status">
                        <i class="fas fa-toggle-on" style="color: #16A34A;"></i>
                        Status
                    </label>
                    <select id="status" name="status" class="form-input">
                        <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $formData['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <!-- Password Change Section -->
                <div style="background: #F7FCF7; padding: 16px; border-radius: 12px; margin-top: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <label class="checkbox-group" style="margin: 0;">
                            <input type="checkbox" name="change_password" id="change_password" value="1">
                            <span>Change Password</span>
                        </label>
                    </div>
                    
                    <div id="passwordSection" style="display: none;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="password">New Password <span style="color: #DC2626;">*</span></label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                                placeholder="Enter new password"
                            >
                            <?php if (isset($errors['password'])): ?>
                                <div class="form-error"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input"
                                placeholder="Confirm new password"
                            >
                        </div>
                    </div>
                    
                    <div id="passwordInfo" style="font-size: 13px; color: #6B7A7B; margin-top: 8px;">
                        <i class="fas fa-info-circle" style="color: #16A34A;"></i>
                        Check the box above to change the staff member's password.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" style="
                padding: 12px 32px;
                background: linear-gradient(135deg, #14532D, #16A34A);
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            ">
                <i class="fas fa-save"></i> Update Staff
            </button>
            
            <a href="staff.php" style="
                padding: 12px 24px;
                background: #F3F4F6;
                color: #4A5B5D;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 15px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            ">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-label {
        display: block;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        color: #14532D;
        margin-bottom: 6px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 14px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        background: white;
        transition: all 0.3s ease;
        color: #052E16;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }
    
    .form-input.error {
        border-color: #DC2626;
        background: rgba(220, 38, 38, 0.05);
    }
    
    .form-input:disabled {
        background: #F3F4F6;
        cursor: not-allowed;
    }
    
    .form-error {
        color: #DC2626;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        margin-top: 4px;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #16A34A;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }
    
    @media (max-width: 768px) {
        form > div:first-child {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password section
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordSection = document.getElementById('passwordSection');
    const passwordInfo = document.getElementById('passwordInfo');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    changePasswordCheckbox.addEventListener('change', function() {
        if (this.checked) {
            passwordSection.style.display = 'block';
            passwordInfo.style.display = 'none';
            passwordInput.disabled = false;
            confirmPasswordInput.disabled = false;
            passwordInput.required = true;
        } else {
            passwordSection.style.display = 'none';
            passwordInfo.style.display = 'block';
            passwordInput.disabled = true;
            confirmPasswordInput.disabled = true;
            passwordInput.required = false;
            passwordInput.value = '';
            confirmPasswordInput.value = '';
        }
    });
    
    // Trigger change event on load (password section hidden by default)
    changePasswordCheckbox.dispatchEvent(new Event('change'));
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>