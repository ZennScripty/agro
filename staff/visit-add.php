<?php
/**
 * SAMRIDHI AGRO - Staff Add Visit
 * 
 * This page allows staff members to add new visits.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Add Visit';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login and permission
requireLogin();
requireRole('staff');
requirePermission('staff.visits.manage');

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
    'visit_type' => '',
    'agent_id' => 0,
    'shop_id' => 0,
    'visit_date' => date('Y-m-d'),
    'visit_time' => date('H:i'),
    'purpose' => '',
    'notes' => '',
    'status' => 'planned'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('staff/visit-add.php');
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'visit_type' => sanitizeInput($_POST['visit_type'] ?? ''),
        'agent_id' => (int)($_POST['agent_id'] ?? 0),
        'shop_id' => (int)($_POST['shop_id'] ?? 0),
        'visit_date' => sanitizeInput($_POST['visit_date'] ?? ''),
        'visit_time' => sanitizeInput($_POST['visit_time'] ?? ''),
        'purpose' => sanitizeInput($_POST['purpose'] ?? ''),
        'notes' => sanitizeInput($_POST['notes'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? 'planned')
    ];
    
    // Validation
    $hasErrors = false;
    
    // Visit Type - required
    if (empty($formData['visit_type'])) {
        $errors['visit_type'] = 'Visit type is required';
        $hasErrors = true;
    } elseif (!in_array($formData['visit_type'], ['agent_visit', 'shop_visit', 'delivery', 'survey', 'maintenance'])) {
        $errors['visit_type'] = 'Invalid visit type';
        $hasErrors = true;
    }
    
    // Agent ID - required if visit type is agent_visit
    if ($formData['visit_type'] === 'agent_visit') {
        if ($formData['agent_id'] <= 0) {
            $errors['agent_id'] = 'Please select an agent';
            $hasErrors = true;
        } else {
            $sql = "SELECT id FROM agents WHERE id = ? AND status = 'approved'";
            $agent = $db->fetchOne($sql, [$formData['agent_id']]);
            if (!$agent) {
                $errors['agent_id'] = 'Selected agent is not valid';
                $hasErrors = true;
            }
        }
    }
    
    // Shop ID - required if visit type is shop_visit or delivery
    if (in_array($formData['visit_type'], ['shop_visit', 'delivery'])) {
        if ($formData['shop_id'] <= 0) {
            $errors['shop_id'] = 'Please select a shop';
            $hasErrors = true;
        } else {
            $sql = "SELECT id FROM shops WHERE id = ? AND status = 'approved'";
            $shop = $db->fetchOne($sql, [$formData['shop_id']]);
            if (!$shop) {
                $errors['shop_id'] = 'Selected shop is not valid';
                $hasErrors = true;
            }
        }
    }
    
    // Visit Date - required
    if (empty($formData['visit_date'])) {
        $errors['visit_date'] = 'Visit date is required';
        $hasErrors = true;
    } else {
        $dateTimestamp = strtotime($formData['visit_date']);
        if (!$dateTimestamp) {
            $errors['visit_date'] = 'Invalid date format';
            $hasErrors = true;
        }
    }
    
    // Visit Time - required
    if (empty($formData['visit_time'])) {
        $errors['visit_time'] = 'Visit time is required';
        $hasErrors = true;
    }
    
    // Purpose - required
    if (empty($formData['purpose'])) {
        $errors['purpose'] = 'Purpose is required';
        $hasErrors = true;
    } elseif (strlen($formData['purpose']) < 5) {
        $errors['purpose'] = 'Purpose must be at least 5 characters';
        $hasErrors = true;
    }
    
    // Status - must be valid
    if (!in_array($formData['status'], ['planned', 'in_progress', 'completed', 'cancelled'])) {
        $errors['status'] = 'Invalid status';
        $hasErrors = true;
    }
    
    // If no errors, insert visit
    if (!$hasErrors) {
        try {
            $sql = "INSERT INTO staff_visits (
                        staff_id, agent_id, shop_id, visit_type,
                        visit_date, visit_time, purpose, notes,
                        status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $db->query($sql, [
                $_SESSION['user_id'],
                $formData['agent_id'] > 0 ? $formData['agent_id'] : null,
                $formData['shop_id'] > 0 ? $formData['shop_id'] : null,
                $formData['visit_type'],
                $formData['visit_date'],
                $formData['visit_time'],
                $formData['purpose'],
                $formData['notes'],
                $formData['status']
            ]);
            
            $visitId = $db->lastInsertId();
            
            logActivity(
                'create',
                $_SESSION['user_id'],
                'visits',
                'Added new visit: ' . $formData['visit_type'] . ' on ' . $formData['visit_date']
            );
            
            setFlashMessage('success', 'Visit added successfully!');
            redirect('staff/visits.php');
            exit;
            
        } catch (Exception $e) {
            error_log('Visit creation error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to add visit. Please try again.');
            redirect('staff/visit-add.php');
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
    
    .visit-type-selector {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .visit-type-selector .type-option {
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
    
    .visit-type-selector .type-option:hover {
        border-color: #16A34A;
        background: #F0FDF4;
    }
    
    .visit-type-selector .type-option.selected {
        border-color: #16A34A;
        background: #DCFCE7;
        color: #065F46;
    }
    
    .visit-type-selector .type-option i {
        display: block;
        font-size: 20px;
        margin-bottom: 4px;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .visit-type-selector .type-option {
            min-width: 80px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle" style="color: #16A34A;"></i>
            Add New Visit
        </h3>
        <a href="visits.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Visits
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
    
    <form method="POST" action="" id="visitForm" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid">
            <!-- Left Column -->
            <div>
                <!-- Visit Type -->
                <div class="form-group">
                    <label class="form-label">
                        Visit Type <span class="required">*</span>
                    </label>
                    <div class="visit-type-selector" id="visitTypeSelector">
                        <div class="type-option <?php echo $formData['visit_type'] === 'agent_visit' ? 'selected' : ''; ?>" data-value="agent_visit" onclick="selectVisitType('agent_visit')">
                            <i class="fas fa-user-tie"></i>
                            Agent Visit
                        </div>
                        <div class="type-option <?php echo $formData['visit_type'] === 'shop_visit' ? 'selected' : ''; ?>" data-value="shop_visit" onclick="selectVisitType('shop_visit')">
                            <i class="fas fa-store"></i>
                            Shop Visit
                        </div>
                        <div class="type-option <?php echo $formData['visit_type'] === 'delivery' ? 'selected' : ''; ?>" data-value="delivery" onclick="selectVisitType('delivery')">
                            <i class="fas fa-truck"></i>
                            Delivery
                        </div>
                        <div class="type-option <?php echo $formData['visit_type'] === 'survey' ? 'selected' : ''; ?>" data-value="survey" onclick="selectVisitType('survey')">
                            <i class="fas fa-clipboard-list"></i>
                            Survey
                        </div>
                        <div class="type-option <?php echo $formData['visit_type'] === 'maintenance' ? 'selected' : ''; ?>" data-value="maintenance" onclick="selectVisitType('maintenance')">
                            <i class="fas fa-tools"></i>
                            Maintenance
                        </div>
                    </div>
                    <input type="hidden" name="visit_type" id="visit_type" value="<?php echo escapeHtml($formData['visit_type']); ?>">
                    <?php if (isset($errors['visit_type'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['visit_type']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Purpose -->
                <div class="form-group">
                    <label class="form-label" for="purpose">
                        <i class="fas fa-bullseye" style="color: #16A34A;"></i>
                        Purpose <span class="required">*</span>
                    </label>
                    <textarea 
                        id="purpose" 
                        name="purpose" 
                        class="form-input <?php echo isset($errors['purpose']) ? 'error' : ''; ?>"
                        rows="2"
                        placeholder="What is the purpose of this visit?"
                        required
                    ><?php echo escapeHtml($formData['purpose']); ?></textarea>
                    <?php if (isset($errors['purpose'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['purpose']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Agent Selection (conditional) -->
                <div class="form-group" id="agentGroup" style="display: <?php echo $formData['visit_type'] === 'agent_visit' ? 'block' : 'none'; ?>;">
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
                <div class="form-group" id="shopGroup" style="display: <?php echo in_array($formData['visit_type'], ['shop_visit', 'delivery']) ? 'block' : 'none'; ?>;">
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
                
                <!-- Date & Time -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" for="visit_date">
                            <i class="fas fa-calendar" style="color: #16A34A;"></i>
                            Date <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="visit_date" 
                            name="visit_date" 
                            class="form-input <?php echo isset($errors['visit_date']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['visit_date']); ?>"
                            required
                        >
                        <?php if (isset($errors['visit_date'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['visit_date']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="visit_time">
                            <i class="fas fa-clock" style="color: #16A34A;"></i>
                            Time <span class="required">*</span>
                        </label>
                        <input 
                            type="time" 
                            id="visit_time" 
                            name="visit_time" 
                            class="form-input <?php echo isset($errors['visit_time']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['visit_time']); ?>"
                            required
                        >
                        <?php if (isset($errors['visit_time'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['visit_time']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="form-group">
                    <label class="form-label" for="notes">
                        <i class="fas fa-sticky-note" style="color: #16A34A;"></i>
                        Notes
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        class="form-input"
                        rows="2"
                        placeholder="Any additional notes about this visit"
                    ><?php echo escapeHtml($formData['notes']); ?></textarea>
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label" for="status">
                        <i class="fas fa-circle" style="color: #16A34A;"></i>
                        Status
                    </label>
                    <select id="status" name="status" class="form-input">
                        <option value="planned" <?php echo $formData['status'] === 'planned' ? 'selected' : ''; ?>>Planned</option>
                        <option value="in_progress" <?php echo $formData['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $formData['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $formData['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Add Visit</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
            
            <a href="visits.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
// Visit Type Selector
function selectVisitType(type) {
    // Update hidden input
    document.getElementById('visit_type').value = type;
    
    // Update UI
    document.querySelectorAll('.visit-type-selector .type-option').forEach(el => {
        el.classList.toggle('selected', el.dataset.value === type);
    });
    
    // Show/hide conditional fields
    document.getElementById('agentGroup').style.display = type === 'agent_visit' ? 'block' : 'none';
    document.getElementById('shopGroup').style.display = (type === 'shop_visit' || type === 'delivery') ? 'block' : 'none';
    
    // Make fields required based on type
    document.getElementById('agent_id').required = type === 'agent_visit';
    document.getElementById('shop_id').required = (type === 'shop_visit' || type === 'delivery');
}

// Form submission loading state
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('visitForm');
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