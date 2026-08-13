<?php
/**
 * SAMRIDHI AGRO - Staff Visits View
 * 
 * This page displays visit records for a specific staff member.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Staff Visits';

// Include admin header
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('staff.visits.view', 'staff-visits.php');

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

// Get visit records
$sql = "SELECT sv.*, 
        a.full_name as agent_name, 
        s.shop_name, s.shop_code
        FROM staff_visits sv
        LEFT JOIN agents ag ON sv.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        LEFT JOIN shops s ON sv.shop_id = s.id
        WHERE sv.staff_id = ?
        ORDER BY sv.visit_date DESC, sv.created_at DESC
        LIMIT 50";
$visits = $db->fetchAll($sql, [$staffId]);

// Calculate statistics
$totalVisits = count($visits);
$completedVisits = 0;
$plannedVisits = 0;
$cancelledVisits = 0;
$inProgressVisits = 0;

foreach ($visits as $visit) {
    switch ($visit['status']) {
        case 'completed': $completedVisits++; break;
        case 'planned': $plannedVisits++; break;
        case 'cancelled': $cancelledVisits++; break;
        case 'in_progress': $inProgressVisits++; break;
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
    .stat-card.completed .stat-number { color: #16A34A; }
    .stat-card.planned .stat-number { color: #3B82F6; }
    .stat-card.cancelled .stat-number { color: #DC2626; }
    
    .visit-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .visit-badge.completed { background: #DCFCE7; color: #065F46; }
    .visit-badge.planned { background: #DBEAFE; color: #1E40AF; }
    .visit-badge.in_progress { background: #FEF3C7; color: #92400E; }
    .visit-badge.cancelled { background: #FEE2E2; color: #991B1B; }
    
    .visit-type-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        background: #F3F4F6;
        color: #4A5B5D;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-route" style="color: #16A34A;"></i>
            Visits - <?php echo escapeHtml($staff['full_name']); ?>
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo $totalVisits; ?> visits)
            </span>
        </h3>
        <a href="staff.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Staff
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $totalVisits; ?></div>
            <div class="stat-label">Total Visits</div>
        </div>
        <div class="stat-card completed">
            <div class="stat-number"><?php echo $completedVisits; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card planned">
            <div class="stat-number"><?php echo $plannedVisits; ?></div>
            <div class="stat-label">Planned</div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-number"><?php echo $cancelledVisits; ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>
    
    <!-- Visits Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Client</th>
                    <th>Purpose</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($visits)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #6B7A7B;">
                        <i class="fas fa-route" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        No visit records found
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($visits as $visit): ?>
                <tr>
                    <td><?php echo formatDate($visit['visit_date']); ?></td>
                    <td>
                        <span class="visit-type-badge">
                            <?php echo str_replace('_', ' ', ucfirst($visit['visit_type'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($visit['agent_name']): ?>
                            <div><i class="fas fa-user-tie" style="color: #7C3AED; width: 14px;"></i> <?php echo escapeHtml($visit['agent_name']); ?></div>
                        <?php endif; ?>
                        <?php if ($visit['shop_name']): ?>
                            <div><i class="fas fa-store" style="color: #16A34A; width: 14px;"></i> <?php echo escapeHtml($visit['shop_name']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 13px; max-width: 150px;">
                        <?php echo escapeHtml(truncateText($visit['purpose'], 40)); ?>
                    </td>
                    <td style="font-size: 12px;">
                        <?php if ($visit['check_in_time']): ?>
                            <?php echo date('h:i A', strtotime($visit['check_in_time'])); ?>
                            <?php if ($visit['check_in_lat'] && $visit['check_in_lng']): ?>
                                <br><span style="color: #6B7A7B; font-size: 10px;">
                                    📍 <?php echo number_format($visit['check_in_lat'], 6); ?>, <?php echo number_format($visit['check_in_lng'], 6); ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px;">
                        <?php if ($visit['check_out_time']): ?>
                            <?php echo date('h:i A', strtotime($visit['check_out_time'])); ?>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="visit-badge <?php echo $visit['status']; ?>">
                            <?php echo str_replace('_', ' ', ucfirst($visit['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($visit['rating']): ?>
                            <span style="color: #EAB308;">
                                <?php echo str_repeat('★', $visit['rating']); ?>
                                <?php echo str_repeat('☆', 5 - $visit['rating']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #6B7A7B;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>