<?php

/**
 * SAMRIDHI AGRO - Agent Shop Payments
 * Version: 3.2.0
 * 
 * This page shows all payments for shops assigned to the agent.
 * - Shows payments where pay_to = 'agent' (agent collects)
 * - Also shows direct payments (pay_to = 'admin') for visibility
 * - Remaining balance shown for each shop
 */

$pageTitle = 'Shop Payments';

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

requireLogin();
requireRole('agent');

$db = getDB();

$sql = "SELECT a.*, u.full_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ?
        LIMIT 1";

$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

if (!$agent) {
    setFlashMessage('error', 'Agent profile not found.');
    redirect('agent/logout.php');
    exit;
}

$agentId = (int)$agent['id'];

// ============================================
// HANDLE: COLLECT PAYMENT (AJAX)
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'collect_payment') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);

    if ($paymentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID.']);
        exit;
    }

    $sql = "SELECT p.*, s.shop_name, s.shop_code
            FROM payments p
            JOIN shops s ON p.shop_id = s.id
            WHERE p.id = ? AND p.agent_id = ? AND p.pay_to = 'agent' AND p.status = 'pending'
            LIMIT 1";

    $payment = $db->fetchOne($sql, [$paymentId, $agentId]);

    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found or already collected.']);
        exit;
    }

    try {
        $sql = "UPDATE payments SET
                    status = 'collected',
                    agent_collected_at = NOW(),
                    agent_collected_by = ?,
                    updated_at = NOW()
                WHERE id = ? AND agent_id = ? AND pay_to = 'agent' AND status = 'pending'";

        $db->query($sql, [$agentId, $paymentId, $agentId]);

        logActivity('update', $_SESSION['user_id'], 'payment', 'Collected payment of ₹' . $payment['amount'] . ' from ' . $payment['shop_name']);

        echo json_encode(['success' => true, 'message' => 'Payment collected successfully!']);
        exit;
    } catch (Exception $e) {
        error_log('Payment collection error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to collect payment. Please try again.']);
        exit;
    }
}

// ============================================
// HANDLE: SUBMIT PAYMENT TO ADMIN (AJAX)
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'submit_to_admin') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);

    if ($paymentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID.']);
        exit;
    }

    $sql = "SELECT p.*, s.shop_name, s.shop_code
            FROM payments p
            JOIN shops s ON p.shop_id = s.id
            WHERE p.id = ? AND p.agent_id = ? AND p.pay_to = 'agent' AND p.status = 'collected'
            LIMIT 1";

    $payment = $db->fetchOne($sql, [$paymentId, $agentId]);

    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found or not collected yet.']);
        exit;
    }

    try {
        $sql = "UPDATE payments SET
                    status = 'submitted',
                    submitted_at = NOW(),
                    submitted_by = ?,
                    updated_at = NOW()
                WHERE id = ? AND agent_id = ? AND pay_to = 'agent' AND status = 'collected'";

        $db->query($sql, [$agentId, $paymentId, $agentId]);

        logActivity('update', $_SESSION['user_id'], 'payment', 'Submitted payment of ₹' . $payment['amount'] . ' to admin for ' . $payment['shop_name']);

        echo json_encode(['success' => true, 'message' => 'Payment submitted to admin successfully!']);
        exit;
    } catch (Exception $e) {
        error_log('Payment submission error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit payment. Please try again.']);
        exit;
    }
}

// ============================================
// GET SHOPS FOR FILTER
// ============================================
$sql = "SELECT id, shop_name FROM shops WHERE agent_id = ? ORDER BY shop_name ASC";
$shops = $db->fetchAll($sql, [$agentId]);

// ============================================
// GET PAYMENTS LIST WITH REMAINING BALANCE
// ============================================

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$shopFilter = isset($_GET['shop']) ? (int)$_GET['shop'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query - Show ALL payments (agent + direct) for agent's shops
$whereConditions = ["s.agent_id = ?"];
$params = [$agentId];

// If shop filter is applied
if ($shopFilter > 0) {
    $whereConditions[] = "p.shop_id = ?";
    $params[] = $shopFilter;
}

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR p.transaction_id LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// FIXED: Status filter - properly handle 'all' and valid statuses
if ($status !== 'all') {
    $allowedStatuses = ['pending', 'collected', 'submitted', 'confirmed'];
    if (in_array($status, $allowedStatuses, true)) {
        $whereConditions[] = "p.status = ?";
        $params[] = $status;
    }
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) AS total 
        FROM payments p 
        JOIN shops s ON p.shop_id = s.id 
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = (int)($result['total'] ?? 0);

// Get payments with shop details
$sql = "SELECT p.*, 
        s.shop_name, s.shop_code, s.owner_name,
        (
            SELECT COALESCE(SUM(total_amount), 0) 
            FROM orders 
            WHERE shop_id = s.id AND status != 'cancelled'
        ) as total_dues,
        (
            SELECT COALESCE(SUM(amount), 0) 
            FROM payments 
            WHERE shop_id = s.id AND status = 'confirmed'
        ) as total_confirmed
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$paymentList = $db->fetchAll($sql, $queryParams);

// Calculate remaining amount for each payment
foreach ($paymentList as &$payment) {
    $payment['remaining_amount'] = max(0, ($payment['total_dues'] ?? 0) - ($payment['total_confirmed'] ?? 0));
}

// Pagination
$totalPages = ceil($totalPayments / $perPage);
$pagination = getPagination(
    $totalPayments,
    $page,
    $perPage,
    'shop-payments.php?page={page}&search=' . urlencode($search) . '&status=' . urlencode($status) . '&shop=' . $shopFilter
);

// ============================================
// PAYMENT STATISTICS
// ============================================

// For agent-collected payments (pay_to = 'agent')
$sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN p.status = 'collected' THEN 1 ELSE 0 END) AS collected,
            SUM(CASE WHEN p.status = 'submitted' THEN 1 ELSE 0 END) AS submitted,
            SUM(CASE WHEN p.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
            COALESCE(SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END), 0) AS pending_amount,
            COALESCE(SUM(CASE WHEN p.status = 'collected' THEN p.amount ELSE 0 END), 0) AS collected_amount,
            COALESCE(SUM(CASE WHEN p.status = 'submitted' THEN p.amount ELSE 0 END), 0) AS submitted_amount,
            COALESCE(SUM(CASE WHEN p.status = 'confirmed' THEN p.amount ELSE 0 END), 0) AS confirmed_amount,
            COALESCE(SUM(p.amount), 0) AS total_amount
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.agent_id = ? AND p.pay_to = 'agent'";

$agentPaymentStats = $db->fetchOne($sql, [$agentId]);

// For direct payments (pay_to = 'admin') from agent's shops
$sql = "SELECT
            COUNT(*) AS total_direct,
            SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) AS pending_direct,
            SUM(CASE WHEN p.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_direct,
            COALESCE(SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END), 0) AS pending_direct_amount,
            COALESCE(SUM(CASE WHEN p.status = 'confirmed' THEN p.amount ELSE 0 END), 0) AS confirmed_direct_amount,
            COALESCE(SUM(p.amount), 0) AS total_direct_amount
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.agent_id = ? AND p.pay_to = 'admin'";

$directPaymentStats = $db->fetchOne($sql, [$agentId]);

// Total Agent Payments (All payments from agent's shops regardless of pay_to)
$sql = "SELECT
            COALESCE(SUM(p.amount), 0) AS total_agent_payments
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.agent_id = ?";

$totalAgentPayments = $db->fetchOne($sql, [$agentId]);
$totalAgentPaymentsAmount = $totalAgentPayments['total_agent_payments'] ?? 0;

$csrfToken = generateCsrfToken();

require_once __DIR__ . '/../includes/agent_header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 14px 16px;
        text-align: center;
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(175, 247, 255, 0.25);
    }

    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 22px;
        font-weight: 700;
    }

    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        color: #6B7A7B;
    }

    .stat-card .stat-sub {
        font-size: 12px;
        font-weight: 600;
        color: #4bc842cf;
        margin-top: 2px;
    }

    .stat-card.total .stat-number {
        color: #14532D;
    }

    .stat-card.pending .stat-number {
        color: #F59E0B;
    }

    .stat-card.collected .stat-number {
        color: #3B82F6;
    }

    .stat-card.submitted .stat-number {
        color: #7C3AED;
    }

    .stat-card.confirmed .stat-number {
        color: #16A34A;
    }

    .stat-card.direct .stat-number {
        color: #DC2626;
    }

    .stat-card.total-agent .stat-number {
        color: #14532D;
    }

    .payment-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        position: relative;
    }

    .payment-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 140px;
        height: 140px;
        background: rgba(22, 163, 74, 0.06);
        border-radius: 0 14px 0 150px;
    }

    .payment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .payment-card .payment-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
    }

    .payment-card .payment-shop {
        font-weight: 600;
        color: #052E16;
        font-size: 16px;
    }

    .payment-card .payment-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #14532D;
    }

    .payment-card .payment-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .payment-card .payment-details .detail-item .detail-label {
        font-size: 11px;
        color: #6B7A7B;
    }

    .payment-card .payment-details .detail-item .detail-value {
        font-size: 13px;
        font-weight: 500;
        color: #052E16;
    }

    .payment-card .payment-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .badge-status {
        display: inline-block;
        padding: 3px 10px;
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

    .badge-status.badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-status.badge-primary {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .badge-status.badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* Paid status badge - special styling */
    .badge-status.badge-paid {
        background: #DCFCE7;
        color: #065F46;
    }

    .badge-status.badge-paid i {
        margin-right: 3px;
    }

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
    }

    .btn-action:hover {
        transform: translateY(-1px);
    }

    .btn-collect {
        background: #DCFCE7;
        color: #16A34A;
    }

    .btn-collect:hover {
        background: #BBF7D0;
    }

    .btn-submit {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .btn-submit:hover {
        background: #DDD6FE;
    }

    .btn-view {
        background: #DBEAFE;
        color: #2563EB;
    }

    .btn-view:hover {
        background: #BFDBFE;
    }

    .pay-to-badge {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }

    .pay-to-badge.agent {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .pay-to-badge.admin {
        background: #FEE2E2;
        color: #991B1B;
    }

    .remaining-amount {
        font-weight: 700;
        color: #14532D;
    }

    .remaining-amount.zero {
        color: #16A34A;
    }

    .stat-divider {
        border: none;
        border-top: 2px dashed #E5EDE7;
        margin: 12px 0;
    }

    .pagination-wrapper {
        margin-top: 20px;
    }

    /* Confirmed payment card highlight */
    .payment-card.confirmed-card {
        border-left: 4px solid #16A34A;
        background: linear-gradient(135deg, #ffffff 0%, #F7FCF7 100%);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .stat-card {
            padding: 10px 12px;
        }

        .stat-card .stat-number {
            font-size: 18px;
        }

        .payment-card {
            padding: 14px 16px;
        }

        .payment-card .payment-shop {
            font-size: 14px;
        }

        .payment-card .payment-amount {
            font-size: 17px;
        }

        .payment-card .payment-details {
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .payment-card .payment-actions {
            flex-direction: column;
        }

        .payment-card .payment-actions .btn-action {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .stat-card {
            padding: 8px 10px;
            border-radius: 8px;
        }

        .stat-card .stat-number {
            font-size: 15px;
        }

        .stat-card .stat-label {
            font-size: 10px;
        }

        .stat-card .stat-sub {
            font-size: 9px;
        }

        .payment-card {
            padding: 12px 14px;
            border-radius: 10px;
        }

        .payment-card .payment-header {
            flex-direction: column;
        }

        .payment-card .payment-shop {
            font-size: 13px;
        }

        .payment-card .payment-amount {
            font-size: 16px;
        }

        .payment-card .payment-details {
            grid-template-columns: 1fr 1fr;
            gap: 4px;
        }

        .payment-card .payment-details .detail-item .detail-label {
            font-size: 9px;
        }

        .payment-card .payment-details .detail-item .detail-value {
            font-size: 11px;
        }

        .badge-status {
            font-size: 9px;
            padding: 2px 8px;
        }

        .btn-action {
            font-size: 11px;
            padding: 6px 10px;
        }

        .search-wrap input {
            font-size: 13px !important;
            padding: 8px 12px 8px 36px !important;
        }

        .filter-row select,
        .filter-row button {
            font-size: 13px !important;
            padding: 8px 12px !important;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-rupee-sign" style="color: #16A34A;"></i>
            Shop Payments
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalPayments); ?>)
            </span>
        </h3>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <!-- Total Agent Payments (All payments from agent's shops) -->
        <div class="stat-card total-agent">
            <div class="stat-number">₹ <?php echo number_format($totalAgentPaymentsAmount, 0); ?></div>
            <div class="stat-label">Total Agent Payments</div>
            <!-- <div class="stat-sub">All payments from your shops</div> -->
        </div>

        <!-- Agent Route Stats -->
        <div class="stat-card pending">
            <div class="stat-number"><?php echo number_format($agentPaymentStats['pending'] ?? 0); ?></div>
            <div class="stat-label">Pending Collection</div>
            <div class="stat-sub">₹ <?php echo number_format($agentPaymentStats['pending_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card collected">
            <div class="stat-number"><?php echo number_format($agentPaymentStats['collected'] ?? 0); ?></div>
            <div class="stat-label">Collected by Agent</div>
            <div class="stat-sub">₹ <?php echo number_format($agentPaymentStats['collected_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card submitted">
            <div class="stat-number"><?php echo number_format($agentPaymentStats['submitted'] ?? 0); ?></div>
            <div class="stat-label">Submitted to Admin</div>
            <div class="stat-sub">₹ <?php echo number_format($agentPaymentStats['submitted_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo number_format($agentPaymentStats['confirmed'] ?? 0); ?></div>
            <div class="stat-label">Admin Confirmed</div>
            <div class="stat-sub">₹ <?php echo number_format($agentPaymentStats['confirmed_amount'] ?? 0, 0); ?></div>
        </div>
    </div>

    <!-- Divider -->
    <hr class="stat-divider">

    <!-- Direct Payments Stats -->
    <div class="stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card direct">
            <div class="stat-number"><?php echo number_format($directPaymentStats['total_direct'] ?? 0); ?></div>
            <div class="stat-label">Direct to Admin Payments</div>
            <div class="stat-sub">₹ <?php echo number_format($directPaymentStats['total_direct_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo number_format($directPaymentStats['pending_direct'] ?? 0); ?></div>
            <div class="stat-label">Pending (Awaiting Admin)</div>
            <div class="stat-sub">₹ <?php echo number_format($directPaymentStats['pending_direct_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo number_format($directPaymentStats['confirmed_direct'] ?? 0); ?></div>
            <div class="stat-label">Confirmed by Admin</div>
            <div class="stat-sub">₹ <?php echo number_format($directPaymentStats['confirmed_direct_amount'] ?? 0, 0); ?></div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;" class="search-wrap">
                <input type="text" name="search" placeholder="Search by shop, transaction ID..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            <select name="status" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
                <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Paid</option>
            </select>
            <select name="shop" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer; min-width: 150px;">
                <option value="0" <?php echo $shopFilter == 0 ? 'selected' : ''; ?>>All Shops</option>
                <?php foreach ($shops as $shop): ?>
                    <option value="<?php echo $shop['id']; ?>" <?php echo $shopFilter == $shop['id'] ? 'selected' : ''; ?>><?php echo escapeHtml($shop['shop_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="padding: 10px 24px; background: #14532D; color: white; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || $status !== 'all' || $shopFilter > 0): ?>
                <a href="shop-payments.php" style="padding: 10px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 10px; text-decoration: none;">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Payment List -->
    <?php if (empty($paymentList)): ?>
        <div style="text-align: center; padding: 40px; color: #6B7A7B;">
            <i class="fas fa-wallet" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
            <p>No payments found</p>
        </div>
    <?php else: ?>
        <?php foreach ($paymentList as $payment): ?>
            <?php $isConfirmed = $payment['status'] === 'confirmed'; ?>
            <div class="payment-card <?php echo $isConfirmed ? 'confirmed-card' : ''; ?>">
                <div class="payment-header">
                    <div>
                        <div class="payment-shop">
                            🏪 <?php echo escapeHtml($payment['shop_name']); ?>
                            <span style="font-size: 13px; color: #6B7A7B; font-weight: 400;">
                                (<?php echo escapeHtml($payment['shop_code']); ?>)
                            </span>
                            <span class="pay-to-badge <?php echo $payment['pay_to']; ?>" style="margin-left: 6px;">
                                <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                                <?php echo $payment['pay_to'] === 'agent' ? 'Agent' : 'Direct'; ?>
                            </span>
                        </div>
                        <div style="font-size: 13px; color: #6B7A7B;">Payment #<?php echo $payment['id']; ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div class="payment-amount">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                        <?php
                        $statusColors = [
                            'pending' => 'badge-warning',
                            'collected' => 'badge-info',
                            'submitted' => 'badge-primary',
                            'confirmed' => 'badge-paid'
                        ];
                        $color = $statusColors[$payment['status']] ?? 'badge-warning';
                        ?>
                        <span class="badge-status <?php echo $color; ?>">
                            <?php if ($isConfirmed): ?>
                                <i class="fas fa-check-circle"></i> Paid
                            <?php else: ?>
                                <?php echo ucfirst($payment['status']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Remaining Amount -->
                <div style="margin-top: 6px; font-size: 13px;">
                    <span style="color: #6B7A7B;">Shop Remaining Balance:</span>
                    <span class="remaining-amount <?php echo ($payment['remaining_amount'] ?? 0) <= 0 ? 'zero' : ''; ?>">
                        ₹ <?php echo number_format($payment['remaining_amount'] ?? 0, 2); ?>
                    </span>
                    <?php if (($payment['remaining_amount'] ?? 0) <= 0): ?>
                        <span style="color: #16A34A; font-size: 11px; margin-left: 4px;">
                            <i class="fas fa-check-circle"></i> Fully Paid
                        </span>
                    <?php endif; ?>
                </div>

                <div class="payment-details">
                    <div class="detail-item">
                        <div class="detail-label">Created</div>
                        <div class="detail-value"><?php echo formatDate($payment['created_at']); ?></div>
                    </div>
                    <?php if ($payment['payment_method']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Method</div>
                            <div class="detail-value"><?php echo ucfirst($payment['payment_method']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($payment['transaction_id']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Transaction ID</div>
                            <div class="detail-value" style="font-size: 11px; font-family: monospace;"><?php echo escapeHtml($payment['transaction_id']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($payment['agent_collected_at']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Collected On</div>
                            <div class="detail-value" style="color: #16A34A;"><?php echo formatDate($payment['agent_collected_at']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($payment['submitted_at']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Submitted On</div>
                            <div class="detail-value" style="color: #7C3AED;"><?php echo formatDate($payment['submitted_at']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($payment['confirmed_at']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Confirmed On</div>
                            <div class="detail-value" style="color: #16A34A;"><?php echo formatDate($payment['confirmed_at']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($payment['notes'])): ?>
                    <div style="font-size: 12px; color: #6B7A7B; margin-top: 6px; background: #F7FCF7; padding: 6px 10px; border-radius: 6px;">
                        <i class="fas fa-sticky-note"></i> <?php echo nl2br(escapeHtml($payment['notes'])); ?>
                    </div>
                <?php endif; ?>

                <div class="payment-actions">
                    <?php if ($payment['pay_to'] === 'agent'): ?>
                        <?php if ($payment['status'] === 'pending'): ?>
                            <button class="btn-action btn-collect" onclick="collectPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                                <i class="fas fa-hand-holding-usd"></i> Mark Collected
                            </button>
                        <?php elseif ($payment['status'] === 'collected'): ?>
                            <button class="btn-action btn-submit" onclick="submitToAdmin(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-arrow-up"></i> Submit to Admin
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="font-size: 12px; color: #6B7A7B;">
                            <i class="fas fa-info-circle"></i> Direct payment to admin
                            <?php if ($payment['status'] === 'confirmed'): ?>
                                <span style="color: #16A34A;">✓ Paid</span>
                            <?php else: ?>
                                <span style="color: #F59E0B;">⏳ Awaiting admin confirmation</span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <a href="shop-payment-view.php?id=<?php echo $payment['id']; ?>" class="btn-action btn-view">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <?php echo $pagination; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- SweetAlert2 Scripts -->
<script>
    const csrfToken = '<?php echo $csrfToken; ?>';

    /* ============================================
       COLLECT PAYMENT
       ============================================ */
    function collectPayment(paymentId, amount) {
        Swal.fire({
            title: 'Confirm Payment Collection?',
            text: 'Are you sure you want to mark this payment as collected from the shop?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16A34A',
            cancelButtonColor: '#6B7A7B',
            confirmButtonText: 'Yes, Confirm',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                        'action': 'collect_payment',
                        'payment_id': paymentId
                    })
                })
                .then(async (response) => {
                    const text = await response.text();
                    if (!text.trim().startsWith('{')) {
                        console.error('Server Response:', text);
                        throw new Error('Server returned an invalid response.');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid server response.');
                    }
                })
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Collected!',
                            text: data.message || 'Payment marked as collected successfully.',
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to collect payment.'
                        });
                    }
                })
                .catch((error) => {
                    console.error('Collect Payment Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Something went wrong',
                        text: error.message || 'Please try again.'
                    });
                });
        });
    }

    /* ============================================
       SUBMIT PAYMENT TO ADMIN
       ============================================ */
    function submitToAdmin(paymentId) {
        Swal.fire({
            title: 'Submit to Admin?',
            text: 'Are you sure you want to submit this payment to admin for confirmation?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
            cancelButtonColor: '#6B7A7B',
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                        'action': 'submit_to_admin',
                        'payment_id': paymentId
                    })
                })
                .then(async (response) => {
                    const text = await response.text();
                    if (!text.trim().startsWith('{')) {
                        console.error('Server Response:', text);
                        throw new Error('Server returned an invalid response.');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid server response.');
                    }
                })
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: data.message || 'Payment submitted to admin successfully.',
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to submit payment.'
                        });
                    }
                })
                .catch((error) => {
                    console.error('Submit Payment Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Something went wrong',
                        text: error.message || 'Please try again.'
                    });
                });
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>