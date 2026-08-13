<?php
/**
 * SAMRIDHI AGRO - Staff Leads View
 * 
 * This page displays lead records for a specific staff member.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Staff Leads';

// Include admin header
require_once '../includes/admin_header.php';

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
$db = getDB();

// Get staff ID from URL
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($staffId <= 0) {
    setFlashMessage('error', 'Invalid staff ID.');
    redirect('admin/staff.php');
    exit;
}

// Get staff details
$sql = "SELECT u.*, sp.department, sp.designation 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ? AND u.role = 'staff'";
$staff = $db->fetchOne($sql, [$staffId]);

if (!$staff) {
    setFlashMessage('error', 'Staff not found.');
    redirect('admin/staff.php');
    exit;
}

// Get lead records
$sql = "SELECT sl.*, 
        a.full_name as agent_name,
        s.shop_name, s.shop_code
        FROM staff_leads sl
        LEFT JOIN agents ag ON sl.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        LEFT JOIN shops s ON sl.shop_id = s.id
        WHERE sl.staff_id = ?
        ORDER BY sl.created_at DESC
        LIMIT 50";
$leads = $db->fetchAll($sql, [$staffId]);

// Calculate statistics
$totalLeads = count($leads);
$newLeads = 0;
$contactedLeads = 0;
$qualifiedLeads = 0;
$convertedLeads = 0;
$lostLeads = 0;

foreach ($leads as $lead) {
    switch ($lead['status']) {
        case 'new': $newLeads++; break;
        case 'contacted': $contactedLeads++; break;
        case 'qualified': $qualifiedLeads++; break;
        case 'converted': $convertedLeads++; break;
        case 'lost': $lostLeads++; break;
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 16px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 24px;
        font-weight: 700;
    }
    
    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.new .stat-number { color: #3B82F6; }
    .stat-card.contacted .stat-number { color: #F59E0B; }
    .stat-card.qualified .stat-number { color: #7C3AED; }
    .stat-card.converted .stat-number { color: #16A34A; }
    .stat-card.lost .stat-number { color: #DC2626; }
    
    .lead-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .lead-badge.new { background: #DBEAFE; color: #1E40AF; }
    .lead-badge.contacted { background: #FEF3C7; color: #92400E; }
    .lead-badge.qualified { background: #EDE9FE; color: #5B21B6; }
    .lead-badge.converted { background: #DCFCE7; color: #065F46; }
    .lead-badge.lost { background: #FEE2E2; color: #991B1B; }
    
    .priority-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .priority-badge.low { background: #F3F4F6; color: #6B7A7B; }
    .priority-badge.medium { background: #DBEAFE; color: #1E40AF; }
    .priority-badge.high { background: #FEF3C7; color: #92400E; }
    .priority-badge.urgent { background: #FEE2E2; color: #991B1B; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bullhorn" style="color: #16A34A;"></i>
            Leads - <?php echo escapeHtml($staff['full_name']); ?>
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo $totalLeads; ?> leads)
            </span>
        </h3>
        <a href="staff.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Staff
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $totalLeads; ?></div>
            <div class="stat-label">Total Leads</div>
        </div>
        <div class="stat-card new">
            <div class="stat-number"><?php echo $newLeads; ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="stat-card contacted">
            <div class="stat-number"><?php echo $contactedLeads; ?></div>
            <div class="stat-label">Contacted</div>
        </div>
        <div class="stat-card qualified">
            <div class="stat-number"><?php echo $qualifiedLeads; ?></div>
            <div class="stat-label">Qualified</div>
        </div>
        <div class="stat-card converted">
            <div class="stat-number"><?php echo $convertedLeads; ?></div>
            <div class="stat-label">Converted</div>
        </div>
        <div class="stat-card lost">
            <div class="stat-number"><?php echo $lostLeads; ?></div>
            <div class="stat-label">Lost</div>
        </div>
    </div>
    
    <!-- Leads Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Follow Up</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #6B7A7B;">
                        <i class="fas fa-bullhorn" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        No leads found
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($leads as $lead): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #052E16;">
                            <?php echo escapeHtml(truncateText($lead['title'], 30)); ?>
                        </div>
                        <?php if (!empty($lead['description'])): ?>
                            <div style="font-size: 12px; color: #6B7A7B;">
                                <?php echo escapeHtml(truncateText($lead['description'], 40)); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="visit-type-badge" style="
                            display: inline-block;
                            padding: 2px 10px;
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 500;
                            background: #F3F4F6;
                            color: #4A5B5D;
                        ">
                            <?php echo str_replace('_', ' ', ucfirst($lead['lead_type'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($lead['agent_name']): ?>
                            <div><i class="fas fa-user-tie" style="color: #7C3AED; width: 14px;"></i> <?php echo escapeHtml($lead['agent_name']); ?></div>
                        <?php endif; ?>
                        <?php if ($lead['shop_name']): ?>
                            <div><i class="fas fa-store" style="color: #16A34A; width: 14px;"></i> <?php echo escapeHtml($lead['shop_name']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px;">
                        <?php if (!empty($lead['contact_name'])): ?>
                            <div><i class="fas fa-user"></i> <?php echo escapeHtml($lead['contact_name']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($lead['contact_phone'])): ?>
                            <div><i class="fas fa-phone"></i> <?php echo escapeHtml($lead['contact_phone']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($lead['contact_email'])): ?>
                            <div><i class="fas fa-envelope" style="font-size: 10px;"></i> <?php echo escapeHtml($lead['contact_email']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="lead-badge <?php echo $lead['status']; ?>">
                            <?php echo ucfirst($lead['status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="priority-badge <?php echo $lead['priority']; ?>">
                            <?php echo ucfirst($lead['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($lead['follow_up_date']): ?>
                            <?php echo formatDate($lead['follow_up_date']); ?>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px;">
                        <?php echo formatDate($lead['created_at']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>