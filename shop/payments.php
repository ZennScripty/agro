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
 * @version 3.0.0
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

        // Store payment success details for SweetAlert + Voice (same keys/shape as before)
        $_SESSION['payment_success_audio'] = [
            'amount' => (float)$amount,
            'receiver_type' => $payTo,
            'receiver_name' => $receiverDisplayName,
            'order_number' => '',
            'installment_number' => 1
        ];

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
        COALESCE(SUM(CASE WHEN (pay_to = 'agent' AND status = 'submitted') OR (pay_to = 'admin' AND status = 'pending') THEN amount ELSE 0 END), 0) as pending_admin_collect
        FROM payments WHERE shop_id = ?";
$paymentStats = $db->fetchOne($sql, [$shop['id']]);

$totalConfirmed = (float)($paymentStats['total_confirmed'] ?? 0);
$remainingAmount = $totalAmount - $totalConfirmed;
$agentCollected = (float)($paymentStats['agent_collected'] ?? 0);
$pendingAgentCollection = (float)($paymentStats['pending_agent_collection'] ?? 0);
$pendingAdminCollect = (float)($paymentStats['pending_admin_collect'] ?? 0);

// Get payment success notification data
$paymentSuccess = $_SESSION['payment_success_audio'] ?? null;

// Remove it immediately so it shows/plays only once
if ($paymentSuccess) {
    unset($_SESSION['payment_success_audio']);
}
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
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 12px 14px;
        text-align: center;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        background: #fae6e6;
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
    }

    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
    }

    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        color: #6B7A7B;
    }

    .stat-card.total .stat-number {
        color: #14532D;
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

    .payment-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13);
        background: linear-gradient(309deg, #8b8b8b00 0%, rgb(184 227 200 / 34%) 100%, rgba(255, 245, 168, 1) 49%);
    }

    .payment-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .payment-card .payment-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }

    .payment-card .payment-order {
        font-weight: 600;
        color: #052E16;
        font-size: 15px;
    }

    .payment-card .payment-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #14532D;
    }

    /* Stage timeline */
    .payment-timeline {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
    }

    .payment-timeline .timeline-step {
        font-size: 12px;
        color: #6B7A7B;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .payment-timeline .timeline-step.done {
        color: #16A34A;
        font-weight: 600;
    }

    .badge-status {
        display: inline-block;
        padding: 2px 10px;
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
        padding: 14px 32px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 15px;
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

    /* Receiver Badge */
    .receiver-badge {
        display: inline-block;
        padding: 1px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }

    .receiver-badge.agent {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .receiver-badge.admin {
        background: #FEE2E2;
        color: #991B1B;
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
            <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected (by Agent)</option>
            <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted to Admin</option>
            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
        </select>
        <button type="submit" class="btn-pay" style="padding: 8px 20px;">Filter</button>
    </form>

    <!-- Payment List -->
    <?php if (empty($paymentList)): ?>
        <div style="text-align: center; padding: 30px; color: #6B7A7B;">
            <i class="fas fa-wallet" style="font-size: 40px; display: block; margin-bottom: 10px; color: #D1D5DB;"></i>
            <p>No payments found</p>
        </div>
    <?php else: ?>
        <?php foreach ($paymentList as $payment): ?>
            <div class="payment-card">
                <div class="payment-header">
                    <div>
                        <div class="payment-order">
                            Payment #<?php echo $payment['id']; ?>
                            <span class="receiver-badge <?php echo $payment['pay_to']; ?>" style="margin-left: 6px;">
                                <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                                <?php echo $payment['pay_to'] === 'agent' ? escapeHtml($payment['agent_name'] ?? 'Agent') : 'Admin'; ?>
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #6B7A7B;">
                            <i class="far fa-calendar"></i> <?php echo formatDate($payment['created_at']); ?>
                            <?php if ($payment['payment_method']): ?>
                                <span style="margin-left: 8px;">
                                    <i class="fas fa-<?php echo $payment['payment_method'] === 'cash' ? 'money-bill' : ($payment['payment_method'] === 'upi' ? 'mobile-alt' : 'university'); ?>"></i>
                                    <?php echo ucfirst($payment['payment_method']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($payment['transaction_id'])): ?>
                                <span style="margin-left: 8px;">
                                    <i class="fas fa-hashtag"></i> <?php echo escapeHtml($payment['transaction_id']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="payment-amount">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                        <?php
                        $statusColors = [
                            'pending' => 'badge-warning',
                            'collected' => 'badge-info',
                            'submitted' => 'badge-primary',
                            'confirmed' => 'badge-success'
                        ];
                        $color = $statusColors[$payment['status']] ?? 'badge-warning';
                        ?>
                        <span class="badge-status <?php echo $color; ?>">
                            <?php echo ucfirst($payment['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Stage Timeline -->
                <div class="payment-timeline">
                    <div class="timeline-step done">
                        <i class="fas fa-check-circle"></i> Paid by you
                    </div>
                    <?php if ($payment['pay_to'] === 'agent'): ?>
                        <div class="timeline-step <?php echo $payment['agent_collected_at'] ? 'done' : ''; ?>">
                            <i class="fas fa-<?php echo $payment['agent_collected_at'] ? 'check-circle' : 'clock'; ?>"></i>
                            Agent Collected
                            <?php if ($payment['agent_collected_at']): ?>
                                (<?php echo formatDate($payment['agent_collected_at']); ?>)
                            <?php endif; ?>
                        </div>
                        <div class="timeline-step <?php echo $payment['submitted_at'] ? 'done' : ''; ?>">
                            <i class="fas fa-<?php echo $payment['submitted_at'] ? 'check-circle' : 'clock'; ?>"></i>
                            Submitted to Admin
                            <?php if ($payment['submitted_at']): ?>
                                (<?php echo formatDate($payment['submitted_at']); ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="timeline-step <?php echo $payment['confirmed_at'] ? 'done' : ''; ?>">
                        <i class="fas fa-<?php echo $payment['confirmed_at'] ? 'check-circle' : 'clock'; ?>"></i>
                        Admin Confirmed
                        <?php if ($payment['confirmed_at']): ?>
                            (<?php echo formatDate($payment['confirmed_at']); ?>)
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($payment['notes'])): ?>
                    <div style="margin-top: 8px; font-size: 12px; color: #6B7A7B;">
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
                    value=""
                    readonly
                    style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px; background: #F9FAFB;">

                <div style="font-size: 12px; color: #6B7A7B; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i>
                    Agent name is automatically assigned.
                </div>
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
    const agentName = <?php echo json_encode($shop['agent_name'] ?? ''); ?>;

    function updateReceiverField() {
        const payTo = document.getElementById('pay_to_select');
        const receiverGroup = document.getElementById('receiverNameGroup');
        const receiverName = document.getElementById('receive_by_name');

        if (payTo.value === 'agent') {
            receiverGroup.style.display = 'block';
            receiverName.value = agentName || 'Agent not assigned';
        } else {
            receiverGroup.style.display = 'none';
            receiverName.value = '';
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

    // Close modal when clicking outside
    document.getElementById('paymentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePaymentModal();
        }
    });

    // Auto-select amount
    document.getElementById('modal_amount').addEventListener('focus', function() {
        this.select();
    });
</script>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>