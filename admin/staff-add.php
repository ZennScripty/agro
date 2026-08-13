<?php
/**
 * SAMRIDHI AGRO - Add Staff
 * 
 * This page allows administrators to create new staff accounts
 * with role-based permissions.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title BEFORE including header
$pageTitle = 'Add Staff';

// Include admin header (which includes all configs)
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('staff.create', 'staff-add.php');

// Get database instance
$db = getDB();

// Initialize variables
$errors = [];
$formData = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'department' => '',
    'designation' => '',
    'status' => 'active'
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

// Handle AJAX form submission
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => '', 'errors' => []];
    
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $response['message'] = 'Invalid security token. Please try again.';
        if ($isAjax) {
            echo json_encode($response);
            exit;
        } else {
            setFlashMessage('error', $response['message']);
            redirect('staff-add.php');
        }
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
    
    $send_email = isset($_POST['send_email']);
    $generate_password = isset($_POST['generate_password']);
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
        // Check if username exists
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
        // Check if email exists
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
    
    // Department - required
    if (empty($formData['department'])) {
        $errors['department'] = 'Department is required';
        $hasErrors = true;
    }
    
    // If validation errors, return them
    if ($hasErrors) {
        $response['success'] = false;
        $response['message'] = 'Please fix the following errors:';
        $response['errors'] = $errors;
        
        if ($isAjax) {
            echo json_encode($response);
            exit;
        }
    } else {
        // If no errors, insert staff
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Hash password
            $hashedPassword = hashPassword($formData['password_plain']);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password_hash, full_name, phone, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'staff', ?, NOW())";
            $db->query($sql, [
                $formData['username'],
                $formData['email'],
                $hashedPassword,
                $formData['full_name'],
                $formData['phone'],
                $formData['status']
            ]);
            
            $userId = $db->lastInsertId();
            
            // Insert staff profile
            $sql = "INSERT INTO staff_profiles (user_id, department, designation, joining_date, created_at) 
                    VALUES (?, ?, ?, CURDATE(), NOW())";
            $db->query($sql, [
                $userId,
                $formData['department'],
                $formData['designation']
            ]);
            
            // Commit transaction
            $db->commit();
            
            // Log activity
            logActivity(
                'create',
                $_SESSION['user_id'],
                'staff',
                'Created new staff: ' . $formData['full_name'] . ' (' . $formData['username'] . ')'
            );
            
            $response['success'] = true;
            $response['message'] = 'Staff member created successfully!';
            $response['staff_name'] = $formData['full_name'];
            $response['staff_username'] = $formData['username'];
            
            if ($send_email) {
                $response['email_sent'] = true;
                $_SESSION['new_staff_password'] = $formData['password_plain'];
                $_SESSION['new_staff_username'] = $formData['username'];
            }
            
            if ($isAjax) {
                echo json_encode($response);
                exit;
            } else {
                setFlashMessage('success', $response['message']);
                redirect('staff.php');
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            error_log('Staff creation error: ' . $e->getMessage());
            
            $response['success'] = false;
            $response['message'] = 'Failed to create staff member. Please try again.';
            
            if ($isAjax) {
                echo json_encode($response);
                exit;
            } else {
                setFlashMessage('error', $response['message']);
                redirect('staff-add.php');
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-plus" style="color: #16A34A;"></i>
            Add New Staff Member
        </h3>
        <a href="staff.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Staff List
        </a>
    </div>
    
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
                    </select>
                </div>
                
                <!-- Password Section -->
                <div style="background: #F7FCF7; padding: 16px; border-radius: 12px; margin-top: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
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
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="password">Password <span style="color: #DC2626;">*</span></label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                                placeholder="Enter password"
                            >
                            <?php if (isset($errors['password'])): ?>
                                <div class="form-error"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input"
                                placeholder="Confirm password"
                            >
                        </div>
                    </div>
                    
                    <div id="autoPasswordInfo" style="font-size: 13px; color: #6B7A7B; margin-top: 8px;">
                        <i class="fas fa-shield-alt" style="color: #16A34A;"></i>
                        A secure password will be generated automatically and sent to the staff member's email.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn" style="
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
                <i class="fas fa-save"></i> <span id="btnText">Create Staff</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
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
    
    .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    
    @media (max-width: 768px) {
        form > div:first-child {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generatePasswordCheckbox = document.getElementById('generate_password');
    const manualSection = document.getElementById('manualPasswordSection');
    const autoInfo = document.getElementById('autoPasswordInfo');
    const form = document.getElementById('staffForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    // Toggle password section
    generatePasswordCheckbox.addEventListener('change', function() {
        if (this.checked) {
            manualSection.style.display = 'none';
            autoInfo.style.display = 'block';
            document.getElementById('password').disabled = true;
            document.getElementById('confirm_password').disabled = true;
        } else {
            manualSection.style.display = 'block';
            autoInfo.style.display = 'none';
            document.getElementById('password').disabled = false;
            document.getElementById('confirm_password').disabled = false;
        }
    });
    
    // Trigger change event on load
    generatePasswordCheckbox.dispatchEvent(new Event('change'));
    
    // Username auto-generate from full name
    const fullNameInput = document.getElementById('full_name');
    const usernameInput = document.getElementById('username');
    
    fullNameInput.addEventListener('blur', function() {
        if (usernameInput.value === '') {
            const name = this.value.toLowerCase()
                .replace(/[^a-z0-9]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
            usernameInput.value = name;
        }
    });
    
    // Handle form submission with AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'inline';
        
        // Get form data
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Hide loading state
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
            
            if (data.success) {
                // Success message with SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: '✅ Staff Created!',
                    html: `
                        <div style="text-align: left; padding: 10px;">
                            <p style="font-size: 16px; margin-bottom: 10px;">
                                Staff member <strong>${escapeHtml(data.staff_name)}</strong> has been created successfully!
                            </p>
                            <div style="background: #F7FCF7; padding: 12px; border-radius: 8px; margin-top: 10px;">
                                <p style="margin: 4px 0;"><strong>Username:</strong> ${escapeHtml(data.staff_username)}</p>
                                ${data.email_sent ? '<p style="margin: 4px 0; color: #16A34A;">📧 Credentials sent via email</p>' : ''}
                            </div>
                        </div>
                    `,
                    confirmButtonColor: '#16A34A',
                    confirmButtonText: 'Go to Staff List',
                    showCancelButton: true,
                    cancelButtonText: 'Add Another',
                    cancelButtonColor: '#6B7A7B'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'staff.php';
                    } else {
                        // Reset form
                        form.reset();
                        document.querySelectorAll('.form-input.error').forEach(el => {
                            el.classList.remove('error');
                        });
                        document.querySelectorAll('.form-error').forEach(el => {
                            el.remove();
                        });
                        // Reset password checkbox
                        generatePasswordCheckbox.checked = true;
                        generatePasswordCheckbox.dispatchEvent(new Event('change'));
                    }
                });
            } else {
                // Error message with SweetAlert
                let errorHtml = '<div style="text-align: left; padding: 10px;">';
                errorHtml += `<p style="color: #DC2626; margin-bottom: 10px;">${escapeHtml(data.message)}</p>`;
                
                if (data.errors) {
                    errorHtml += '<ul style="list-style: none; padding: 0; margin: 0;">';
                    for (const [field, error] of Object.entries(data.errors)) {
                        errorHtml += `<li style="padding: 4px 0; border-bottom: 1px solid #F3F4F6;">
                            <i class="fas fa-times-circle" style="color: #DC2626; margin-right: 8px;"></i>
                            ${escapeHtml(error)}
                        </li>`;
                    }
                    errorHtml += '</ul>';
                }
                errorHtml += '</div>';
                
                Swal.fire({
                    icon: 'error',
                    title: '❌ Validation Error',
                    html: errorHtml,
                    confirmButtonColor: '#DC2626',
                    confirmButtonText: 'Fix Errors'
                });
                
                // Highlight error fields
                if (data.errors) {
                    for (const [field, error] of Object.entries(data.errors)) {
                        const input = document.getElementById(field);
                        if (input) {
                            input.classList.add('error');
                            // Add error message below input
                            const parent = input.closest('.form-group');
                            if (parent) {
                                const existingError = parent.querySelector('.form-error');
                                if (!existingError) {
                                    const errorDiv = document.createElement('div');
                                    errorDiv.className = 'form-error';
                                    errorDiv.textContent = error;
                                    parent.appendChild(errorDiv);
                                }
                            }
                        }
                    }
                }
            }
        })
        .catch(error => {
            // Hide loading state
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#DC2626'
            });
            console.error('Error:', error);
        });
    });
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>