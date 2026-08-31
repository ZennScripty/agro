<?php

/**
 * SAMRIDHI AGRO - Agent Visits
 *
 * This page displays agent's visits with filtering and pagination.
 *
 * Filters:
 * - All
 * - Self
 * - New Shop
 * - Assigned
 * - Pending
 * - Completed
 * - Cancelled
 * - Visit Date
 *
 * Agent cannot cancel visits from this page.
 *
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 3.1.0
 */

$pageTitle = 'My Visits';
require_once __DIR__ . '/../includes/agent_header.php';

requireLogin();
requireRole('agent');

$db = getDB();


// ============================================
// GET AGENT DATA
// ============================================

$sql = "SELECT a.*,
               u.full_name,
               u.username,
               u.email,
               u.phone
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ?
        LIMIT 1";

$agent = $db->fetchOne(
    $sql,
    [$_SESSION['user_id']]
);

if (!$agent) {

    setFlashMessage(
        'error',
        'Agent profile not found.'
    );

    redirect('agent/dashboard.php');
    exit;
}

$agentId = (int)$agent['id'];


// ============================================
// VISIT TYPE FILTER
// ============================================

$allowedFilters = [
    'all',
    'self',
    'new_shop',
    'assigned'
];

$filter = sanitizeInput(
    $_GET['filter'] ?? 'all'
);

if (
    !in_array(
        $filter,
        $allowedFilters,
        true
    )
) {
    $filter = 'all';
}


// ============================================
// STATUS FILTER
// ============================================

$allowedStatuses = [
    'all',
    'pending',
    'completed',
    'cancelled'
];

$statusFilter = sanitizeInput(
    $_GET['status'] ?? 'all'
);

if (
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {
    $statusFilter = 'all';
}


// ============================================
// DATE FILTER
// ============================================

$visitDate = sanitizeInput(
    $_GET['visit_date'] ?? ''
);


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


// ============================================
// BUILD FILTER CONDITION
// ============================================

$where = "WHERE v.agent_id = ?";
$params = [$agentId];


// ============================================
// VISIT TYPE FILTER
// ============================================

switch ($filter) {

    case 'self':

        $where .= " AND v.visit_type = 'new_shop'";
        break;


    case 'new_shop':

        $where .= " AND v.visit_type = 'new_shop'";
        break;


    case 'assigned':

        $where .= " AND v.visit_type = 'self'";
        break;


    case 'all':
    default:

        break;
}


// ============================================
// STATUS FILTER
// ============================================

switch ($statusFilter) {

    case 'pending':

        /*
         * Pending visits are stored as
         * "assigned" in the database.
         */

        $where .= " AND v.status = 'assigned'";
        break;


    case 'completed':

        $where .= " AND v.status = 'completed'";
        break;


    case 'cancelled':

        $where .= " AND v.status = 'cancelled'";
        break;


    case 'all':
    default:

        break;
}


// ============================================
// DATE FILTER
// ============================================

if (!empty($visitDate)) {

    $where .= " AND DATE(v.visit_date) = ?";
    $params[] = $visitDate;
}


// ============================================
// GET VISIT STATISTICS
// ============================================

$sql = "SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN status = 'assigned'
                    THEN 1
                    ELSE 0
                END
            ) AS assigned,

            SUM(
                CASE
                    WHEN status = 'completed'
                    THEN 1
                    ELSE 0
                END
            ) AS completed,

            SUM(
                CASE
                    WHEN status = 'cancelled'
                    THEN 1
                    ELSE 0
                END
            ) AS cancelled

        FROM visits

        WHERE agent_id = ?";

$visitStats = $db->fetchOne(
    $sql,
    [$agentId]
);


// ============================================
// TOTAL FILTERED RECORDS
// ============================================

$countSql = "SELECT COUNT(*) AS total
             FROM visits v
             $where";

$countResult = $db->fetchOne(
    $countSql,
    $params
);

$totalRecords = (int)(
    $countResult['total'] ?? 0
);

$totalPages = max(
    1,
    (int)ceil(
        $totalRecords / $perPage
    )
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = (
    $currentPage - 1
) * $perPage;


// ============================================
// GET VISITS
// ============================================

$sql = "SELECT
            v.*,

            /* Existing shop details */
            s.shop_name AS existing_shop_name,
            s.shop_code,
            s.owner_name AS shop_owner,
            s.latitude AS shop_latitude,
            s.longitude AS shop_longitude,
            s.address AS shop_address,
            s.city AS shop_city,
            s.state AS shop_state,

            /* Agent details */
            a.agent_code AS agent_code,

            u.full_name AS agent_name,
            u.username AS agent_username,
            u.email AS agent_email,
            u.phone AS agent_phone

        FROM visits v

        LEFT JOIN shops s
            ON v.shop_id = s.id

        LEFT JOIN agents a
            ON v.agent_id = a.id

        LEFT JOIN users u
            ON a.user_id = u.id

        $where

        ORDER BY v.created_at DESC

        LIMIT $perPage OFFSET $offset";

$visits = $db->fetchAll(
    $sql,
    $params
);


// ============================================
// FILTER LABELS
// ============================================

$filterLabels = [
    'all'       => 'All Visits',
    'self'      => 'Self',
    'new_shop'  => 'New Shop',
    'assigned'  => 'Assigned'
];


// ============================================
// STATUS LABELS
// ============================================

$statusLabels = [
    'all'       => 'All Status',
    'pending'   => 'Pending',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];


// ============================================
// STATUS COLORS
// ============================================

$statusColors = [
    'assigned'  => 'badge-warning',
    'completed' => 'badge-success',
    'cancelled' => 'badge-danger'
];


// ============================================
// PAGINATION URL
// ============================================

function visitPaginationUrl(
    $page,
    $filter,
    $statusFilter = 'all',
    $visitDate = ''
) {

    $params = [
        'page' => (int)$page
    ];

    if ($filter !== 'all') {
        $params['filter'] = $filter;
    }

    if ($statusFilter !== 'all') {
        $params['status'] = $statusFilter;
    }

    if (!empty($visitDate)) {
        $params['visit_date'] = $visitDate;
    }

    return 'visits.php?' .
        http_build_query($params);
}

?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>

/* ============================================
   FILTER BAR
   ============================================ */

.visit-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 12px;
    background: #F7FCF7;
    border: 1px solid #E5EDE7;
    border-radius: 10px;
}

.visit-filter-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.visit-filter-label {
    font-size: 13px;
    font-weight: 600;
    color: #14532D;
}

.visit-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 13px;
    border-radius: 7px;
    border: 1px solid #D1D5DB;
    background: white;
    color: #4A5B5D;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.25s ease;
}

.visit-filter-btn:hover {
    background: #F0FDF4;
    border-color: #86EFAC;
    color: #14532D;
    transform: translateY(-1px);
}

.visit-filter-btn.active {
    background: #14532D;
    border-color: #14532D;
    color: white;
}

.visit-filter-btn.filter-cancelled.active {
    background: #DC2626;
    border-color: #DC2626;
}

.visit-filter-btn.filter-completed.active {
    background: #16A34A;
    border-color: #16A34A;
}

.visit-filter-btn.filter-pending.active {
    background: #D97706;
    border-color: #D97706;
}

.visit-filter-count {
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    font-size: 10px;
}

.visit-filter-btn:not(.active)
.visit-filter-count {
    background: #F3F4F6;
    color: #6B7A7B;
}


/* ============================================
   DATE FILTER
   ============================================ */

.visit-date-filter {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.visit-date-filter input {
    height: 34px;
    padding: 5px 9px;
    border: 1px solid #D1D5DB;
    border-radius: 7px;
    background: white;
    color: #4A5B5D;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
}

.visit-date-filter input:focus {
    outline: none;
    border-color: #16A34A;
    box-shadow:
        0 0 0 3px rgba(
            22,
            163,
            74,
            0.1
        );
}

.visit-date-btn {
    height: 34px;
    padding: 0 12px;
    border: none;
    border-radius: 7px;
    background: #14532D;
    color: white;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.visit-date-btn:hover {
    background: #052E16;
}

.visit-date-clear {
    height: 34px;
    padding: 0 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 7px;
    background: #F3F4F6;
    color: #6B7A7B;
    text-decoration: none;
    font-size: 12px;
}


/* ============================================
   STATISTICS
   ============================================ */

.stats-grid {
    display: grid;
    grid-template-columns:
        repeat(
            auto-fit,
            minmax(120px, 1fr)
        );

    gap: 10px;
    margin-bottom: 20px;
}

.statuslocation {
    display: flex;
    width: 100%;
    justify-content: space-between;
    align-items: center;
}

.stat-card {
    background: white;
    border: 1px solid #E5EDE7;
    border-radius: 10px;
    padding: 12px 14px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow:
        0 2px 4px rgba(
            5,
            46,
            22,
            0.04
        );
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow:
        0 4px 12px rgba(
            5,
            46,
            22,
            0.08
        );
}

.stat-card .stat-number {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
}

.stat-card .stat-label {
    font-size: 11px;
    color: #6B7A7B;
    margin-top: 2px;
}

.stat-card .stat-icon {
    display: block;
    font-size: 18px;
    margin-bottom: 4px;
}

.stat-card.total .stat-number {
    color: #14532D;
}

.stat-card.total .stat-icon {
    color: #14532D;
}

.stat-card.assigned .stat-number {
    color: #F59E0B;
}

.stat-card.assigned .stat-icon {
    color: #F59E0B;
}

.stat-card.completed .stat-number {
    color: #16A34A;
}

.stat-card.completed .stat-icon {
    color: #16A34A;
}

.stat-card.cancelled .stat-number {
    color: #DC2626;
}

.stat-card.cancelled .stat-icon {
    color: #DC2626;
}


/* ============================================
   VISIT CARD
   ============================================ */

.visit-card {
    background: white;
    border: 1px solid #E5EDE7;
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    box-shadow:
        0 2px 4px rgba(
            5,
            46,
            22,
            0.04
        );
}


/* ============================================
   COMPLETED CARD - GREEN GRADIENT
   ============================================ */

.visit-card.status-completed {
    background:
        linear-gradient(
            135deg,
            #F0FDF4 0%,
            #DCFCE7 55%,
            #BBF7D0 100%
        );

    border: 2px solid transparent;

    background-image:
        linear-gradient(
            135deg,
            #F0FDF4,
            #DCFCE7,
            #BBF7D0
        ),
        linear-gradient(
            135deg,
            #86EFAC,
            #16A34A,
            #15803D
        );

    background-origin: border-box;
    background-clip: padding-box, border-box;
}


/* ============================================
   CANCELLED CARD - RED GRADIENT
   ============================================ */

.visit-card.status-cancelled {
    background:
        linear-gradient(
            135deg,
            #FFF7F7 0%,
            #FEE2E2 55%,
            #FECACA 100%
        );

    border: 2px solid transparent;

    background-image:
        linear-gradient(
            135deg,
            #FFF7F7,
            #FEE2E2,
            #FECACA
        ),
        linear-gradient(
            135deg,
            #FCA5A5,
            #DC2626,
            #991B1B
        );

    background-origin: border-box;
    background-clip: padding-box, border-box;
}


/* ============================================
   CARD HOVER
   ============================================ */

.visit-card:hover {
    box-shadow:
        0 4px 16px rgba(
            5,
            46,
            22,
            0.08
        );

    transform: translateY(-2px);
}

.visit-card.status-completed:hover {
    box-shadow:
        0 8px 22px rgba(
            22,
            163,
            74,
            0.15
        );
}

.visit-card.status-cancelled:hover {
    box-shadow:
        0 8px 22px rgba(
            220,
            38,
            38,
            0.15
        );
}


.visit-card .visit-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 8px;
}

.visit-card .visit-shop {
    font-weight: 600;
    color: #052E16;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.visit-card .visit-shop .shop-name {
    word-break: break-word;
}

.visit-card .visit-meta {
    font-size: 12px;
    color: #6B7A7B;
    margin-top: 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
}

.visit-card .visit-meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.visit-card .visit-meta .meta-label {
    font-weight: 500;
    color: #4A5B5D;
}


/* ============================================
   LOCATION
   ============================================ */

.visit-card .visit-location {
    margin-top: 6px;
    padding: 6px 10px;
    background: #F7FCF7;
    border-radius: 6px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6B7A7B;
}

.visit-card .visit-location .coords {
    font-family: monospace;
    font-weight: 600;
    color: #14532D;
}

.visit-card .visit-location .btn-map {
    padding: 2px 10px;
    background: #DBEAFE;
    color: #2563EB;
    border: none;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.visit-card .visit-location .btn-map:hover {
    background: #BFDBFE;
    transform: scale(1.02);
}

.visit-card .visit-location
.btn-map.shop-location {
    background: #DCFCE7;
    color: #16A34A;
    border: 1px solid #00ee58;
}

.visit-card .visit-location
.btn-map.shop-location:hover {
    background: #BBF7D0;
}


/* ============================================
   ACTIONS
   ============================================ */

.visit-card .visit-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    padding-top: 12px;
    border-top: 1px solid #F0FDF4;
    align-items: center;
    justify-content: space-between;
}

.visit-card .visit-photo-thumb {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    background: #F3F4F6;
    border: 1px solid #E5EDE7;
    cursor: pointer;
    transition: all 0.3s ease;
}

.visit-card .visit-photo-thumb:hover {
    transform: scale(1.05);
    box-shadow:
        0 4px 12px rgba(
            0,
            0,
            0,
            0.1
        );
}

.visit-card .visit-purpose {
    font-size: 13px;
    color: #4A5B5D;
    margin-top: 6px;
    padding: 6px 10px;
    background: #FAFDFA;
    border-radius: 6px;
    border-left: 3px solid #16A34A;
}


/* ============================================
   BADGES
   ============================================ */

.badge-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
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

.visit-type-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}


/* ============================================
   BUTTONS
   ============================================ */

.btn-action {
    padding: 6px 14px;
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
    white-space: nowrap;
    height: fit-content;
}

.btn-action:hover {
    transform: translateY(-1px);
}

.btn-start {
    background: #DCFCE7;
    color: #16A34A;
}

.btn-start:hover {
    background: #BBF7D0;
}

.btn-view {
    background: #DBEAFE;
    color: #2563EB;
}

.btn-view:hover {
    background: #BFDBFE;
}

.btn-new {
    background: #14532D;
    color: white;
}

.btn-new:hover {
    background: #052E16;
}


/* ============================================
   EMPTY STATE
   ============================================ */

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6B7A7B;
}

.empty-state i {
    font-size: 48px;
    display: block;
    margin-bottom: 12px;
    color: #D1D5DB;
}

.empty-state .sub-text {
    font-size: 13px;
    margin-top: 4px;
    color: #9CA3AF;
}


/* ============================================
   PAGINATION
   ============================================ */

.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 16px;
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

@media (max-width: 768px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .visit-filter-bar {
        align-items: flex-start;
    }

    .visit-filter-left {
        width: 100%;
    }

    .visit-filter-btn {
        flex: 1;
        justify-content: center;
        min-width: 80px;
    }

    .visit-date-filter {
        width: 100%;
    }

    .visit-date-filter input {
        flex: 1;
    }

    .visit-card .visit-header {
        flex-direction: column;
    }

    .visit-card .visit-shop {
        font-size: 15px;
    }

    .visit-card .visit-meta {
        font-size: 11px;
        gap: 6px 10px;
    }

    .visit-card .visit-location {
        font-size: 11px;
        flex-wrap: wrap;
    }

    .visit-card .visit-location .btn-map {
        font-size: 10px;
        padding: 2px 8px;
    }

    .visit-card .visit-actions {
        gap: 4px;
    }

    .visit-card .visit-actions .btn-action {
        font-size: 11px;
        padding: 10px;
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

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .stat-card {
        padding: 10px 8px;
    }

    .stat-card .stat-number {
        font-size: 18px;
    }

    .visit-card {
        padding: 14px 14px;
    }

    .visit-card .visit-shop {
        font-size: 14px;
    }

    .visit-card .visit-meta {
        font-size: 11px;
        gap: 4px 8px;
    }

    .visit-card .visit-actions .btn-action {
        font-size: 10px;
        padding: 4px 8px;
    }

    .visit-card .visit-location {
        font-size: 10px;
        padding: 4px 8px;
    }

    .visit-card .visit-purpose {
        font-size: 12px;
        padding: 4px 8px;
    }

    .card-header {
        flex-wrap: wrap;
    }

    .card-header .card-title {
        font-size: 16px;
    }

    .btn-new {
        font-size: 12px !important;
        padding: 6px 14px !important;
    }

    .visit-filter-left {
        gap: 5px;
    }

    .visit-filter-btn {
        padding: 6px 8px;
        font-size: 10px;
        min-width: 65px;
    }

    .page-link {
        min-width: 30px;
        height: 30px;
        font-size: 11px;
    }
}


@media (max-width: 360px) {

    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }

    .visit-card .visit-actions {
        flex-direction: column;
    }

    .visit-card .visit-actions .btn-action {
        width: 100%;
        justify-content: center;
    }
}

</style>


<div class="content-card">


    <!-- ============================================
         HEADER
         ============================================ -->

    <div class="card-header">

        <h3 class="card-title">

            <i
                class="fas fa-route"
                style="color:#16A34A;">
            </i>

            My Visits

            <span
                style="
                font-size:14px;
                font-weight:400;
                color:#6B7A7B;
                margin-left:8px;
                ">

                (<?php
                echo $visitStats['total'] ?? 0;
                ?>)

            </span>

        </h3>


        <a
            href="visit-new.php"
            class="btn-action btn-new"
            style="
            padding:8px 20px;
            font-size:14px;
            ">

            <i class="fas fa-plus"></i>

            New Visit

        </a>

    </div>


    <!-- ============================================
         STATISTICS
         ============================================ -->

    <div class="stats-grid">


        <div class="stat-card total">

            <span class="stat-icon">
                <i class="fas fa-list"></i>
            </span>

            <div class="stat-number">

                <?php
                echo $visitStats['total'] ?? 0;
                ?>

            </div>

            <div class="stat-label">
                Total Visits
            </div>

        </div>


        <div class="stat-card assigned">

            <span class="stat-icon">
                <i class="fas fa-clock"></i>
            </span>

            <div class="stat-number">

                <?php
                echo $visitStats['assigned'] ?? 0;
                ?>

            </div>

            <div class="stat-label">
                Pending
            </div>

        </div>


        <div class="stat-card completed">

            <span class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </span>

            <div class="stat-number">

                <?php
                echo $visitStats['completed'] ?? 0;
                ?>

            </div>

            <div class="stat-label">
                Completed
            </div>

        </div>


        <div class="stat-card cancelled">

            <span class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </span>

            <div class="stat-number">

                <?php
                echo $visitStats['cancelled'] ?? 0;
                ?>

            </div>

            <div class="stat-label">
                Cancelled
            </div>

        </div>

    </div>


    <!-- ============================================
         VISIT TYPE FILTER
         ============================================ -->

    <div class="visit-filter-bar">


        <div class="visit-filter-left">

            <span class="visit-filter-label">

                <i class="fas fa-filter"></i>

                Type:

            </span>


            <!-- ALL -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    'all',
                    $statusFilter,
                    $visitDate
                );
                ?>"
                class="visit-filter-btn
                <?php
                echo $filter === 'all'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-list"></i>

                All

                <span class="visit-filter-count">

                    <?php
                    echo $visitStats['total'] ?? 0;
                    ?>

                </span>

            </a>


            <!-- SELF -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    'self',
                    $statusFilter,
                    $visitDate
                );
                ?>"
                class="visit-filter-btn
                <?php
                echo $filter === 'self'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-user"></i>

                Self

            </a>


            <!-- NEW SHOP -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    'new_shop',
                    $statusFilter,
                    $visitDate
                );
                ?>"
                class="visit-filter-btn
                <?php
                echo $filter === 'new_shop'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-store"></i>

                New Shop

            </a>


            <!-- ASSIGNED -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    'assigned',
                    $statusFilter,
                    $visitDate
                );
                ?>"
                class="visit-filter-btn
                <?php
                echo $filter === 'assigned'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-user-plus"></i>

                Assigned

            </a>

        </div>


    </div>


    <!-- ============================================
         STATUS + DATE FILTER
         ============================================ -->

    <div
        class="visit-filter-bar"
        style="margin-top:-10px;">

        <div class="visit-filter-left">

            <span class="visit-filter-label">

                <i class="fas fa-tasks"></i>

                Status:

            </span>


            <!-- ALL STATUS -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    $filter,
                    'all',
                    $visitDate
                );
                ?>"
                class="visit-filter-btn
                <?php
                echo $statusFilter === 'all'
                    ? 'active'
                    : '';
                ?>">

                All

            </a>


            <!-- PENDING -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    $filter,
                    'pending',
                    $visitDate
                );
                ?>"
                class="visit-filter-btn filter-pending
                <?php
                echo $statusFilter === 'pending'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-clock"></i>

                Pending

                <span class="visit-filter-count">

                    <?php
                    echo $visitStats['assigned'] ?? 0;
                    ?>

                </span>

            </a>


            <!-- COMPLETED -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    $filter,
                    'completed',
                    $visitDate
                );
                ?>"
                class="visit-filter-btn filter-completed
                <?php
                echo $statusFilter === 'completed'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-check-circle"></i>

                Completed

                <span class="visit-filter-count">

                    <?php
                    echo $visitStats['completed'] ?? 0;
                    ?>

                </span>

            </a>


            <!-- CANCELLED -->

            <a
                href="<?php
                echo visitPaginationUrl(
                    1,
                    $filter,
                    'cancelled',
                    $visitDate
                );
                ?>"
                class="visit-filter-btn filter-cancelled
                <?php
                echo $statusFilter === 'cancelled'
                    ? 'active'
                    : '';
                ?>">

                <i class="fas fa-times-circle"></i>

                Cancelled

                <span class="visit-filter-count">

                    <?php
                    echo $visitStats['cancelled'] ?? 0;
                    ?>

                </span>

            </a>

        </div>


        <!-- ============================================
             DATE FILTER
             ============================================ -->

        <form
            method="GET"
            class="visit-date-filter">

            <input
                type="hidden"
                name="filter"
                value="<?php
                echo escapeHtml($filter);
                ?>">

            <input
                type="hidden"
                name="status"
                value="<?php
                echo escapeHtml($statusFilter);
                ?>">

            <label
                style="
                font-size:12px;
                font-weight:600;
                color:#14532D;
                ">

                <i class="far fa-calendar"></i>

                Date:

            </label>


            <input
                type="date"
                name="visit_date"
                value="<?php
                echo escapeHtml($visitDate);
                ?>">


            <button
                type="submit"
                class="visit-date-btn">

                <i class="fas fa-filter"></i>

                Apply

            </button>


            <?php if (!empty($visitDate)): ?>

                <a
                    href="<?php
                    echo visitPaginationUrl(
                        1,
                        $filter,
                        $statusFilter,
                        ''
                    );
                    ?>"
                    class="visit-date-clear">

                    <i class="fas fa-times"></i>

                    Clear Date

                </a>

            <?php endif; ?>

        </form>


        <div
            style="
            font-size:12px;
            color:#6B7A7B;
            white-space:nowrap;
            width:100%;
            ">

            Showing:

            <strong style="color:#14532D;">

                <?php
                echo escapeHtml(
                    $filterLabels[$filter]
                );
                ?>

            </strong>

            +

            <strong
                style="color:#14532D;">

                <?php
                echo escapeHtml(
                    $statusLabels[$statusFilter]
                );
                ?>

            </strong>


            <?php if (!empty($visitDate)): ?>

                +

                <strong
                    style="color:#14532D;">

                    <?php
                    echo escapeHtml(
                        formatDate($visitDate)
                    );
                    ?>

                </strong>

            <?php endif; ?>

        </div>

    </div>


    <!-- ============================================
         VISIT LIST
         ============================================ -->

    <?php if (empty($visits)): ?>

        <div class="empty-state">

            <i class="fas fa-map-marked-alt"></i>

            <p>
                No visits found
            </p>

            <p class="sub-text">

                <?php if (
                    $filter !== 'all'
                    || $statusFilter !== 'all'
                    || !empty($visitDate)
                ): ?>

                    No visits match the
                    selected filters.

                <?php else: ?>

                    Start a new visit by clicking
                    the "New Visit" button.

                <?php endif; ?>

            </p>

        </div>

    <?php else: ?>


        <?php foreach ($visits as $visit): ?>


            <?php

            /*
             * Status based card class.
             *
             * completed => green gradient
             * cancelled => red gradient
             * assigned  => normal card
             */

            $cardStatusClass = '';

            if (
                $visit['status'] === 'completed'
            ) {

                $cardStatusClass =
                    'status-completed';

            } elseif (
                $visit['status'] === 'cancelled'
            ) {

                $cardStatusClass =
                    'status-cancelled';
            }

            ?>


            <div
                class="visit-card
                <?php
                echo $cardStatusClass;
                ?>">


                <!-- ====================================
                     VISIT HEADER
                     ==================================== -->

                <div class="visit-header">


                    <div
                        style="
                        flex:1;
                        min-width:0;
                        ">


                        <div class="visit-shop">


                            <span class="shop-name">

                                <?php
                                echo escapeHtml(
                                    $visit['shop_name']
                                        ?? $visit['existing_shop_name']
                                        ?? 'Unknown Shop'
                                );
                                ?>

                            </span>


                            <!-- VISIT TYPE -->

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


                        </div>


                        <!-- META -->

                        <div class="visit-meta">


                            <?php

                            $ownerName =
                                $visit['owner_name']
                                ?? $visit['shop_owner']
                                ?? '';

                            ?>


                            <?php if (
                                !empty($ownerName)
                            ): ?>

                                <span>

                                    <i class="fas fa-user"></i>

                                    <?php
                                    echo escapeHtml(
                                        $ownerName
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $visit['contact_number']
                                )
                            ): ?>

                                <span>

                                    <i class="fas fa-phone"></i>

                                    <?php
                                    echo escapeHtml(
                                        $visit['contact_number']
                                    );
                                    ?>

                                </span>


                            <?php elseif (
                                !empty(
                                    $visit['shop_phone']
                                )
                            ): ?>

                                <span>

                                    <i class="fas fa-phone"></i>

                                    <?php
                                    echo escapeHtml(
                                        $visit['shop_phone']
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $visit['visit_date']
                                )
                            ): ?>

                                <span>

                                    <i
                                        class="far fa-calendar">
                                    </i>

                                    <?php
                                    echo formatDate(
                                        $visit['visit_date']
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>


                        </div>

                    </div>


                    <!-- STATUS -->

                    <div
                        class="statuslocation"
                        style="flex-shrink:0;">


                        <?php

                        $color =
                            $statusColors[
                                $visit['status']
                            ]
                            ?? 'badge-info';

                        ?>


                        <span
                            class="badge-status
                            <?php
                            echo $color;
                            ?>">

                            <?php
                            echo ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $visit['status']
                                )
                            );
                            ?>

                        </span>


                        <!-- LOCATION -->

                        <?php if (
                            !empty(
                                $visit['latitude']
                            )
                            &&
                            !empty(
                                $visit['longitude']
                            )
                        ): ?>


                            <div class="visit-location">


                                <span>

                                    <i
                                        class="fas fa-map-marker-alt"
                                        style="
                                        color:#16A34A;
                                        ">
                                    </i>

                                </span>


                                <a
                                    href="https://www.google.com/maps?q=<?php
                                    echo urlencode(
                                        $visit['latitude']
                                        . ','
                                        . $visit['longitude']
                                    );
                                    ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn-map"
                                    title="View visit location on map">

                                    <i
                                        class="fas fa-external-link-alt">
                                    </i>

                                    View on Map

                                </a>


                                <?php if (
                                    !empty(
                                        $visit['shop_latitude']
                                    )
                                    &&
                                    !empty(
                                        $visit['shop_longitude']
                                    )
                                ): ?>


                                    <span
                                        style="
                                        color:#D1D5DB;
                                        ">

                                        |

                                    </span>


                                    <a
                                        href="https://www.google.com/maps?q=<?php
                                        echo urlencode(
                                            $visit['shop_latitude']
                                            . ','
                                            . $visit['shop_longitude']
                                        );
                                        ?>"
                                        target="_blank"
                                        rel="noopener"
                                        class="btn-map shop-location"
                                        title="View shop location on map">

                                        <i
                                            class="fas fa-store">
                                        </i>

                                        Shop Location

                                    </a>

                                <?php endif; ?>


                            </div>


                        <?php elseif (
                            !empty(
                                $visit['shop_latitude']
                            )
                            &&
                            !empty(
                                $visit['shop_longitude']
                            )
                        ): ?>


                            <div class="visit-location">


                                <span>

                                    <i
                                        class="fas fa-store"
                                        style="
                                        color:#16A34A;
                                        ">
                                    </i>

                                </span>


                                <a
                                    href="https://www.google.com/maps?q=<?php
                                    echo urlencode(
                                        $visit['shop_latitude']
                                        . ','
                                        . $visit['shop_longitude']
                                    );
                                    ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn-map shop-location"
                                    title="View shop location on map">

                                    <i
                                        class="fas fa-external-link-alt">
                                    </i>

                                    Shop Location

                                </a>


                            </div>

                        <?php endif; ?>


                    </div>

                </div>


                <!-- ====================================
                     PURPOSE & REMARK
                     ==================================== -->

                <?php if (
                    !empty($visit['purpose'])
                    ||
                    !empty($visit['remark'])
                ): ?>


                    <div class="visit-purpose">


                        <?php if (
                            !empty(
                                $visit['purpose']
                            )
                        ): ?>

                            <strong>
                                Purpose:
                            </strong>

                            <?php
                            echo escapeHtml(
                                $visit['purpose']
                            );
                            ?>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $visit['remark']
                            )
                        ): ?>

                            <?php

                            if (
                                !empty(
                                    $visit['purpose']
                                )
                            ) {
                                echo ' • ';
                            }

                            ?>

                            <strong>
                                Remark:
                            </strong>

                            <?php
                            echo escapeHtml(
                                $visit['remark']
                            );
                            ?>

                        <?php endif; ?>


                    </div>

                <?php endif; ?>


                <!-- ====================================
                     ACTIONS
                     ==================================== -->

                <div class="visit-actions">


                    <?php if (
                        $visit['status']
                        === 'assigned'
                    ): ?>

                        <a
                            href="visit-start.php?id=<?php
                            echo (int)$visit['id'];
                            ?>"
                            class="btn-action btn-start">

                            <i class="fas fa-play"></i>

                            Start Visit

                        </a>

                    <?php endif; ?>


                    <!-- PHOTO -->

                    <?php if (
                        !empty(
                            $visit['photo']
                        )
                    ): ?>

                        <div
                            style="
                            margin-top:8px;
                            ">

                            <img
                                src="../uploads/visits/<?php
                                echo escapeHtml(
                                    $visit['photo']
                                );
                                ?>"
                                alt="Visit Photo"
                                class="visit-photo-thumb"
                                title="Click to view full image">

                        </div>

                    <?php endif; ?>


                    <a
                        href="visit-view.php?id=<?php
                        echo (int)$visit['id'];
                        ?>"
                        class="btn-action btn-view">

                        <i class="fas fa-eye"></i>

                        View Details

                    </a>


                </div>


            </div>


        <?php endforeach; ?>


        <!-- ============================================
             PAGINATION
             ============================================ -->

        <?php if (
            $totalPages > 1
        ): ?>


            <div class="pagination-wrapper">


                <div class="pagination-info">

                    Showing

                    <strong>
                        <?php
                        echo $offset + 1;
                        ?>
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
                        <?php
                        echo $totalRecords;
                        ?>
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
                            echo visitPaginationUrl(
                                $currentPage - 1,
                                $filter,
                                $statusFilter,
                                $visitDate
                            );
                            ?>"
                            class="page-link"
                            title="Previous page">

                            <i
                                class="fas fa-chevron-left">
                            </i>

                        </a>

                    <?php else: ?>

                        <span
                            class="page-link disabled">

                            <i
                                class="fas fa-chevron-left">
                            </i>

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
                            echo visitPaginationUrl(
                                1,
                                $filter,
                                $statusFilter,
                                $visitDate
                            );
                            ?>"
                            class="page-link">

                            1

                        </a>


                        <?php if (
                            $startPage > 2
                        ): ?>

                            <span
                                class="page-dots">

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
                            echo visitPaginationUrl(
                                $page,
                                $filter,
                                $statusFilter,
                                $visitDate
                            );
                            ?>"
                            class="page-link
                            <?php
                            echo $page === $currentPage
                                ? 'active'
                                : '';
                            ?>">

                            <?php
                            echo $page;
                            ?>

                        </a>

                    <?php endfor; ?>


                    <!-- LAST PAGE -->

                    <?php if (
                        $endPage < $totalPages
                    ): ?>


                        <?php if (
                            $endPage
                            <
                            $totalPages - 1
                        ): ?>

                            <span
                                class="page-dots">

                                ...

                            </span>

                        <?php endif; ?>


                        <a
                            href="<?php
                            echo visitPaginationUrl(
                                $totalPages,
                                $filter,
                                $statusFilter,
                                $visitDate
                            );
                            ?>"
                            class="page-link">

                            <?php
                            echo $totalPages;
                            ?>

                        </a>

                    <?php endif; ?>


                    <!-- NEXT -->

                    <?php if (
                        $currentPage
                        <
                        $totalPages
                    ): ?>

                        <a
                            href="<?php
                            echo visitPaginationUrl(
                                $currentPage + 1,
                                $filter,
                                $statusFilter,
                                $visitDate
                            );
                            ?>"
                            class="page-link"
                            title="Next page">

                            <i
                                class="fas fa-chevron-right">
                            </i>

                        </a>

                    <?php else: ?>

                        <span
                            class="page-link disabled">

                            <i
                                class="fas fa-chevron-right">
                            </i>

                        </span>

                    <?php endif; ?>


                </div>

            </div>


        <?php endif; ?>


    <?php endif; ?>


</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function() {


        // ============================================
        // PHOTO CLICK TO OPEN FULL SIZE
        // ============================================

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

                            window.open(
                                this.src,
                                '_blank'
                            );

                        }
                    );

                }
            );

    }
);

</script>


<?php
require_once
    __DIR__
    . '/../includes/agent_footer.php';
?>