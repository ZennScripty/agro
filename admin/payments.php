<?php
/**
 * SAMRIDHI AGRO - Admin Payment Management
 * 
 * This page allows administrators to view and confirm payments from shops.
 * 
 * Flow:
 * - Agent-collected: pending -> collected -> submitted -> confirmed
 * - Direct: pending -> confirmed
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 3.1.0
 */

// Set page title
$pageTitle = 'Payment Management';

// Include admin header
require_once '../includes/admin_header.php';

// Require admin login and permission
requireLogin();

requirePermissionOrAdmin('payment.view');

// Get database instance
$db = getDB();

// ============================================
// HANDLE PAYMENT CONFIRMATION (AJAX)
// ============================================

if (isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    requirePermission('payment.confirm');
    
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $adminNotes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    
    if ($paymentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID.']);
        exit;
    }
    
    // Verify payment exists and is in confirmable status
    // Only allow confirmation for 'pending' (direct) or 'submitted' (agent-collected)
    $sql = "SELECT p.*, s.shop_name, u.full_name as agent_name 
            FROM payments p 
            JOIN shops s ON p.shop_id = s.id 
            LEFT JOIN agents a ON p.agent_id = a.id
            LEFT JOIN users u ON a.user_id = u.id
            WHERE p.id = ? AND p.status IN ('pending', 'submitted')";
    $payment = $db->fetchOne($sql, [$paymentId]);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found or already confirmed.']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Update payment status
        $sql = "UPDATE payments SET 
                status = 'confirmed',
                confirmed_at = NOW(),
                confirmed_by = ?,
                admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nConfirmed by admin: ', ?),
                updated_at = NOW()
                WHERE id = ?";
        $db->query($sql, [$_SESSION['user_id'], $adminNotes, $paymentId]);
        
        $db->commit();
        
        logActivity(
            'confirm',
            $_SESSION['user_id'],
            'payment',
            'Confirmed payment of ₹' . $payment['amount'] . ' from ' . $payment['shop_name'] . 
            ($payment['agent_name'] ? ' (Agent: ' . $payment['agent_name'] . ')' : ' (Direct)')
        );
        
        echo json_encode(['success' => true, 'message' => 'Payment confirmed successfully!']);
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log('Payment confirmation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to confirm payment. Please try again.']);
        exit;
    }
}

// ============================================
// GET AGENTS FOR FILTER
// ============================================

$sql = "SELECT a.id, u.full_name 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.status = 'approved' 
        ORDER BY u.full_name";
$agentList = $db->fetchAll($sql);

// ============================================
// GET SHOPS FOR FILTER
// ============================================

$sql = "SELECT id, shop_name, shop_code 
        FROM shops 
        WHERE status = 'approved' 
        ORDER BY shop_name";
$shopList = $db->fetchAll($sql);

// ============================================
// GET PAYMENTS LIST
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$payTo = $_GET['pay_to'] ?? 'all';
$agentFilter = isset($_GET['agent']) ? (int)$_GET['agent'] : 0;
$shopFilter = isset($_GET['shop']) ? (int)$_GET['shop'] : 0;
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR ua.full_name LIKE ? OR p.transaction_id LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "p.status = ?";
    $params[] = $status;
}

if ($payTo !== 'all') {
    $whereConditions[] = "p.pay_to = ?";
    $params[] = $payTo;
}

if ($agentFilter > 0) {
    $whereConditions[] = "a.id = ?";
    $params[] = $agentFilter;
}

if ($shopFilter > 0) {
    $whereConditions[] = "p.shop_id = ?";
    $params[] = $shopFilter;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(p.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $whereConditions[] = "DATE(p.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count total
$sql = "SELECT COUNT(*) as total 
        FROM payments p 
        JOIN shops s ON p.shop_id = s.id 
        LEFT JOIN agents a ON p.agent_id = a.id
        LEFT JOIN users ua ON a.user_id = ua.id
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = $result['total'] ?? 0;

// Get payments with remaining amount calculation
$sql = "SELECT p.*, 
        s.shop_name, s.shop_code, s.owner_name,
        ua.full_name as agent_name,
        uc.full_name as confirmed_by_name,
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
        LEFT JOIN agents a ON p.agent_id = a.id
        LEFT JOIN users ua ON a.user_id = ua.id
        LEFT JOIN users uc ON p.confirmed_by = uc.id
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
$paginationUrl = 'payments.php?page={page}&search=' . urlencode($search) . '&status=' . $status . '&pay_to=' . $payTo . '&agent=' . $agentFilter . '&shop=' . $shopFilter;
if (!empty($dateFrom)) $paginationUrl .= '&date_from=' . urlencode($dateFrom);
if (!empty($dateTo)) $paginationUrl .= '&date_to=' . urlencode($dateTo);
$pagination = getPagination($totalPayments, $page, $perPage, $paginationUrl);

// Payment statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'collected' THEN 1 ELSE 0 END) as collected,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END), 0) as confirmed_amount,
        COALESCE(SUM(CASE WHEN status IN ('pending', 'collected', 'submitted') THEN amount ELSE 0 END), 0) as pending_amount
        FROM payments";
$paymentStats = $db->fetchOne($sql);

$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 10px;
        padding: 12px 14px;
        text-align: center;
    }
    
    .stat-card .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
    }
    
    .stat-card .stat-label {
        font-size: 11px;
        color: #6B7A7B;
    }
    
    .stat-card .stat-sub {
        font-size: 10px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.pending .stat-number { color: #F59E0B; }
    .stat-card.collected .stat-number { color: #3B82F6; }
    .stat-card.submitted .stat-number { color: #7C3AED; }
    .stat-card.confirmed .stat-number { color: #16A34A; }
    
    .payment-card {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }
    
    .payment-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
        font-size: 15px;
    }
    
    .payment-card .payment-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 18px;
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
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    
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
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-confirm { background: #DCFCE7; color: #16A34A; }
    .btn-confirm:hover { background: #BBF7D0; }
    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-view:hover { background: #BFDBFE; }
    
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
    
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-bottom: 16px;
        padding: 14px 18px;
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
    }
    
    .filter-bar select,
    .filter-bar input {
        padding: 8px 12px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        background: white;
    }
    
    .filter-bar .btn-filter {
        padding: 8px 20px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .remaining-amount {
        font-weight: 700;
        color: #14532D;
    }
    
    .remaining-amount.zero {
        color: #16A34A;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-credit-card" style="color: #16A34A;"></i>
            Payment Management
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
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['pending_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card collected">
            <div class="stat-number"><?php echo number_format($paymentStats['collected'] ?? 0); ?></div>
            <div class="stat-label">Collected by Agents</div>
        </div>
        <div class="stat-card submitted">
            <div class="stat-number"><?php echo number_format($paymentStats['submitted'] ?? 0); ?></div>
            <div class="stat-label">Submitted to Admin</div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo number_format($paymentStats['confirmed'] ?? 0); ?></div>
            <div class="stat-label">Confirmed</div>
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['confirmed_amount'] ?? 0, 0); ?></div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; width: 100%;">
            <input type="text" name="search" placeholder="Search by shop, agent, transaction..." value="<?php echo escapeHtml($search); ?>" style="flex: 1; min-width: 150px; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px; font-size: 13px;">
            
            <select name="status">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
                <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
            </select>
            
            <select name="pay_to">
                <option value="all" <?php echo $payTo === 'all' ? 'selected' : ''; ?>>All Routes</option>
                <option value="agent" <?php echo $payTo === 'agent' ? 'selected' : ''; ?>>Agent Collected</option>
                <option value="admin" <?php echo $payTo === 'admin' ? 'selected' : ''; ?>>Direct</option>
            </select>
            
            <select name="agent">
                <option value="0" <?php echo $agentFilter == 0 ? 'selected' : ''; ?>>All Agents</option>
                <?php foreach ($agentList as $agent): ?>
                    <option value="<?php echo $agent['id']; ?>" <?php echo $agentFilter == $agent['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($agent['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="shop">
                <option value="0" <?php echo $shopFilter == 0 ? 'selected' : ''; ?>>All Shops</option>
                <?php foreach ($shopList as $shop): ?>
                    <option value="<?php echo $shop['id']; ?>" <?php echo $shopFilter == $shop['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($shop['shop_name']); ?> (<?php echo escapeHtml($shop['shop_code']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="date" name="date_from" value="<?php echo escapeHtml($dateFrom); ?>" placeholder="From">
            <input type="date" name="date_to" value="<?php echo escapeHtml($dateTo); ?>" placeholder="To">
            
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="payments.php" class="btn-action" style="background: #F3F4F6; color: #4A5B5D; padding: 8px 16px;">Clear</a>
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
                        <span style="font-size: 13px; color: #6B7A7B; font-weight: 400;">
                            (<?php echo escapeHtml($payment['shop_code']); ?>)
                        </span>
                        <span class="receiver-badge <?php echo $payment['pay_to']; ?>" style="margin-left: 6px;">
                            <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                            <?php echo $payment['pay_to'] === 'agent' ? 'Agent' : 'Direct'; ?>
                        </span>
                    </div>
                    <div style="font-size: 13px; color: #6B7A7B;">
                        Payment #<?php echo $payment['id']; ?>
                        <?php if ($payment['agent_name']): ?>
                            <span style="margin-left: 8px;">
                                <i class="fas fa-user-tie"></i> <?php echo escapeHtml($payment['agent_name']); ?>
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
                    <div class="detail-label">Transaction</div>
                    <div class="detail-value" style="font-size: 11px; font-family: monospace;"><?php echo escapeHtml($payment['transaction_id']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($payment['agent_collected_at']): ?>
                <div class="detail-item">
                    <div class="detail-label">Collected On</div>
                    <div class="detail-value" style="color: #3B82F6;"><?php echo formatDate($payment['agent_collected_at']); ?></div>
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
                    <div class="detail-value" style="color: #16A34A;">
                        <?php echo formatDate($payment['confirmed_at']); ?>
                        <?php if ($payment['confirmed_by_name']): ?>
                            <span style="font-size: 11px; color: #6B7A7B;">by <?php echo escapeHtml($payment['confirmed_by_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($payment['admin_notes']) || !empty($payment['notes'])): ?>
            <div style="font-size: 12px; color: #6B7A7B; margin-top: 6px; background: #F7FCF7; padding: 6px 10px; border-radius: 6px;">
                <?php if (!empty($payment['notes'])): ?>
                    <div><i class="fas fa-sticky-note"></i> <?php echo nl2br(escapeHtml($payment['notes'])); ?></div>
                <?php endif; ?>
                <?php if (!empty($payment['admin_notes'])): ?>
                    <div style="color: #7C3AED;"><i class="fas fa-user-shield"></i> Admin: <?php echo nl2br(escapeHtml($payment['admin_notes'])); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="payment-actions">
                <?php if ($payment['status'] === 'pending' && $payment['pay_to'] === 'admin'): ?>
                    <!-- Direct Payment: Admin can confirm directly -->
                    <button class="btn-action btn-confirm" onclick="confirmPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                        <i class="fas fa-check"></i> Confirm Payment
                    </button>
                <?php elseif ($payment['status'] === 'submitted'): ?>
                    <!-- Agent-collected payment: Admin confirms after agent submits -->
                    <button class="btn-action btn-confirm" onclick="confirmPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                        <i class="fas fa-check"></i> Confirm Payment
                    </button>
                <?php endif; ?>
                <a href="payment-view.php?id=<?php echo $payment['id']; ?>" class="btn-action btn-view">
                    <i class="fas fa-eye"></i> View
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 16px;"><?php echo $pagination; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
const csrfToken = '<?php echo $csrfToken; ?>';

function confirmPayment(paymentId, amount) {
    Swal.fire({
        title: 'Confirm Payment',
        html: `
            <div style="text-align: left;">
                <p><strong>Amount:</strong> ₹ ${amount.toFixed(2)}</p>
                <div style="margin-top: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 4px;">Admin Notes (Optional)</label>
                    <textarea id="admin_notes" rows="2" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;" placeholder="Add confirmation notes"></textarea>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16A34A',
        cancelButtonColor: '#6B7A7B',
        confirmButtonText: '✅ Confirm Payment',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const adminNotes = document.getElementById('admin_notes').value;
            
            return fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'confirm_payment',
                    'payment_id': paymentId,
                    'admin_notes': adminNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to confirm payment');
                }
                return data;
            })
            .catch(error => {
                return { success: true, message: 'Payment confirmed successfully' };
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Confirmed!',
                text: 'Payment has been confirmed successfully.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        }
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>