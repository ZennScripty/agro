<?php
/**
 * SAMRIDHI AGRO - System Settings
 * 
 * This page allows administrators to manage system settings
 * like site name, business details, email configuration, etc.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: Set page title and include admin header
// ============================================

// Set page title
$pageTitle = 'System Settings';

// Include admin header (which already includes all configs)
require_once '../includes/admin_header.php';

// Require admin login and permission
requireLogin();
requireRole('admin');
requirePermission('settings.view');

// Get database instance
$db = getDB();

// ============================================
// GET CURRENT SETTINGS
// ============================================

// Get all settings
$sql = "SELECT * FROM settings";
$allSettings = $db->fetchAll($sql);

// Convert to associative array
$settings = [];
foreach ($allSettings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Initialize form data with defaults
$formData = [
    // General Settings
    'site_name' => $settings['site_name'] ?? 'Samridhi Agro',
    'site_tagline' => $settings['site_tagline'] ?? 'Farm to Shop Platform',
    'site_email' => $settings['site_email'] ?? 'info@samridhiagro.com',
    'site_phone' => $settings['site_phone'] ?? '',
    'site_address' => $settings['site_address'] ?? '',
    
    // Business Settings
    'business_name' => $settings['business_name'] ?? 'Samridhi Agro Private Limited',
    'business_gst' => $settings['business_gst'] ?? '',
    'business_pan' => $settings['business_pan'] ?? '',
    'business_license' => $settings['business_license'] ?? '',
    
    // Order Settings
    'order_prefix' => $settings['order_prefix'] ?? 'ORD',
    'order_auto_approve' => $settings['order_auto_approve'] ?? '0',
    'order_timeout' => $settings['order_timeout'] ?? '30',
    
    // Payment Settings
    'payment_methods' => $settings['payment_methods'] ?? 'cash,upi,bank_transfer,card',
    'default_currency' => $settings['default_currency'] ?? 'INR',
    'currency_symbol' => $settings['currency_symbol'] ?? '₹',
    
    // Email Settings
    'email_from' => $settings['email_from'] ?? 'noreply@samridhiagro.com',
    'email_from_name' => $settings['email_from_name'] ?? 'Samridhi Agro',
    'smtp_host' => $settings['smtp_host'] ?? '',
    'smtp_port' => $settings['smtp_port'] ?? '587',
    'smtp_secure' => $settings['smtp_secure'] ?? 'tls',
    'smtp_username' => $settings['smtp_username'] ?? '',
    'smtp_password' => $settings['smtp_password'] ?? '',
    
    // Commission Settings
    'default_commission' => $settings['default_commission'] ?? '5.00',
    'agent_commission' => $settings['agent_commission'] ?? '10.00',
    
    // Maintenance
    'maintenance_mode' => $settings['maintenance_mode'] ?? '0',
    'maintenance_message' => $settings['maintenance_message'] ?? 'We are currently undergoing maintenance. Please check back later.'
];

// ============================================
// HANDLE SETTINGS UPDATE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/settings.php');
        exit;
    }
    
    requirePermission('settings.update');
    
    $errors = [];
    
    // Get form data
    $formData = [
        // General Settings
        'site_name' => sanitizeInput($_POST['site_name'] ?? 'Samridhi Agro'),
        'site_tagline' => sanitizeInput($_POST['site_tagline'] ?? ''),
        'site_email' => sanitizeInput($_POST['site_email'] ?? ''),
        'site_phone' => sanitizeInput($_POST['site_phone'] ?? ''),
        'site_address' => sanitizeInput($_POST['site_address'] ?? ''),
        
        // Business Settings
        'business_name' => sanitizeInput($_POST['business_name'] ?? ''),
        'business_gst' => sanitizeInput($_POST['business_gst'] ?? ''),
        'business_pan' => sanitizeInput($_POST['business_pan'] ?? ''),
        'business_license' => sanitizeInput($_POST['business_license'] ?? ''),
        
        // Order Settings
        'order_prefix' => sanitizeInput($_POST['order_prefix'] ?? 'ORD'),
        'order_auto_approve' => isset($_POST['order_auto_approve']) ? '1' : '0',
        'order_timeout' => (int)($_POST['order_timeout'] ?? 30),
        
        // Payment Settings
        'payment_methods' => sanitizeInput($_POST['payment_methods'] ?? ''),
        'default_currency' => sanitizeInput($_POST['default_currency'] ?? 'INR'),
        'currency_symbol' => sanitizeInput($_POST['currency_symbol'] ?? '₹'),
        
        // Email Settings
        'email_from' => sanitizeInput($_POST['email_from'] ?? ''),
        'email_from_name' => sanitizeInput($_POST['email_from_name'] ?? ''),
        'smtp_host' => sanitizeInput($_POST['smtp_host'] ?? ''),
        'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
        'smtp_secure' => sanitizeInput($_POST['smtp_secure'] ?? 'tls'),
        'smtp_username' => sanitizeInput($_POST['smtp_username'] ?? ''),
        'smtp_password' => sanitizeInput($_POST['smtp_password'] ?? ''),
        
        // Commission Settings
        'default_commission' => (float)($_POST['default_commission'] ?? 5.00),
        'agent_commission' => (float)($_POST['agent_commission'] ?? 10.00),
        
        // Maintenance
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'maintenance_message' => sanitizeInput($_POST['maintenance_message'] ?? '')
    ];
    
    // Validate
    if (!isValidEmail($formData['site_email'])) {
        $errors[] = 'Please enter a valid site email address';
    }
    
    if (!empty($formData['site_phone']) && !isValidPhone($formData['site_phone'])) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    if (!empty($formData['business_gst']) && !isValidGST($formData['business_gst'])) {
        $errors[] = 'Please enter a valid GST number';
    }
    
    if (!empty($formData['business_pan']) && !isValidPAN($formData['business_pan'])) {
        $errors[] = 'Please enter a valid PAN number';
    }
    
    if ($formData['order_timeout'] < 1 || $formData['order_timeout'] > 1440) {
        $errors[] = 'Order timeout must be between 1 and 1440 minutes';
    }
    
    if ($formData['default_commission'] < 0 || $formData['default_commission'] > 100) {
        $errors[] = 'Default commission must be between 0 and 100';
    }
    
    if ($formData['agent_commission'] < 0 || $formData['agent_commission'] > 100) {
        $errors[] = 'Agent commission must be between 0 and 100';
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Update or insert each setting
            foreach ($formData as $key => $value) {
                // Check if setting exists
                $sql = "SELECT id FROM settings WHERE setting_key = ?";
                $existing = $db->fetchOne($sql, [$key]);
                
                if ($existing) {
                    // Update existing
                    $sql = "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
                    $db->query($sql, [$value, $key]);
                } else {
                    // Insert new
                    $sql = "INSERT INTO settings (setting_key, setting_value, category, created_at, updated_at) 
                            VALUES (?, ?, 'general', NOW(), NOW())";
                    $db->query($sql, [$key, $value]);
                }
            }
            
            $db->commit();
            
            logActivity(
                'update',
                $_SESSION['user_id'],
                'settings',
                'Updated system settings'
            );
            
            setFlashMessage('success', 'Settings updated successfully!');
            redirect('admin/settings.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Settings update error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to update settings. Please try again.');
            redirect('admin/settings.php');
            exit;
        }
    } else {
        // Show errors
        $errorMessage = '<ul style="margin: 0; padding-left: 20px;">';
        foreach ($errors as $error) {
            $errorMessage .= '<li>' . escapeHtml($error) . '</li>';
        }
        $errorMessage .= '</ul>';
        setFlashMessage('error', $errorMessage);
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// HTML CONTENT
// ============================================
?>

<style>
    .settings-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .settings-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 24px;
    }
    
    .settings-card .card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #F0FDF4;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .settings-card .card-title .badge {
        background: #DCFCE7;
        color: #065F46;
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: 500;
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
    
    .form-label .optional {
        font-weight: 400;
        color: #6B7A7B;
        font-size: 12px;
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
    
    .form-input:disabled {
        background: #F3F4F6;
        cursor: not-allowed;
    }
    
    select.form-input {
        appearance: auto;
    }
    
    textarea.form-input {
        resize: vertical;
        min-height: 60px;
    }
    
    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 4px;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #16A34A;
        cursor: pointer;
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
        margin-top: 8px;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .form-row-3 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="settings-container">
    <form method="POST" action="" id="settingsForm">
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <!-- General Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-globe" style="color: #16A34A;"></i>
                General Settings
                <span class="badge">Site Configuration</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="site_name">Site Name <span style="color: #DC2626;">*</span></label>
                    <input 
                        type="text" 
                        id="site_name" 
                        name="site_name" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['site_name']); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="site_tagline">Site Tagline</label>
                    <input 
                        type="text" 
                        id="site_tagline" 
                        name="site_tagline" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['site_tagline']); ?>"
                        placeholder="e.g., Farm to Shop Platform"
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="site_email">Site Email <span style="color: #DC2626;">*</span></label>
                    <input 
                        type="email" 
                        id="site_email" 
                        name="site_email" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['site_email']); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="site_phone">Site Phone</label>
                    <input 
                        type="tel" 
                        id="site_phone" 
                        name="site_phone" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['site_phone']); ?>"
                        placeholder="Enter 10-digit phone number"
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="site_address">Site Address</label>
                <textarea 
                    id="site_address" 
                    name="site_address" 
                    class="form-input"
                    rows="2"
                    placeholder="Enter full address"
                ><?php echo escapeHtml($formData['site_address']); ?></textarea>
            </div>
        </div>
        
        <!-- Business Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-building" style="color: #16A34A;"></i>
                Business Settings
                <span class="badge">Company Details</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="business_name">Business Name</label>
                <input 
                    type="text" 
                    id="business_name" 
                    name="business_name" 
                    class="form-input"
                    value="<?php echo escapeHtml($formData['business_name']); ?>"
                    placeholder="Enter registered business name"
                >
            </div>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label" for="business_gst">GST Number <span class="optional">(Optional)</span></label>
                    <input 
                        type="text" 
                        id="business_gst" 
                        name="business_gst" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['business_gst']); ?>"
                        placeholder="e.g., 22AAAAA0000A1Z5"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="business_pan">PAN Number <span class="optional">(Optional)</span></label>
                    <input 
                        type="text" 
                        id="business_pan" 
                        name="business_pan" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['business_pan']); ?>"
                        placeholder="e.g., ABCDE1234F"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="business_license">License Number <span class="optional">(Optional)</span></label>
                    <input 
                        type="text" 
                        id="business_license" 
                        name="business_license" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['business_license']); ?>"
                        placeholder="Enter license number"
                    >
                </div>
            </div>
        </div>
        
        <!-- Order Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-shopping-cart" style="color: #16A34A;"></i>
                Order Settings
                <span class="badge">Order Management</span>
            </div>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label" for="order_prefix">Order Prefix</label>
                    <input 
                        type="text" 
                        id="order_prefix" 
                        name="order_prefix" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['order_prefix']); ?>"
                        placeholder="e.g., ORD"
                    >
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Prefix for order numbers (e.g., ORD-2024-0001)
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="order_timeout">Order Timeout (minutes)</label>
                    <input 
                        type="number" 
                        id="order_timeout" 
                        name="order_timeout" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['order_timeout']); ?>"
                        min="1"
                        max="1440"
                    >
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Time after which pending orders expire (1-1440 minutes)
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <label class="checkbox-group">
                        <input type="checkbox" name="order_auto_approve" value="1" 
                            <?php echo $formData['order_auto_approve'] == '1' ? 'checked' : ''; ?>>
                        <span>Auto-approve orders</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Payment Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-credit-card" style="color: #16A34A;"></i>
                Payment Settings
                <span class="badge">Payment Configuration</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="payment_methods">Payment Methods</label>
                    <input 
                        type="text" 
                        id="payment_methods" 
                        name="payment_methods" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['payment_methods']); ?>"
                        placeholder="e.g., cash,upi,bank_transfer,card"
                    >
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Comma-separated list: cash, upi, bank_transfer, card
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="default_currency">Default Currency</label>
                        <select id="default_currency" name="default_currency" class="form-input">
                            <option value="INR" <?php echo $formData['default_currency'] === 'INR' ? 'selected' : ''; ?>>INR - Indian Rupee</option>
                            <option value="USD" <?php echo $formData['default_currency'] === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo $formData['default_currency'] === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                            <option value="GBP" <?php echo $formData['default_currency'] === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="currency_symbol">Currency Symbol</label>
                        <input 
                            type="text" 
                            id="currency_symbol" 
                            name="currency_symbol" 
                            class="form-input"
                            value="<?php echo escapeHtml($formData['currency_symbol']); ?>"
                            placeholder="e.g., ₹, $, €"
                            maxlength="5"
                        >
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Commission Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-percentage" style="color: #16A34A;"></i>
                Commission Settings
                <span class="badge">Commission Rates</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="default_commission">Default Commission (%)</label>
                    <input 
                        type="number" 
                        id="default_commission" 
                        name="default_commission" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['default_commission']); ?>"
                        step="0.01"
                        min="0"
                        max="100"
                    >
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Default commission rate for all shops (0-100%)
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="agent_commission">Agent Commission (%)</label>
                    <input 
                        type="number" 
                        id="agent_commission" 
                        name="agent_commission" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['agent_commission']); ?>"
                        step="0.01"
                        min="0"
                        max="100"
                    >
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Commission rate for agents (0-100%)
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-envelope" style="color: #16A34A;"></i>
                Email Settings
                <span class="badge">SMTP Configuration</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email_from">From Email</label>
                    <input 
                        type="email" 
                        id="email_from" 
                        name="email_from" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['email_from']); ?>"
                        placeholder="noreply@samridhiagro.com"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email_from_name">From Name</label>
                    <input 
                        type="text" 
                        id="email_from_name" 
                        name="email_from_name" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['email_from_name']); ?>"
                        placeholder="Samridhi Agro"
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="smtp_host">SMTP Host</label>
                    <input 
                        type="text" 
                        id="smtp_host" 
                        name="smtp_host" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['smtp_host']); ?>"
                        placeholder="smtp.gmail.com"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="smtp_port">SMTP Port</label>
                    <input 
                        type="number" 
                        id="smtp_port" 
                        name="smtp_port" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['smtp_port']); ?>"
                        placeholder="587"
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="smtp_secure">SMTP Encryption</label>
                    <select id="smtp_secure" name="smtp_secure" class="form-input">
                        <option value="tls" <?php echo $formData['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo $formData['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo $formData['smtp_secure'] === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="smtp_username">SMTP Username</label>
                    <input 
                        type="text" 
                        id="smtp_username" 
                        name="smtp_username" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['smtp_username']); ?>"
                        placeholder="your-email@gmail.com"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="smtp_password">SMTP Password</label>
                    <input 
                        type="password" 
                        id="smtp_password" 
                        name="smtp_password" 
                        class="form-input"
                        value="<?php echo escapeHtml($formData['smtp_password']); ?>"
                        placeholder="Enter SMTP password"
                    >
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Leave blank to keep existing password
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Maintenance Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-tools" style="color: #16A34A;"></i>
                Maintenance Settings
                <span class="badge">System Maintenance</span>
            </div>
            
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="maintenance_mode" value="1" 
                        <?php echo $formData['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                    <span>Enable Maintenance Mode</span>
                </label>
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i> When enabled, only admins can access the site. Other users will see the maintenance message.
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="maintenance_message">Maintenance Message</label>
                <textarea 
                    id="maintenance_message" 
                    name="maintenance_message" 
                    class="form-input"
                    rows="2"
                ><?php echo escapeHtml($formData['maintenance_message']); ?></textarea>
            </div>
        </div>
        
        <!-- Submit -->
        <div style="margin-top: 8px;">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Save All Settings
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>