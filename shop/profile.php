<?php
/**
 * SAMRIDHI AGRO - Shop Profile
 * 
 * This page allows shop owners to view and update their profile,
 * shop details, and change password.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'My Profile';

// Include shop header
require_once __DIR__ . '/../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get shop data with user details
$sql = "SELECT s.*, u.full_name, u.username, u.email, u.phone, u.created_at, u.last_login,
        u.status as user_status, u.avatar,
        a.full_name as agent_name
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN agents ag ON s.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        WHERE s.user_id = ?";
$shop = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Initialize variables
$errors = [];
$success = [];

// ============================================
// GET LOCATION FROM DATABASE
// ============================================
$shopLatitude = $shop['latitude'] ?? null;
$shopLongitude = $shop['longitude'] ?? null;
$locationSet = ($shopLatitude !== null && $shopLongitude !== null);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('shop/profile.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    // ============================================
    // UPDATE PROFILE
    // ============================================
    if ($action === 'update_profile') {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $shopName = sanitizeInput($_POST['shop_name'] ?? '');
        $shopType = sanitizeInput($_POST['shop_type'] ?? 'retail');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $pincode = sanitizeInput($_POST['pincode'] ?? '');
        $gstNumber = sanitizeInput($_POST['gst_number'] ?? '');
        $establishmentYear = (int)($_POST['establishment_year'] ?? 0);
        $shopCategory = sanitizeInput($_POST['shop_category'] ?? 'grocery');
        $deliveryAvailable = isset($_POST['delivery_available']) ? 1 : 0;
        $workingHoursStart = sanitizeInput($_POST['working_hours_start'] ?? '');
        $workingHoursEnd = sanitizeInput($_POST['working_hours_end'] ?? '');
        
        // Get weekend days from checkboxes
        $weekendDays = isset($_POST['weekend_days']) ? implode(',', $_POST['weekend_days']) : '';
        
        // ============================================
        // GET LOCATION FROM FORM
        // ============================================
        $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
        
        // Validation
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
        
        if (empty($shopName)) {
            $errors['shop_name'] = 'Shop name is required';
            $hasErrors = true;
        } elseif (strlen($shopName) < 3) {
            $errors['shop_name'] = 'Shop name must be at least 3 characters';
            $hasErrors = true;
        }
        
        if (empty($shopType) || !in_array($shopType, ['retail', 'wholesale', 'both'])) {
            $errors['shop_type'] = 'Invalid shop type';
            $hasErrors = true;
        }
        
        if (!empty($pincode) && !isValidPincode($pincode)) {
            $errors['pincode'] = 'Please enter a valid 6-digit pincode';
            $hasErrors = true;
        }
        
        if (!empty($gstNumber) && !isValidGST($gstNumber)) {
            $errors['gst_number'] = 'Please enter a valid GST number';
            $hasErrors = true;
        }
        
        if ($establishmentYear > 0 && ($establishmentYear < 1900 || $establishmentYear > date('Y'))) {
            $errors['establishment_year'] = 'Please enter a valid establishment year';
            $hasErrors = true;
        }
        
        if (!empty($workingHoursStart) && !empty($workingHoursEnd) && $workingHoursStart >= $workingHoursEnd) {
            $errors['working_hours'] = 'Closing time must be after opening time';
            $hasErrors = true;
        }
        
        // Validate location - if both fields are provided, they should be valid numbers
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            $errors['latitude'] = 'Latitude must be between -90 and 90';
            $hasErrors = true;
        }
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            $errors['longitude'] = 'Longitude must be between -180 and 180';
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
                if (!empty($shop['avatar']) && file_exists($uploadDir . $shop['avatar'])) {
                    @unlink($uploadDir . $shop['avatar']);
                }
            } else {
                $errors['avatar'] = $uploadResult['message'];
                $hasErrors = true;
            }
        }
        
        if (!$hasErrors) {
            // Build update query dynamically for users table
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
            
            // Update shop with location
            $sql = "UPDATE shops SET 
                    shop_name = ?,
                    shop_type = ?,
                    address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?,
                    gst_number = ?,
                    establishment_year = ?,
                    shop_category = ?,
                    delivery_available = ?,
                    working_hours_start = ?,
                    working_hours_end = ?,
                    weekend_days = ?,
                    latitude = ?,
                    longitude = ?,
                    updated_at = NOW()
                    WHERE user_id = ?";
            $db->query($sql, [
                $shopName,
                $shopType,
                $address,
                $city,
                $state,
                $pincode,
                $gstNumber,
                $establishmentYear > 0 ? $establishmentYear : null,
                $shopCategory,
                $deliveryAvailable,
                $workingHoursStart ?: null,
                $workingHoursEnd ?: null,
                $weekendDays,
                $latitude,
                $longitude,
                $_SESSION['user_id']
            ]);
            
            // Update session
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            
            logActivity('update', $_SESSION['user_id'], 'profile', 'Updated shop profile with location');
            
            setFlashMessage('success', 'Profile updated successfully!');
            redirect('shop/profile.php');
            exit;
        }
    }
    
    // ============================================
    // CHANGE PASSWORD
    // ============================================
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
            
            logActivity('update', $_SESSION['user_id'], 'profile', 'Changed shop password');
            
            setFlashMessage('success', 'Password changed successfully!');
            redirect('shop/profile.php');
            exit;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Weekend days options
$weekendDaysOptions = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Shop categories
$shopCategories = [
    'grocery' => 'Grocery Store',
    'produce' => 'Fresh Produce',
    'organic' => 'Organic Store',
    'wholesale' => 'Wholesale Market',
    'specialty' => 'Specialty Store',
    'supermarket' => 'Supermarket',
    'convenience' => 'Convenience Store'
];

// Parse weekend days from database
$selectedWeekendDays = [];
if (!empty($shop['weekend_days'])) {
    $selectedWeekendDays = explode(',', $shop['weekend_days']);
}
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        margin: 0 auto 12px;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #14532D, #16A34A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
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
        font-size: 42px;
        font-weight: 700;
        color: white;
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
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: #E5E7EB;
    }
    
    .btn-location {
        padding: 10px 20px;
        background: #2563EB;
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-location:hover {
        background: #1D4ED8;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    
    .btn-location:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
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
    
    /* Weekend days checkbox grid */
    .weekend-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .weekend-grid .checkbox-group {
        padding: 6px 10px;
        border: 1px solid #E5EDE7;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .weekend-grid .checkbox-group:hover {
        background: #F0FDF4;
        border-color: #16A34A;
    }
    
    .weekend-grid .checkbox-group input[type="checkbox"]:checked + span {
        color: #16A34A;
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
    
    /* Location Section */
    .location-container {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        margin-top: 8px;
    }
    
    .location-status {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 200px;
    }
    
    .location-status.success {
        background: #DCFCE7;
        color: #065F46;
        border: 1px solid #BBF7D0;
    }
    
    .location-status.error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FECACA;
    }
    
    .location-status.warning {
        background: #FEF3C7;
        color: #92400E;
        border: 1px solid #FDE68A;
    }
    
    .location-status.info {
        background: #DBEAFE;
        color: #1E40AF;
        border: 1px solid #BFDBFE;
    }
    
    .location-coords {
        font-family: monospace;
        font-weight: 600;
        color: #14532D;
        font-size: 13px;
        padding: 4px 8px;
        background: #F7FCF7;
        border-radius: 4px;
        display: inline-block;
    }
    
    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 1024px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .form-row-3 {
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
        .weekend-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .location-container {
            flex-direction: column;
            align-items: stretch;
        }
        .location-status {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .profile-sidebar {
            padding: 16px;
        }
        .profile-card {
            padding: 16px;
        }
        .weekend-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar">
                <?php if (!empty($shop['avatar']) && file_exists('../uploads/avatars/' . $shop['avatar'])): ?>
                    <img src="../uploads/avatars/<?php echo escapeHtml($shop['avatar']); ?>" alt="<?php echo escapeHtml($shop['shop_name'] ?? 'Shop'); ?>">
                <?php else: ?>
                    <span class="avatar-text"><?php echo strtoupper(substr($shop['shop_name'] ?? 'S', 0, 2)); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-name"><?php echo escapeHtml($shop['shop_name'] ?? 'Shop'); ?></div>
        <div class="profile-role">
            <i class="fas fa-store" style="color: #16A34A;"></i> 
            <?php 
            $typeLabels = [
                'retail' => 'Retail Shop',
                'wholesale' => 'Wholesale Shop',
                'both' => 'Retail & Wholesale'
            ];
            echo $typeLabels[$shop['shop_type']] ?? 'Shop';
            ?>
        </div>
        
        <div class="profile-meta">
            <div class="meta-item">
                <span class="label">Shop Code</span>
                <span class="value"><?php echo escapeHtml($shop['shop_code'] ?? ''); ?></span>
            </div>
            <?php if ($shop['agent_name']): ?>
            <div class="meta-item">
                <span class="label">Agent</span>
                <span class="value"><?php echo escapeHtml($shop['agent_name']); ?></span>
            </div>
            <?php endif; ?>
            <div class="meta-item">
                <span class="label">Status</span>
                <span class="value">
                    <?php if ($shop['status'] === 'approved'): ?>
                        <span style="color: #16A34A;">✓ Active</span>
                    <?php else: ?>
                        <span style="color: #F59E0B;">⏳ <?php echo ucfirst($shop['status']); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="meta-item">
                <span class="label">Username</span>
                <span class="value"><?php echo escapeHtml($shop['username'] ?? ''); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Email</span>
                <span class="value"><?php echo escapeHtml($shop['email'] ?? ''); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Phone</span>
                <span class="value"><?php echo !empty($shop['phone']) ? escapeHtml($shop['phone']) : 'Not provided'; ?></span>
            </div>
            <?php if ($shop['latitude'] && $shop['longitude']): ?>
            <div class="meta-item">
                <span class="label">Location</span>
                <span class="value" style="font-size: 11px; font-family: monospace;">
                    <?php echo number_format($shop['latitude'], 6); ?>, <?php echo number_format($shop['longitude'], 6); ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="meta-item">
                <span class="label">Joined</span>
                <span class="value"><?php echo formatDate($shop['created_at'] ?? date('Y-m-d')); ?></span>
            </div>
            <div class="meta-item">
                <span class="label">Last Login</span>
                <span class="value">
                    <?php if (!empty($shop['last_login'])): ?>
                        <?php echo timeAgo($shop['last_login']); ?>
                    <?php else: ?>
                        Never
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="profile-content">
        <!-- Update Shop Profile -->
        <div class="profile-card">
            <div class="card-title">
                <i class="fas fa-store-edit" style="color: #16A34A;"></i>
                Shop Information
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
            
            <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="latitude" id="latitude" value="<?php echo $shop['latitude'] ?? ''; ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?php echo $shop['longitude'] ?? ''; ?>">
                
                <!-- Avatar Upload -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-image" style="color: #16A34A;"></i>
                        Profile Photo
                    </label>
                    <div class="avatar-preview-wrap">
                        <div class="preview-box" id="avatarPreview">
                            <?php if (!empty($shop['avatar']) && file_exists('../uploads/avatars/' . $shop['avatar'])): ?>
                                <img src="../uploads/avatars/<?php echo escapeHtml($shop['avatar']); ?>" alt="Avatar">
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
                        <label class="form-label" for="shop_name">Shop Name <span style="color: #DC2626;">*</span></label>
                        <input type="text" id="shop_name" name="shop_name" class="form-input <?php echo isset($errors['shop_name']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['shop_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['shop_name'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['shop_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="shop_type">Shop Type <span style="color: #DC2626;">*</span></label>
                        <select id="shop_type" name="shop_type" class="form-input <?php echo isset($errors['shop_type']) ? 'error' : ''; ?>" required>
                            <option value="retail" <?php echo ($shop['shop_type'] ?? '') === 'retail' ? 'selected' : ''; ?>>Retail</option>
                            <option value="wholesale" <?php echo ($shop['shop_type'] ?? '') === 'wholesale' ? 'selected' : ''; ?>>Wholesale</option>
                            <option value="both" <?php echo ($shop['shop_type'] ?? '') === 'both' ? 'selected' : ''; ?>>Both</option>
                        </select>
                        <?php if (isset($errors['shop_type'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['shop_type']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="shop_category">Shop Category <span style="color: #DC2626;">*</span></label>
                        <select id="shop_category" name="shop_category" class="form-input" required>
                            <?php foreach ($shopCategories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($shop['shop_category'] ?? 'grocery') === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="establishment_year">Establishment Year</label>
                        <input type="number" id="establishment_year" name="establishment_year" class="form-input <?php echo isset($errors['establishment_year']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['establishment_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="e.g., 2010">
                        <?php if (isset($errors['establishment_year'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['establishment_year']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Owner Name <span style="color: #DC2626;">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-input <?php echo isset($errors['full_name']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['full_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['full_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span style="color: #DC2626;">*</span></label>
                        <input type="email" id="email" name="email" class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['email'] ?? ''); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input <?php echo isset($errors['phone']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['phone'] ?? ''); ?>" placeholder="Enter 10-digit phone number">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="gst_number">GST Number</label>
                        <input type="text" id="gst_number" name="gst_number" class="form-input <?php echo isset($errors['gst_number']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['gst_number'] ?? ''); ?>" placeholder="Enter GST number">
                        <?php if (isset($errors['gst_number'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['gst_number']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" name="address" class="form-input" rows="2" placeholder="Enter full address"><?php echo escapeHtml($shop['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="form-input" value="<?php echo escapeHtml($shop['city'] ?? ''); ?>" placeholder="City">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="state">State</label>
                        <input type="text" id="state" name="state" class="form-input" value="<?php echo escapeHtml($shop['state'] ?? ''); ?>" placeholder="State">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" class="form-input <?php echo isset($errors['pincode']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($shop['pincode'] ?? ''); ?>" placeholder="6-digit pincode">
                        <?php if (isset($errors['pincode'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['pincode']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ============================================
                SHOP LOCATION SECTION
                ============================================ -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
                        Shop Location
                        <span style="font-weight: 400; font-size: 12px; color: #6B7A7B;">
                            (Optional - Helps agents find your shop)
                        </span>
                    </label>
                    
                    <div class="location-container">
                        <button type="button" class="btn-location" id="getLocationBtn">
                            <i class="fas fa-crosshairs"></i> Get Current Location
                        </button>
                        
                        <?php if ($locationSet): ?>
                            <div class="location-status success" id="locationStatus">
                                <i class="fas fa-check-circle"></i> 
                                Location saved: 
                                <span class="location-coords">
                                    <?php echo number_format($shopLatitude, 6); ?>, <?php echo number_format($shopLongitude, 6); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="location-status info" id="locationStatus">
                                <i class="fas fa-info-circle"></i> 
                                No location set. Click "Get Current Location" to add your shop location.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="locationDetails" style="font-size: 13px; color: #6B7A7B; margin-top: 6px;"></div>
                    
                    <?php if (isset($errors['latitude'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['latitude']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($errors['longitude'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['longitude']); ?></div>
                    <?php endif; ?>
                    
                    
                </div>
                
                <!-- Working Hours -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="working_hours_start">Opening Time</label>
                        <input type="time" id="working_hours_start" name="working_hours_start" class="form-input" value="<?php echo escapeHtml($shop['working_hours_start'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="working_hours_end">Closing Time</label>
                        <input type="time" id="working_hours_end" name="working_hours_end" class="form-input" value="<?php echo escapeHtml($shop['working_hours_end'] ?? ''); ?>">
                    </div>
                </div>
                <?php if (isset($errors['working_hours'])): ?>
                    <div class="form-error"><?php echo escapeHtml($errors['working_hours']); ?></div>
                <?php endif; ?>
                
                <!-- Weekend Days - Checkbox -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-week" style="color: #16A34A;"></i>
                        Weekend Days
                    </label>
                    <div class="weekend-grid">
                        <?php foreach ($weekendDaysOptions as $day): ?>
                            <label class="checkbox-group">
                                <input type="checkbox" name="weekend_days[]" value="<?php echo $day; ?>" 
                                    <?php echo in_array($day, $selectedWeekendDays) ? 'checked' : ''; ?>>
                                <span><?php echo $day; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Select days when your shop is closed
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <label class="checkbox-group" style="margin: 0;">
                            <input type="checkbox" name="delivery_available" value="1" <?php echo ($shop['delivery_available'] ?? 0) ? 'checked' : ''; ?>>
                            <span><i class="fas fa-truck" style="color: #16A34A;"></i> Delivery Available</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Update Shop Profile
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

<script>
// ============================================
// AVATAR PREVIEW
// ============================================
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

// ============================================
// LOCATION - GET CURRENT LOCATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const getLocationBtn = document.getElementById('getLocationBtn');
    const locationStatus = document.getElementById('locationStatus');
    const locationDetails = document.getElementById('locationDetails');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    
    // Store original status HTML if location already set
    let originalStatusHTML = locationStatus ? locationStatus.innerHTML : '';
    
    getLocationBtn.addEventListener('click', function() {
        if (!navigator.geolocation) {
            locationStatus.className = 'location-status error';
            locationStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Your browser does not support GPS location.';
            return;
        }
        
        getLocationBtn.disabled = true;
        getLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching location...';
        
        locationStatus.className = 'location-status warning';
        locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Detecting location...';
        locationDetails.innerHTML = '';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const acc = position.coords.accuracy || 0;
                
                // Set hidden fields
                latInput.value = lat;
                lngInput.value = lng;
                
                // Update status
                locationStatus.className = 'location-status success';
                locationStatus.innerHTML = '<i class="fas fa-check-circle"></i> Location captured successfully: ' +
                    '<span class="location-coords">' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</span>';
                
                locationDetails.innerHTML = 'Accuracy: ± ' + acc.toFixed(0) + ' meters';
                
                getLocationBtn.disabled = false;
                getLocationBtn.innerHTML = '<i class="fas fa-sync"></i> Refresh Location';
                
                // Auto-submit form after location capture
                Swal.fire({
                    icon: 'success',
                    title: 'Location Captured!',
                    text: 'Shop location has been captured. Save the form to update your profile.',
                    timer: 3000,
                    showConfirmButton: true,
                    confirmButtonColor: '#16A34A',
                    confirmButtonText: 'OK'
                });
            },
            function(error) {
                let message = 'Location access denied. Please enable location in browser settings.';
                if (error.code === error.TIMEOUT) {
                    message = 'Location request timed out. Please try again.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    message = 'GPS signal unavailable. Please move to an open area.';
                }
                locationStatus.className = 'location-status error';
                locationStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                locationDetails.innerHTML = '';
                
                getLocationBtn.disabled = false;
                getLocationBtn.innerHTML = '<i class="fas fa-crosshairs"></i> Get Current Location';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Location Error',
                    text: message,
                    confirmButtonColor: '#DC2626'
                });
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
});
</script>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>