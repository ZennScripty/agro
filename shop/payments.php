<?php

/**
 * SAMRIDHI AGRO - Shop Payments
 * 
 * This page allows shops to view complete payment history,
 * make partial/full payments, and track payment status.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 2.0.2
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

// Get shop data
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
// HANDLE PAYMENT ACTIONS
// ============================================

// Make a payment (partial or full)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_payment') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('shop/payments.php');
        exit;
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'cash');
    $transactionId = sanitizeInput($_POST['transaction_id'] ?? '');
    $receiveBy = sanitizeInput($_POST['receive_by'] ?? 'agent');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    // Agent ID only comes from the shop's assigned agent.
    // Do not trust agent ID from POST.
    $receiveById = null;

    if ($receiveBy === 'agent') {
        $receiveById = !empty($shop['agent_id']) ? (int)$shop['agent_id'] : null;

        if (!$receiveById) {
            setFlashMessage('error', 'No agent is assigned to this shop.');
            redirect('shop/payments.php');
            exit;
        }
    }

    if ($amount <= 0) {
        setFlashMessage('error', 'Please enter a valid amount.');
        redirect('shop/payments.php');
        exit;
    }

    // Get payment details
    $sql = "SELECT sp.*, o.order_number 
            FROM shop_payments sp 
            LEFT JOIN orders o ON sp.order_id = o.id 
            WHERE sp.id = ? AND sp.shop_id = ?";
    $payment = $db->fetchOne($sql, [$paymentId, $shop['id']]);

    if (!$payment) {
        setFlashMessage('error', 'Payment record not found.');
        redirect('shop/payments.php');
        exit;
    }

    if ($amount > $payment['remaining_amount']) {
        setFlashMessage('error', 'Amount cannot exceed remaining balance: ₹ ' . number_format($payment['remaining_amount'], 2));
        redirect('shop/payments.php');
        exit;
    }

    try {
        $db->beginTransaction();

        // Get next installment number
        $sql = "SELECT MAX(installment_number) as max FROM payment_installments WHERE payment_id = ?";
        $result = $db->fetchOne($sql, [$paymentId]);
        $nextInstallment = ($result['max'] ?? 0) + 1;

        // Get receiver details for storing
        $receiverDisplayName = $receiveBy === 'agent'
            ? ($shop['agent_name'] ?? 'Agent')
            : 'Admin';

        // Create installment record with receiver details
        $sql = "INSERT INTO payment_installments (
            payment_id,
            shop_id,
            order_id,
            installment_number,
            amount,
            payment_date,
            payment_method,
            transaction_id,
            received_by,
            received_by_id,
            status,
            notes,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'pending', ?, NOW()
        )";

        $db->query($sql, [
            $paymentId,
            $shop['id'],
            $payment['order_id'],
            $nextInstallment,
            $amount,
            $paymentMethod,
            $transactionId,
            $receiveBy,
            $receiveById,
            $notes
        ]);

        $installmentId = $db->lastInsertId();

        // Update payment record
        $newPaidAmount = $payment['paid_amount'] + $amount;
        $newRemaining = $payment['amount'] - $newPaidAmount;
        $newStatus = $newRemaining <= 0 ? 'collected' : 'pending';

        $sql = "UPDATE shop_payments SET 
                paid_amount = ?,
                remaining_amount = ?,
                status = ?,
                updated_at = NOW()
                WHERE id = ?";
        $db->query($sql, [$newPaidAmount, $newRemaining, $newStatus, $paymentId]);

        // Update order total paid
        if ($payment['order_id']) {
            $sql = "UPDATE orders SET 
                    total_paid_amount = total_paid_amount + ?,
                    remaining_payment = remaining_payment - ?
                    WHERE id = ?";
            $db->query($sql, [$amount, $amount, $payment['order_id']]);
        }

        $db->commit();

        // Log activity
        logActivity(
            'create',
            $_SESSION['user_id'],
            'payment',
            'Made payment of ₹' . $amount . ' for order #' . ($payment['order_number'] ?? 'N/A') .
                ' (Installment ' . $nextInstallment . ') to ' . $receiverDisplayName
        );

        // Store payment success details for SweetAlert + Voice
        $_SESSION['payment_success_audio'] = [
            'amount' => (float)$amount,
            'receiver_type' => $receiveBy,
            'receiver_name' => $receiverDisplayName,
            'order_number' => $payment['order_number'] ?? '',
            'installment_number' => $nextInstallment
        ];

        setFlashMessage(
            'success',
            'Payment of ₹' . number_format($amount, 2) . ' recorded successfully!'
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
// GET PAYMENTS LIST WITH COMPLETE HISTORY
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["sp.shop_id = ?"];
$params = [$shop['id']];

if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE ? OR sp.transaction_id LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "sp.status = ?";
    $params[] = $status;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM shop_payments sp $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = $result['total'] ?? 0;

// Get payments with complete history
$sql = "SELECT sp.*, o.order_number,
        (SELECT COUNT(*) FROM payment_installments WHERE payment_id = sp.id) as installment_count,
        (SELECT COALESCE(SUM(amount), 0) FROM payment_installments WHERE payment_id = sp.id AND status = 'confirmed') as confirmed_amount,
        (SELECT COALESCE(SUM(amount), 0) FROM payment_installments WHERE payment_id = sp.id) as total_paid_via_installments
        FROM shop_payments sp
        LEFT JOIN orders o ON sp.order_id = o.id
        $whereClause
        ORDER BY sp.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$paymentList = $db->fetchAll($sql, $queryParams);

// Get installment details for each payment with receiver info
foreach ($paymentList as &$payment) {
    $sql = "SELECT * FROM payment_installments WHERE payment_id = ? ORDER BY installment_number ASC";
    $payment['installments'] = $db->fetchAll($sql, [$payment['id']]);
}

// Pagination
$totalPages = ceil($totalPayments / $perPage);
$pagination = getPagination($totalPayments, $page, $perPage, 'payments.php?page={page}&search=' . urlencode($search) . '&status=' . $status);

// Payment statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'collected' THEN 1 ELSE 0 END) as collected,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END), 0) as total_confirmed,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(SUM(remaining_amount), 0) as total_remaining
        FROM shop_payments WHERE shop_id = ?";
$paymentStats = $db->fetchOne($sql, [$shop['id']]);


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
        /* background: white; */
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
        /* box-shadow: 4px 5px 8px 1px rgba(0, 0, 0, 0.13); */
    }

    .stat-card .stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        color: #6B7A7B;
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

    .stat-card.confirmed .stat-number {
        color: #16A34A;
    }

    .stat-card.amount .stat-number {
        color: #7C3AED;
        font-size: 16px;
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

    .payment-card .payment-progress {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #F0FDF4;
    }

    .payment-card .payment-progress .progress-bar {
        height: 6px;
        background: #E5EDE7;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 4px;
    }

    .payment-card .payment-progress .progress-bar .progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
        background: linear-gradient(90deg, #16A34A, #22C55E);
    }

    .payment-card .payment-actions {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Installment History */
    .installment-history {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #F0FDF4;
    }

    .installment-history .installment-title {
        font-size: 12px;
        font-weight: 600;
        color: #6B7A7B;
        margin-bottom: 6px;
    }

    .installment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        font-size: 13px;
        border-bottom: 1px solid #F7FCF7;
    }

    .installment-item:last-child {
        border-bottom: none;
    }

    .installment-item .inst-number {
        font-weight: 500;
        color: #052E16;
    }

    .installment-item .inst-amount {
        font-weight: 600;
        color: #14532D;
    }

    .installment-item .inst-receiver {
        font-size: 11px;
        color: #6B7A7B;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .installment-item .inst-receiver .receiver-agent {
        color: #7C3AED;
        font-weight: 500;
    }

    .installment-item .inst-receiver .receiver-admin {
        color: #DC2626;
        font-weight: 500;
    }

    .installment-item .inst-status {
        font-size: 11px;
    }

    .badge-installment {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-installment.pending {
        background: #FEF3C7;
        color: #92400E;
    }

    .badge-installment.collected {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-installment.submitted {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .badge-installment.confirmed {
        background: #DCFCE7;
        color: #065F46;
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

    .badge-status.badge-danger {
        background: #FEE2E2;
        color: #991B1B;
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
            Payment History
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalPayments); ?>)
            </span>
        </h3>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo number_format($paymentStats['total'] ?? 0); ?></div>
            <div class="stat-label">Total Payments</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo number_format($paymentStats['pending'] ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card collected">
            <div class="stat-number"><?php echo number_format($paymentStats['collected'] ?? 0); ?></div>
            <div class="stat-label">Collected</div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo number_format($paymentStats['confirmed'] ?? 0); ?></div>
            <div class="stat-label">Confirmed</div>
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['total_confirmed'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card amount">
            <div class="stat-number" style="color: #DC2626;">₹ <?php echo number_format($paymentStats['total_remaining'] ?? 0, 0); ?></div>
            <div class="stat-label">Remaining</div>
        </div>
    </div>

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
                            <?php if ($payment['order_number']): ?>
                                Order: #<?php echo escapeHtml($payment['order_number']); ?>
                            <?php else: ?>
                                Payment #<?php echo $payment['id']; ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7A7B;">
                            <i class="far fa-calendar"></i> <?php echo formatDate($payment['created_at']); ?>
                            <?php if ($payment['installment_count'] > 1): ?>
                                <span style="margin-left: 8px;">
                                    <i class="fas fa-credit-card"></i> <?php echo $payment['installment_count']; ?> installments
                                </span>
                            <?php endif; ?>
                            <?php if ($payment['payment_method']): ?>
                                <span style="margin-left: 8px;">
                                    <i class="fas fa-<?php echo $payment['payment_method'] === 'cash' ? 'money-bill' : ($payment['payment_method'] === 'upi' ? 'mobile-alt' : 'university'); ?>"></i>
                                    <?php echo ucfirst($payment['payment_method']); ?>
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
                            'confirmed' => 'badge-success',
                            'failed' => 'badge-danger'
                        ];
                        $color = $statusColors[$payment['status']] ?? 'badge-secondary';
                        ?>
                        <span class="badge-status <?php echo $color; ?>">
                            <?php echo ucfirst($payment['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Payment Progress -->
                <?php
                $paidAmount = $payment['paid_amount'] ?? 0;
                $remainingAmount = $payment['remaining_amount'] ?? ($payment['amount'] - $paidAmount);
                $paidPercent = $payment['amount'] > 0 ? round(($paidAmount / $payment['amount']) * 100) : 0;
                if ($payment['amount'] > 0):
                ?>
                    <div class="payment-progress">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #6B7A7B;">
                            <span>Paid: ₹ <?php echo number_format($paidAmount, 2); ?></span>
                            <span>Remaining: ₹ <?php echo number_format($remainingAmount, 2); ?></span>
                            <span><?php echo $paidPercent; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $paidPercent; ?>%;"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Installment History with Receiver Details -->
                <?php if (!empty($payment['installments'])): ?>
                    <div class="installment-history">
                        <div class="installment-title">
                            <i class="fas fa-list"></i> Installment History
                        </div>
                        <?php foreach ($payment['installments'] as $inst): ?>
                            <div class="installment-item">
                                <span class="inst-number">#<?php echo $inst['installment_number']; ?></span>
                                <span class="inst-amount">₹ <?php echo number_format($inst['amount'], 2); ?></span>
                                <span class="inst-receiver">
                                    <?php
                                    $receiverType = $inst['received_by'] ?? 'agent';
                                    $receiverName = $inst['received_by_name'] ?? ($receiverType === 'agent' ? 'Agent' : 'Admin');
                                    ?>
                                    <span class="receiver-badge <?php echo $receiverType; ?>">
                                        <i class="fas fa-<?php echo $receiverType === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                                        <?php echo escapeHtml($receiverName); ?>
                                    </span>
                                </span>
                                <span class="inst-status">
                                    <?php
                                    $instStatusColors = [
                                        'pending' => 'pending',
                                        'collected' => 'collected',
                                        'submitted' => 'submitted',
                                        'confirmed' => 'confirmed'
                                    ];
                                    $instColor = $instStatusColors[$inst['status']] ?? 'pending';
                                    ?>
                                    <span class="badge-installment <?php echo $instColor; ?>">
                                        <?php echo ucfirst($inst['status']); ?>
                                    </span>
                                    <?php if ($inst['status'] === 'confirmed'): ?>
                                        <span style="font-size: 10px; color: #16A34A;">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <span style="font-size: 11px; color: #6B7A7B;">
                                    <?php echo formatDate($inst['payment_date']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <?php if ($remainingAmount > 0): ?>
                    <div class="payment-actions">
                        <button class="btn-pay" onclick="openPaymentModal(<?php echo $payment['id']; ?>, <?php echo $remainingAmount; ?>, '<?php echo addslashes($payment['order_number'] ?? ''); ?>')">
                            <i class="fas fa-rupee-sign"></i> Pay Now
                        </button>
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
            <input type="hidden" name="payment_id" id="modal_payment_id">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Order <span id="modal_order_number"></span>
                </label>
                <div style="font-size: 13px; color: #6B7A7B;">
                    Remaining Amount: <strong style="color: #14532D;" id="modal_remaining_amount"></strong>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                    Amount (₹) <span style="color: #DC2626;">*</span>
                </label>
                <input type="number" name="amount" id="modal_amount" class="form-input" step="0.01" min="1" required style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
                <div style="font-size: 12px; color: #6B7A7B; margin-top: 4px;">
                    <i class="fas fa-info-circle"></i> Maximum: ₹ <span id="modal_max_amount"></span>
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
                <select name="receive_by" class="form-input" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 14px;">
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
    const agentId = <?php echo json_encode($shop['agent_id'] ?? null); ?>;
    const agentName = <?php echo json_encode($shop['agent_name'] ?? ''); ?>;

    function updateReceiverField() {

        const receiveBy = document.querySelector('select[name="receive_by"]');
        const receiverGroup = document.getElementById('receiverNameGroup');
        const receiverName = document.getElementById('receive_by_name');

        if (receiveBy.value === 'agent') {

            // Show agent name
            receiverGroup.style.display = 'block';

            // Automatically show assigned agent name
            receiverName.value = agentName || 'Agent not assigned';

        } else {

            // Hide receiver name for admin
            receiverGroup.style.display = 'none';

            receiverName.value = '';
        }
    }


    function openPaymentModal(paymentId, remainingAmount, orderNumber) {

        document.getElementById('modal_payment_id').value = paymentId;

        document.getElementById('modal_remaining_amount').textContent =
            '₹ ' + remainingAmount.toFixed(2);

        document.getElementById('modal_max_amount').textContent =
            remainingAmount.toFixed(2);

        document.getElementById('modal_order_number').textContent =
            orderNumber ? '# ' + orderNumber : '';

        document.getElementById('modal_amount').max = remainingAmount;

        document.getElementById('modal_amount').value = remainingAmount;

        // Default receiver = Agent
        const receiveBy = document.querySelector('select[name="receive_by"]');
        receiveBy.value = 'agent';

        updateReceiverField();

        document.getElementById('paymentModal').style.display = 'flex';
    }


    // Agent / Admin selection
    document.querySelector('select[name="receive_by"]').addEventListener('change', function() {
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