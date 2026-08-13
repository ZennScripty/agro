<?php
/**
 * SAMRIDHI AGRO - Edit Shop
 * 
 * This page allows administrators to update existing shop details.
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
$pageTitle = 'Edit Shop';

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with shop.edit permission
// ============================================
requirePermissionOrAdmin('shop.edit', 'shop-edit.php');

// Get database instance
$db = getDB();

// Get shop ID from URL
$shopId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to shop list
if ($shopId <= 0) {
    setFlashMessage('error', 'Invalid shop ID.');
    redirect('admin/shops.php');
    exit;
}

// Get shop data with user details
$sql = "SELECT s.*, u.id as user_id, u.username, u.email, u.phone, u.full_name as owner_name,
        u.status as user_status, u.created_at as user_created_at
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?";
$shop = $db->fetchOne($sql, [$shopId]);

// If shop not found, redirect
if (!$shop) {
    setFlashMessage('error', 'Shop not found.');
    redirect('admin/shops.php');
    exit;
}

// Get approved agents for dropdown
$sql = "SELECT a.id, u.full_name, a.agent_code 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.status = 'approved' 
        ORDER BY u.full_name";
$agentList = $db->fetchAll($sql);

// Initialize variables
$errors = [];
$formData = [
    'shop_name' => $shop['shop_name'],
    'shop_code' => $shop['shop_code'],
    'shop_type' => $shop['shop_type'],
    'owner_name' => $shop['owner_name'],
    'username' => $shop['username'],
    'email' => $shop['email'],
    'phone' => $shop['phone'] ?? '',
    'address' => $shop['address'] ?? '',
    'city' => $shop['city'] ?? '',
    'state' => $shop['state'] ?? '',
    'pincode' => $shop['pincode'] ?? '',
    'gst_number' => $shop['gst_number'] ?? '',
    'agent_id' => $shop['agent_id'] ?? 0,
    'status' => $shop['status']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('shop-edit.php?id=' . $shopId);
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
        'status' => sanitizeInput($_POST['status'] ?? 'pending')
    ];
    
    $change_password = isset($_POST['change_password']);
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
    
    // Shop Code - required, unique (except current shop)
    if (empty($formData['shop_code'])) {
        $errors['shop_code'] = 'Shop code is required';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM shops WHERE shop_code = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['shop_code'], $shopId]);
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
    
    // Owner Name - required
    if (empty($formData['owner_name'])) {
        $errors['owner_name'] = 'Owner name is required';
        $hasErrors = true;
    } elseif (strlen($formData['owner_name']) < 3) {
        $errors['owner_name'] = 'Owner name must be at least 3 characters';
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
        $existing = $db->fetchOne($sql, [$formData['username'], $shop['user_id']]);
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
        $existing = $db->fetchOne($sql, [$formData['email'], $shop['user_id']]);
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
    
    // Agent ID - optional, check if exists
    if ($formData['agent_id'] > 0) {
        $sql = "SELECT id FROM agents WHERE id = ? AND status = 'approved'";
        $agent = $db->fetchOne($sql, [$formData['agent_id']]);
        if (!$agent) {
            $errors['agent_id'] = 'Selected agent is not valid or not approved.';
            $hasErrors = true;
        }
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
    
    // If no errors, update shop
    if (!$hasErrors) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Build update query for users table
            $sql = "UPDATE users SET 
                    username = ?,
                    email = ?,
                    full_name = ?,
                    phone = ?
                    WHERE id = ?";
            
            $params = [
                $formData['username'],
                $formData['email'],
                $formData['owner_name'],
                $formData['phone'],
                $shop['user_id']
            ];
            
            // If changing password
            if ($change_password && !empty($password)) {
                $hashedPassword = hashPassword($password);
                $sql = "UPDATE users SET 
                        username = ?,
                        email = ?,
                        full_name = ?,
                        phone = ?,
                        password_hash = ?
                        WHERE id = ?";
                $params = [
                    $formData['username'],
                    $formData['email'],
                    $formData['owner_name'],
                    $formData['phone'],
                    $hashedPassword,
                    $shop['user_id']
                ];
            }
            
            $db->query($sql, $params);
            
            // Update shop
            $sql = "UPDATE shops SET 
                    shop_name = ?,
                    shop_code = ?,
                    shop_type = ?,
                    owner_name = ?,
                    phone = ?,
                    email = ?,
                    address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?,
                    gst_number = ?,
                    agent_id = ?,
                    status = ?
                    WHERE id = ?";
            
            $db->query($sql, [
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
                $shopId
            ]);
            
            // If status changed to approved, update approved_by
            if ($formData['status'] === 'approved' && $shop['status'] !== 'approved') {
                $sql = "UPDATE shops SET approved_by = ?, approved_at = NOW() WHERE id = ?";
                $db->query($sql, [$_SESSION['user_id'], $shopId]);
            }
            
            // Update user status based on shop status
            $userStatus = $formData['status'] === 'approved' ? 'active' : 
                         ($formData['status'] === 'suspended' ? 'suspended' : 'active');
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $db->query($sql, [$userStatus, $shop['user_id']]);
            
            // Commit transaction
            $db->commit();
            
            // Log activity
            logActivity(
                'update',
                $_SESSION['user_id'],
                'shop',
                'Updated shop: ' . $formData['shop_name'] . ' (' . $formData['shop_code'] . ')'
            );
            
            // Set success message
            setFlashMessage('success', 'Shop updated successfully!');
            
            // Redirect to shop list
            redirect('admin/shops.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            error_log('Shop update error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to update shop. Please try again.');
            redirect('shop-edit.php?id=' . $shopId);
            exit;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<!-- Rest of the HTML remains same -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-store-edit" style="color: #16A34A;"></i>
            Edit Shop
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $shop['id']; ?> - <?php echo escapeHtml($shop['shop_name']); ?>
            </span>
        </h3>
        <div style="display: flex; gap: 8px;">
            <a href="shop-view.php?id=<?php echo $shop['id']; ?>" class="card-action">
                <i class="fas fa-eye"></i> View
            </a>
            <a href="admin/shops.php" class="card-action">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
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
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Left Column -->
            <div>
                <!-- Shop Name -->
                <div class="form-group">
                    <label class="form-label" for="shop_name">
                        <i class="fas fa-store" style="color: #16A34A;"></i>
                        Shop Name <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="shop_name" 
                        name="shop_name" 
                        class="form-input <?php echo isset($errors['shop_name']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['shop_name']); ?>"
                        placeholder="Enter shop name"
                        required
                    >
                    <?php if (isset($errors['shop_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Shop Code -->
                <div class="form-group">
                    <label class="form-label" for="shop_code">
                        <i class="fas fa-id-badge" style="color: #16A34A;"></i>
                        Shop Code <span style="color: #DC2626;">*</span>
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input 
                            type="text" 
                            id="shop_code" 
                            name="shop_code" 
                            class="form-input <?php echo isset($errors['shop_code']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['shop_code']); ?>"
                            placeholder="Enter shop code"
                            required
                            style="flex: 1;"
                        >
                        <button type="button" onclick="generateShopCode()" style="
                            padding: 10px 16px;
                            background: #F3F4F6;
                            border: 2px solid #E5EDE7;
                            border-radius: 8px;
                            cursor: pointer;
                            font-family: 'Inter', sans-serif;
                            font-size: 14px;
                            transition: all 0.3s ease;
                            white-space: nowrap;
                        ">
                            <i class="fas fa-sync"></i> Generate
                        </button>
                    </div>
                    <?php if (isset($errors['shop_code'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_code']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Unique code for shop identification
                    </div>
                </div>
                
                <!-- Shop Type -->
                <div class="form-group">
                    <label class="form-label" for="shop_type">
                        <i class="fas fa-tag" style="color: #16A34A;"></i>
                        Shop Type <span style="color: #DC2626;">*</span>
                    </label>
                    <select id="shop_type" name="shop_type" class="form-input <?php echo isset($errors['shop_type']) ? 'error' : ''; ?>" required>
                        <option value="retail" <?php echo $formData['shop_type'] === 'retail' ? 'selected' : ''; ?>>Retail</option>
                        <option value="wholesale" <?php echo $formData['shop_type'] === 'wholesale' ? 'selected' : ''; ?>>Wholesale</option>
                        <option value="both" <?php echo $formData['shop_type'] === 'both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                    <?php if (isset($errors['shop_type'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_type']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Owner Name -->
                <div class="form-group">
                    <label class="form-label" for="owner_name">
                        <i class="fas fa-user" style="color: #16A34A;"></i>
                        Owner Name <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="owner_name" 
                        name="owner_name" 
                        class="form-input <?php echo isset($errors['owner_name']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['owner_name']); ?>"
                        placeholder="Enter owner's full name"
                        required
                    >
                    <?php if (isset($errors['owner_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['owner_name']); ?></div>
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
                        placeholder="Enter username"
                        required
                    >
                    <?php if (isset($errors['username'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['username']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
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
            </div>
            
            <!-- Right Column -->
            <div>
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
                
                <!-- Address -->
                <div class="form-group">
                    <label class="form-label" for="address">
                        <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
                        Address
                    </label>
                    <textarea 
                        id="address" 
                        name="address" 
                        class="form-input"
                        rows="2"
                        placeholder="Enter full address"
                    ><?php echo escapeHtml($formData['address']); ?></textarea>
                </div>
                
                <!-- City, State, Pincode -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="city">City</label>
                        <input 
                            type="text" 
                            id="city" 
                            name="city" 
                            class="form-input"
                            value="<?php echo escapeHtml($formData['city']); ?>"
                            placeholder="City"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="state">State</label>
                        <input 
                            type="text" 
                            id="state" 
                            name="state" 
                            class="form-input"
                            value="<?php echo escapeHtml($formData['state']); ?>"
                            placeholder="State"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pincode">Pincode</label>
                        <input 
                            type="text" 
                            id="pincode" 
                            name="pincode" 
                            class="form-input <?php echo isset($errors['pincode']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['pincode']); ?>"
                            placeholder="6-digit pincode"
                        >
                        <?php if (isset($errors['pincode'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['pincode']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- GST Number -->
                <div class="form-group">
                    <label class="form-label" for="gst_number">
                        <i class="fas fa-file-invoice" style="color: #16A34A;"></i>
                        GST Number
                    </label>
                    <input 
                        type="text" 
                        id="gst_number" 
                        name="gst_number" 
                        class="form-input <?php echo isset($errors['gst_number']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['gst_number']); ?>"
                        placeholder="Enter GST number"
                    >
                    <?php if (isset($errors['gst_number'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['gst_number']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Agent Assignment -->
                <div class="form-group">
                    <label class="form-label" for="agent_id">
                        <i class="fas fa-user-tie" style="color: #16A34A;"></i>
                        Assigned Agent
                    </label>
                    <select id="agent_id" name="agent_id" class="form-input <?php echo isset($errors['agent_id']) ? 'error' : ''; ?>">
                        <option value="0">Select Agent (Optional)</option>
                        <?php foreach ($agentList as $agent): ?>
                            <option value="<?php echo $agent['id']; ?>" 
                                <?php echo $formData['agent_id'] == $agent['id'] ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($agent['full_name']); ?> (<?php echo escapeHtml($agent['agent_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['agent_id'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['agent_id']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Assign an approved agent to manage this shop
                    </div>
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label" for="status">
                        <i class="fas fa-toggle-on" style="color: #16A34A;"></i>
                        Status
                    </label>
                    <select id="status" name="status" class="form-input">
                        <option value="pending" <?php echo $formData['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $formData['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="suspended" <?php echo $formData['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="rejected" <?php echo $formData['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </div>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
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
            </div>
            
            <div id="passwordInfo" style="font-size: 13px; color: #6B7A7B; margin-top: 8px;">
                <i class="fas fa-info-circle" style="color: #16A34A;"></i>
                Check the box above to change the shop owner's password.
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Update Shop</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
            
            <a href="admin/shops.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

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
    
    // Trigger change event on load
    changePasswordCheckbox.dispatchEvent(new Event('change'));
});

// Generate Shop Code
function generateShopCode() {
    const year = new Date().getFullYear();
    const random = String(Math.floor(Math.random() * 9999)).padStart(4, '0');
    const code = 'SH' + year + random;
    document.getElementById('shop_code').value = code;
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>