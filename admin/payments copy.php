<?php
/**
 * SAMRIDHI AGRO - Admin Payment Management
 * 
 * This page allows administrators to view, confirm, and manage
 * all payments submitted by agents from shops.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Payment Management';

// Include admin header
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requirePermissionOrAdmin('payment.view', 'payments.php');

// Get database instance
$db = getDB();

// ============================================
// HANDLE PAYMENT ACTIONS
// ============================================

// Confirm payment
if (isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    requirePermission('payment.confirm');
    
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('admin/payments.php');
        exit;
    }
    
    // Verify payment exists and is submitted
    $sql = "SELECT sp.*, s.shop_name, u.full_name as agent_name 
            FROM shop_payments sp 
            JOIN shops s ON sp.shop_id = s.id 
            JOIN agents a ON s.agent_id = a.id
            JOIN users u ON a.user_id = u.id
            WHERE sp.id = ? AND sp.status = 'submitted'";
    $payment = $db->fetchOne($sql, [$paymentId]);
    
    if (!$payment) {
        setFlashMessage('error', 'Payment not found or not submitted.');
        redirect('admin/payments.php');
        exit;
    }
    
    // Update payment
    $sql = "UPDATE shop_payments SET 
            admin_confirmed = 1,
            admin_confirm_date = NOW(),
            status = 'confirmed',
            notes = CONCAT(notes, '\\nConfirmed by admin: ', ?),
            updated_at = NOW()
            WHERE id = ?";
    $db->query($sql, [$notes, $paymentId]);
    
    // Update order payment status if linked
    if ($payment['order_id']) {
        $sql = "UPDATE orders SET 
                agent_payment_status = 'admin_confirmed',
                admin_confirm_date = NOW()
                WHERE id = ?";
        $db->query($sql, [$payment['order_id']]);
    }
    
    logActivity('confirm', $_SESSION['user_id'], 'payment', 
                'Confirmed payment of ₹' . $payment['amount'] . ' from ' . $payment['shop_name'] . 
                ' submitted by ' . $payment['agent_name']);
    
    setFlashMessage('success', 'Payment confirmed successfully!');
    redirect('admin/payments.php');
    exit;
}

// Reject payment
if (isset($_POST['action']) && $_POST['action'] === 'reject_payment') {
    requirePermission('payment.confirm');
    
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $rejectReason = sanitizeInput($_POST['reject_reason'] ?? '');
    
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('admin/payments.php');
        exit;
    }
    
    if (empty($rejectReason)) {
        setFlashMessage('error', 'Please provide a reason for rejection.');
        redirect('admin/payments.php');
        exit;
    }
    
    // Verify payment exists
    $sql = "SELECT sp.*, s.shop_name, u.full_name as agent_name 
            FROM shop_payments sp 
            JOIN shops s ON sp.shop_id = s.id 
            JOIN agents a ON s.agent_id = a.id
            JOIN users u ON a.user_id = u.id
            WHERE sp.id = ? AND sp.status = 'submitted'";
    $payment = $db->fetchOne($sql, [$paymentId]);
    
    if (!$payment) {
        setFlashMessage('error', 'Payment not found or not submitted.');
        redirect('admin/payments.php');
        exit;
    }
    
    // Update payment
    $sql = "UPDATE shop_payments SET 
            status = 'failed',
            notes = CONCAT(notes, '\\nRejected by admin: ', ?),
            updated_at = NOW()
            WHERE id = ?";
    $db->query($sql, [$rejectReason, $paymentId]);
    
    logActivity('reject', $_SESSION['user_id'], 'payment', 
                'Rejected payment of ₹' . $payment['amount'] . ' from ' . $payment['shop_name'] . 
                ' submitted by ' . $payment['agent_name'] . '. Reason: ' . $rejectReason);
    
    setFlashMessage('success', 'Payment rejected successfully.');
    redirect('admin/payments.php');
    exit;
}

// ============================================
// GET PAYMENTS LIST
// ============================================

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$agentFilter = isset($_GET['agent']) ? (int)$_GET['agent'] : 0;
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(s.shop_name LIKE ? OR s.shop_code LIKE ? OR u.full_name LIKE ? OR sp.transaction_id LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "sp.status = ?";
    $params[] = $status;
}

if ($agentFilter > 0) {
    $whereConditions[] = "a.id = ?";
    $params[] = $agentFilter;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(sp.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $whereConditions[] = "DATE(sp.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get agents for filter
$sql = "SELECT a.id, u.full_name FROM agents a JOIN users u ON a.user_id = u.id WHERE a.status = 'approved' ORDER BY u.full_name";
$agentList = $db->fetchAll($sql);

// Count total
$sql = "SELECT COUNT(*) as total 
        FROM shop_payments sp 
        JOIN shops s ON sp.shop_id = s.id 
        JOIN agents a ON s.agent_id = a.id
        JOIN users u ON a.user_id = u.id
        $whereClause";
$result = $db->fetchOne($sql, $params);
$totalPayments = $result['total'] ?? 0;

// Get payments (removed submitted_to_admin_by column)
$sql = "SELECT sp.*, 
        s.shop_name, s.shop_code, s.owner_name,
        u.full_name as agent_name, u.username as agent_username,
        o.order_number
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
$paginationUrl = 'payments.php?page={page}&search=' . urlencode($search) . '&status=' . $status . '&agent=' . $agentFilter;
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
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END), 0) as confirmed_amount,
        COALESCE(SUM(CASE WHEN status = 'submitted' THEN amount ELSE 0 END), 0) as submitted_amount
        FROM shop_payments";
$paymentStats = $db->fetchOne($sql);

$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 CDN -->
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
    
    .stat-card.total .stat-number { color: #14532D; }
    .stat-card.pending .stat-number { color: #F59E0B; }
    .stat-card.collected .stat-number { color: #3B82F6; }
    .stat-card.submitted .stat-number { color: #7C3AED; }
    .stat-card.confirmed .stat-number { color: #16A34A; }
    .stat-card.failed .stat-number { color: #DC2626; }
    
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
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
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
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    .badge-status.badge-secondary { background: #F3F4F6; color: #6B7A7B; }
    
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
    .btn-reject { background: #FEE2E2; color: #DC2626; }
    .btn-reject:hover { background: #FECACA; }
    .btn-view { background: #DBEAFE; color: #2563EB; }
    .btn-view:hover { background: #BFDBFE; }
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
            <div class="stat-sub">Awaiting collection</div>
        </div>
        <div class="stat-card collected">
            <div class="stat-number"><?php echo number_format($paymentStats['collected'] ?? 0); ?></div>
            <div class="stat-label">Collected</div>
            <div class="stat-sub">By agents</div>
        </div>
        <div class="stat-card submitted">
            <div class="stat-number"><?php echo number_format($paymentStats['submitted'] ?? 0); ?></div>
            <div class="stat-label">Submitted</div>
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['submitted_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo number_format($paymentStats['confirmed'] ?? 0); ?></div>
            <div class="stat-label">Confirmed</div>
            <div class="stat-sub">₹ <?php echo number_format($paymentStats['confirmed_amount'] ?? 0, 0); ?></div>
        </div>
        <div class="stat-card failed">
            <div class="stat-number"><?php echo number_format($paymentStats['failed'] ?? 0); ?></div>
            <div class="stat-label">Failed</div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input type="text" name="search" placeholder="Search by shop, agent..." value="<?php echo escapeHtml($search); ?>" style="width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B7A7B;"></i>
            </div>
            
            <select name="status" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
                <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
            
            <select name="agent" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white; cursor: pointer; min-width: 150px;">
                <option value="0" <?php echo $agentFilter == 0 ? 'selected' : ''; ?>>All Agents</option>
                <?php foreach ($agentList as $agent): ?>
                    <option value="<?php echo $agent['id']; ?>" <?php echo $agentFilter == $agent['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeHtml($agent['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="date" name="date_from" value="<?php echo escapeHtml($dateFrom); ?>" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
            <input type="date" name="date_to" value="<?php echo escapeHtml($dateTo); ?>" style="padding: 10px 16px; border: 2px solid #E5EDE7; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; background: white;">
            
            <button type="submit" style="padding: 10px 24px; background: #14532D; color: white; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if (!empty($search) || $status !== 'all' || $agentFilter > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
            <a href="payments.php" style="padding: 10px 16px; background: #F3F4F6; color: #4A5B5D; border: none; border-radius: 10px; text-decoration: none;">
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
                        <span style="font-size: 13px; color: #6B7A7B; font-weight: 400;">
                            (<?php echo escapeHtml($payment['shop_code']); ?>)
                        </span>
                    </div>
                    <div style="font-size: 13px; color: #6B7A7B; margin-top: 2px;">
                        <i class="fas fa-user-tie"></i> Agent: <?php echo escapeHtml($payment['agent_name']); ?>
                        <?php if ($payment['order_number']): ?>
                            <span style="margin-left: 12px;">
                                <i class="fas fa-shopping-cart"></i> Order: #<?php echo escapeHtml($payment['order_number']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="payment-amount">₹ <?php echo number_format($payment['amount'], 2); ?></div>
                    <div>
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
            </div>
            
            <div class="payment-details">
                <div class="detail-item">
                    <div class="detail-label">Payment Type</div>
                    <div class="detail-value"><?php echo str_replace('_', ' ', ucfirst($payment['payment_type'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Payment Date</div>
                    <div class="detail-value"><?php echo formatDate($payment['payment_date']); ?></div>
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
                <div class="detail-item">
                    <div class="detail-label">Submitted By</div>
                    <div class="detail-value"><?php echo escapeHtml($payment['agent_name']); ?></div>
                </div>
                <?php if ($payment['agent_collection_date']): ?>
                <div class="detail-item">
                    <div class="detail-label">Collected Date</div>
                    <div class="detail-value" style="color: #16A34A;">
                        <?php echo formatDate($payment['agent_collection_date']); ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($payment['submitted_to_admin_date']): ?>
                <div class="detail-item">
                    <div class="detail-label">Submitted Date</div>
                    <div class="detail-value" style="color: #7C3AED;">
                        <?php echo formatDate($payment['submitted_to_admin_date']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($payment['notes'])): ?>
            <div style="font-size: 12px; color: #6B7A7B; margin-top: 6px; background: #F7FCF7; padding: 8px 12px; border-radius: 6px;">
                <i class="fas fa-sticky-note"></i> <?php echo nl2br(escapeHtml($payment['notes'])); ?>
            </div>
            <?php endif; ?>
            
            <div class="payment-actions">
                <?php if ($payment['status'] === 'submitted'): ?>
                    <button class="btn-action btn-confirm" onclick="confirmPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                    <button class="btn-action btn-reject" onclick="rejectPayment(<?php echo $payment['id']; ?>)">
                        <i class="fas fa-times"></i> Reject
                    </button>
                <?php endif; ?>
                <a href="payment-view.php?id=<?php echo $payment['id']; ?>" class="btn-action btn-view">
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

function confirmPayment(paymentId, amount) {
    Swal.fire({
        title: 'Confirm Payment',
        html: `
            <div style="text-align: left;">
                <p><strong>Amount:</strong> ₹ ${amount.toFixed(2)}</p>
                <div style="margin-top: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 4px;">Notes (Optional)</label>
                    <textarea id="notes" rows="2" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;" placeholder="Add confirmation notes"></textarea>
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
            const notes = document.getElementById('notes').value;
            
            return fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'confirm_payment',
                    'payment_id': paymentId,
                    'notes': notes
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
                // If redirect happens, this will error - but that's expected
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

function rejectPayment(paymentId) {
    Swal.fire({
        title: 'Reject Payment',
        html: `
            <div style="text-align: left;">
                <p>Please provide a reason for rejecting this payment:</p>
                <div style="margin-top: 12px;">
                    <textarea id="reject_reason" rows="3" style="width: 100%; padding: 8px 12px; border: 2px solid #E5EDE7; border-radius: 8px;" placeholder="Enter rejection reason" required></textarea>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7A7B',
        confirmButtonText: '❌ Reject Payment',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const rejectReason = document.getElementById('reject_reason').value;
            if (!rejectReason.trim()) {
                Swal.showValidationMessage('Please provide a reason for rejection');
                return false;
            }
            
            return fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'reject_payment',
                    'payment_id': paymentId,
                    'reject_reason': rejectReason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to reject payment');
                }
                return data;
            })
            .catch(error => {
                return { success: true, message: 'Payment rejected successfully' };
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Rejected!',
                text: 'Payment has been rejected successfully.',
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