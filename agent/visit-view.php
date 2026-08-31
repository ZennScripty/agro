<?php

/**
 * SAMRIDHI AGRO - Agent Visit View
 *
 * This page displays detailed information about a specific visit
 * for the agent to review.
 *
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'Visit Details';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();


// ============================================
// GET LOGGED-IN AGENT
// ============================================
// IMPORTANT:
// $_SESSION['user_id'] = users.id
// visits.agent_id      = agents.id
//
// Therefore first we find the agents.id
// using the logged-in users.id.

$sql = "SELECT 
            a.id AS agent_id,
            a.user_id,
            a.agent_code,
            a.commission_rate,
            u.full_name,
            u.username,
            u.email,
            u.phone
        FROM agents a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ?
        LIMIT 1";

$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

if (!$agent) {
    setFlashMessage('error', 'Agent profile not found.');
    redirect('agent/visits.php');
    exit;
}

// This is the actual ID stored in visits.agent_id
$agentId = (int) $agent['agent_id'];


// ============================================
// GET VISIT ID FROM URL
// ============================================

$visitId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// If no ID or invalid ID, redirect to visit list
if ($visitId <= 0) {
    setFlashMessage('error', 'Invalid visit ID.');
    redirect('agent/visits.php');
    exit;
}


// ============================================
// GET VISIT DETAILS
// ============================================
// IMPORTANT RELATION:
//
// visits.agent_id
//       ↓
// agents.id
//       ↓
// agents.user_id
//       ↓
// users.id
//
// Visit ownership is checked using agents.id.

$sql = "SELECT 
            v.*,

            /* =========================
               SHOP DETAILS
               ========================= */
            s.shop_name AS existing_shop_name,
            s.shop_code,
            s.shop_type,
            s.owner_name AS shop_owner,
            s.address AS shop_address,
            s.city AS shop_city,
            s.state AS shop_state,
            s.pincode AS shop_pincode,
            s.phone AS shop_phone,
            s.email AS shop_email,
            s.latitude AS shop_latitude,
            s.longitude AS shop_longitude,

            /* =========================
               AGENT DETAILS
               ========================= */
            ag.id AS agent_id,
            ag.agent_code,
            ag.commission_rate,

            u.full_name AS agent_name,
            u.username AS agent_username,
            u.email AS agent_email,
            u.phone AS agent_phone,

            /* =========================
               ASSIGNED BY
               ========================= */
            u2.full_name AS assigned_by_name

        FROM visits v

        LEFT JOIN shops s 
            ON v.shop_id = s.id

        LEFT JOIN agents ag 
            ON v.agent_id = ag.id

        LEFT JOIN users u 
            ON ag.user_id = u.id

        LEFT JOIN users u2 
            ON v.assigned_by = u2.id

        WHERE v.id = ?
          AND v.agent_id = ?";

$visit = $db->fetchOne($sql, [
    $visitId,
    $agentId
]);


// ============================================
// VISIT NOT FOUND / NOT OWNED BY AGENT
// ============================================

if (!$visit) {
    setFlashMessage(
        'error',
        'Visit not found or you do not have permission to view it.'
    );

    redirect('agent/visits.php');
    exit;
}


// ============================================
// GET VISIT TIMELINE
// ============================================

$sql = "SELECT 
            al.*,
            u.full_name

        FROM activity_logs al

        LEFT JOIN users u 
            ON al.user_id = u.id

        WHERE al.module = 'visit'
          AND al.description LIKE ?

        ORDER BY al.created_at DESC

        LIMIT 10";

$timeline = $db->fetchAll(
    $sql,
    ['%' . $visitId . '%']
);


// ============================================
// GENERATE CSRF TOKEN
// ============================================

$csrfToken = generateCsrfToken();


// ============================================
// PHOTO CHECK
// ============================================

$hasPhoto = !empty($visit['photo'])
    && file_exists('../uploads/visits/' . $visit['photo']);

$photoPath = $hasPhoto
    ? '../uploads/visits/' . $visit['photo']
    : '';


// ============================================
// LOCATION CHECK
// ============================================

$hasShopLocation =
    !empty($visit['shop_latitude']) &&
    !empty($visit['shop_longitude']);

$hasVisitLocation =
    !empty($visit['latitude']) &&
    !empty($visit['longitude']);


// ============================================
// VISIT TYPE LABELS
// ============================================

$visitTypeLabels = [
    'assigned' => 'Assigned Visit',
    'self' => 'Self Visit',
    'new_shop' => 'New Shop Visit'
];

$visitTypeIcons = [
    'assigned' => 'fa-user-plus',
    'self' => 'fa-user',
    'new_shop' => 'fa-store'
];

$visitTypeColors = [
    'assigned' => '#F59E0B',
    'self' => '#3B82F6',
    'new_shop' => '#8B5CF6'
];


// ============================================
// STATUS COLORS
// ============================================

$statusColors = [
    'assigned' => 'badge-warning',
    'completed' => 'badge-success',
    'cancelled' => 'badge-danger'
];


// ============================================
// SHOP TYPE LABELS
// ============================================

$shopTypeLabels = [
    'retail' => 'Retail',
    'wholesale' => 'Wholesale',
    'both' => 'Both'
];

?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>
    .visit-header {
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 24px;
        border: 1px solid #E5EDE7;
    }

    .visit-header .visit-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #052E16;
        margin: 0 0 4px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .visit-header .visit-title .visit-id {
        font-size: 14px;
        font-weight: 400;
        color: #6B7A7B;
    }

    .visit-header .visit-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #4A5B5D;
        margin-top: 8px;
    }

    .visit-header .visit-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .visit-actions {
        margin-top: 12px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .detail-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
    }

    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
        width: 150px;
        flex-shrink: 0;
    }

    .detail-value {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
        flex: 1;
    }

    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-status.badge-success {
        background: #DCFCE7;
        color: #065F46;
    }

    .badge-status.badge-warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .badge-status.badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .badge-status.badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-status.badge-primary {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .photo-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .photo-container .main-photo {
        max-width: 100%;
        max-height: 500px;
        border-radius: 12px;
        border: 2px solid #E5EDE7;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .photo-container .main-photo:hover {
        transform: scale(1.02);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .btn-action {
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-action:hover {
        transform: translateY(-1px);
    }

    .btn-primary {
        background: #14532D;
        color: white;
    }

    .btn-primary:hover {
        background: #052E16;
    }

    .btn-secondary {
        background: #F3F4F6;
        color: #4A5B5D;
    }

    .btn-secondary:hover {
        background: #E5E7EB;
    }

    .btn-info {
        background: #DBEAFE;
        color: #2563EB;
    }

    .btn-info:hover {
        background: #BFDBFE;
    }

    .btn-location {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .btn-location:hover {
        background: #DDD6FE;
    }

    .btn-cancel {
        background: #FEE2E2;
        color: #DC2626;
    }

    .btn-cancel:hover {
        background: #FECACA;
    }

    .location-map {
        width: 100%;
        height: 280px;
        border-radius: 12px;
        border: 1px solid #E5EDE7;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #6B7A7B;
        overflow: hidden;
    }

    .location-map iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .location-map .map-placeholder {
        text-align: center;
        padding: 20px;
    }

    .location-map .map-placeholder i {
        font-size: 48px;
        display: block;
        margin-bottom: 8px;
        color: #D1D5DB;
    }

    .location-map .map-placeholder .sub-text {
        font-size: 13px;
        color: #6B7A7B;
        margin-top: 4px;
    }

    .location-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        background: #DCFCE7;
        color: #065F46;
    }

    .timeline-item {
        display: flex;
        gap: 14px;
        padding: 10px 0;
        border-bottom: 1px solid #F7FCF7;
        align-items: flex-start;
    }

    .timeline-item:last-child {
        border-bottom: none;
    }

    .timeline-item .timeline-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #F0FDF4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #16A34A;
        flex-shrink: 0;
        font-size: 14px;
    }

    .timeline-item .timeline-content {
        flex: 1;
    }

    .timeline-item .timeline-content .timeline-text {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
    }

    .timeline-item .timeline-content .timeline-time {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }

    .visit-type-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .grid-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .location-type-tag {
        display: inline-block;
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: 500;
        margin-left: 8px;
    }

    .location-type-tag.visit-location {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .location-type-tag.shop-location {
        background: #DCFCE7;
        color: #065F46;
    }

    .location-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .location-buttons .btn-map {
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .location-buttons .btn-map:hover {
        transform: translateY(-1px);
    }

    .location-buttons .btn-map.visit-map {
        background: #DBEAFE;
        color: #2563EB;
    }

    .location-buttons .btn-map.visit-map:hover {
        background: #BFDBFE;
    }

    .location-buttons .btn-map.shop-map {
        background: #DCFCE7;
        color: #16A34A;
    }

    .location-buttons .btn-map.shop-map:hover {
        background: #BBF7D0;
    }

    @media (max-width: 768px) {

        .visit-header .visit-title {
            font-size: 20px;
        }

        .grid-2col {
            grid-template-columns: 1fr;
        }

        .detail-row {
            flex-direction: column;
            padding: 10px 0;
        }

        .detail-label {
            width: 100%;
            margin-bottom: 4px;
        }

        .photo-container .main-photo {
            max-height: 300px;
        }

        .location-map {
            height: 200px;
        }

        .visit-header .visit-meta {
            gap: 8px;
            font-size: 13px;
        }

        .visit-actions {
            flex-direction: column;
        }

        .visit-actions .btn-action {
            width: 100%;
            justify-content: center;
        }

        .location-buttons {
            flex-direction: column;
        }

        .location-buttons .btn-map {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {

        .visit-header {
            padding: 16px 18px;
        }

        .visit-header .visit-title {
            font-size: 18px;
        }

        .detail-section {
            padding: 16px 18px;
        }

        .photo-container .main-photo {
            max-height: 250px;
        }
    }
</style>


<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">

    <!-- ============================================
         VISIT HEADER
         ============================================ -->

    <div class="visit-header">

        <div class="visit-title">

            <i class="fas fa-route" style="color: #16A34A;"></i>

            Visit Details

            <span class="visit-id">
                #<?php echo str_pad($visit['id'], 6, '0', STR_PAD_LEFT); ?>
            </span>

            <span class="badge-status <?php echo $statusColors[$visit['status']] ?? 'badge-secondary'; ?>">
                <?php echo ucfirst($visit['status']); ?>
            </span>

        </div>


        <div class="visit-meta">

            <span>
                <i class="far fa-calendar"></i>
                <?php echo formatDate($visit['visit_date']); ?>
            </span>

            <span>
                <i class="far fa-clock"></i>
                <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
            </span>

            <span>

                <i class="fas <?php echo $visitTypeIcons[$visit['visit_type']] ?? 'fa-circle'; ?>"
                    style="color: <?php echo $visitTypeColors[$visit['visit_type']] ?? '#6B7A7B'; ?>;">
                </i>

                <?php echo $visitTypeLabels[$visit['visit_type']] ?? ucfirst($visit['visit_type']); ?>

            </span>

            <?php if (!empty($visit['assigned_by_name'])): ?>

                <span>
                    <i class="fas fa-user-check"></i>
                    Assigned by:
                    <?php echo escapeHtml($visit['assigned_by_name']); ?>
                </span>

            <?php endif; ?>

        </div>


        <div class="visit-actions">

            <?php if ($visit['status'] === 'assigned'): ?>

                <a href="visit-start.php?id=<?php echo $visit['id']; ?>" class="btn-action btn-primary">

                    <i class="fas fa-play"></i>
                    Start Visit

                </a>


                <a href="visits.php?action=cancel&id=<?php echo $visit['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                    class="btn-action btn-cancel" onclick="return confirmCancel()">

                    <i class="fas fa-times"></i>
                    Cancel

                </a>

            <?php endif; ?>


            <a href="visits.php" class="btn-action btn-secondary">

                <i class="fas fa-arrow-left"></i>
                Back to Visits

            </a>

        </div>

    </div>



    <!-- ============================================
         VISIT INFORMATION GRID
         ============================================ -->

    <div class="grid-2col">


        <!-- Visit Details -->

        <div class="detail-section" style="margin-bottom: 0;">

            <div class="section-title">

                <i class="fas fa-info-circle" style="color: #16A34A;"></i>

                Visit Information

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Visit ID
                </span>

                <span class="detail-value">
                    #<?php echo str_pad($visit['id'], 6, '0', STR_PAD_LEFT); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Visit Type
                </span>

                <span class="detail-value">

                    <span class="visit-type-badge" style="
                              background: <?php echo $visitTypeColors[$visit['visit_type']] ?? '#6B7A7B'; ?>20;
                              color: <?php echo $visitTypeColors[$visit['visit_type']] ?? '#6B7A7B'; ?>;
                          ">

                        <i class="fas <?php echo $visitTypeIcons[$visit['visit_type']] ?? 'fa-circle'; ?>"></i>

                        <?php echo $visitTypeLabels[$visit['visit_type']] ?? ucfirst($visit['visit_type']); ?>

                    </span>

                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Status
                </span>

                <span class="detail-value">

                    <span class="badge-status <?php echo $statusColors[$visit['status']] ?? 'badge-secondary'; ?>">

                        <?php echo ucfirst($visit['status']); ?>

                    </span>

                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Visit Date
                </span>

                <span class="detail-value">
                    <?php echo formatDate($visit['visit_date']); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Visit Time
                </span>

                <span class="detail-value">
                    <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Created At
                </span>

                <span class="detail-value">

                    <?php
                    echo formatDate($visit['created_at'])
                        . ' ('
                        . timeAgo($visit['created_at'])
                        . ')';
                    ?>

                </span>

            </div>


            <?php if (!empty($visit['assigned_by_name'])): ?>

                <div class="detail-row">

                    <span class="detail-label">
                        Assigned By
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['assigned_by_name']); ?>
                    </span>

                </div>

            <?php endif; ?>


            <?php if (!empty($visit['purpose'])): ?>

                <div class="detail-row">

                    <span class="detail-label">
                        Purpose
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['purpose']); ?>
                    </span>

                </div>

            <?php endif; ?>


            <?php if (!empty($visit['remark'])): ?>

                <div class="detail-row">

                    <span class="detail-label">
                        Remark
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['remark']); ?>
                    </span>

                </div>

            <?php endif; ?>

        </div>



        <!-- ============================================
             SHOP INFORMATION
             ============================================ -->

        <div class="detail-section" style="margin-bottom: 0;">

            <div class="section-title">

                <i class="fas fa-store" style="color: #16A34A;"></i>

                Shop Information

            </div>


            <?php if ($visit['shop_id']): ?>

                <div class="detail-row">

                    <span class="detail-label">
                        Shop
                    </span>

                    <span class="detail-value">

                        <?php
                        echo escapeHtml(
                            $visit['existing_shop_name']
                            ?? $visit['shop_name']
                            ?? 'N/A'
                        );
                        ?>

                        <?php if (!empty($visit['shop_code'])): ?>

                            <span style="color: #6B7A7B; font-size: 13px;">

                                (<?php echo escapeHtml($visit['shop_code']); ?>)

                            </span>

                        <?php endif; ?>

                    </span>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Shop Type
                    </span>

                    <span class="detail-value">

                        <?php
                        echo $shopTypeLabels[$visit['shop_type']]
                            ?? $visit['shop_type']
                            ?? 'N/A';
                        ?>

                    </span>

                </div>


                <?php if (!empty($visit['shop_owner'])): ?>

                    <div class="detail-row">

                        <span class="detail-label">
                            Owner
                        </span>

                        <span class="detail-value">
                            <?php echo escapeHtml($visit['shop_owner']); ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if (!empty($visit['shop_phone'])): ?>

                    <div class="detail-row">

                        <span class="detail-label">
                            Phone
                        </span>

                        <span class="detail-value">
                            <?php echo escapeHtml($visit['shop_phone']); ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if (!empty($visit['shop_address'])): ?>

                    <div class="detail-row">

                        <span class="detail-label">
                            Address
                        </span>

                        <span class="detail-value">

                            <?php echo escapeHtml($visit['shop_address']); ?>

                            <?php if (!empty($visit['shop_city']) || !empty($visit['shop_state'])): ?>

                                <br>

                                <?php

                                $locationParts = [];

                                if (!empty($visit['shop_city'])) {
                                    $locationParts[] = $visit['shop_city'];
                                }

                                if (!empty($visit['shop_state'])) {
                                    $locationParts[] = $visit['shop_state'];
                                }

                                if (!empty($visit['shop_pincode'])) {
                                    $locationParts[] = $visit['shop_pincode'];
                                }

                                echo escapeHtml(
                                    implode(', ', $locationParts)
                                );

                                ?>

                            <?php endif; ?>

                        </span>

                    </div>

                <?php endif; ?>


            <?php else: ?>

                <div class="detail-row">

                    <span class="detail-label">
                        Shop Name
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['shop_name'] ?? 'N/A'); ?>
                    </span>

                </div>


                <?php if (!empty($visit['owner_name'])): ?>

                    <div class="detail-row">

                        <span class="detail-label">
                            Owner Name
                        </span>

                        <span class="detail-value">
                            <?php echo escapeHtml($visit['owner_name']); ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if (!empty($visit['contact_number'])): ?>

                    <div class="detail-row">

                        <span class="detail-label">
                            Contact Number
                        </span>

                        <span class="detail-value">
                            <?php echo escapeHtml($visit['contact_number']); ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if (!empty($visit['address'])): ?>

                    <div class="detail-row">

                        <span class="detail-label">
                            Address
                        </span>

                        <span class="detail-value">
                            <?php echo escapeHtml($visit['address']); ?>
                        </span>

                    </div>

                <?php endif; ?>

            <?php endif; ?>


            <!-- Shop Location -->

            <?php if ($hasShopLocation): ?>

                <div class="detail-row" style="border-bottom: none;">

                    <span class="detail-label">
                        Shop Location
                    </span>

                    <span class="detail-value">

                        <span class="location-badge">

                            <i class="fas fa-check-circle"></i>
                            Location Set

                        </span>


                        <div style="margin-top: 10px;">

                            <div class="location-map" style="height: 250px;">

                                <iframe
                                    src="https://www.google.com/maps?q=<?php echo $visit['shop_latitude']; ?>,<?php echo $visit['shop_longitude']; ?>&z=16&output=embed"
                                    allowfullscreen loading="lazy">
                                </iframe>

                            </div>

                            <div class="location-buttons">

                                <a href="https://www.google.com/maps?q=<?php echo $visit['shop_latitude']; ?>,<?php echo $visit['shop_longitude']; ?>"
                                    target="_blank" class="btn-map shop-map">

                                    <i class="fas fa-external-link-alt"></i>
                                    Open in Google Maps

                                </a>

                            </div>

                        </div>




                    </span>

                </div>

            <?php endif; ?>

        </div>

    </div>



    <!-- ============================================
         AGENT INFORMATION
         ============================================ -->

    <div class="detail-section">

        <div class="section-title">

            <i class="fas fa-user-tie" style="color: #16A34A;"></i>

            Agent Information

        </div>


        <div class="grid-2col">

            <div>

                <div class="detail-row">

                    <span class="detail-label">
                        Agent Name
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['agent_name'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Agent Code
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['agent_code'] ?? 'N/A'); ?>
                    </span>

                </div>

            </div>


            <div>

                <div class="detail-row">

                    <span class="detail-label">
                        Username
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['agent_username'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Phone
                    </span>

                    <span class="detail-value">
                        <?php echo escapeHtml($visit['agent_phone'] ?? 'N/A'); ?>
                    </span>

                </div>

            </div>

        </div>

    </div>



    <!-- ============================================
         VISIT LOCATION
         ============================================ -->

    <!-- Location Section - Visit Location -->
    <div class="detail-section">

        <div class="section-title">
            <i class="fas fa-map-marker-alt" style="color: #16A34A;"></i>
            Visit Location (Captured by You)
        </div>

        <?php if ($hasVisitLocation): ?>

            <div class="grid-2col" style="margin-bottom: 0;">

                <!-- LOCATION DETAILS -->
                <div>

                    <!-- Captured Visit Location -->
                    <div class="detail-row">
                        <span class="detail-label">Captured Location</span>

                        <span class="detail-value">

                            <span class="location-badge" style="background:#DBEAFE;color:#1E40AF;">
                                <i class="fas fa-location-arrow"></i>
                                Visit Location
                            </span>

                            <div style="
                            margin-top:6px;
                            font-family:monospace;
                            font-size:13px;
                            color:#14532D;
                        ">
                                <?php echo number_format($visit['latitude'], 6); ?>,
                                <?php echo number_format($visit['longitude'], 6); ?>
                            </div>

                        </span>
                    </div>


                    <!-- ACCURACY -->
                    <?php if (!empty($visit['accuracy'])): ?>

                        <div class="detail-row">

                            <span class="detail-label">
                                GPS Accuracy
                            </span>

                            <span class="detail-value">
                                ± <?php echo number_format($visit['accuracy'], 0); ?> meters
                            </span>

                        </div>

                    <?php endif; ?>


                    <?php if ($hasShopLocation): ?>

                        <?php
                        // Calculate distance between
                        // captured visit location and shop location
                
                        $distance = calculateDistance(
                            (float) $visit['latitude'],
                            (float) $visit['longitude'],
                            (float) $visit['shop_latitude'],
                            (float) $visit['shop_longitude']
                        );

                        // Distance label
                        if ($distance < 1000) {
                            $distanceText = number_format($distance, 0) . ' meters';
                        } else {
                            $distanceText = number_format($distance / 1000, 2) . ' km';
                        }

                        // Distance status
                        if ($distance <= 100) {

                            $distanceStatus = 'At Shop';
                            $distanceIcon = 'fa-check-circle';
                            $distanceBg = '#DCFCE7';
                            $distanceColor = '#065F46';
                        } elseif ($distance <= 500) {

                            $distanceStatus = 'Nearby Shop';
                            $distanceIcon = 'fa-location-dot';
                            $distanceBg = '#FEF3C7';
                            $distanceColor = '#92400E';
                        } else {

                            $distanceStatus = 'Far from Shop';
                            $distanceIcon = 'fa-exclamation-triangle';
                            $distanceBg = '#FEE2E2';
                            $distanceColor = '#991B1B';
                        }
                        ?>


                        <!-- DISTANCE FROM SHOP -->
                        <div class="detail-row">

                            <span class="detail-label">
                                Distance from Shop
                            </span>

                            <span class="detail-value">

                                <div style="
                                display:flex;
                                align-items:center;
                                gap:10px;
                                flex-wrap:wrap;
                            ">

                                    <strong style="
                                    font-size:16px;
                                    color:#14532D;
                                ">
                                        <?php echo $distanceText; ?>
                                    </strong>

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:5px;
                                    padding:4px 10px;
                                    border-radius:20px;
                                    background:<?php echo $distanceBg; ?>;
                                    color:<?php echo $distanceColor; ?>;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                        <i class="fas <?php echo $distanceIcon; ?>"></i>
                                        <?php echo $distanceStatus; ?>
                                    </span>

                                </div>

                            </span>

                        </div>





                        <!-- MAP BUTTONS -->
                        <div class="detail-row" style="border-bottom:none;">

                            <span class="detail-label"></span>

                            <span class="detail-value">

                                <div class="location-buttons">

                                    <!-- VISIT LOCATION -->
                                    <a href="https://www.google.com/maps?q=<?php echo $visit['latitude']; ?>,<?php echo $visit['longitude']; ?>"
                                        target="_blank" class="btn-map visit-map">
                                        <i class="fas fa-location-arrow"></i>
                                        View Visit Location
                                    </a>


                                 

                                </div>

                            </span>

                        </div>

                    <?php else: ?>

                        <!-- NO SHOP LOCATION -->
                        <div class="detail-row">

                            <span class="detail-label">
                                Shop Location
                            </span>

                            <span class="detail-value">

                                <span style="
                                color:#6B7A7B;
                                font-size:13px;
                            ">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Shop location is not available.
                                </span>

                            </span>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- MAP -->
                <div>

                    <div class="location-map">

                        <?php

                        /*
                         * Map center = captured visit location
                         */

                        $mapCenter =
                            $visit['latitude'] . ',' .
                            $visit['longitude'];

                        ?>

                        <iframe src="https://www.google.com/maps?q=<?php echo $mapCenter; ?>&z=16&output=embed"
                            allowfullscreen loading="lazy">
                        </iframe>

                    </div>

                    <div style="
                    font-size:11px;
                    color:#6B7A7B;
                    text-align:center;
                    margin-top:4px;
                ">

                        <span style="color:#2563EB;">
                            ● Visit Location
                        </span>

                        <?php if ($hasShopLocation): ?>

                            &nbsp; | &nbsp;

                            <span style="color:#16A34A;">
                                ● Shop Location
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php else: ?>

            <!-- LOCATION NOT CAPTURED -->

            <div class="location-map">

                <div class="map-placeholder">

                    <i class="fas fa-map-pin"></i>

                    <p>
                        Location not captured during this visit
                    </p>

                    <p class="sub-text">
                        You did not capture location while completing the visit
                    </p>

                </div>

            </div>

        <?php endif; ?>

    </div>



    <!-- ============================================
         PHOTO SECTION
         ============================================ -->

    <div class="detail-section">

        <div class="section-title">

            <i class="fas fa-camera" style="color: #16A34A;"></i>

            Visit Photo Proof

        </div>


        <?php if ($hasPhoto): ?>

            <div class="photo-container">

                <img src="<?php echo $photoPath; ?>" alt="Visit Photo" class="main-photo"
                    onclick="window.open('<?php echo $photoPath; ?>', '_blank')">


                <div class="photo-actions">

                    <a href="<?php echo $photoPath; ?>" download class="btn-action btn-primary">

                        <i class="fas fa-download"></i>
                        Download

                    </a>


                    <a href="<?php echo $photoPath; ?>" target="_blank" class="btn-action btn-info">

                        <i class="fas fa-external-link-alt"></i>
                        Open Full Size

                    </a>

                </div>


                <div style="
                    font-size: 12px;
                    color: #6B7A7B;
                    text-align: center;
                    margin-top: 4px;
                ">

                    <i class="fas fa-info-circle"></i>
                    Click image to view full size

                </div>

            </div>


        <?php else: ?>

            <div style="
                text-align: center;
                padding: 30px 20px;
                color: #6B7A7B;
            ">

                <i class="fas fa-camera-slash" style="
                       font-size: 48px;
                       display: block;
                       margin-bottom: 12px;
                       color: #D1D5DB;
                   ">
                </i>

                <p>
                    No photo uploaded for this visit
                </p>

            </div>

        <?php endif; ?>

    </div>



    <!-- ============================================
         TIMELINE
         ============================================ -->

    <div class="detail-section" style="margin-bottom: 0;">

        <div class="section-title">

            <i class="fas fa-clock" style="color: #16A34A;"></i>

            Activity Timeline

        </div>


        <?php if (empty($timeline)): ?>

            <p style="
                color: #6B7A7B;
                text-align: center;
                padding: 16px 0;
            ">

                <i class="fas fa-history" style="
                       font-size: 20px;
                       display: block;
                       margin-bottom: 4px;
                       opacity: 0.5;
                   ">
                </i>

                No activity recorded for this visit yet.

            </p>


        <?php else: ?>

            <?php foreach ($timeline as $activity): ?>

                <div class="timeline-item">

                    <div class="timeline-icon">

                        <i class="fas fa-<?php

                        echo match ($activity['action']) {

                            'create' => 'plus',
                            'update' => 'edit',
                            'delete' => 'trash',
                            'login' => 'sign-in-alt',
                            'logout' => 'sign-out-alt',

                            default => 'circle'
                        };

                        ?>"></i>

                    </div>


                    <div class="timeline-content">

                        <div class="timeline-text">

                            <?php if (!empty($activity['full_name'])): ?>

                                <strong>
                                    <?php echo escapeHtml($activity['full_name']); ?>
                                </strong>

                            <?php endif; ?>


                            <?php echo escapeHtml(
                                $activity['description']
                                ?? $activity['action']
                            ); ?>

                        </div>


                        <div class="timeline-time">

                            <i class="far fa-clock"></i>

                            <?php echo formatDate($activity['created_at']); ?>

                            (<?php echo timeAgo($activity['created_at']); ?>)

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>



<script>
    // ============================================
    // CONFIRM CANCEL
    // ============================================

    function confirmCancel() {

        Swal.fire({

            title: 'Cancel Visit?',

            text: 'Are you sure you want to cancel this visit?',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#D97706',

            cancelButtonColor: '#6B7A7B',

            confirmButtonText: 'Yes, Cancel',

            cancelButtonText: 'Cancel'

        }).then((result) => {

            if (result.isConfirmed) {

                const event = window.event;

                const target = event.target.closest('a');

                if (target) {

                    window.location.href = target.href;

                }

            }

        });

        return false;
    }


    // ============================================
    // PHOTO CLICK
    // ============================================

    document.addEventListener('DOMContentLoaded', function () {

        document
            .querySelectorAll('.main-photo')
            .forEach(function (img) {

                img.addEventListener('click', function () {

                    window.open(
                        this.src,
                        '_blank'
                    );

                });

            });

    });
</script>


<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>