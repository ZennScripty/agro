<?php

/**
 * SAMRIDHI AGRO - Agent Shop Payments
 * Version: 3.0.1
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

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$shopFilter = isset($_GET['shop']) ? (int)$_GET['shop'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["p.agent_id = ?", "p.pay_to = 'agent'"];
$params = [$agentId];

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR p.transaction_id LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$allowedStatuses = ['pending', 'collected', 'submitted', 'confirmed'];
if ($status !== 'all' && in_array($status, $allowedStatuses, true)) {
    $whereConditions[] = "p.status = ?";
    $params[] = $status;
}

if ($shopFilter > 0) {
    $whereConditions[] = "p.shop_id = ?";
    $params[] = $shopFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

$sql = "SELECT COUNT(*) AS total FROM payments p JOIN shops s ON p.shop_id = s.id $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = (int)($result['total'] ?? 0);

$sql = "SELECT p.*, s.shop_name, s.shop_code, s.owner_name
        FROM payments p
        JOIN shops s ON p.shop_id = s.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$paymentList = $db->fetchAll($sql, $queryParams);

$totalPages = ceil($totalPayments / $perPage);

$pagination = getPagination($totalPayments, $page, $perPage, 'shop-payments.php?page={page}&search=' . urlencode($search) . '&status=' . urlencode($status) . '&shop=' . $shopFilter);

$sql = "SELECT id, shop_name FROM shops WHERE agent_id = ? ORDER BY shop_name ASC";
$shops = $db->fetchAll($sql, [$agentId]);

$sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN p.status = 'collected' THEN 1 ELSE 0 END) AS collected,
            SUM(CASE WHEN p.status = 'submitted' THEN 1 ELSE 0 END) AS submitted,
            SUM(CASE WHEN p.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
            COALESCE(SUM(CASE WHEN p.status = 'confirmed' THEN p.amount ELSE 0 END), 0) AS confirmed_amount,
            COALESCE(SUM(CASE WHEN p.status IN ('collected', 'submitted', 'confirmed') THEN p.amount ELSE 0 END), 0) AS collected_amount
        FROM payments p
        WHERE p.agent_id = ? AND p.pay_to = 'agent'";

$paymentStats = $db->fetchOne($sql, [$agentId]);

$csrfToken = generateCsrfToken();

require_once __DIR__ . '/../includes/agent_header.php';
?>



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
        font-size: 11px;
        color: #6B7A7B;
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

    .payment-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }

    .payment-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
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

    .btn-submit {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .btn-view {
        background: #DBEAFE;
        color: #2563EB;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-rupee-sign" style="color: #16A34A;"></i>
            Shop Payments
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">(<?php echo number_format($totalPayments); ?>)</span>
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
            <div class="stat-sub">To be collected</div>
        </div>
        <div class="stat-card collected">
            <div class="stat-number"><?php echo number_format($paymentStats['collected'] ?? 0); ?></div>
            <div class="stat-label">Collected</div>
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['collected_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card submitted">
            <div class="stat-number"><?php echo number_format($paymentStats['submitted'] ?? 0); ?></div>
            <div class="stat-label">Submitted to Admin</div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo number_format($paymentStats['confirmed'] ?? 0); ?></div>
            <div class="stat-label">Admin Confirmed</div>
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['confirmed_amount'] ?? 0, 0); ?></div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input type="text" name="search" placeholder="Search by shop, transaction ID..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            <select name="status" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
                <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
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
            <div class="payment-card">
                <div class="payment-header">
                    <div>
                        <div class="payment-shop">
                            🏪 <?php echo escapeHtml($payment['shop_name']); ?>
                            <span style="font-size: 13px; color: #6B7A7B; font-weight: 400;">(<?php echo escapeHtml($payment['shop_code']); ?>)</span>
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
                            'confirmed' => 'badge-success'
                        ];
                        $color = $statusColors[$payment['status']] ?? 'badge-warning';
                        ?>
                        <span class="badge-status <?php echo $color; ?>"><?php echo ucfirst($payment['status']); ?></span>
                    </div>
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
                    <?php if ($payment['status'] === 'pending'): ?>
                        <button class="btn-action btn-collect" onclick="collectPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                            <i class="fas fa-hand-holding-usd"></i> Mark Collected
                        </button>
                    <?php elseif ($payment['status'] === 'collected'): ?>
                        <button class="btn-action btn-submit" onclick="submitToAdmin(<?php echo $payment['id']; ?>)">
                            <i class="fas fa-arrow-up"></i> Submit to Admin
                        </button>
                    <?php endif; ?>
                    <a href="shop-payment-view.php?id=<?php echo $payment['id']; ?>" class="btn-action btn-view">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px;"><?php echo $pagination; ?></div>
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
            title: 'Confirm Payment?',
            text: 'Are you sure you want to mark this payment as collected?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16A34A',
            cancelButtonColor: '#6B7A7B',
            confirmButtonText: 'Yes, Confirm',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {

            if (!result.isConfirmed) {
                return;
            }

            // Show loading
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

                    // PHP error / HTML response check
                    if (!text.trim().startsWith('{')) {
                        console.error('Server Response:', text);
                        throw new Error(
                            'Server returned an invalid response. Please check PHP error.'
                        );
                    }

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON:', text);
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
            text: 'Are you sure you want to submit this payment to admin?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
            cancelButtonColor: '#6B7A7B',
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {

            if (!result.isConfirmed) {
                return;
            }

            // Show loading
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

                    // PHP error / HTML response check
                    if (!text.trim().startsWith('{')) {
                        console.error('Server Response:', text);
                        throw new Error(
                            'Server returned an invalid response. Please check PHP error.'
                        );
                    }

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON:', text);
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