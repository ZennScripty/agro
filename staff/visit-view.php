<?php
/**
 * SAMRIDHI AGRO - Staff Visit View
 * 
 * This page displays detailed information about a specific visit.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Visit Details';

// Include staff header
require_once __DIR__ . '/../includes/staff_header.php';

// Require staff login
requireLogin();
requireRole('staff');

// Get database instance
$db = getDB();

// Get visit ID
$visitId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($visitId <= 0) {
    setFlashMessage('error', 'Invalid visit ID.');
    redirect('staff/visits.php');
    exit;
}

// Get visit details
$sql = "SELECT sv.*, 
        a.full_name as agent_name, a.agent_code,
        s.shop_name, s.shop_code,
        u.full_name as staff_name
        FROM staff_visits sv
        LEFT JOIN agents ag ON sv.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        LEFT JOIN shops s ON sv.shop_id = s.id
        JOIN users u ON sv.staff_id = u.id
        WHERE sv.id = ? AND sv.staff_id = ?";
$visit = $db->fetchOne($sql, [$visitId, $_SESSION['user_id']]);

if (!$visit) {
    setFlashMessage('error', 'Visit not found.');
    redirect('staff/visits.php');
    exit;
}

// Get visit photos
$sql = "SELECT * FROM staff_visit_photos WHERE visit_id = ?";
$photos = $db->fetchAll($sql, [$visitId]);

$csrfToken = generateCsrfToken();
?>

<style>
    .visit-header {
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
    
    .visit-header .visit-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #052E16;
    }
    
    .visit-header .visit-meta {
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
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 8px;
    }
    
    .photo-grid .photo-item {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #E5EDE7;
        position: relative;
    }
    
    .photo-grid .photo-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }
    
    .photo-grid .photo-item .photo-type {
        position: absolute;
        bottom: 4px;
        right: 4px;
        background: rgba(0,0,0,0.7);
        color: white;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
    }
    
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
            <i class="fas fa-route" style="color: #16A34A;"></i>
            Visit Details
        </h3>
        <a href="visits.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Visits
        </a>
    </div>
    
    <!-- Visit Header -->
    <div class="visit-header">
        <div>
            <div class="visit-title">
                <?php echo str_replace('_', ' ', ucfirst($visit['visit_type'])); ?>
                <span style="font-size: 16px; font-weight: 400; color: #6B7A7B;">
                    - <?php echo formatDate($visit['visit_date']); ?>
                </span>
            </div>
            <div class="visit-meta">
                <i class="fas fa-calendar"></i> <?php echo formatDate($visit['visit_date']); ?>
                <?php if ($visit['visit_time']): ?>
                    at <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                <?php endif; ?>
                <?php if ($visit['agent_name']): ?>
                    | <i class="fas fa-user-tie"></i> <?php echo escapeHtml($visit['agent_name']); ?>
                <?php endif; ?>
                <?php if ($visit['shop_name']): ?>
                    | <i class="fas fa-store"></i> <?php echo escapeHtml($visit['shop_name']); ?>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php 
            $statusColors = [
                'planned' => 'badge-info',
                'in_progress' => 'badge-warning',
                'completed' => 'badge-success',
                'cancelled' => 'badge-danger'
            ];
            $color = $statusColors[$visit['status']] ?? 'badge-secondary';
            ?>
            <span class="badge-status <?php echo $color; ?>" style="font-size: 14px; padding: 4px 14px;">
                <?php echo str_replace('_', ' ', ucfirst($visit['status'])); ?>
            </span>
        </div>
    </div>
    
    <!-- Visit Details -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle" style="color: #16A34A;"></i>
            Visit Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Visit Type</span>
            <span class="detail-value"><?php echo str_replace('_', ' ', ucfirst($visit['visit_type'])); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Purpose</span>
            <span class="detail-value"><?php echo escapeHtml($visit['purpose']); ?></span>
        </div>
        <?php if ($visit['notes']): ?>
        <div class="detail-row">
            <span class="detail-label">Notes</span>
            <span class="detail-value"><?php echo nl2br(escapeHtml($visit['notes'])); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($visit['feedback']): ?>
        <div class="detail-row">
            <span class="detail-label">Feedback</span>
            <span class="detail-value"><?php echo escapeHtml($visit['feedback']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($visit['rating']): ?>
        <div class="detail-row">
            <span class="detail-label">Rating</span>
            <span class="detail-value" style="color: #EAB308; font-size: 16px;">
                <?php echo str_repeat('★', $visit['rating']); ?>
                <?php echo str_repeat('☆', 5 - $visit['rating']); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Client Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-user" style="color: #16A34A;"></i>
            Client Information
        </div>
        <?php if ($visit['agent_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Agent Name</span>
            <span class="detail-value"><?php echo escapeHtml($visit['agent_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Agent Code</span>
            <span class="detail-value"><?php echo escapeHtml($visit['agent_code']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($visit['shop_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Shop Name</span>
            <span class="detail-value"><?php echo escapeHtml($visit['shop_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Code</span>
            <span class="detail-value"><?php echo escapeHtml($visit['shop_code']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Check-in/Out Details -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Check-in / Check-out Details
        </div>
        <div class="detail-row">
            <span class="detail-label">Check-in Time</span>
            <span class="detail-value">
                <?php if ($visit['check_in_time']): ?>
                    <?php echo date('h:i A', strtotime($visit['check_in_time'])); ?>
                    <?php if ($visit['check_in_lat'] && $visit['check_in_lng']): ?>
                        <span style="font-size: 11px; color: #6B7A7B; margin-left: 8px;">
                            (📍 <?php echo number_format($visit['check_in_lat'], 6); ?>, <?php echo number_format($visit['check_in_lng'], 6); ?>)
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Not checked in</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Check-out Time</span>
            <span class="detail-value">
                <?php if ($visit['check_out_time']): ?>
                    <?php echo date('h:i A', strtotime($visit['check_out_time'])); ?>
                    <?php if ($visit['check_out_lat'] && $visit['check_out_lng']): ?>
                        <span style="font-size: 11px; color: #6B7A7B; margin-left: 8px;">
                            (📍 <?php echo number_format($visit['check_out_lat'], 6); ?>, <?php echo number_format($visit['check_out_lng'], 6); ?>)
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Not checked out</span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    
    <!-- Photos -->
    <?php if (!empty($photos)): ?>
    <div class="detail-section" style="margin-bottom: 0;">
        <div class="section-title">
            <i class="fas fa-images" style="color: #16A34A;"></i>
            Photos
        </div>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?>
            <div class="photo-item">
                <img src="../uploads/visits/<?php echo escapeHtml($photo['photo_path']); ?>" alt="Visit Photo">
                <span class="photo-type"><?php echo str_replace('_', ' ', ucfirst($photo['photo_type'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/staff_footer.php'; ?>
