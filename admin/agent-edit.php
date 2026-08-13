<?php
/**
 * SAMRIDHI AGRO - Edit Agent
 * 
 * This page allows administrators to update existing agent details.
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
$pageTitle = 'Edit Agent';

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
    logActivity('unauthorized_access', $_SESSION['user_id'], 'security', 
                'Attempted to access agents.php without permission');
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

// Get database instance
$db = getDB();

// Get agent ID from URL
$agentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to agent list
if ($agentId <= 0) {
    setFlashMessage('error', 'Invalid agent ID.');
    redirect('admin/agents.php');
    exit;
}

// Get agent data with user details
$sql = "SELECT a.*, u.full_name, u.username, u.email, u.phone, u.status as user_status,
        u.created_at as user_created_at
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.id = ?";
$agent = $db->fetchOne($sql, [$agentId]);

// If agent not found, redirect
if (!$agent) {
    setFlashMessage('error', 'Agent not found.');
    redirect('admin/agents.php');
    exit;
}

// Initialize variables
$errors = [];
$formData = [
    'full_name' => $agent['full_name'],
    'username' => $agent['username'],
    'email' => $agent['email'],
    'phone' => $agent['phone'] ?? '',
    'agent_code' => $agent['agent_code'],
    'company_name' => $agent['company_name'] ?? '',
    'gst_number' => $agent['gst_number'] ?? '',
    'address' => $agent['address'] ?? '',
    'city' => $agent['city'] ?? '',
    'state' => $agent['state'] ?? '',
    'pincode' => $agent['pincode'] ?? '',
    'commission_rate' => $agent['commission_rate'],
    'status' => $agent['status']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('agent-edit.php?id=' . $agentId);
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'agent_code' => sanitizeInput($_POST['agent_code'] ?? ''),
        'company_name' => sanitizeInput($_POST['company_name'] ?? ''),
        'gst_number' => sanitizeInput($_POST['gst_number'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'city' => sanitizeInput($_POST['city'] ?? ''),
        'state' => sanitizeInput($_POST['state'] ?? ''),
        'pincode' => sanitizeInput($_POST['pincode'] ?? ''),
        'commission_rate' => sanitizeInput($_POST['commission_rate'] ?? '0.00'),
        'status' => sanitizeInput($_POST['status'] ?? 'pending')
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
        $existing = $db->fetchOne($sql, [$formData['username'], $agent['user_id']]);
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
        $existing = $db->fetchOne($sql, [$formData['email'], $agent['user_id']]);
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
    
    // Agent Code - required, unique (except current agent)
    if (empty($formData['agent_code'])) {
        $errors['agent_code'] = 'Agent code is required';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM agents WHERE agent_code = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['agent_code'], $agentId]);
        if ($existing) {
            $errors['agent_code'] = 'Agent code already exists. Please generate a new one.';
            $hasErrors = true;
        }
    }
    
    // GST Number - optional, valid if provided
    if (!empty($formData['gst_number']) && !isValidGST($formData['gst_number'])) {
        $errors['gst_number'] = 'Please enter a valid GST number';
        $hasErrors = true;
    }
    
    // Pincode - optional, valid if provided
    if (!empty($formData['pincode']) && !isValidPincode($formData['pincode'])) {
        $errors['pincode'] = 'Please enter a valid 6-digit pincode';
        $hasErrors = true;
    }
    
    // Commission Rate - valid number
    if (!is_numeric($formData['commission_rate']) || $formData['commission_rate'] < 0) {
        $errors['commission_rate'] = 'Commission rate must be a valid number (0-100)';
        $hasErrors = true;
    } elseif ($formData['commission_rate'] > 100) {
        $errors['commission_rate'] = 'Commission rate cannot exceed 100%';
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
    
    // If no errors, update agent
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
                $formData['full_name'],
                $formData['phone'],
                $agent['user_id']
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
                    $formData['full_name'],
                    $formData['phone'],
                    $hashedPassword,
                    $agent['user_id']
                ];
            }
            
            $db->query($sql, $params);
            
            // Update agent
            $sql = "UPDATE agents SET 
                    agent_code = ?,
                    company_name = ?,
                    gst_number = ?,
                    address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?,
                    commission_rate = ?,
                    status = ?
                    WHERE id = ?";
            
            $db->query($sql, [
                $formData['agent_code'],
                $formData['company_name'],
                $formData['gst_number'],
                $formData['address'],
                $formData['city'],
                $formData['state'],
                $formData['pincode'],
                $formData['commission_rate'],
                $formData['status'],
                $agentId
            ]);
            
            // If status changed to approved, update approved_by
            if ($formData['status'] === 'approved' && $agent['status'] !== 'approved') {
                $sql = "UPDATE agents SET approved_by = ?, approved_at = NOW() WHERE id = ?";
                $db->query($sql, [$_SESSION['user_id'], $agentId]);
            }
            
            // Update user status based on agent status
            $userStatus = $formData['status'] === 'approved' ? 'active' : 
                         ($formData['status'] === 'suspended' ? 'suspended' : 'active');
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $db->query($sql, [$userStatus, $agent['user_id']]);
            
            // Commit transaction
            $db->commit();
            
            // Log activity
            logActivity(
                'update',
                $_SESSION['user_id'],
                'agent',
                'Updated agent: ' . $formData['full_name'] . ' (' . $formData['agent_code'] . ')'
            );
            
            // Set success message
            setFlashMessage('success', 'Agent updated successfully!');
            
            // Redirect to agent list
            redirect('admin/agents.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            error_log('Agent update error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to update agent. Please try again.');
            redirect('admin/agent-edit.php?id=' . $agentId);
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
    
    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
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
    
    .btn-primary {
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
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }
    
    .btn-secondary {
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
    }
    
    .btn-secondary:hover {
        background: #E5E7EB;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-edit" style="color: #16A34A;"></i>
            Edit Agent
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $agent['id']; ?> - <?php echo escapeHtml($agent['full_name']); ?>
            </span>
        </h3>
        <div style="display: flex; gap: 8px;">
            <a href="admin/agent-view.php?id=<?php echo $agent['id']; ?>" class="card-action">
                <i class="fas fa-eye"></i> View
            </a>
            <a href="admin/agents.php" class="card-action">
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
    
    <form method="POST" action="" id="agentForm" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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
                
                <!-- Agent Code -->
                <div class="form-group">
                    <label class="form-label" for="agent_code">
                        <i class="fas fa-id-badge" style="color: #16A34A;"></i>
                        Agent Code <span style="color: #DC2626;">*</span>
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input 
                            type="text" 
                            id="agent_code" 
                            name="agent_code" 
                            class="form-input <?php echo isset($errors['agent_code']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['agent_code']); ?>"
                            placeholder="Enter agent code"
                            required
                            style="flex: 1;"
                        >
                        <button type="button" onclick="generateAgentCode()" style="
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
                    <?php if (isset($errors['agent_code'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['agent_code']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Unique code for agent identification
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Company Name -->
                <div class="form-group">
                    <label class="form-label" for="company_name">
                        <i class="fas fa-building" style="color: #16A34A;"></i>
                        Company Name
                    </label>
                    <input 
                        type="text" 
                        id="company_name" 
                        name="company_name" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['company_name']); ?>"
                        placeholder="Enter company name"
                    >
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
                
                <!-- Commission Rate -->
                <div class="form-group">
                    <label class="form-label" for="commission_rate">
                        <i class="fas fa-percentage" style="color: #16A34A;"></i>
                        Commission Rate (%)
                    </label>
                    <input 
                        type="number" 
                        id="commission_rate" 
                        name="commission_rate" 
                        class="form-input <?php echo isset($errors['commission_rate']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['commission_rate']); ?>"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        max="100"
                    >
                    <?php if (isset($errors['commission_rate'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['commission_rate']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Commission percentage for agent (0-100%)
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
                Check the box above to change the agent's password.
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Update Agent</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
            
            <a href="admin/agents.php" class="btn-secondary">
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

// Generate Agent Code
function generateAgentCode() {
    const year = new Date().getFullYear();
    const random = String(Math.floor(Math.random() * 9999)).padStart(4, '0');
    const code = 'AG' + year + random;
    document.getElementById('agent_code').value = code;
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>