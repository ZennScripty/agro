<?php
/**
 * SAMRIDHI AGRO - Staff Lead View
 * 
 * This page displays detailed information about a specific lead.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Lead Details';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login
requireLogin();
requireRole('staff');

// Get database instance
$db = getDB();

// Get lead ID
$leadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($leadId <= 0) {
    setFlashMessage('error', 'Invalid lead ID.');
    redirect('staff/leads.php');
    exit;
}

// Get lead details
$sql = "SELECT sl.*, 
        a.full_name as agent_name, a.agent_code,
        s.shop_name, s.shop_code,
        u.full_name as staff_name
        FROM staff_leads sl
        LEFT JOIN agents ag ON sl.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        LEFT JOIN shops s ON sl.shop_id = s.id
        JOIN users u ON sl.staff_id = u.id
        WHERE sl.id = ? AND sl.staff_id = ?";
$lead = $db->fetchOne($sql, [$leadId, $_SESSION['user_id']]);

if (!$lead) {
    setFlashMessage('error', 'Lead not found.');
    redirect('staff/leads.php');
    exit;
}

$csrfToken = generateCsrfToken();
?>

<style>
    .lead-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 16px 20px;
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .lead-header .lead-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
    }
    
    .lead-header .lead-meta {
        font-size: 14px;
        color: #4A5B5D;
        margin-top: 4px;
    }
    
    .detail-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
    }
    
    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
    }
    
    .detail-row {
        display: flex;
        padding: 4px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-size: 13px;
        font-weight: 500;
        color: #6B7A7B;
        width: 140px;
        flex-shrink: 0;
    }
    
    .detail-value {
        font-size: 13px;
        color: #052E16;
        flex: 1;
    }
    
    .badge-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .priority-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .priority-badge.low { background: #F3F4F6; color: #6B7A7B; }
    .priority-badge.medium { background: #DBEAFE; color: #1E40AF; }
    .priority-badge.high { background: #FEF3C7; color: #92400E; }
    .priority-badge.urgent { background: #FEE2E2; color: #991B1B; }
    
    .btn-back {
        padding: 6px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: #E5E7EB;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bullhorn" style="color: #16A34A;"></i>
            Lead Details
        </h3>
        <a href="leads.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Leads
        </a>
    </div>
    
    <!-- Lead Header -->
    <div class="lead-header">
        <div>
            <div class="lead-title">
                <?php echo escapeHtml($lead['title']); ?>
                <span style="font-size: 16px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                    (<?php echo str_replace('_', ' ', ucfirst($lead['lead_type'])); ?>)
                </span>
            </div>
            <div class="lead-meta">
                <i class="fas fa-calendar"></i> <?php echo formatDate($lead['created_at']); ?>
                <?php if ($lead['agent_name']): ?>
                    | <i class="fas fa-user-tie"></i> <?php echo escapeHtml($lead['agent_name']); ?>
                <?php endif; ?>
                <?php if ($lead['shop_name']): ?>
                    | <i class="fas fa-store"></i> <?php echo escapeHtml($lead['shop_name']); ?>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align: right;">
            <div>
                <?php 
                $statusColors = [
                    'new' => 'badge-info',
                    'contacted' => 'badge-warning',
                    'qualified' => 'badge-primary',
                    'converted' => 'badge-success',
                    'lost' => 'badge-danger'
                ];
                $color = $statusColors[$lead['status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $color; ?>" style="font-size: 14px; padding: 4px 14px;">
                    <?php echo ucfirst($lead['status']); ?>
                </span>
                <span class="priority-badge <?php echo $lead['priority']; ?>" style="margin-left: 4px;">
                    <?php echo ucfirst($lead['priority']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Lead Details -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle" style="color: #16A34A;"></i>
            Lead Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Title</span>
            <span class="detail-value"><?php echo escapeHtml($lead['title']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Type</span>
            <span class="detail-value"><?php echo str_replace('_', ' ', ucfirst($lead['lead_type'])); ?></span>
        </div>
        <?php if ($lead['description']): ?>
        <div class="detail-row">
            <span class="detail-label">Description</span>
            <span class="detail-value"><?php echo nl2br(escapeHtml($lead['description'])); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($lead['notes']): ?>
        <div class="detail-row">
            <span class="detail-label">Notes</span>
            <span class="detail-value"><?php echo nl2br(escapeHtml($lead['notes'])); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($lead['follow_up_date']): ?>
        <div class="detail-row">
            <span class="detail-label">Follow Up Date</span>
            <span class="detail-value"><?php echo formatDate($lead['follow_up_date']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($lead['converted_at']): ?>
        <div class="detail-row">
            <span class="detail-label">Converted Date</span>
            <span class="detail-value" style="color: #16A34A;"><?php echo formatDate($lead['converted_at']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Contact Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-address-card" style="color: #16A34A;"></i>
            Contact Information
        </div>
        <?php if ($lead['contact_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Contact Name</span>
            <span class="detail-value"><?php echo escapeHtml($lead['contact_name']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($lead['contact_phone']): ?>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?php echo escapeHtml($lead['contact_phone']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($lead['contact_email']): ?>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo escapeHtml($lead['contact_email']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Client Information -->
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title">
            <i class="fas fa-user" style="color: #16A34A;"></i>
            Client Information
        </div>
        <?php if ($lead['agent_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Agent Name</span>
            <span class="detail-value"><?php echo escapeHtml($lead['agent_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Agent Code</span>
            <span class="detail-value"><?php echo escapeHtml($lead['agent_code']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($lead['shop_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Shop Name</span>
            <span class="detail-value"><?php echo escapeHtml($lead['shop_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Code</span>
            <span class="detail-value"><?php echo escapeHtml($lead['shop_code']); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>