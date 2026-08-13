<?php
/**
 * SAMRIDHI AGRO - Staff Profile
 * 
 * This page allows staff members to view and update their profile,
 * change password, and view their details.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'My Profile';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login
requireLogin();
requireRole('staff');

// Get database instance
$db = getDB();

// Get staff data
$sql = "SELECT u.*, sp.department, sp.designation, sp.joining_date 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ?";
$staff = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Initialize variables
$errors = [];
$success = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('staff/profile.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    // Update Profile
    if ($action === 'update_profile') {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        $hasErrors = false;
        
        if (empty($fullName)) {
            $errors['full_name'] = 'Full name is required';
            $hasErrors = true;
        } elseif (strlen($fullName) < 3) {
            $errors['full_name'] = 'Full name must be at least 3 characters';
            $hasErrors = true;
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email address is required';
            $hasErrors = true;
        } elseif (!isValidEmail($email)) {
            $errors['email'] = 'Please enter a valid email address';
            $hasErrors = true;
        } else {
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $existing = $db->fetchOne($sql, [$email, $_SESSION['user_id']]);
            if ($existing) {
                $errors['email'] = 'Email already exists. Please use another.';
                $hasErrors = true;
            }
        }
        
        if (!empty($phone) && !isValidPhone($phone)) {
            $errors['phone'] = 'Please enter a valid 10-digit phone number';
            $hasErrors = true;
        }
        
        if (!$hasErrors) {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$fullName, $email, $phone, $_SESSION['user_id']]);
            
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            
            logActivity('update', $_SESSION['user_id'], 'profile', 'Updated staff profile');
            
            setFlashMessage('success', 'Profile updated successfully!');
            redirect('staff/profile.php');
            exit;
        }
    }
    
    // Change Password
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $hasErrors = false;
        
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Current password is required';
            $hasErrors = true;
        } else {
            $sql = "SELECT password_hash FROM users WHERE id = ?";
            $userData = $db->fetchOne($sql, [$_SESSION['user_id']]);
            if (!password_verify($currentPassword, $userData['password_hash'])) {
                $errors['current_password'] = 'Current password is incorrect';
                $hasErrors = true;
            }
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = 'New password is required';
            $hasErrors = true;
        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $errors['new_password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
            $hasErrors = true;
        } else {
            $validation = validatePassword($newPassword);
            if (!$validation['valid']) {
                $errors['new_password'] = implode(' ', $validation['errors']);
                $hasErrors = true;
            }
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
            $hasErrors = true;
        }
        
        if (!$hasErrors) {
            $hashedPassword = hashPassword($newPassword);
            $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$hashedPassword, $_SESSION['user_id']]);
            
            logActivity('update', $_SESSION['user_id'], 'profile', 'Changed staff password');
            
            setFlashMessage('success', 'Password changed successfully!');
            redirect('staff/profile.php');
            exit;
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<style>
    .profile-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
    }
    
    .profile-sidebar {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #14532D, #16A34A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 700;
        color: white;
        margin: 0 auto 12px;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }
    
    .profile-sidebar .profile-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #052E16;
    }
    
    .profile-sidebar .profile-role {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #6B7A7B;
    }
    
    .profile-sidebar .profile-meta {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #E5EDE7;
        text-align: left;
    }
    
    .profile-sidebar .profile-meta .meta-item {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
    }
    
    .profile-sidebar .profile-meta .meta-item .label {
        color: #6B7A7B;
    }
    
    .profile-sidebar .profile-meta .meta-item .value {
        color: #052E16;
        font-weight: 500;
    }
    
    .profile-content {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    
    .profile-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 24px;
    }
    
    .profile-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #F0FDF4;
    }
    
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
    
    .form-error {
        color: #DC2626;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        margin-top: 4px;
    }
    
    .btn-primary {
        padding: 10px 28px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    @media (max-width: 1024px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
   
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($staff['full_name'] ?? 'S', 0, 2)); ?>
        </div>
        <div class="profile-name"><?php echo escapeHtml($staff['full_name'] ?? 'Staff'); ?></div>
        <div class="profile-role">
            <i class="fas fa-users" style="color: #16A34A;"></i> 
            <?php echo escapeHtml($staff['designation'] ?? 'Staff Member'); ?>
        </div>
        
        <div class="profile-meta">
            <div class="meta-item">
                <span class="label">Department</span>
                <span class="value"><?php echo escapeHtml($staff['department'] ?? 'N/A'); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Designation</span>
                <span class="value"><?php echo escapeHtml($staff['designation'] ?? 'N/A'); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Joined</span>
                <span class="value"><?php echo $staff['joining_date'] ? formatDate($staff['joining_date']) : 'N/A'; ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Username</span>
                <span class="value"><?php echo escapeHtml($staff['username'] ?? ''); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Email</span>
                <span class="value"><?php echo escapeHtml($staff['email'] ?? ''); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Phone</span>
                <span class="value"><?php echo !empty($staff['phone']) ? escapeHtml($staff['phone']) : 'Not provided'; ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Last Login</span>
                <span class="value">
                    <?php if (!empty($staff['last_login'])): ?>
                        <?php echo timeAgo($staff['last_login']); ?>
                    <?php else: ?>
                        Never
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="profile-content">
        <!-- Update Profile -->
        <div class="profile-card">
            <div class="card-title">
                <i class="fas fa-user-edit" style="color: #16A34A;"></i>
                Update Profile
            </div>
            
            <?php if (!empty($errors)): ?>
            <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                <p style="color: #991B1B; font-weight: 600; margin-bottom: 4px;">
                    <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
                </p>
                <ul style="margin: 0; padding-left: 20px; color: #991B1B;">
                    <?php foreach ($errors as $field => $error): ?>
                        <li><?php echo escapeHtml($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name <span style="color: #DC2626;">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-input <?php echo isset($errors['full_name']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($staff['full_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['full_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span style="color: #DC2626;">*</span></label>
                        <input type="email" id="email" name="email" class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($staff['email'] ?? ''); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input <?php echo isset($errors['phone']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($staff['phone'] ?? ''); ?>" placeholder="Enter 10-digit phone number">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['phone']); ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="profile-card">
            <div class="card-title">
                <i class="fas fa-lock" style="color: #16A34A;"></i>
                Change Password
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password <span style="color: #DC2626;">*</span></label>
                    <input type="password" id="current_password" name="current_password" class="form-input <?php echo isset($errors['current_password']) ? 'error' : ''; ?>" placeholder="Enter current password" required>
                    <?php if (isset($errors['current_password'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['current_password']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password <span style="color: #DC2626;">*</span></label>
                        <input type="password" id="new_password" name="new_password" class="form-input <?php echo isset($errors['new_password']) ? 'error' : ''; ?>" placeholder="Enter new password" required>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['new_password']); ?></div>
                        <?php endif; ?>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> 
                            Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters, with uppercase, number and special character
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password <span style="color: #DC2626;">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" placeholder="Confirm new password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['confirm_password']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>