<?php
/**
 * SAMRIDHI AGRO - Add Shop
 * 
 * This page allows administrators to create new shop accounts.
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
$pageTitle = 'Add Shop';

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
requirePermission('shop.create');

// Get database instance
$db = getDB();

// Initialize variables
$errors = [];
$formData = [
    'shop_name' => '',
    'shop_code' => '',
    'shop_type' => 'retail',
    'owner_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'pincode' => '',
    'gst_number' => '',
    'agent_id' => '',
    'status' => 'pending',
    // New Fields
    'establishment_year' => '',
    'shop_category' => 'grocery',
    'delivery_available' => 0,
    'working_hours_start' => '09:00',
    'working_hours_end' => '21:00',
    'weekend_days' => '',
    'latitude' => '',
    'longitude' => '',
    'shop_image' => ''
];

// Generate unique shop code
$shopCode = 'SH' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
$formData['shop_code'] = $shopCode;

// Get approved agents for dropdown
$sql = "SELECT a.id, u.full_name, a.agent_code 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.status = 'approved' 
        ORDER BY u.full_name";
$agentList = $db->fetchAll($sql);

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

// Weekend days options
$weekendDays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/shop-add.php');
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'shop_name' => sanitizeInput($_POST['shop_name'] ?? ''),
        'shop_code' => sanitizeInput($_POST['shop_code'] ?? ''),
        'shop_type' => sanitizeInput($_POST['shop_type'] ?? 'retail'),
        'owner_name' => sanitizeInput($_POST['owner_name'] ?? ''),
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'city' => sanitizeInput($_POST['city'] ?? ''),
        'state' => sanitizeInput($_POST['state'] ?? ''),
        'pincode' => sanitizeInput($_POST['pincode'] ?? ''),
        'gst_number' => sanitizeInput($_POST['gst_number'] ?? ''),
        'agent_id' => (int)($_POST['agent_id'] ?? 0),
        'status' => sanitizeInput($_POST['status'] ?? 'pending'),
        // New Fields
        'establishment_year' => (int)($_POST['establishment_year'] ?? 0),
        'shop_category' => sanitizeInput($_POST['shop_category'] ?? 'grocery'),
        'delivery_available' => isset($_POST['delivery_available']) ? 1 : 0,
        'working_hours_start' => sanitizeInput($_POST['working_hours_start'] ?? '09:00'),
        'working_hours_end' => sanitizeInput($_POST['working_hours_end'] ?? '21:00'),
        'weekend_days' => sanitizeInput($_POST['weekend_days'] ?? ''),
        'latitude' => (float)($_POST['latitude'] ?? 0),
        'longitude' => (float)($_POST['longitude'] ?? 0)
    ];
    
    $send_email = isset($_POST['send_email']);
    $generate_password = isset($_POST['generate_password']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $hasErrors = false;
    
    // Shop Name - required
    if (empty($formData['shop_name'])) {
        $errors['shop_name'] = 'Shop name is required';
        $hasErrors = true;
    } elseif (strlen($formData['shop_name']) < 3) {
        $errors['shop_name'] = 'Shop name must be at least 3 characters';
        $hasErrors = true;
    }
    
    // Shop Code - required, unique
    if (empty($formData['shop_code'])) {
        $errors['shop_code'] = 'Shop code is required';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM shops WHERE shop_code = ?";
        $existing = $db->fetchOne($sql, [$formData['shop_code']]);
        if ($existing) {
            $errors['shop_code'] = 'Shop code already exists. Please generate a new one.';
            $hasErrors = true;
        }
    }
    
    // Shop Type - required
    if (empty($formData['shop_type'])) {
        $errors['shop_type'] = 'Shop type is required';
        $hasErrors = true;
    } elseif (!in_array($formData['shop_type'], ['retail', 'wholesale', 'both'])) {
        $errors['shop_type'] = 'Invalid shop type';
        $hasErrors = true;
    }
    
    // Shop Category - required
    if (empty($formData['shop_category'])) {
        $errors['shop_category'] = 'Shop category is required';
        $hasErrors = true;
    }
    
    // Owner Name - required
    if (empty($formData['owner_name'])) {
        $errors['owner_name'] = 'Owner name is required';
        $hasErrors = true;
    } elseif (strlen($formData['owner_name']) < 3) {
        $errors['owner_name'] = 'Owner name must be at least 3 characters';
        $hasErrors = true;
    }
    
    // Username - required, unique
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
        $sql = "SELECT id FROM users WHERE username = ?";
        $existing = $db->fetchOne($sql, [$formData['username']]);
        if ($existing) {
            $errors['username'] = 'Username already exists. Please choose another.';
            $hasErrors = true;
        }
    }
    
    // Email - required, valid, unique
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required';
        $hasErrors = true;
    } elseif (!isValidEmail($formData['email'])) {
        $errors['email'] = 'Please enter a valid email address';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        $existing = $db->fetchOne($sql, [$formData['email']]);
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
    
    // Pincode - optional, valid if provided
    if (!empty($formData['pincode']) && !isValidPincode($formData['pincode'])) {
        $errors['pincode'] = 'Please enter a valid 6-digit pincode';
        $hasErrors = true;
    }
    
    // GST Number - optional, valid if provided
    if (!empty($formData['gst_number']) && !isValidGST($formData['gst_number'])) {
        $errors['gst_number'] = 'Please enter a valid GST number';
        $hasErrors = true;
    }
    
    // Establishment Year - optional
    if ($formData['establishment_year'] > 0 && ($formData['establishment_year'] < 1900 || $formData['establishment_year'] > date('Y'))) {
        $errors['establishment_year'] = 'Please enter a valid establishment year';
        $hasErrors = true;
    }
    
    // Working hours - validate
    if (!empty($formData['working_hours_start']) && !empty($formData['working_hours_end'])) {
        if ($formData['working_hours_start'] >= $formData['working_hours_end']) {
            $errors['working_hours'] = 'Closing time must be after opening time';
            $hasErrors = true;
        }
    }
    
    // Agent ID - optional, check if exists
    if ($formData['agent_id'] > 0) {
        $sql = "SELECT id FROM agents WHERE id = ? AND status = 'approved'";
        $agent = $db->fetchOne($sql, [$formData['agent_id']]);
        if (!$agent) {
            $errors['agent_id'] = 'Selected agent is not valid or not approved.';
            $hasErrors = true;
        }
    }
    
    // Password - required if not generated
    if ($generate_password) {
        // Generate a secure password
        $password = generateSecurePassword(12);
        $formData['password_plain'] = $password;
    } else {
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
        $formData['password_plain'] = $password;
    }
    
    // If no errors, insert shop
    if (!$hasErrors) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Hash password
            $hashedPassword = hashPassword($formData['password_plain']);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password_hash, full_name, phone, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'shop', ?, NOW())";
            
            $userStatus = $formData['status'] === 'approved' ? 'active' : 'active';
            $db->query($sql, [
                $formData['username'],
                $formData['email'],
                $hashedPassword,
                $formData['owner_name'],
                $formData['phone'],
                $userStatus
            ]);
            
            $userId = $db->lastInsertId();
            
            // Insert shop with new fields
            $sql = "INSERT INTO shops (
                        user_id, shop_name, shop_code, shop_type, 
                        owner_name, phone, email, 
                        address, city, state, pincode, 
                        gst_number, agent_id, status,
                        establishment_year, shop_category, delivery_available,
                        working_hours_start, working_hours_end, weekend_days,
                        latitude, longitude,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $db->query($sql, [
                $userId,
                $formData['shop_name'],
                $formData['shop_code'],
                $formData['shop_type'],
                $formData['owner_name'],
                $formData['phone'],
                $formData['email'],
                $formData['address'],
                $formData['city'],
                $formData['state'],
                $formData['pincode'],
                $formData['gst_number'],
                $formData['agent_id'] > 0 ? $formData['agent_id'] : null,
                $formData['status'],
                $formData['establishment_year'] > 0 ? $formData['establishment_year'] : null,
                $formData['shop_category'],
                $formData['delivery_available'],
                $formData['working_hours_start'],
                $formData['working_hours_end'],
                $formData['weekend_days'],
                $formData['latitude'] > 0 ? $formData['latitude'] : null,
                $formData['longitude'] > 0 ? $formData['longitude'] : null
            ]);
            
            // If status is approved, update approved_by and approved_at
            if ($formData['status'] === 'approved') {
                $shopId = $db->lastInsertId();
                $sql = "UPDATE shops SET approved_by = ?, approved_at = NOW() WHERE id = ?";
                $db->query($sql, [$_SESSION['user_id'], $shopId]);
            }
            
            // Commit transaction
            $db->commit();
            
            // Log activity
            logActivity(
                'create',
                $_SESSION['user_id'],
                'shop',
                'Created new shop: ' . $formData['shop_name'] . ' (' . $formData['shop_code'] . ')'
            );
            
            setFlashMessage('success', 'Shop created successfully!');
            
            // Store password in session to display if email not sent
            if (!$send_email) {
                $_SESSION['new_shop_password'] = $formData['password_plain'];
                $_SESSION['new_shop_username'] = $formData['username'];
            }
            
            redirect('admin/shops.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Shop creation error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to create shop. Please try again.');
            redirect('admin/shop-add.php');
            exit;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header
// ============================================
require_once '../includes/admin_header.php';
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; color: #14532D; margin-bottom: 6px; }
    .form-input { width: 100%; padding: 10px 14px; font-family: 'Inter', sans-serif; font-size: 14px; border: 2px solid #E5EDE7; border-radius: 8px; background: white; transition: all 0.3s ease; color: #052E16; box-sizing: border-box; }
    .form-input:focus { outline: none; border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1); }
    .form-input.error { border-color: #DC2626; background: rgba(220, 38, 38, 0.05); }
    .form-error { color: #DC2626; font-size: 13px; font-family: 'Inter', sans-serif; margin-top: 4px; }
    .form-hint { font-size: 12px; color: #6B7A7B; margin-top: 4px; }
    .checkbox-group { display: flex; align-items: center; gap: 8px; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 14px; color: #4A5B5D; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #16A34A; cursor: pointer; }
    .btn-primary { padding: 12px 32px; background: linear-gradient(135deg, #14532D, #16A34A); color: white; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3); }
    .btn-secondary { padding: 12px 24px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; cursor: pointer; }
    .btn-secondary:hover { background: #E5E7EB; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr !important; } }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-store-plus" style="color: #16A34A;"></i>
            Add New Shop
        </h3>
        <a href="admin/shops.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Shop List
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <p style="color: #991B1B; font-weight: 600; margin-bottom: 8px;">
            <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
        </p>
        <ul style="margin: 0; padding-left: 20px; color: #991B1B;">
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo escapeHtml($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="shopForm" novalidate>
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Left Column -->
            <div>
                <div class="form-group">
                    <label class="form-label" for="shop_name">Shop Name <span style="color: #DC2626;">*</span></label>
                    <input type="text" id="shop_name" name="shop_name" class="form-input <?php echo isset($errors['shop_name']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['shop_name']); ?>" placeholder="Enter shop name" required>
                    <?php if (isset($errors['shop_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="shop_code">Shop Code <span style="color: #DC2626;">*</span></label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="shop_code" name="shop_code" class="form-input <?php echo isset($errors['shop_code']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['shop_code']); ?>" placeholder="Enter shop code" required style="flex: 1;">
                        <button type="button" onclick="generateShopCode()" style="padding: 10px 16px; background: #F3F4F6; border: 2px solid #E5EDE7; border-radius: 8px; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 14px; transition: all 0.3s ease; white-space: nowrap;">
                            <i class="fas fa-sync"></i> Generate
                        </button>
                    </div>
                    <?php if (isset($errors['shop_code'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_code']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Unique code for shop identification</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="shop_type">Shop Type <span style="color: #DC2626;">*</span></label>
                    <select id="shop_type" name="shop_type" class="form-input <?php echo isset($errors['shop_type']) ? 'error' : ''; ?>" required>
                        <option value="retail" <?php echo $formData['shop_type'] === 'retail' ? 'selected' : ''; ?>>Retail</option>
                        <option value="wholesale" <?php echo $formData['shop_type'] === 'wholesale' ? 'selected' : ''; ?>>Wholesale</option>
                        <option value="both" <?php echo $formData['shop_type'] === 'both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                    <?php if (isset($errors['shop_type'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_type']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="shop_category">Shop Category <span style="color: #DC2626;">*</span></label>
                    <select id="shop_category" name="shop_category" class="form-input <?php echo isset($errors['shop_category']) ? 'error' : ''; ?>" required>
                        <?php foreach ($shopCategories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $formData['shop_category'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['shop_category'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_category']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="establishment_year">Establishment Year</label>
                    <input type="number" id="establishment_year" name="establishment_year" class="form-input <?php echo isset($errors['establishment_year']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['establishment_year']); ?>" placeholder="e.g., 2010" min="1900" max="<?php echo date('Y'); ?>">
                    <?php if (isset($errors['establishment_year'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['establishment_year']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="owner_name">Owner Name <span style="color: #DC2626;">*</span></label>
                    <input type="text" id="owner_name" name="owner_name" class="form-input <?php echo isset($errors['owner_name']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['owner_name']); ?>" placeholder="Enter owner's full name" required>
                    <?php if (isset($errors['owner_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['owner_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="username">Username <span style="color: #DC2626;">*</span></label>
                    <input type="text" id="username" name="username" class="form-input <?php echo isset($errors['username']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['username']); ?>" placeholder="Enter username" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['username']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint"><i class="fas fa-info-circle"></i> 3-50 characters, letters, numbers and underscore only</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address <span style="color: #DC2626;">*</span></label>
                    <input type="email" id="email" name="email" class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['email']); ?>" placeholder="Enter email address" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['email']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input <?php echo isset($errors['phone']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['phone']); ?>" placeholder="Enter 10-digit phone number">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['phone']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" name="address" class="form-input" rows="2" placeholder="Enter full address"><?php echo escapeHtml($formData['address']); ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="form-input" value="<?php echo escapeHtml($formData['city']); ?>" placeholder="City">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="state">State</label>
                        <input type="text" id="state" name="state" class="form-input" value="<?php echo escapeHtml($formData['state']); ?>" placeholder="State">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" class="form-input <?php echo isset($errors['pincode']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['pincode']); ?>" placeholder="6-digit pincode">
                        <?php if (isset($errors['pincode'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['pincode']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="gst_number">GST Number</label>
                    <input type="text" id="gst_number" name="gst_number" class="form-input <?php echo isset($errors['gst_number']) ? 'error' : ''; ?>" value="<?php echo escapeHtml($formData['gst_number']); ?>" placeholder="Enter GST number">
                    <?php if (isset($errors['gst_number'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['gst_number']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Location -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="latitude">Latitude</label>
                        <input type="text" id="latitude" name="latitude" class="form-input" value="<?php echo escapeHtml($formData['latitude']); ?>" placeholder="e.g., 28.6139">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="longitude">Longitude</label>
                        <input type="text" id="longitude" name="longitude" class="form-input" value="<?php echo escapeHtml($formData['longitude']); ?>" placeholder="e.g., 77.2090">
                    </div>
                </div>
                
                <!-- Working Hours -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="working_hours_start">Opening Time</label>
                        <input type="time" id="working_hours_start" name="working_hours_start" class="form-input" value="<?php echo escapeHtml($formData['working_hours_start']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="working_hours_end">Closing Time</label>
                        <input type="time" id="working_hours_end" name="working_hours_end" class="form-input" value="<?php echo escapeHtml($formData['working_hours_end']); ?>">
                    </div>
                </div>
                <?php if (isset($errors['working_hours'])): ?>
                    <div class="form-error"><?php echo escapeHtml($errors['working_hours']); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label" for="weekend_days">Weekend Days</label>
                    <select id="weekend_days" name="weekend_days" class="form-input" multiple style="height: 80px;">
                        <?php foreach ($weekendDays as $day): ?>
                            <option value="<?php echo $day; ?>" <?php echo strpos($formData['weekend_days'], $day) !== false ? 'selected' : ''; ?>>
                                <?php echo $day; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple days</div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-group">
                        <input type="checkbox" name="delivery_available" value="1" <?php echo $formData['delivery_available'] ? 'checked' : ''; ?>>
                        <span>Delivery Available</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="agent_id">Assigned Agent</label>
                    <select id="agent_id" name="agent_id" class="form-input <?php echo isset($errors['agent_id']) ? 'error' : ''; ?>">
                        <option value="0">Select Agent (Optional)</option>
                        <?php foreach ($agentList as $agent): ?>
                            <option value="<?php echo $agent['id']; ?>" <?php echo $formData['agent_id'] == $agent['id'] ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($agent['full_name']); ?> (<?php echo escapeHtml($agent['agent_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['agent_id'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['agent_id']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint"><i class="fas fa-info-circle"></i> Assign an approved agent to manage this shop</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-input">
                        <option value="pending" <?php echo $formData['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $formData['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Password Section -->
        <div style="background: #F7FCF7; padding: 16px; border-radius: 12px; margin-top: 8px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                <label class="checkbox-group" style="margin: 0;">
                    <input type="checkbox" name="generate_password" id="generate_password" value="1" checked>
                    <span>Auto-generate password</span>
                </label>
                <label class="checkbox-group" style="margin: 0;">
                    <input type="checkbox" name="send_email" id="send_email" value="1" checked>
                    <span>Send credentials via email</span>
                </label>
            </div>
            
            <div id="manualPasswordSection" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="password">Password <span style="color: #DC2626;">*</span></label>
                        <input type="password" id="password" name="password" class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>" placeholder="Enter password">
                        <?php if (isset($errors['password'])): ?>
                            <div class="form-error"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm password">
                    </div>
                </div>
            </div>
            
            <div id="autoPasswordInfo" style="font-size: 13px; color: #6B7A7B; margin-top: 8px;">
                <i class="fas fa-shield-alt" style="color: #16A34A;"></i>
                A secure password will be generated automatically and sent to the shop's email.
            </div>
        </div>
        
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Create Shop</span>
                <span id="btnSpinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
            </button>
            <a href="admin/shops.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generatePasswordCheckbox = document.getElementById('generate_password');
    const manualSection = document.getElementById('manualPasswordSection');
    const autoInfo = document.getElementById('autoPasswordInfo');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    generatePasswordCheckbox.addEventListener('change', function() {
        if (this.checked) {
            manualSection.style.display = 'none';
            autoInfo.style.display = 'block';
            passwordInput.disabled = true;
            confirmPasswordInput.disabled = true;
        } else {
            manualSection.style.display = 'block';
            autoInfo.style.display = 'none';
            passwordInput.disabled = false;
            confirmPasswordInput.disabled = false;
        }
    });
    
    generatePasswordCheckbox.dispatchEvent(new Event('change'));
    
    const ownerNameInput = document.getElementById('owner_name');
    const usernameInput = document.getElementById('username');
    
    ownerNameInput.addEventListener('blur', function() {
        if (usernameInput.value === '') {
            const name = this.value.toLowerCase()
                .replace(/[^a-z0-9]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
            usernameInput.value = name;
        }
    });
});

function generateShopCode() {
    const year = new Date().getFullYear();
    const random = String(Math.floor(Math.random() * 9999)).padStart(4, '0');
    document.getElementById('shop_code').value = 'SH' + year + random;
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>