<?php
/**
 * SAMRIDHI AGRO - Staff Add Lead
 * 
 * This page allows staff members to add new leads.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Add Lead';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login and permission
requireLogin();
requireRole('staff');
requirePermission('staff.leads.manage');

// Get database instance
$db = getDB();

// Get staff data
$sql = "SELECT u.*, sp.department, sp.designation 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ?";
$staff = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Get agents for dropdown (approved agents only)
$sql = "SELECT a.id, u.full_name, a.agent_code 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.status = 'approved' 
        ORDER BY u.full_name";
$agentList = $db->fetchAll($sql);

// Get shops for dropdown (approved shops only)
$sql = "SELECT s.id, s.shop_name, s.shop_code 
        FROM shops s 
        WHERE s.status = 'approved' 
        ORDER BY s.shop_name";
$shopList = $db->fetchAll($sql);

// Initialize variables
$errors = [];
$formData = [
    'lead_type' => '',
    'title' => '',
    'description' => '',
    'agent_id' => 0,
    'shop_id' => 0,
    'contact_name' => '',
    'contact_phone' => '',
    'contact_email' => '',
    'status' => 'new',
    'priority' => 'medium',
    'follow_up_date' => '',
    'notes' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('staff/lead-add.php');
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'lead_type' => sanitizeInput($_POST['lead_type'] ?? ''),
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'agent_id' => (int)($_POST['agent_id'] ?? 0),
        'shop_id' => (int)($_POST['shop_id'] ?? 0),
        'contact_name' => sanitizeInput($_POST['contact_name'] ?? ''),
        'contact_phone' => sanitizeInput($_POST['contact_phone'] ?? ''),
        'contact_email' => sanitizeInput($_POST['contact_email'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? 'new'),
        'priority' => sanitizeInput($_POST['priority'] ?? 'medium'),
        'follow_up_date' => sanitizeInput($_POST['follow_up_date'] ?? ''),
        'notes' => sanitizeInput($_POST['notes'] ?? '')
    ];
    
    // Validation
    $hasErrors = false;
    
    // Lead Type - required
    if (empty($formData['lead_type'])) {
        $errors['lead_type'] = 'Lead type is required';
        $hasErrors = true;
    } elseif (!in_array($formData['lead_type'], ['agent', 'shop', 'product_enquiry', 'service'])) {
        $errors['lead_type'] = 'Invalid lead type';
        $hasErrors = true;
    }
    
    // Title - required
    if (empty($formData['title'])) {
        $errors['title'] = 'Title is required';
        $hasErrors = true;
    } elseif (strlen($formData['title']) < 3) {
        $errors['title'] = 'Title must be at least 3 characters';
        $hasErrors = true;
    }
    
    // Agent ID - required if lead type is agent
    if ($formData['lead_type'] === 'agent') {
        if ($formData['agent_id'] <= 0) {
            $errors['agent_id'] = 'Please select an agent';
            $hasErrors = true;
        } else {
            // Verify agent exists
            $sql = "SELECT id FROM agents WHERE id = ? AND status = 'approved'";
            $agent = $db->fetchOne($sql, [$formData['agent_id']]);
            if (!$agent) {
                $errors['agent_id'] = 'Selected agent is not valid';
                $hasErrors = true;
            }
        }
    }
    
    // Shop ID - required if lead type is shop
    if ($formData['lead_type'] === 'shop') {
        if ($formData['shop_id'] <= 0) {
            $errors['shop_id'] = 'Please select a shop';
            $hasErrors = true;
        } else {
            // Verify shop exists
            $sql = "SELECT id FROM shops WHERE id = ? AND status = 'approved'";
            $shop = $db->fetchOne($sql, [$formData['shop_id']]);
            if (!$shop) {
                $errors['shop_id'] = 'Selected shop is not valid';
                $hasErrors = true;
            }
        }
    }
    
    // Contact Name - optional, but validate if provided
    if (!empty($formData['contact_name']) && strlen($formData['contact_name']) < 2) {
        $errors['contact_name'] = 'Contact name must be at least 2 characters';
        $hasErrors = true;
    }
    
    // Contact Phone - optional, validate if provided
    if (!empty($formData['contact_phone']) && !isValidPhone($formData['contact_phone'])) {
        $errors['contact_phone'] = 'Please enter a valid 10-digit phone number';
        $hasErrors = true;
    }
    
    // Contact Email - optional, validate if provided
    if (!empty($formData['contact_email']) && !isValidEmail($formData['contact_email'])) {
        $errors['contact_email'] = 'Please enter a valid email address';
        $hasErrors = true;
    }
    
    // Status - must be valid
    if (!in_array($formData['status'], ['new', 'contacted', 'qualified', 'converted', 'lost'])) {
        $errors['status'] = 'Invalid status';
        $hasErrors = true;
    }
    
    // Priority - must be valid
    if (!in_array($formData['priority'], ['low', 'medium', 'high', 'urgent'])) {
        $errors['priority'] = 'Invalid priority';
        $hasErrors = true;
    }
    
    // Follow-up date - optional, validate if provided
    if (!empty($formData['follow_up_date'])) {
        $followUpTimestamp = strtotime($formData['follow_up_date']);
        if (!$followUpTimestamp) {
            $errors['follow_up_date'] = 'Invalid date format';
            $hasErrors = true;
        }
    }
    
    // If no errors, insert lead
    if (!$hasErrors) {
        try {
            $sql = "INSERT INTO staff_leads (
                        staff_id, agent_id, shop_id, lead_type,
                        title, description, contact_name, contact_phone,
                        contact_email, status, priority, follow_up_date,
                        notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $db->query($sql, [
                $_SESSION['user_id'],
                $formData['agent_id'] > 0 ? $formData['agent_id'] : null,
                $formData['shop_id'] > 0 ? $formData['shop_id'] : null,
                $formData['lead_type'],
                $formData['title'],
                $formData['description'],
                $formData['contact_name'],
                $formData['contact_phone'],
                $formData['contact_email'],
                $formData['status'],
                $formData['priority'],
                !empty($formData['follow_up_date']) ? $formData['follow_up_date'] : null,
                $formData['notes']
            ]);
            
            $leadId = $db->lastInsertId();
            
            logActivity(
                'create',
                $_SESSION['user_id'],
                'leads',
                'Added new lead: ' . $formData['title'] . ' (Type: ' . $formData['lead_type'] . ')'
            );
            
            setFlashMessage('success', 'Lead added successfully!');
            redirect('staff/leads.php');
            exit;
            
        } catch (Exception $e) {
            error_log('Lead creation error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to add lead. Please try again.');
            redirect('staff/lead-add.php');
            exit;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    
    .form-label .required {
        color: #DC2626;
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
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .lead-type-selector {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .lead-type-selector .type-option {
        flex: 1;
        min-width: 100px;
        padding: 10px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 500;
        color: #4A5B5D;
    }
    
    .lead-type-selector .type-option:hover {
        border-color: #16A34A;
        background: #F0FDF4;
    }
    
    .lead-type-selector .type-option.selected {
        border-color: #16A34A;
        background: #DCFCE7;
        color: #065F46;
    }
    
    .lead-type-selector .type-option i {
        display: block;
        font-size: 20px;
        margin-bottom: 4px;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .lead-type-selector .type-option {
            min-width: 80px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle" style="color: #16A34A;"></i>
            Add New Lead
        </h3>
        <a href="leads.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Leads
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
    
    <form method="POST" action="" id="leadForm" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid">
            <!-- Left Column -->
            <div>
                <!-- Lead Type -->
                <div class="form-group">
                    <label class="form-label">
                        Lead Type <span class="required">*</span>
                    </label>
                    <div class="lead-type-selector" id="leadTypeSelector">
                        <div class="type-option <?php echo $formData['lead_type'] === 'agent' ? 'selected' : ''; ?>" data-value="agent" onclick="selectLeadType('agent')">
                            <i class="fas fa-user-tie"></i>
                            Agent
                        </div>
                        <div class="type-option <?php echo $formData['lead_type'] === 'shop' ? 'selected' : ''; ?>" data-value="shop" onclick="selectLeadType('shop')">
                            <i class="fas fa-store"></i>
                            Shop
                        </div>
                        <div class="type-option <?php echo $formData['lead_type'] === 'product_enquiry' ? 'selected' : ''; ?>" data-value="product_enquiry" onclick="selectLeadType('product_enquiry')">
                            <i class="fas fa-box"></i>
                            Product Enquiry
                        </div>
                        <div class="type-option <?php echo $formData['lead_type'] === 'service' ? 'selected' : ''; ?>" data-value="service" onclick="selectLeadType('service')">
                            <i class="fas fa-tools"></i>
                            Service
                        </div>
                    </div>
                    <input type="hidden" name="lead_type" id="lead_type" value="<?php echo escapeHtml($formData['lead_type']); ?>">
                    <?php if (isset($errors['lead_type'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['lead_type']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Title -->
                <div class="form-group">
                    <label class="form-label" for="title">
                        <i class="fas fa-heading" style="color: #16A34A;"></i>
                        Title <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input <?php echo isset($errors['title']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['title']); ?>"
                        placeholder="e.g., Interested in bulk order"
                        required
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['title']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label class="form-label" for="description">
                        <i class="fas fa-align-left" style="color: #16A34A;"></i>
                        Description
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-input"
                        rows="3"
                        placeholder="Detailed description of the lead"
                    ><?php echo escapeHtml($formData['description']); ?></textarea>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Agent Selection (conditional) -->
                <div class="form-group" id="agentGroup" style="display: <?php echo $formData['lead_type'] === 'agent' ? 'block' : 'none'; ?>;">
                    <label class="form-label" for="agent_id">
                        <i class="fas fa-user-tie" style="color: #7C3AED;"></i>
                        Select Agent <span class="required">*</span>
                    </label>
                    <select id="agent_id" name="agent_id" class="form-input <?php echo isset($errors['agent_id']) ? 'error' : ''; ?>">
                        <option value="0">Select an agent</option>
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
                </div>
                
                <!-- Shop Selection (conditional) -->
                <div class="form-group" id="shopGroup" style="display: <?php echo $formData['lead_type'] === 'shop' ? 'block' : 'none'; ?>;">
                    <label class="form-label" for="shop_id">
                        <i class="fas fa-store" style="color: #16A34A;"></i>
                        Select Shop <span class="required">*</span>
                    </label>
                    <select id="shop_id" name="shop_id" class="form-input <?php echo isset($errors['shop_id']) ? 'error' : ''; ?>">
                        <option value="0">Select a shop</option>
                        <?php foreach ($shopList as $shop): ?>
                            <option value="<?php echo $shop['id']; ?>" 
                                <?php echo $formData['shop_id'] == $shop['id'] ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($shop['shop_name']); ?> (<?php echo escapeHtml($shop['shop_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['shop_id'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['shop_id']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Contact Information -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-address-card" style="color: #16A34A;"></i>
                        Contact Information
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <input 
                                type="text" 
                                name="contact_name" 
                                class="form-input <?php echo isset($errors['contact_name']) ? 'error' : ''; ?>"
                                value="<?php echo escapeHtml($formData['contact_name']); ?>"
                                placeholder="Contact Name"
                            >
                            <?php if (isset($errors['contact_name'])): ?>
                                <div class="form-error"><?php echo escapeHtml($errors['contact_name']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input 
                                type="tel" 
                                name="contact_phone" 
                                class="form-input <?php echo isset($errors['contact_phone']) ? 'error' : ''; ?>"
                                value="<?php echo escapeHtml($formData['contact_phone']); ?>"
                                placeholder="Phone Number"
                            >
                            <?php if (isset($errors['contact_phone'])): ?>
                                <div class="form-error"><?php echo escapeHtml($errors['contact_phone']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <input 
                                type="email" 
                                name="contact_email" 
                                class="form-input <?php echo isset($errors['contact_email']) ? 'error' : ''; ?>"
                                value="<?php echo escapeHtml($formData['contact_email']); ?>"
                                placeholder="Email Address"
                            >
                            <?php if (isset($errors['contact_email'])): ?>
                                <div class="form-error"><?php echo escapeHtml($errors['contact_email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Status & Priority -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" for="status">
                            <i class="fas fa-circle" style="color: #16A34A;"></i>
                            Status
                        </label>
                        <select id="status" name="status" class="form-input">
                            <option value="new" <?php echo $formData['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="contacted" <?php echo $formData['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                            <option value="qualified" <?php echo $formData['status'] === 'qualified' ? 'selected' : ''; ?>>Qualified</option>
                            <option value="converted" <?php echo $formData['status'] === 'converted' ? 'selected' : ''; ?>>Converted</option>
                            <option value="lost" <?php echo $formData['status'] === 'lost' ? 'selected' : ''; ?>>Lost</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="priority">
                            <i class="fas fa-flag" style="color: #D97706;"></i>
                            Priority
                        </label>
                        <select id="priority" name="priority" class="form-input">
                            <option value="low" <?php echo $formData['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $formData['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $formData['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $formData['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                </div>
                
                <!-- Follow-up Date -->
                <div class="form-group">
                    <label class="form-label" for="follow_up_date">
                        <i class="fas fa-calendar-alt" style="color: #16A34A;"></i>
                        Follow-up Date
                    </label>
                    <input 
                        type="date" 
                        id="follow_up_date" 
                        name="follow_up_date" 
                        class="form-input <?php echo isset($errors['follow_up_date']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['follow_up_date']); ?>"
                    >
                    <?php if (isset($errors['follow_up_date'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['follow_up_date']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> When to follow up on this lead
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="form-group">
            <label class="form-label" for="notes">
                <i class="fas fa-sticky-note" style="color: #16A34A;"></i>
                Additional Notes
            </label>
            <textarea 
                id="notes" 
                name="notes" 
                class="form-input"
                rows="2"
                placeholder="Any additional notes about this lead"
            ><?php echo escapeHtml($formData['notes']); ?></textarea>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Add Lead</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
            
            <a href="leads.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
// Lead Type Selector
function selectLeadType(type) {
    // Update hidden input
    document.getElementById('lead_type').value = type;
    
    // Update UI
    document.querySelectorAll('.lead-type-selector .type-option').forEach(el => {
        el.classList.toggle('selected', el.dataset.value === type);
    });
    
    // Show/hide conditional fields
    document.getElementById('agentGroup').style.display = type === 'agent' ? 'block' : 'none';
    document.getElementById('shopGroup').style.display = type === 'shop' ? 'block' : 'none';
    
    // Make fields required based on type
    document.getElementById('agent_id').required = type === 'agent';
    document.getElementById('shop_id').required = type === 'shop';
}

// Form submission loading state
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('leadForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'inline-block';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>