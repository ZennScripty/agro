<?php
/**
 * SAMRIDHI AGRO - Agent Shop Payments
 * 
 * This page allows agents to view, collect, and manage payments
 * from their assigned shops with complete history.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'Shop Payments';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get agent data
$sql = "SELECT a.*, u.full_name FROM agents a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?";
$agent = $db->fetchOne($sql, [$_SESSION['user_id']]);

// ============================================
// HANDLE PAYMENT ACTIONS
// ============================================

// Collect payment from shop
if (isset($_POST['action']) && $_POST['action'] === 'collect_payment') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'cash');
    $transactionId = sanitizeInput($_POST['transaction_id'] ?? '');
    $receiverName = sanitizeInput($_POST['receiver_name'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
    if (empty($receiverName)) {
        echo json_encode(['success' => false, 'message' => 'Please enter receiver name']);
        exit;
    }
    
    // Verify payment belongs to agent's shop
    $sql = "SELECT sp.*, s.shop_name FROM shop_payments sp 
            JOIN shops s ON sp.shop_id = s.id 
            WHERE sp.id = ? AND s.agent_id = ? AND sp.status = 'pending'";
    $payment = $db->fetchOne($sql, [$paymentId, $agent['id']]);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found or already collected']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Update payment
        $sql = "UPDATE shop_payments SET 
                payment_method = ?,
                transaction_id = ?,
                collected_by_agent = 1,
                agent_collection_date = NOW(),
                status = 'collected',
                payment_received_by = 'agent',
                notes = CONCAT(notes, '\\nCollected by agent: ', ?, '\\nReceiver: ', ?),
                updated_at = NOW()
                WHERE id = ?";
        $db->query($sql, [$paymentMethod, $transactionId, $agent['full_name'], $receiverName, $paymentId]);
        
        // Update paid_amount and remaining_amount if partial payment
        $newPaidAmount = $payment['paid_amount'] + $amount;
        $newRemaining = $payment['amount'] - $newPaidAmount;
        
        $sql = "UPDATE shop_payments SET 
                paid_amount = ?,
                remaining_amount = ?
                WHERE id = ?";
        $db->query($sql, [$newPaidAmount, $newRemaining, $paymentId]);
        
        $db->commit();
        
        logActivity('update', $_SESSION['user_id'], 'payment', 
                    'Collected payment of ₹' . $amount . ' from ' . $payment['shop_name'] . 
                    ' (Receiver: ' . $receiverName . ')');
        
        echo json_encode(['success' => true, 'message' => 'Payment collected successfully!']);
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to collect payment: ' . $e->getMessage()]);
        exit;
    }
}

// Submit payment to admin
if (isset($_POST['action']) && $_POST['action'] === 'submit_to_admin') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
    $sql = "SELECT sp.*, s.shop_name FROM shop_payments sp 
            JOIN shops s ON sp.shop_id = s.id 
            WHERE sp.id = ? AND s.agent_id = ? AND sp.status = 'collected'";
    $payment = $db->fetchOne($sql, [$paymentId, $agent['id']]);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found or not collected']);
        exit;
    }
    
    $sql = "UPDATE shop_payments SET 
            submitted_to_admin = 1,
            submitted_to_admin_date = NOW(),
            status = 'submitted',
            updated_at = NOW()
            WHERE id = ?";
    $db->query($sql, [$paymentId]);
    
    logActivity('update', $_SESSION['user_id'], 'payment', 
                'Submitted payment of ₹' . $payment['amount'] . ' to admin for ' . $payment['shop_name']);
    
    echo json_encode(['success' => true, 'message' => 'Payment submitted to admin successfully!']);
    exit;
}

// ============================================
// GET PAYMENTS LIST
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$shopFilter = isset($_GET['shop']) ? (int)$_GET['shop'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

$whereConditions = ["s.agent_id = ?"];
$params = [$agent['id']];

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR sp.transaction_id LIKE ? OR sp.payment_type LIKE ? OR sp.receiver_name LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "sp.status = ?";
    $params[] = $status;
}

if ($shopFilter > 0) {
    $whereConditions[] = "sp.shop_id = ?";
    $params[] = $shopFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Count total
$sql = "SELECT COUNT(*) as total FROM shop_payments sp JOIN shops s ON sp.shop_id = s.id $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = $result['total'] ?? 0;

// Get payments with receiver info
$sql = "SELECT sp.*, s.shop_name, s.shop_code, s.owner_name,
        o.order_number,
        u.full_name as agent_name,
        sp.notes,
        sp.paid_amount,
        sp.remaining_amount
        FROM shop_payments sp 
        JOIN shops s ON sp.shop_id = s.id 
        JOIN agents a ON s.agent_id = a.id
        JOIN users u ON a.user_id = u.id
        LEFT JOIN orders o ON sp.order_id = o.id
        $whereClause
        ORDER BY sp.created_at DESC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$paymentList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalPayments / $perPage);
$pagination = getPagination($totalPayments, $page, $perPage, 'shop-payments.php?page={page}&search=' . urlencode($search) . '&status=' . $status . '&shop=' . $shopFilter);

// Get shops for filter
$sql = "SELECT id, shop_name FROM shops WHERE agent_id = ? ORDER BY shop_name";
$shops = $db->fetchAll($sql, [$agent['id']]);

// Payment statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN sp.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN sp.status = 'collected' THEN 1 ELSE 0 END) as collected,
        SUM(CASE WHEN sp.status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN sp.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        COALESCE(SUM(CASE WHEN sp.status = 'confirmed' THEN sp.amount ELSE 0 END), 0) as confirmed_amount,
        COALESCE(SUM(CASE WHEN sp.status IN ('collected', 'submitted', 'confirmed') THEN sp.amount ELSE 0 END), 0) as collected_amount
        FROM shop_payments sp
        JOIN shops s ON sp.shop_id = s.id
        WHERE s.agent_id = ?";
$paymentStats = $db->fetchOne($sql, [$agent['id']]);

$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .stat-card { background: white; border: 1px solid #E5EDE7; border-radius: 10px; padding: 14px 16px; text-align: center; }
    .stat-card .stat-number { font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; }
    .stat-card .stat-label { font-family: 'Inter', sans-serif; font-size: 12px; color: #6B7A7B; }
    .stat-card .stat-sub { font-size: 11px; color: #6B7A7B; margin-top: 2px; }
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.pending .stat-number { color: #F59E0B; }
    .stat-card.collected .stat-number { color: #3B82F6; }
    .stat-card.submitted .stat-number { color: #7C3AED; }
    .stat-card.confirmed .stat-number { color: #16A34A; }
    
    .payment-card { background: white; border: 1px solid #E5EDE7; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; transition: all 0.3s ease; }
    .payment-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .payment-card .payment-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; }
    .payment-card .payment-shop { font-weight: 600; color: #052E16; font-size: 16px; }
    .payment-card .payment-amount { font-family: 'Space Grotesk', sans-serif; font-size: 20px; font-weight: 700; color: #14532D; }
    .payment-card .payment-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #F0FDF4; }
    .payment-card .payment-details .detail-item .detail-label { font-size: 11px; color: #6B7A7B; }
    .payment-card .payment-details .detail-item .detail-value { font-size: 13px; font-weight: 500; color: #052E16; }
    .payment-card .payment-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; padding-top: 10px; border-top: 1px solid #F0FDF4; }
    
    .badge-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize; }
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .receiver-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; }
    .receiver-badge.agent { background: #EDE9FE; color: #5B21B6; }
    .receiver-badge.admin { background: #FEE2E2; color: #991B1B; }
    
    .btn-action { padding: 6px 14px; border-radius: 6px; border: none; font-size: 12px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.3s ease; cursor: pointer; }
    .btn-action:hover { transform: translateY(-1px); }
    .btn-collect { background: #DCFCE7; color: #16A34A; }
    .btn-submit { background: #EDE9FE; color: #7C3AED; }
    .btn-view { background: #DBEAFE; color: #2563EB; }
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
        <div class="stat-card total"><div class="stat-number"><?php echo number_format($paymentStats['total'] ?? 0); ?></div><div class="stat-label">Total Payments</div></div>
        <div class="stat-card pending"><div class="stat-number"><?php echo number_format($paymentStats['pending'] ?? 0); ?></div><div class="stat-label">Pending</div><div class="stat-sub">To be collected</div></div>
        <div class="stat-card collected"><div class="stat-number"><?php echo number_format($paymentStats['collected'] ?? 0); ?></div><div class="stat-label">Collected</div><div class="stat-sub">₹ <?php echo number_format($paymentStats['collected_amount'] ?? 0, 0); ?></div></div>
        <div class="stat-card submitted"><div class="stat-number"><?php echo number_format($paymentStats['submitted'] ?? 0); ?></div><div class="stat-label">Submitted to Admin</div></div>
        <div class="stat-card confirmed"><div class="stat-number"><?php echo number_format($paymentStats['confirmed'] ?? 0); ?></div><div class="stat-label">Admin Confirmed</div><div class="stat-sub">₹ <?php echo number_format($paymentStats['confirmed_amount'] ?? 0, 0); ?></div></div>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input type="text" name="search" placeholder="Search by shop, receiver, transaction..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
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
                    <?php if ($payment['order_number']): ?>
                        <div style="font-size: 13px; color: #6B7A7B;">Order: #<?php echo escapeHtml($payment['order_number']); ?></div>
                    <?php endif; ?>
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
                    <span class="badge-status <?php echo $color; ?>"><?php echo ucfirst($payment['status']); ?></span>
                </div>
            </div>
            
            <div class="payment-details">
                <div class="detail-item">
                    <div class="detail-label">Payment Type</div>
                    <div class="detail-value"><?php echo str_replace('_', ' ', ucfirst($payment['payment_type'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Amount</div>
                    <div class="detail-value">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                </div>
                <?php if ($payment['paid_amount'] > 0): ?>
                <div class="detail-item">
                    <div class="detail-label">Paid</div>
                    <div class="detail-value" style="color: #16A34A;">₹ <?php echo number_format($payment['paid_amount'], 2); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($payment['remaining_amount'] > 0): ?>
                <div class="detail-item">
                    <div class="detail-label">Remaining</div>
                    <div class="detail-value" style="color: #DC2626;">₹ <?php echo number_format($payment['remaining_amount'], 2); ?></div>
                </div>
                <?php endif; ?>
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
                <?php if ($payment['agent_collection_date']): ?>
                <div class="detail-item">
                    <div class="detail-label">Collected Date</div>
                    <div class="detail-value" style="color: #16A34A;"><?php echo formatDate($payment['agent_collection_date']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($payment['admin_confirm_date']): ?>
                <div class="detail-item">
                    <div class="detail-label">Admin Confirmed</div>
                    <div class="detail-value" style="color: #16A34A;"><?php echo formatDate($payment['admin_confirm_date']); ?></div>
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
                    <button class="btn-action btn-collect" onclick="collectPayment(<?php echo $payment['id']; ?>, <?php echo $payment['remaining_amount']; ?>)">
                        <i class="fas fa-hand-holding-usd"></i> Collect Payment
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

function collectPayment(paymentId, amount) {
    Swal.fire({
        title: 'Collect Payment',
        html: `
            <div style="text-align: left;">
                <p><strong>Amount:</strong> ₹ ${amount.toFixed(2)}</p>
                <div style="margin-top: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 4px;">Payment Method</label>
                    <select id="payment_method" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;">
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div style="margin-top: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 4px;">Transaction ID (Optional)</label>
                    <input type="text" id="transaction_id" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;" placeholder="Enter transaction ID">
                </div>
                <div style="margin-top: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 4px;">Receiver Name</label>
                    <input type="text" id="receiver_name" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;" placeholder="Enter receiver name" required>
                </div>
                <div style="margin-top: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 4px;">Notes (Optional)</label>
                    <textarea id="notes" rows="2" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;" placeholder="Any additional notes"></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#16A34A',
        cancelButtonColor: '#6B7A7B',
        confirmButtonText: '✅ Confirm Collection',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const paymentMethod = document.getElementById('payment_method').value;
            const transactionId = document.getElementById('transaction_id').value;
            const receiverName = document.getElementById('receiver_name').value;
            const notes = document.getElementById('notes').value;
            
            if (!receiverName.trim()) {
                Swal.showValidationMessage('Please enter receiver name');
                return false;
            }
            
            return fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'collect_payment',
                    'payment_id': paymentId,
                    'amount': amount,
                    'payment_method': paymentMethod,
                    'transaction_id': transactionId,
                    'receiver_name': receiverName,
                    'notes': notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to collect payment');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Collected!',
                text: result.value.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => window.location.reload());
        }
    });
}

function submitToAdmin(paymentId) {
    Swal.fire({
        title: 'Submit to Admin?',
        text: 'Are you sure you want to submit this payment to admin for confirmation?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7C3AED',
        cancelButtonColor: '#6B7A7B',
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'submit_to_admin',
                    'payment_id': paymentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Submitted!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>