<?php

/**
 * SAMRIDHI AGRO - Shop Payments
 * 
 * Balance-based payment system (NOT tied to individual orders).
 * Shop pays against its total outstanding dues (sum of all order totals
 * minus sum of confirmed payments).
 * 
 * Flow 1 (Shop -> Agent -> Admin):
 *   pending -> collected (agent marks) -> submitted (agent forwards to admin) -> confirmed (admin marks)
 * 
 * Flow 2 (Shop -> Admin direct):
 *   pending -> confirmed (admin marks received & paid)
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 3.1.0
 */

// Set page title
$pageTitle = 'Payments';

// Include shop header
require_once __DIR__ . '/../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get shop data with agent details
$sql = "SELECT 
            s.*,
            ag.id AS agent_id,
            a.full_name AS agent_name
        FROM shops s
        LEFT JOIN agents ag ON s.agent_id = ag.id
        LEFT JOIN users a ON ag.user_id = a.id
        WHERE s.user_id = ?";

$shop = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// HANDLE: MAKE A NEW PAYMENT (against total remaining balance)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_payment') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('shop/payments.php');
        exit;
    }

    $amount = (float)($_POST['amount'] ?? 0);
    $payTo = sanitizeInput($_POST['pay_to'] ?? 'agent');
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'cash');
    $transactionId = sanitizeInput($_POST['transaction_id'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    if (!in_array($payTo, ['agent', 'admin'], true)) {
        $payTo = 'agent';
    }

    if ($amount <= 0) {
        setFlashMessage('error', 'Please enter a valid amount.');
        redirect('shop/payments.php');
        exit;
    }

    // Agent ID only comes from the shop's assigned agent. Never trust POST for this.
    $agentId = null;
    if ($payTo === 'agent') {
        $agentId = !empty($shop['agent_id']) ? (int)$shop['agent_id'] : null;
        if (!$agentId) {
            setFlashMessage('error', 'No agent is assigned to this shop.');
            redirect('shop/payments.php');
            exit;
        }
    }

    try {
        // Recalculate remaining balance server-side (never trust client value)
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_dues 
                FROM orders WHERE shop_id = ? AND status != 'cancelled'";
        $duesRow = $db->fetchOne($sql, [$shop['id']]);
        $totalDues = (float)($duesRow['total_dues'] ?? 0);

        $sql = "SELECT COALESCE(SUM(amount), 0) as total_confirmed 
                FROM payments WHERE shop_id = ? AND status = 'confirmed'";
        $confirmedRow = $db->fetchOne($sql, [$shop['id']]);
        $totalConfirmed = (float)($confirmedRow['total_confirmed'] ?? 0);

        $remainingBalance = $totalDues - $totalConfirmed;

        if ($amount > $remainingBalance) {
            setFlashMessage('error', 'Amount cannot exceed remaining balance: ₹ ' . number_format($remainingBalance, 2));
            redirect('shop/payments.php');
            exit;
        }

        $db->beginTransaction();

        $sql = "INSERT INTO payments (
                    shop_id, agent_id, amount, pay_to,
                    payment_method, transaction_id, notes,
                    status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";

        $db->query($sql, [
            $shop['id'],
            $agentId,
            $amount,
            $payTo,
            $paymentMethod,
            $transactionId,
            $notes,
            $_SESSION['user_id']
        ]);

        $paymentId = $db->lastInsertId();

        $db->commit();

        $receiverDisplayName = $payTo === 'agent'
            ? ($shop['agent_name'] ?? 'Agent')
            : 'Admin';

        logActivity(
            'create',
            $_SESSION['user_id'],
            'payment',
            'Made payment of ₹' . $amount . ' to ' . $receiverDisplayName . ' (Payment #' . $paymentId . ')'
        );

        setFlashMessage(
            'success',
            'Payment of ₹' . number_format($amount, 2) . ' recorded successfully! Waiting for confirmation.'
        );

        redirect('shop/payments.php');
        exit;
    } catch (Exception $e) {
        $db->rollback();
        error_log('Payment error: ' . $e->getMessage());
        setFlashMessage('error', 'Failed to process payment. Please try again.');
        redirect('shop/payments.php');
        exit;
    }
}

// ============================================
// GET PAYMENTS LIST
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["p.shop_id = ?"];
$params = [$shop['id']];

if (!empty($search)) {
    $whereConditions[] = "(p.transaction_id LIKE ? OR p.notes LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "p.status = ?";
    $params[] = $status;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM payments p $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = $result['total'] ?? 0;

// Get payments list with receiver (agent) name
$sql = "SELECT p.*, ua.full_name AS agent_name
        FROM payments p
        LEFT JOIN agents ag ON p.agent_id = ag.id
        LEFT JOIN users ua ON ag.user_id = ua.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$paymentList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalPayments / $perPage);
$pagination = getPagination($totalPayments, $page, $perPage, 'payments.php?page={page}&search=' . urlencode($search) . '&status=' . $status);

// ============================================
// BALANCE + STATISTICS
// ============================================

// Total dues from all (non-cancelled) orders
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total_dues 
        FROM orders WHERE shop_id = ? AND status != 'cancelled'";
$duesRow = $db->fetchOne($sql, [$shop['id']]);
$totalAmount = (float)($duesRow['total_dues'] ?? 0);

// Payment stats in one query
$sql = "SELECT
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END), 0) as total_confirmed,
        COALESCE(SUM(CASE WHEN pay_to = 'agent' AND status IN ('collected','submitted','confirmed') THEN amount ELSE 0 END), 0) as agent_collected,
        COALESCE(SUM(CASE WHEN pay_to = 'agent' AND status = 'pending' THEN amount ELSE 0 END), 0) as pending_agent_collection,
        COALESCE(SUM(CASE WHEN pay_to = 'agent' AND status = 'collected' THEN amount ELSE 0 END), 0) as collected_agent_collection,
        COALESCE(SUM(CASE WHEN (pay_to = 'agent' AND status = 'submitted') OR (pay_to = 'admin' AND status = 'pending') THEN amount ELSE 0 END), 0) as pending_admin_collect,
        COALESCE(SUM(CASE WHEN pay_to = 'admin' AND status = 'confirmed' THEN amount ELSE 0 END), 0) as admin_confirmed
        FROM payments WHERE shop_id = ?";
$paymentStats = $db->fetchOne($sql, [$shop['id']]);

$totalConfirmed = (float)($paymentStats['total_confirmed'] ?? 0);
$remainingAmount = $totalAmount - $totalConfirmed;
// $agentCollected = (float)($paymentStats['agent_collected'] ?? 0);
$agentCollected = (float)($paymentStats['collected_agent_collection'] ?? 0);
$pendingAgentCollection = (float)($paymentStats['pending_agent_collection'] ?? 0);
$pendingAdminCollect = (float)($paymentStats['pending_admin_collect'] ?? 0);
$adminConfirmed = (float)($paymentStats['admin_confirmed'] ?? 0);

$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
        /* border: 1px solid #E5EDE7; */
        border-radius: 10px;
        padding: 14px 16px;
        text-align: center;
        background: white;
        transition: all 0.3s ease;
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
        border: 1px solid rgba(20, 83, 45, 0.11);
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.08);
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

    .stat-card.total .stat-number {
        color: #14532D;
    }

    .stat-card.paid .stat-number {
        color: #16A34A;
    }

    .stat-card.remaining .stat-number {
        color: #DC2626;
    }

    .stat-card.agent-collected .stat-number {
        color: #7C3AED;
    }

    .stat-card.pending-agent .stat-number {
        color: #F59E0B;
    }

    .stat-card.pending-admin .stat-number {
        color: #3B82F6;
    }

    .stat-card.paid .stat-icon {
        color: #16A34A;
    }

    .stat-card.total .stat-icon {
        color: #14532D;
    }

    .stat-card.remaining .stat-icon {
        color: #DC2626;
    }

    .stat-card.agent-collected .stat-icon {
        color: #7C3AED;
    }

    .stat-card.pending-agent .stat-icon {
        color: #F59E0B;
    }

    .stat-card.pending-admin .stat-icon {
        color: #3B82F6;
    }

    /* Payment Card - Enhanced */
    .payment-card {
        background: white;
        border: 2px solid #E5EDE7;
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 14px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(5, 46, 22, 0.04);
    }

    .payment-card:hover {
        box-shadow: 0 8px 24px rgba(5, 46, 22, 0.10);
        transform: translateY(-2px);
    }

    .payment-card.confirmed {
        border-color: #16A34A;
        background: linear-gradient(135deg, #ffffff 0%, #F7FCF7 100%);
    }

    .payment-card.confirmed::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 80px;
        height: 80px;
        background: rgba(22, 163, 74, 0.06);
        border-radius: 0 14px 0 80px;
    }

    .payment-card .payment-header {
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .payment-card .payment-id {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px 22px;
    }

    .payment-card .payment-id .pid {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
    }

    .payment-card .payment-id span {
        color: #0b1c1193;
        font-size: 12px;

    }

    .payment-card .payment-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #14532D;
    }

    /* Payment Timeline Flow - Enhanced */
    .payment-timeline {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #F0FDF4;
        display: flex;
        align-items: center;
        gap: 6px;
        justify-content: center;
    }

    .timeline-box {
        display: inline-flex;
        align-items: center;
        gap: 2payment-idpx;
        /* padding: 6px 14px; */
        /* border-radius: 8px; */
        font-size: 11px;
        font-weight: 600;
        /* background: #F3F4F6; */
        background: none;
        border: none;
        color: #6B7A7B;
        /* border: 2px solid #E5EDE7; */
        transition: all 0.3s ease;
        white-space: nowrap;
        flex-direction: column;
    }

    .timeline-box i {
        font-size: 18px;
    }

    .timeline-box .sdate {
        font-size: 7px;

    }

    .timeline-box.done {
        color: #065F46;

    }

    .timeline-box.active {
        color: #92400E;
    }

    .timeline-box.pending {
        color: #6B7A7B;
    }

    .timeline-box .check {
        color: #16A34A;
    }

    .timeline-box .clock {
        color: #F59E0B;
    }

    .timeline-arrow {
        color: #D1D5DB;
        font-size: 16px;
        font-weight: 300;
    }

    .timeline-arrow.done {
        color: #16A34A;
    }

    .payment-card .payment-meta {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .payment-card .payment-meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

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

    .badge-status.badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-status.badge-primary {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .receiver-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }

    .receiver-badge.agent {
        background: #EDE9FE !important;
        color: #5B21B6 !important;
    }

    .receiver-badge.admin {
        background: #FEE2E2 !important;
        color: #991B1B !important;
    }

    .btn-pay {
        padding: 6px 16px;
        background: #16A34A;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-pay:hover {
        background: #14532D;
        transform: translateY(-1px);
    }

    .btn-pay-large {
        padding: 12px 28px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-pay-large:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }

    .btn-pay-large:disabled {
        background: #E5EDE7;
        color: #6B7A7B;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .payment-card .payment-notes {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 8px;
        padding: 6px 12px;
        background: #F7FCF7;
        border-radius: 6px;
        border: 1px solid #F0FDF4;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .stat-card .stat-number {
            font-size: 18px;
        }



        .timeline-arrow {
            /* transform: rotate(90deg); */
            font-size: 12px;
        }



        .payment-card .payment-amount {
            font-size: 18px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .stat-card {
            padding: 10px 12px;
        }

        .stat-card .stat-number {
            font-size: 16px;
        }

        .payment-card {
            padding: 12px 14px;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-credit-card" style="color: #16A34A;"></i>
            Payments
        </h3>
        <?php if ($remainingAmount > 0): ?>
            <button class="btn-pay-large" onclick="openPaymentModal()">
                <i class="fas fa-rupee-sign"></i> Make Payment
            </button>
        <?php endif; ?>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number">₹ <?php echo number_format($totalAmount, 0); ?></div>
            <div class="stat-label">Total Amount</div>
        </div>
        <div class="stat-card paid">
            <div class="stat-number">₹ <?php echo number_format($totalConfirmed, 0); ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card remaining">
            <div class="stat-number">₹ <?php echo number_format($remainingAmount, 0); ?></div>
            <div class="stat-label">Remaining</div>
        </div>
        <div class="stat-card agent-collected">
            <div class="stat-number">₹ <?php echo number_format($agentCollected, 0); ?></div>
            <div class="stat-label">Agent Collected</div>
        </div>
        <div class="stat-card pending-agent">
            <div class="stat-number">₹ <?php echo number_format($pendingAgentCollection, 0); ?></div>
            <div class="stat-label">Pending Agent Collection</div>
        </div>
        <div class="stat-card pending-admin">
            <div class="stat-number">₹ <?php echo number_format($pendingAdminCollect, 0); ?></div>
            <div class="stat-label">Pending Admin Collect</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
        <input type="text" name="search" value="<?php echo escapeHtml($search); ?>" placeholder="Search transaction ID / notes..." class="form-input" style="flex: 1; min-width: 200px; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px;">
        <select name="status" class="form-input" style="padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px;">
            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
            <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted to Admin</option>
            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Paid</option>
        </select>
        <button type="submit" class="btn-pay" style="padding: 8px 20px;">Filter</button>
    </form>

    <!-- Payment List -->
    <?php if (empty($paymentList)): ?>
        <div style="text-align: center; padding: 40px; color: #6B7A7B;">
            <i class="fas fa-wallet" style="font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
            <p>No payments found</p>
        </div>
    <?php else: ?>
        <?php foreach ($paymentList as $payment): ?>
            <?php
            $isConfirmed = $payment['status'] === 'confirmed';
            $isCollected = $payment['status'] === 'collected';
            $isSubmitted = $payment['status'] === 'submitted';
            $isPending = $payment['status'] === 'pending';
            ?>
            <div class="payment-card <?php echo $isConfirmed ? 'confirmed' : ''; ?>">
                <div class="payment-header">
                    <div>
                        <div class="payment-id">
                            <span class="pid">
                                Payment #<?php echo $payment['id']; ?>
                            </span>
                            <span class="receiver-badge <?php echo $payment['pay_to']; ?>" style="margin-left: 6px;">
                                <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                                <?php echo $payment['pay_to'] === 'agent' ? escapeHtml($payment['agent_name'] ?? 'Agent') : 'Direct to Admin'; ?>
                            </span>
                            <?php if ($isConfirmed): ?>
                                <span style="color: #16A34A; font-size: 12px; margin-left: 4px;">
                                    <i class="fas fa-check-circle"></i> Paid ✓
                                </span>
                            <?php endif; ?>

                            <span><i class="far fa-calendar"></i> <?php echo formatDate($payment['created_at']); ?></span>
                            <?php if ($payment['payment_method']): ?>
                                <span>
                                    <i class="fas fa-<?php echo $payment['payment_method'] === 'cash' ? 'money-bill' : ($payment['payment_method'] === 'upi' ? 'mobile-alt' : 'university'); ?>"></i>
                                    <?php echo ucfirst($payment['payment_method']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($payment['transaction_id'])): ?>
                                <span><i class="fas fa-hashtag"></i> <?php echo escapeHtml($payment['transaction_id']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: right; min-width: max-content;">
                        <div class="payment-amount">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                        <?php
                        $statusColors = [
                            'pending' => 'badge-warning',
                            'collected' => 'badge-info',
                            'submitted' => 'badge-primary',
                            'confirmed' => 'badge-success'
                        ];
                        $color = $statusColors[$payment['status']] ?? 'badge-warning';
                        $statusLabel = $payment['status'] === 'confirmed' ? 'Paid' : ucfirst($payment['status']);
                        ?>
                        <span class="badge-status <?php echo $color; ?>">
                            <?php echo $statusLabel; ?>
                        </span>
                    </div>
                </div>

                <!-- Timeline Flow -->
                <div class="payment-timeline">
                    <!-- Step 1: Paid by you -->
                    <span class="timeline-box done">

                        <span><i class="fas fa-check-circle check"></i> </span>
                        <span>Paid</span>


                    </span>

                    <?php if ($payment['pay_to'] === 'agent'): ?>
                        <!-- Arrow -->
                        <span class="timeline-arrow <?php echo ($isCollected || $isSubmitted || $isConfirmed) ? 'done' : ''; ?>">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                        <!-- Step 2: Agent Collected -->
                        <span class="timeline-box <?php echo $isCollected || $isSubmitted || $isConfirmed ? 'done' : ($isPending ? 'pending' : ''); ?>">
                            <?php if ($isCollected || $isSubmitted || $isConfirmed): ?>
                                <i class="fas fa-check-circle check"></i>
                            <?php else: ?>
                                <i class="fas fa-clock clock"></i>
                            <?php endif; ?>
                            Collected
                            <?php if ($payment['agent_collected_at']): ?>
                                <span class="sdate"><?php echo formatDate($payment['agent_collected_at']); ?></span>
                            <?php endif; ?>
                        </span>

                        <!-- Arrow -->
                        <span class="timeline-arrow <?php echo ($isSubmitted || $isConfirmed) ? 'done' : ''; ?>">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                        <!-- Step 3: Submitted to Admin -->
                        <span class="timeline-box <?php echo $isSubmitted || $isConfirmed ? 'done' : ($isCollected ? 'active' : 'pending'); ?>">
                            <?php if ($isSubmitted || $isConfirmed): ?>
                                <i class="fas fa-check-circle check"></i>
                            <?php else: ?>
                                <i class="fas fa-clock clock"></i>
                            <?php endif; ?>
                            Submitted
                            <?php if ($payment['submitted_at']): ?>
                                <span class="sdate"><?php echo formatDate($payment['submitted_at']); ?></span>
                            <?php endif; ?>
                        </span>

                        <!-- Arrow -->
                        <span class="timeline-arrow <?php echo $isConfirmed ? 'done' : ''; ?>">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                        <!-- Step 4: Admin Confirmed / Paid -->
                        <span class="timeline-box <?php echo $isConfirmed ? 'done' : 'pending'; ?>">
                            <?php if ($isConfirmed): ?>
                                <i class="fas fa-check-circle check"></i>
                            <?php else: ?>
                                <i class="fas fa-clock clock"></i>
                            <?php endif; ?>
                            <?php echo $isConfirmed ? 'Paid ✓' : 'Awaiting'; ?>
                            <?php if ($payment['confirmed_at']): ?>
                                <span class="sdate"><?php echo formatDate($payment['confirmed_at']); ?></span>
                            <?php endif; ?>
                        </span>

                    <?php else: ?>
                        <!-- Direct to Admin -->
                        <!-- Arrow -->
                        <span class="timeline-arrow <?php echo $isConfirmed ? 'done' : ''; ?>">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                        <!-- Step 2: Admin Confirmed / Paid -->
                        <span class="timeline-box <?php echo $isConfirmed ? 'done' : 'pending'; ?>">
                            <?php if ($isConfirmed): ?>
                                <i class="fas fa-check-circle check"></i>
                            <?php else: ?>
                                <i class="fas fa-clock clock"></i>
                            <?php endif; ?>
                            <?php echo $isConfirmed ? 'Paid ✓' : 'Awaiting Admin Confirmation'; ?>
                            <?php if ($payment['confirmed_at']): ?>
                                <span class="sdate"><?php echo formatDate($payment['confirmed_at']); ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($payment['notes'])): ?>
                    <div class="payment-notes">
                        <i class="fas fa-sticky-note"></i> <?php echo escapeHtml($payment['notes']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 16px;"><?php echo $pagination; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 20px; color: #052E16; margin-bottom: 20px;">
            <i class="fas fa-credit-card" style="color: #16A34A;"></i> Make Payment
        </h3>

        <form method="POST" action="" id="paymentForm">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="make_payment">

            <div style="margin-bottom: 16px;">
                <div style="font-size: 13px; color: #6B7A7B;">
                    Total Remaining Amount: <strong style="color: #14532D;">₹ <?php echo number_format($remainingAmount, 2); ?></strong>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Amount (₹) <span style="color: #DC2626;">*</span>
                </label>
                <input type="number" name="amount" id="modal_amount" class="form-input" step="0.01" min="1" max="<?php echo $remainingAmount; ?>" required style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
                <div style="font-size: 12px; color: #6B7A7B; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i> Maximum: ₹ <?php echo number_format($remainingAmount, 2); ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Payment Method <span style="color: #DC2626;">*</span>
                </label>
                <select name="payment_method" class="form-input" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Pay to <span style="color: #DC2626;">*</span>
                </label>
                <select name="pay_to" id="pay_to_select" class="form-input" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
                    <option value="agent">Agent</option>
                    <option value="admin">Admin (Direct)</option>
                </select>
                <div style="font-size: 12px; color: #6B7A7B; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i> Select who will receive this payment
                </div>
            </div>

            <div class="form-group" id="receiverNameGroup" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Receiver Name
                </label>
                <input
                    type="text"
                    id="receive_by_name"
                    class="form-input"
                    value="<?php echo escapeHtml($shop['agent_name'] ?? ''); ?>"
                    readonly
                    style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px; background: #F9FAFB;">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Transaction ID (Optional)
                </label>
                <input type="text" name="transaction_id" class="form-input" placeholder="Enter transaction reference" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Notes (Optional)
                </label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Any additional notes" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;"></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn-pay" style="padding: 10px 24px; background: #16A34A; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-check"></i> Confirm Payment
                </button>
                <button type="button" onclick="closePaymentModal()" style="padding: 10px 24px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateReceiverField() {
        const payTo = document.getElementById('pay_to_select');
        const receiverName = document.getElementById('receive_by_name');

        if (payTo.value === 'agent') {
            receiverName.value = <?php echo json_encode($shop['agent_name'] ?? ''); ?> || 'Agent not assigned';
        } else {
            receiverName.value = 'Admin (Direct)';
        }
    }

    function openPaymentModal() {
        document.getElementById('pay_to_select').value = 'agent';
        updateReceiverField();
        document.getElementById('paymentModal').style.display = 'flex';
    }

    document.getElementById('pay_to_select').addEventListener('change', function() {
        updateReceiverField();
    });

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }

    document.getElementById('paymentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePaymentModal();
        }
    });

    document.getElementById('modal_amount').addEventListener('focus', function() {
        this.select();
    });
</script>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>