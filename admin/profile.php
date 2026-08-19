<?php
/**
 * SAMRIDHI AGRO - Admin Profile
 *
 * This page allows administrators to view and update their profile,
 * change password, and manage account settings.
 */

$pageTitle = 'My Profile';
require_once '../includes/admin_header.php';

// ============================================
// STEP 2: Start secure session
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// STEP 3: Authentication & authorization
// ============================================

requireLogin();
requireRole('admin');

// ============================================
// STEP 4: Database & user data
// ============================================

$db = getDB();

$user = getCurrentUser();

// Get complete user data with avatar
$sql = "SELECT * FROM users WHERE id = ?";
$userData = $db->fetchOne($sql, [$_SESSION['user_id']]);

$errors = [];
$success = [];

// ============================================
// STEP 5: HANDLE POST REQUEST
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (
        !isset($_POST[CSRF_TOKEN_NAME]) ||
        !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])
    ) {
        setFlashMessage(
            'error',
            'Invalid security token. Please try again.'
        );

        redirect('admin/profile.php');
    }

    $action = $_POST['action'] ?? '';

    // ========================================
    // UPDATE PROFILE
    // ========================================

    if ($action === 'update_profile') {

        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email    = sanitizeInput($_POST['email'] ?? '');
        $phone    = sanitizeInput($_POST['phone'] ?? '');

        $hasErrors = false;

        if (empty($fullName)) {
            $errors['full_name'] = 'Full name is required';
            $hasErrors = true;

        } elseif (strlen($fullName) < 3) {
            $errors['full_name'] =
                'Full name must be at least 3 characters';

            $hasErrors = true;
        }

        if (empty($email)) {

            $errors['email'] = 'Email address is required';
            $hasErrors = true;

        } elseif (!isValidEmail($email)) {

            $errors['email'] =
                'Please enter a valid email address';

            $hasErrors = true;

        } else {

            $sql = "
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
            ";

            $existing = $db->fetchOne(
                $sql,
                [$email, $_SESSION['user_id']]
            );

            if ($existing) {
                $errors['email'] =
                    'Email already exists. Please use another.';

                $hasErrors = true;
            }
        }

        if (!empty($phone) && !isValidPhone($phone)) {

            $errors['phone'] =
                'Please enter a valid 10-digit phone number';

            $hasErrors = true;
        }

        // ============================================
        // HANDLE AVATAR UPLOAD - uploads/avatars/
        // ============================================
        $avatarFileName = null;
        $avatarUploaded = false;

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Check if uploads/avatars directory exists
            $uploadDir = '../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Upload and compress image
            $uploadResult = uploadAndCompressImage(
                $_FILES['avatar'],
                $uploadDir,
                70,     // Quality (70%)
                500,    // Max width
                500,    // Max height
                false,  // No thumbnail needed
                0,
                0
            );

            if ($uploadResult['success']) {
                $avatarFileName = $uploadResult['filename'];
                $avatarUploaded = true;

                // Delete old avatar if exists
                if (!empty($userData['avatar']) && file_exists($uploadDir . $userData['avatar'])) {
                    @unlink($uploadDir . $userData['avatar']);
                }
            } else {
                $errors['avatar'] = $uploadResult['message'];
                $hasErrors = true;
            }
        }

        if (!$hasErrors) {

            // Build update query dynamically
            $updateFields = [];
            $updateParams = [];

            $updateFields[] = "full_name = ?";
            $updateParams[] = $fullName;

            $updateFields[] = "email = ?";
            $updateParams[] = $email;

            $updateFields[] = "phone = ?";
            $updateParams[] = $phone;

            // Add avatar if uploaded
            if ($avatarUploaded && $avatarFileName !== null) {
                $updateFields[] = "avatar = ?";
                $updateParams[] = $avatarFileName;
            }

            $updateFields[] = "updated_at = NOW()";

            // Add user_id at the end
            $updateParams[] = $_SESSION['user_id'];

            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $db->query($sql, $updateParams);

            $_SESSION['user_name']  = $fullName;
            $_SESSION['user_email'] = $email;

            logActivity(
                'update',
                $_SESSION['user_id'],
                'profile',
                'Updated profile information'
            );

            setFlashMessage(
                'success',
                'Profile updated successfully!'
            );

            redirect('admin/profile.php');
        }
    }

    // ========================================
    // CHANGE PASSWORD
    // ========================================

    if ($action === 'change_password') {

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $hasErrors = false;

        // Verify current password
        if (empty($currentPassword)) {

            $errors['current_password'] =
                'Current password is required';

            $hasErrors = true;

        } else {

            $sql = "
                SELECT password_hash
                FROM users
                WHERE id = ?
            ";

            $userData = $db->fetchOne(
                $sql,
                [$_SESSION['user_id']]
            );

            if (
                !$userData ||
                !password_verify(
                    $currentPassword,
                    $userData['password_hash']
                )
            ) {
                $errors['current_password'] =
                    'Current password is incorrect';

                $hasErrors = true;
            }
        }

        if (empty($newPassword)) {

            $errors['new_password'] =
                'New password is required';

            $hasErrors = true;

        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {

            $errors['new_password'] =
                'Password must be at least ' .
                PASSWORD_MIN_LENGTH .
                ' characters';

            $hasErrors = true;

        } else {

            $validation = validatePassword($newPassword);

            if (!$validation['valid']) {

                $errors['new_password'] =
                    implode(' ', $validation['errors']);

                $hasErrors = true;
            }
        }

        if ($newPassword !== $confirmPassword) {

            $errors['confirm_password'] =
                'Passwords do not match';

            $hasErrors = true;
        }

        if (!$hasErrors) {

            $hashedPassword = hashPassword($newPassword);

            $sql = "
                UPDATE users
                SET
                    password_hash = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";

            $db->query(
                $sql,
                [
                    $hashedPassword,
                    $_SESSION['user_id']
                ]
            );

            logActivity(
                'update',
                $_SESSION['user_id'],
                'profile',
                'Changed password'
            );

            setFlashMessage(
                'success',
                'Password changed successfully!'
            );

            redirect('admin/profile.php');
        }
    }
}

// ============================================
// STEP 6: Generate CSRF token
// ============================================

$csrfToken = generateCsrfToken();

// ============================================
// STEP 7: ONLY NOW include admin header
// ============================================

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
        box-shadow: 0 2px 6px rgba(5, 46, 22, 0.06);
    }
    
    .profile-avatar-wrapper {
        width: 120px;
        height: 120px;
        margin: 0 auto 16px;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #14532D, #16A34A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: 700;
        color: white;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        overflow: hidden;
        object-fit: cover;
        border: 3px solid #16A34A;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-avatar .avatar-text {
        font-size: 48px;
        font-weight: 700;
        color: white;
    }
    
    .profile-sidebar .profile-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
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
        padding: 6px 0;
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
        box-shadow: 0 2px 6px rgba(5, 46, 22, 0.06);
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
    
    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
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
    
    .btn-secondary {
        padding: 10px 24px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: #E5E7EB;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    /* Avatar preview in form */
    .avatar-preview-wrap {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .avatar-preview-wrap .preview-box {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 2px dashed #E5EDE7;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #F7FCF7;
        flex-shrink: 0;
    }
    
    .avatar-preview-wrap .preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .avatar-preview-wrap .preview-box .placeholder-icon {
        font-size: 28px;
        color: #6B7A7B;
    }
    
    @media (max-width: 1024px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
        
        .profile-sidebar {
            max-width: 400px;
            margin: 0 auto;
        }
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .profile-avatar-wrapper {
            width: 100px;
            height: 100px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            font-size: 36px;
        }
        .profile-avatar .avatar-text {
            font-size: 36px;
        }
    }
    
    @media (max-width: 480px) {
        .profile-sidebar {
            padding: 16px;
        }
        .profile-card {
            padding: 16px;
        }
        .avatar-preview-wrap {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar">
                <?php if (!empty($userData['avatar']) && file_exists('../uploads/avatars/' . $userData['avatar'])): ?>
                    <img src="../uploads/avatars/<?php echo escapeHtml($userData['avatar']); ?>" alt="<?php echo escapeHtml($userData['full_name'] ?? 'Admin'); ?>">
                <?php else: ?>
                    <span class="avatar-text"><?php echo strtoupper(substr($userData['full_name'] ?? 'A', 0, 2)); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-name"><?php echo escapeHtml($userData['full_name'] ?? 'Admin'); ?></div>
        <div class="profile-role">Administrator</div>
        
        <div class="profile-meta">
            <div class="meta-item">
                <span class="label">Username</span>
                <span class="value"><?php echo escapeHtml($userData['username'] ?? ''); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Email</span>
                <span class="value"><?php echo escapeHtml($userData['email'] ?? ''); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Phone</span>
                <span class="value"><?php echo !empty($userData['phone']) ? escapeHtml($userData['phone']) : 'Not provided'; ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Joined</span>
                <span class="value"><?php echo formatDate($userData['created_at'] ?? date('Y-m-d')); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Last Login</span>
                <span class="value">
                    <?php if (!empty($userData['last_login'])): ?>
                        <?php echo timeAgo($userData['last_login']); ?>
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
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <!-- Avatar Upload -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-image" style="color: #16A34A;"></i>
                        Profile Photo
                    </label>
                    <div class="avatar-preview-wrap">
                        <div class="preview-box" id="avatarPreview">
                            <?php if (!empty($userData['avatar']) && file_exists('../uploads/avatars/' . $userData['avatar'])): ?>
                                <img src="../uploads/avatars/<?php echo escapeHtml($userData['avatar']); ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user placeholder-icon"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="avatarInputForm" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatarForm(this)">
                            <button type="button" class="btn-secondary" onclick="document.getElementById('avatarInputForm').click()">
                                <i class="fas fa-upload"></i> Choose Image
                            </button>
                            <div class="form-hint" style="margin-top: 4px;">
                                <i class="fas fa-info-circle"></i> 
                                Allowed: JPG, PNG, GIF, WebP (Max 5MB) 
                            </div>
                            <?php if (isset($errors['avatar'])): ?>
                                <div class="form-error"><?php echo escapeHtml($errors['avatar']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name <span style="color: #DC2626;">*</span></label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            class="form-input <?php echo isset($errors['full_name']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($userData['full_name'] ?? ''); ?>"
                            required
                        >
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['full_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span style="color: #DC2626;">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($userData['email'] ?? ''); ?>"
                            required
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-input <?php echo isset($errors['phone']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($userData['phone'] ?? ''); ?>"
                        placeholder="Enter 10-digit phone number"
                    >
                    <?php if (isset($errors['phone'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['phone']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Optional - Used for contact purposes
                    </div>
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
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-input <?php echo isset($errors['current_password']) ? 'error' : ''; ?>"
                        placeholder="Enter current password"
                        required
                    >
                    <?php if (isset($errors['current_password'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['current_password']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password <span style="color: #DC2626;">*</span></label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-input <?php echo isset($errors['new_password']) ? 'error' : ''; ?>"
                            placeholder="Enter new password"
                            required
                        >
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
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>"
                            placeholder="Confirm new password"
                            required
                        >
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

<script>
// Preview avatar from form upload
function previewAvatarForm(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewBox = document.getElementById('avatarPreview');
            previewBox.innerHTML = '<img src="' + e.target.result + '" alt="Avatar Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>