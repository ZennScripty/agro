<?php

/**
 * SAMRIDHI AGRO - Admin Visits Management
 *
 * This page allows administrators to view, assign, and manage agent visits.
 *
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 2.1.0
 */

$pageTitle = 'Visit Management';
require_once '../includes/admin_header.php';

requireLogin();

requirePermissionOrAdmin('visit.view', 'visits.php');


$db = getDB();

// ============================================
// GET AGENTS (for filter and assign)
// ============================================

$sql = "SELECT a.id as agent_id,
               a.user_id,
               u.full_name,
               u.username,
               a.agent_code
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'approved'
        ORDER BY u.full_name";

$agentList = $db->fetchAll($sql);

// Get shops for assign
$sql = "SELECT id, shop_name, shop_code
        FROM shops
        WHERE status = 'approved'
        ORDER BY shop_name";

$shopList = $db->fetchAll($sql);


// ============================================
// HANDLE ASSIGN VISIT
// ============================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['assign_visit'])
) {

    requirePermission('visit.assign');

    if (
        !isset($_POST[CSRF_TOKEN_NAME])
        || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])
    ) {

        setFlashMessage(
            'error',
            'Invalid security token'
        );

        redirect('admin/visits.php');
        exit;
    }

    $agentId = (int)($_POST['agent_id'] ?? 0);
    $shopId = (int)($_POST['shop_id'] ?? 0);

    $purpose = sanitizeInput(
        $_POST['purpose'] ?? ''
    );

    $remark = sanitizeInput(
        $_POST['remark'] ?? ''
    );

    if (
        $agentId <= 0
        || $shopId <= 0
    ) {

        setFlashMessage(
            'error',
            'Please select both agent and shop'
        );
    } else {

        $result = assignVisit(
            $agentId,
            $shopId,
            $purpose,
            $_SESSION['user_id'],
            $remark
        );

        if ($result['success']) {

            setFlashMessage(
                'success',
                $result['message']
            );
        } else {

            setFlashMessage(
                'error',
                $result['message']
            );
        }
    }

    redirect('admin/visits.php');
    exit;
}


// ============================================
// HANDLE CANCEL VISIT
// ============================================

if (
    isset($_GET['action'])
    && $_GET['action'] === 'cancel'
    && isset($_GET['id'])
) {

    requirePermission('visit.edit');

    $visitId = (int)$_GET['id'];

    $csrf = $_GET['csrf'] ?? '';

    if (!verifyCsrfToken($csrf)) {

        setFlashMessage(
            'error',
            'Invalid security token'
        );
    } else {

        /*
         * Admin cancellation:
         * status => cancelled
         */

        $result = updateVisitStatus(
            $visitId,
            'cancelled'
        );

        setFlashMessage(
            $result['success']
                ? 'success'
                : 'error',
            $result['message']
        );
    }

    redirect('admin/visits.php');
    exit;
}


// ============================================
// HANDLE DELETE VISIT
// ============================================

if (
    isset($_GET['action'])
    && $_GET['action'] === 'delete'
    && isset($_GET['id'])
) {

    requirePermission('visit.delete');

    $visitId = (int)$_GET['id'];

    $csrf = $_GET['csrf'] ?? '';

    if (!verifyCsrfToken($csrf)) {

        setFlashMessage(
            'error',
            'Invalid security token'
        );
    } else {

        $sql = "DELETE FROM visits WHERE id = ?";

        $db->query(
            $sql,
            [$visitId]
        );

        logActivity(
            'delete',
            $_SESSION['user_id'],
            'visit',
            'Deleted visit ID: ' . $visitId
        );

        setFlashMessage(
            'success',
            'Visit deleted successfully'
        );
    }

    redirect('admin/visits.php');
    exit;
}


// ============================================
// GET FILTERED VISITS
// ============================================

$filters = [

    'agent_id' => isset($_GET['agent'])
        ? (int)$_GET['agent']
        : 0,

    'status' => $_GET['status'] ?? 'all',

    'visit_type' => $_GET['visit_type'] ?? 'all',

    'date_from' => $_GET['date_from'] ?? '',

    'date_to' => $_GET['date_to'] ?? '',

    'search' => $_GET['search'] ?? ''
];


// ============================================
// PAGINATION
// ============================================

$perPage = 10;

$currentPage = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if ($currentPage < 1) {
    $currentPage = 1;
}


/*
 * Existing getFilteredVisits() function is kept unchanged.
 *
 * We fetch the filtered result and paginate the
 * returned array here so no existing helper function
 * needs to be modified.
 */

$allVisits = getFilteredVisits(
    $filters,
    10000
);

$totalRecords = count($allVisits);

$totalPages = max(
    1,
    (int)ceil($totalRecords / $perPage)
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

$visits = array_slice(
    $allVisits,
    $offset,
    $perPage
);


// ============================================
// CSRF
// ============================================

$csrfToken = generateCsrfToken();


// ============================================
// VISIT TYPE LABELS AND COLORS
// ============================================

$visitTypeLabels = [
    'assigned' => 'Assigned',
    'self' => 'Self',
    'new_shop' => 'New Shop'
];

$visitTypeColors = [
    'assigned' => '#F59E0B',
    'self' => '#3B82F6',
    'new_shop' => '#8B5CF6'
];

$visitTypeIcons = [
    'assigned' => 'fa-user-plus',
    'self' => 'fa-user',
    'new_shop' => 'fa-store'
];

$statusColors = [
    'assigned' => 'badge-warning',
    'completed' => 'badge-success',
    'cancelled' => 'badge-danger'
];



// ============================================
// CALCULATE DISTANCE BETWEEN SHOP & VISIT
// ============================================

function calculateDistanceKm(
    $lat1,
    $lon1,
    $lat2,
    $lon2
) {
    $earthRadius = 6371; // Earth radius in KM

    $lat1 = deg2rad((float)$lat1);
    $lon1 = deg2rad((float)$lon1);
    $lat2 = deg2rad((float)$lat2);
    $lon2 = deg2rad((float)$lon2);

    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;

    $a =
        sin($dLat / 2) * sin($dLat / 2)
        +
        cos($lat1)
        * cos($lat2)
        * sin($dLon / 2)
        * sin($dLon / 2);

    $c = 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );

    return $earthRadius * $c;
}

// ============================================
// PAGINATION URL HELPER
// ============================================

function adminVisitPageUrl($page, $filters)
{
    $params = [
        'page' => (int)$page
    ];

    if (!empty($filters['search'])) {
        $params['search'] = $filters['search'];
    }

    if (!empty($filters['agent_id'])) {
        $params['agent'] = $filters['agent_id'];
    }

    if (
        isset($filters['status'])
        && $filters['status'] !== 'all'
    ) {
        $params['status'] = $filters['status'];
    }

    if (
        isset($filters['visit_type'])
        && $filters['visit_type'] !== 'all'
    ) {
        $params['visit_type'] = $filters['visit_type'];
    }

    if (!empty($filters['date_from'])) {
        $params['date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $params['date_to'] = $filters['date_to'];
    }

    return 'visits.php?' . http_build_query($params);
}

?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ============================================
       ASSIGN VISIT FORM
       ============================================ */

    .assign-form {
        background: linear-gradient(135deg,
                #F7FCF7 0%,
                #F0FDF4 100%);

        border-radius: 12px;
        padding: 24px 28px;
        border: 2px solid #DCFCE7;
        margin-bottom: 24px;
        transition: all 0.3s ease;
    }

    .assign-form:hover {
        border-color: #86EFAC;
        box-shadow:
            0 4px 16px rgba(22,
                163,
                74,
                0.08);
    }

    .assign-form .form-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #052E16;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .assign-form .form-title .badge-new {
        background: #16A34A;
        color: white;
        font-size: 11px;
        padding: 2px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    .assign-form .form-grid {
        display: grid;
        grid-template-columns:
            1fr 1fr 1fr 0.5fr;

        gap: 16px;
        align-items: end;
    }

    .assign-form .form-grid .form-group {
        margin-bottom: 0;
    }

    .assign-form .form-grid .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #14532D;
        margin-bottom: 4px;
        display: block;
    }

    .assign-form .form-grid .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
        transition: all 0.3s ease;
    }

    .assign-form .form-grid .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow:
            0 0 0 3px rgba(22,
                163,
                74,
                0.1);
    }

    .assign-form .btn-assign {
        padding: 10px 28px;
        background: linear-gradient(135deg,
                #14532D,
                #16A34A);

        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        width: 100%;
        justify-content: center;
    }

    .assign-form .btn-assign:hover {
        transform: translateY(-2px);

        box-shadow:
            0 4px 16px rgba(22,
                163,
                74,
                0.3);
    }

    .assign-form .btn-assign:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .assign-form .remark-input {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed #E5EDE7;
    }

    .assign-form .remark-input .form-input {
        width: 100%;
        padding: 8px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
    }

    .assign-form .remark-input .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow:
            0 0 0 3px rgba(22,
                163,
                74,
                0.1);
    }


    /* ============================================
       FILTER BAR
       ============================================ */

    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        padding: 16px 20px;
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        box-shadow:
            0 2px 6px rgba(5,
                46,
                22,
                0.04);
    }

    .filter-bar select,
    .filter-bar input {
        padding: 8px 12px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: white;
        transition: all 0.3s ease;
        min-height: 40px;
    }

    .filter-bar select:focus,
    .filter-bar input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow:
            0 0 0 3px rgba(22,
                163,
                74,
                0.1);
    }

    .filter-bar .btn-filter {
        padding: 8px 24px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .filter-bar .btn-filter:hover {
        background: #052E16;
        transform: translateY(-1px);
    }

    .filter-bar .btn-clear {
        padding: 8px 16px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .filter-bar .btn-clear:hover {
        background: #E5E7EB;
    }


    /* ============================================
       VISIT CARD
       ============================================ */

    .visit-card {
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        border: 1px solid #9ee9b1;
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(5, 46, 22, 0.04);
    }

    .visit-card:hover {
        box-shadow:
            0 4px 16px rgba(5,
                46,
                22,
                0.08);

        border-color: #DCFCE7;
        transform: translateY(-1px);
    }

    .visit-card .visit-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .visit-card .visit-info {
        flex: 1;
        min-width: 200px;
    }

    .visit-card .visit-shop {
        font-weight: 600;
        color: #052E16;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .visit-card .visit-details {
        font-size: 13px;
        color: #4A5B5D;
        margin-top: 2px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .visit-card .visit-details span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .visit-card .visit-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .visit-card .visit-photo-thumb {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #E5EDE7;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .visit-card .visit-photo-thumb:hover {
        transform: scale(1.05);
        box-shadow:
            0 4px 12px rgba(0,
                0,
                0,
                0.1);
    }


    /* ============================================
       BADGES
       ============================================ */

    .badge-status {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 11px;
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

    .visit-type-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }


    /* ============================================
       BUTTONS
       ============================================ */

    .btn-action {
        padding: 5px 12px;
        border-radius: 6px;
        border: none;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-action:hover {
        transform: translateY(-1px);
    }

    .btn-view {
        background: #DBEAFE;
        color: #2563EB;
    }

    .btn-view:hover {
        background: #BFDBFE;
    }

    .btn-cancel {
        background: #FEE2E2;
        color: #DC2626;
    }

    .btn-cancel:hover {
        background: #FECACA;
    }

    .btn-delete {
        background: #FEE2E2;
        color: #DC2626;
    }

    .btn-delete:hover {
        background: #FECACA;
    }

    .btn-location {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .btn-location:hover {
        background: #DDD6FE;
    }

    .btn-assign-action {
        background: #DCFCE7;
        color: #16A34A;
    }

    .btn-assign-action:hover {
        background: #BBF7D0;
    }


    /* ============================================
       PAGINATION
       ============================================ */

    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
        padding: 16px 4px 4px;
        border-top: 1px solid #E5EDE7;
    }

    .pagination-info {
        font-size: 12px;
        color: #6B7A7B;
    }

    .pagination-info strong {
        color: #14532D;
    }

    .pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }

    .page-link {
        min-width: 34px;
        height: 34px;
        padding: 0 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #E5EDE7;
        background: white;
        color: #4A5B5D;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .page-link:hover {
        background: #F0FDF4;
        border-color: #86EFAC;
        color: #14532D;
    }

    .page-link.active {
        background: #14532D;
        border-color: #14532D;
        color: white;
    }

    .page-link.disabled {
        opacity: 0.45;
        pointer-events: none;
    }

    .page-dots {
        padding: 0 4px;
        color: #9CA3AF;
        font-size: 12px;
    }


    /* ============================================
       RESPONSIVE
       ============================================ */

    @media (max-width: 1024px) {

        .assign-form .form-grid {
            grid-template-columns: 1fr 1fr;
        }

    }

    @media (max-width: 768px) {

        .assign-form .form-grid {
            grid-template-columns: 1fr;
        }

        .assign-form {
            padding: 16px 18px;
        }

        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-bar select,
        .filter-bar input {
            width: 100%;
        }

        .filter-bar .btn-filter,
        .filter-bar .btn-clear {
            width: 100%;
            justify-content: center;
        }

        .visit-card .visit-row {
            flex-direction: column;
            align-items: stretch;
        }

        .visit-card .visit-actions {
            justify-content: flex-start;
        }

        .visit-card .visit-photo-thumb {
            width: 40px;
            height: 40px;
        }

        .pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
        }

        .pagination {
            justify-content: center;
        }

    }

    @media (max-width: 480px) {

        .assign-form .form-grid {
            grid-template-columns: 1fr;
        }

        .visit-card {
            padding: 14px 16px;
        }

        .visit-card .visit-shop {
            font-size: 15px;
        }

        .visit-card .visit-details {
            font-size: 12px;
            gap: 6px;
            flex-direction: column;
        }

        .page-link {
            min-width: 30px;
            height: 30px;
            font-size: 11px;
        }

    }
</style>


<!-- ============================================
MAIN CONTENT
============================================ -->

<div class="content-card">

    <div class="card-header">

        <h3 class="card-title">

            <i
                class="fas fa-route"
                style="color:#16A34A;">
            </i>

            Visit Management

            <span
                style="
                font-size:14px;
                font-weight:400;
                color:#6B7A7B;
                margin-left:8px;
                ">

                (<?php echo $totalRecords; ?> records)

            </span>

        </h3>

        <?php if (hasPermission('visit.assign')): ?>
            <button
                class="btn-action btn-assign-action"
                onclick="toggleAssignForm()"
                style="
            padding:8px 20px;
            font-size:14px;
            ">

                <i class="fas fa-plus"></i>

                Assign Visit

            </button>
        <?php endif; ?>

    </div>


    <!-- ============================================
    ASSIGN VISIT FORM
    ============================================ -->

    <div
        id="assignForm"
        class="assign-form"
        style="display:none;">

        <div class="form-title">

            <i
                class="fas fa-user-plus"
                style="color:#16A34A;">
            </i>

            Assign Visit to Agent

            <span class="badge-new">
                New
            </span>

        </div>


        <form
            method="POST"
            id="assignVisitForm">

            <input
                type="hidden"
                name="<?php echo CSRF_TOKEN_NAME; ?>"
                value="<?php echo $csrfToken; ?>">

            <input
                type="hidden"
                name="assign_visit"
                value="1">


            <div class="form-grid">


                <div class="form-group">

                    <label
                        class="form-label"
                        for="assign_agent">

                        <i
                            class="fas fa-user-tie"
                            style="color:#7C3AED;">
                        </i>

                        Select Agent

                        <span style="color:#DC2626;">
                            *
                        </span>

                    </label>


                    <select
                        id="assign_agent"
                        name="agent_id"
                        class="form-input"
                        required>

                        <option value="">
                            — Select Agent —
                        </option>

                        <?php foreach ($agentList as $agent): ?>

                            <option
                                value="<?php
                                        echo $agent['agent_id'];
                                        ?>">

                                <?php
                                echo escapeHtml(
                                    $agent['full_name']
                                );
                                ?>

                                (<?php
                                    echo escapeHtml(
                                        $agent['agent_code']
                                    );
                                    ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="assign_shop">

                        <i
                            class="fas fa-store"
                            style="color:#16A34A;">
                        </i>

                        Select Shop

                        <span style="color:#DC2626;">
                            *
                        </span>

                    </label>


                    <select
                        id="assign_shop"
                        name="shop_id"
                        class="form-input"
                        required>

                        <option value="">
                            — Select Shop —
                        </option>

                        <?php foreach ($shopList as $shop): ?>

                            <option
                                value="<?php
                                        echo $shop['id'];
                                        ?>">

                                <?php
                                echo escapeHtml(
                                    $shop['shop_name']
                                );
                                ?>

                                (<?php
                                    echo escapeHtml(
                                        $shop['shop_code']
                                    );
                                    ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="assign_purpose">

                        <i
                            class="fas fa-bullseye"
                            style="color:#D97706;">
                        </i>

                        Purpose

                    </label>


                    <input
                        type="text"
                        id="assign_purpose"
                        name="purpose"
                        class="form-input"
                        placeholder="e.g., Product Demo">

                </div>


                <div class="form-group">

                    <button
                        type="submit"
                        class="btn-assign"
                        id="assignSubmitBtn">

                        <i class="fas fa-check"></i>

                        Assign Visit

                    </button>

                </div>

            </div>


            <div class="remark-input">

                <input
                    type="text"
                    name="remark"
                    class="form-input"
                    placeholder="Additional remarks / notes (optional)">

            </div>

        </form>

    </div>


    <!-- ============================================
    FILTER BAR
    ============================================ -->

    <div class="filter-bar">

        <form
            method="GET"
            style="
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            align-items:center;
            width:100%;
            ">


            <input
                type="text"
                name="search"
                placeholder="🔍 Search..."
                value="<?php
                        echo escapeHtml(
                            $filters['search']
                        );
                        ?>"
                style="
                flex:1;
                min-width:150px;
                ">


            <select name="agent">

                <option value="0">
                    👤 All Agents
                </option>

                <?php foreach ($agentList as $agent): ?>

                    <option
                        value="<?php
                                echo $agent['agent_id'];
                                ?>"
                        <?php
                        echo $filters['agent_id']
                            == $agent['agent_id']
                            ? 'selected'
                            : '';
                        ?>>

                        <?php
                        echo escapeHtml(
                            $agent['full_name']
                        );
                        ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <select name="status">

                <option
                    value="all"
                    <?php
                    echo $filters['status'] === 'all'
                        ? 'selected'
                        : '';
                    ?>>

                    📋 All Status

                </option>


                <option
                    value="assigned"
                    <?php
                    echo $filters['status'] === 'assigned'
                        ? 'selected'
                        : '';
                    ?>>

                    📌 Assigned

                </option>


                <option
                    value="completed"
                    <?php
                    echo $filters['status'] === 'completed'
                        ? 'selected'
                        : '';
                    ?>>

                    ✅ Completed

                </option>


                <option
                    value="cancelled"
                    <?php
                    echo $filters['status'] === 'cancelled'
                        ? 'selected'
                        : '';
                    ?>>

                    ❌ Cancelled

                </option>

            </select>


            <select name="visit_type">

                <option
                    value="all"
                    <?php
                    echo $filters['visit_type'] === 'all'
                        ? 'selected'
                        : '';
                    ?>>

                    📂 All Types

                </option>


                <option
                    value="assigned"
                    <?php
                    echo $filters['visit_type'] === 'assigned'
                        ? 'selected'
                        : '';
                    ?>>

                    📌 Assigned

                </option>


                <option
                    value="self"
                    <?php
                    echo $filters['visit_type'] === 'self'
                        ? 'selected'
                        : '';
                    ?>>

                    👤 Self

                </option>


                <option
                    value="new_shop"
                    <?php
                    echo $filters['visit_type'] === 'new_shop'
                        ? 'selected'
                        : '';
                    ?>>

                    🏪 New Shop

                </option>

            </select>


            <input
                type="date"
                name="date_from"
                value="<?php
                        echo escapeHtml(
                            $filters['date_from']
                        );
                        ?>"
                placeholder="From">


            <input
                type="date"
                name="date_to"
                value="<?php
                        echo escapeHtml(
                            $filters['date_to']
                        );
                        ?>"
                placeholder="To">


            <button
                type="submit"
                class="btn-filter">

                <i class="fas fa-filter"></i>

                Filter

            </button>


            <?php if (
                !empty($filters['search'])
                || $filters['agent_id'] > 0
                || $filters['status'] !== 'all'
                || $filters['visit_type'] !== 'all'
                || !empty($filters['date_from'])
                || !empty($filters['date_to'])
            ): ?>

                <a
                    href="visits.php"
                    class="btn-clear">

                    <i class="fas fa-times"></i>

                    Clear

                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- ============================================
    VISITS LIST
    ============================================ -->

    <?php if (empty($visits)): ?>

        <div
            style="
            text-align:center;
            padding:40px 20px;
            color:#6B7A7B;
            ">

            <i
                class="fas fa-map-marked-alt"
                style="
                font-size:48px;
                display:block;
                margin-bottom:12px;
                color:#D1D5DB;
                ">
            </i>

            <p
                style="
                font-size:16px;
                font-weight:500;
                ">

                No visits found

            </p>

            <p
                style="
                font-size:13px;
                margin-top:4px;
                ">

                Try adjusting your filters
                or assign a new visit

            </p>

        </div>

    <?php else: ?>


        <?php foreach ($visits as $visit): ?>

            <div class="visit-card">

                <div class="visit-row">


                    <div class="visit-info">

                        <div class="visit-shop">

                            <?php
                            echo escapeHtml(
                                $visit['shop_name']
                                    ?? $visit['existing_shop_name']
                                    ?? 'Unknown Shop'
                            );
                            ?>


                            <?php if (
                                $visit['visit_type']
                                === 'new_shop'
                            ): ?>

                                <span
                                    class="visit-type-badge"
                                    style="
                                    background:#EDE9FE;
                                    color:#5B21B6;
                                    ">

                                    <i class="fas fa-store"></i>

                                    New Shop

                                </span>


                            <?php elseif (
                                $visit['visit_type']
                                === 'self'
                            ): ?>

                                <span
                                    class="visit-type-badge"
                                    style="
                                    background:#DBEAFE;
                                    color:#1E40AF;
                                    ">

                                    <i class="fas fa-user"></i>

                                    Self

                                </span>


                            <?php else: ?>

                                <span
                                    class="visit-type-badge"
                                    style="
                                    background:#FEF3C7;
                                    color:#92400E;
                                    ">

                                    <i class="fas fa-user-plus"></i>

                                    Assigned

                                </span>

                            <?php endif; ?>


                            <span
                                class="badge-status
                                <?php
                                echo $statusColors[$visit['status']] ?? 'badge-secondary';
                                ?>">

                                <?php
                                echo ucfirst(
                                    $visit['status']
                                );
                                ?>

                            </span>

                        </div>


                        <div class="visit-details">

                            <span>

                                <i class="fas fa-user-tie"></i>

                                <?php
                                echo escapeHtml(
                                    $visit['agent_name']
                                        ?? 'N/A'
                                );
                                ?>

                            </span>


                            <?php if (
                                !empty($visit['owner_name'])
                            ): ?>

                                <span>

                                    <i class="fas fa-user"></i>

                                    <?php
                                    echo escapeHtml(
                                        $visit['owner_name']
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>


                            <span>

                                <i class="far fa-calendar"></i>

                                <?php
                                echo formatDate(
                                    $visit['visit_date']
                                );
                                ?>

                            </span>


                            <span>

                                <i class="far fa-clock"></i>

                                <?php
                                echo date(
                                    'h:i A',
                                    strtotime(
                                        $visit['visit_time']
                                    )
                                );
                                ?>

                            </span>


                            <?php if (
                                !empty($visit['contact_number'])
                            ): ?>

                                <span>

                                    <i class="fas fa-phone"></i>

                                    <?php
                                    echo escapeHtml(
                                        $visit['contact_number']
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- ACTIONS -->

                    <div class="visit-actions">


                        <?php if (
                            !empty($visit['latitude'])
                            && !empty($visit['longitude'])
                        ): ?>

                            <a
                                href="https://www.google.com/maps?q=<?php
                                                                    echo $visit['latitude'];
                                                                    ?>,<?php
                                                                        echo $visit['longitude'];
                                                                        ?>"
                                target="_blank"
                                class="btn-action btn-location"
                                title="Open in Maps">

                                <i class="fas fa-map-pin"></i>

                            </a>

                        <?php endif; ?>


                        <?php
                        $thumbnail = $visit['photo_thumbnail'] ?? '';
                        $originalPhoto = $visit['photo'] ?? '';

                        if (!empty($thumbnail) || !empty($originalPhoto)):
                        ?>

                            <img
                                src="../uploads/visits/<?php
                                                        echo escapeHtml(
                                                            !empty($thumbnail)
                                                                ? $thumbnail
                                                                : $originalPhoto
                                                        );
                                                        ?>"
                                data-original="../uploads/visits/<?php
                                                                    echo escapeHtml($originalPhoto);
                                                                    ?>"
                                alt="Visit Photo"
                                class="visit-photo-thumb"
                                title="Click to view full photo">

                        <?php endif; ?>


                        <a
                            href="visit-view.php?id=<?php
                                                    echo $visit['id'];
                                                    ?>"
                            class="btn-action btn-view"
                            title="View Details">

                            <i class="fas fa-eye"></i>

                        </a>


                        <!-- ====================================
                        CANCEL BUTTON
                        ==================================== -->

                        <?php if (
                            $visit['status'] === 'assigned'
                        ): ?>

                            <a
                                href="visits.php?action=cancel&id=<?php
                                                                    echo (int)$visit['id'];
                                                                    ?>&csrf=<?php
                                                                            echo urlencode($csrfToken);
                                                                            ?>"
                                class="btn-action btn-cancel"
                                onclick="return confirmCancel(this, 'cancel')"
                                title="Cancel Visit">

                                <i class="fas fa-times"></i>

                            </a>

                        <?php endif; ?>


                        <!-- ====================================
                        DELETE BUTTON
                        ==================================== -->

                        <?php if (
                            hasPermission('visit.delete')
                        ): ?>

                            <a
                                href="visits.php?action=delete&id=<?php
                                                                    echo (int)$visit['id'];
                                                                    ?>&csrf=<?php
                                                                            echo urlencode($csrfToken);
                                                                            ?>"
                                class="btn-action btn-delete"
                                onclick="return confirmCancel(this, 'delete')"
                                title="Delete Visit">

                                <i class="fas fa-trash"></i>

                            </a>

                        <?php endif; ?>


                    </div>

                </div>


                <?php if (
                    !empty($visit['purpose'])
                    || !empty($visit['remark'])
                ): ?>

                    <div
                        style="
                        font-size:13px;
                        color:#4A5B5D;
                        margin-top:6px;
                        padding-top:6px;
                        border-top:1px solid #F0FDF4;
                        ">


                        <?php if (
                            !empty($visit['purpose'])
                        ): ?>

                            <span>

                                <strong>
                                    Purpose:
                                </strong>

                                <?php
                                echo escapeHtml(
                                    $visit['purpose']
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if (
                            !empty($visit['remark'])
                        ): ?>

                            <?php
                            if (
                                !empty($visit['purpose'])
                            ) {
                                echo ' | ';
                            }
                            ?>

                            <span>

                                <strong>
                                    Remark:
                                </strong>

                                <?php
                                echo escapeHtml(
                                    $visit['remark']
                                );
                                ?>

                            </span>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


            </div>

        <?php endforeach; ?>


        <!-- ============================================
        PAGINATION
        ============================================ -->

        <?php if ($totalPages > 1): ?>

            <div class="pagination-wrapper">


                <div class="pagination-info">

                    Showing

                    <strong>
                        <?php echo $offset + 1; ?>
                    </strong>

                    -

                    <strong>
                        <?php
                        echo min(
                            $offset + $perPage,
                            $totalRecords
                        );
                        ?>
                    </strong>

                    of

                    <strong>
                        <?php echo $totalRecords; ?>
                    </strong>

                    visits

                </div>


                <div class="pagination">


                    <!-- PREVIOUS -->

                    <?php if (
                        $currentPage > 1
                    ): ?>

                        <a
                            href="<?php
                                    echo adminVisitPageUrl(
                                        $currentPage - 1,
                                        $filters
                                    );
                                    ?>"
                            class="page-link"
                            title="Previous">

                            <i class="fas fa-chevron-left"></i>

                        </a>

                    <?php else: ?>

                        <span
                            class="page-link disabled">

                            <i class="fas fa-chevron-left"></i>

                        </span>

                    <?php endif; ?>


                    <?php

                    $startPage = max(
                        1,
                        $currentPage - 2
                    );

                    $endPage = min(
                        $totalPages,
                        $currentPage + 2
                    );

                    ?>


                    <!-- FIRST PAGE -->

                    <?php if (
                        $startPage > 1
                    ): ?>

                        <a
                            href="<?php
                                    echo adminVisitPageUrl(
                                        1,
                                        $filters
                                    );
                                    ?>"
                            class="page-link">

                            1

                        </a>


                        <?php if (
                            $startPage > 2
                        ): ?>

                            <span class="page-dots">
                                ...
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>


                    <!-- PAGE NUMBERS -->

                    <?php for (
                        $page = $startPage;
                        $page <= $endPage;
                        $page++
                    ): ?>

                        <a
                            href="<?php
                                    echo adminVisitPageUrl(
                                        $page,
                                        $filters
                                    );
                                    ?>"
                            class="page-link
                            <?php
                            echo $page === $currentPage
                                ? 'active'
                                : '';
                            ?>">

                            <?php echo $page; ?>

                        </a>

                    <?php endfor; ?>


                    <!-- LAST PAGE -->

                    <?php if (
                        $endPage < $totalPages
                    ): ?>

                        <?php if (
                            $endPage < $totalPages - 1
                        ): ?>

                            <span class="page-dots">
                                ...
                            </span>

                        <?php endif; ?>


                        <a
                            href="<?php
                                    echo adminVisitPageUrl(
                                        $totalPages,
                                        $filters
                                    );
                                    ?>"
                            class="page-link">

                            <?php echo $totalPages; ?>

                        </a>

                    <?php endif; ?>


                    <!-- NEXT -->

                    <?php if (
                        $currentPage < $totalPages
                    ): ?>

                        <a
                            href="<?php
                                    echo adminVisitPageUrl(
                                        $currentPage + 1,
                                        $filters
                                    );
                                    ?>"
                            class="page-link"
                            title="Next">

                            <i class="fas fa-chevron-right"></i>

                        </a>

                    <?php else: ?>

                        <span
                            class="page-link disabled">

                            <i class="fas fa-chevron-right"></i>

                        </span>

                    <?php endif; ?>


                </div>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>


<script>
    // ============================================
    // TOGGLE ASSIGN FORM
    // ============================================

    function toggleAssignForm() {

        const form =
            document.getElementById(
                'assignForm'
            );

        if (form.style.display === 'none') {

            form.style.display = 'block';

            form.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

        } else {

            form.style.display = 'none';

        }
    }


    // ============================================
    // CONFIRM CANCEL / DELETE
    // ============================================
    //
    // FIX:
    // Previously this function used window.event.
    // SweetAlert's asynchronous callback could not
    // reliably access the original clicked link.
    //
    // Now the clicked link is passed directly:
    //
    // confirmCancel(this, 'cancel')
    //
    // or
    //
    // confirmCancel(this, 'delete')
    //
    // ============================================

    function confirmCancel(
        target,
        action
    ) {

        const titles = {

            cancel: 'Cancel Visit?',

            delete: 'Delete Visit?'

        };


        const texts = {

            cancel: 'Are you sure you want to cancel this visit?',

            delete: 'Are you sure you want to delete this visit? This action cannot be undone.'

        };


        const confirmTexts = {

            cancel: 'Yes, Cancel',

            delete: 'Yes, Delete'

        };


        const colors = {

            cancel: '#D97706',

            delete: '#DC2626'

        };


        Swal.fire({

            title: titles[action] ||
                'Confirm',

            text: texts[action] ||
                'Are you sure?',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: colors[action] ||
                '#DC2626',

            cancelButtonColor: '#6B7A7B',

            confirmButtonText: confirmTexts[action] ||
                'Yes',

            cancelButtonText: 'Cancel'

        }).then(function(result) {

            if (
                result.isConfirmed &&
                target &&
                target.href
            ) {

                window.location.href =
                    target.href;

            }

        });


        return false;
    }


    // ============================================
    // ASSIGN FORM VALIDATION
    // ============================================

    document.addEventListener(
        'DOMContentLoaded',
        function() {

            const assignForm =
                document.getElementById(
                    'assignVisitForm'
                );

            const agentSelect =
                document.getElementById(
                    'assign_agent'
                );

            const shopSelect =
                document.getElementById(
                    'assign_shop'
                );

            const submitBtn =
                document.getElementById(
                    'assignSubmitBtn'
                );


            function validateAssignForm() {

                if (
                    agentSelect.value &&
                    shopSelect.value
                ) {

                    submitBtn.disabled = false;

                } else {

                    submitBtn.disabled = true;

                }

            }


            agentSelect.addEventListener(
                'change',
                validateAssignForm
            );


            shopSelect.addEventListener(
                'change',
                validateAssignForm
            );


            validateAssignForm();


            // ========================================
            // PHOTO CLICK TO OPEN FULL SIZE
            // ========================================

            document
                .querySelectorAll(
                    '.visit-photo-thumb'
                )
                .forEach(
                    function(img) {

                        img.addEventListener(
                            'click',
                            function(e) {

                                e.stopPropagation();

                                const originalPhoto = this.dataset.original;

                                if (originalPhoto) {
                                    window.open(originalPhoto, '_blank');
                                }

                            }
                        );

                    }
                );

        }
    );
</script>


<?php require_once '../includes/admin_footer.php'; ?>