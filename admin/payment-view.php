<?php
/**
 * SAMRIDHI AGRO - Admin Payment View
 * 
 * This page displays detailed information about a specific payment.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Payment Details';

// Include admin header
require_once '../includes/admin_header.php';

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requireLogin();

// Admin has all access, Staff needs specific permission
if (!isAdmin() && !hasPermission('agent.view')) {
    logActivity('unauthorized_access', $_SESSION['user_id'], 'security', 
                'Attempted to access agents.php without permission');
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
    exit;
}

// Check if user has edit permissions for actions
$canEdit = isAdmin() || hasPermission('agent.edit');
$canDelete = isAdmin() || hasPermission('agent.delete');
$canApprove = isAdmin() || hasPermission('agent.approve');
$canCreate = isAdmin() || hasPermission('agent.create');

// Get database instance
$db = getDB();

// Get payment ID
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paymentId <= 0) {
    setFlashMessage('error', 'Invalid payment ID.');
    redirect('admin/payments.php');
    exit;
}

// Get payment details
$sql = "SELECT sp.*, 
        s.shop_name, s.shop_code, s.owner_name, s.phone, s.email,
        s.address, s.city, s.state, s.pincode,
        u.full_name as agent_name, u.username as agent_username,
        o.order_number, o.total_amount as order_total,
        a.commission_rate
        FROM shop_payments sp 
        JOIN shops s ON sp.shop_id = s.id 
        JOIN agents ag ON s.agent_id = ag.id
        JOIN users u ON ag.user_id = u.id
        LEFT JOIN orders o ON sp.order_id = o.id
        LEFT JOIN agents a ON s.agent_id = a.id
        WHERE sp.id = ?";
$payment = $db->fetchOne($sql, [$paymentId]);

if (!$payment) {
    setFlashMessage('error', 'Payment not found.');
    redirect('admin/payments.php');
    exit;
}

$csrfToken = generateCsrfToken();
?>

<style>
    .detail-section {
        background: white;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
    }
    
    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
    }
    
    .detail-row {
        display: flex;
        padding: 6px 0;
        border-bottom: 1px solid #F7FCF7;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
        width: 160px;
        flex-shrink: 0;
    }
    
    .detail-value {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
        flex: 1;
    }
    
    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .badge-status.badge-success { background: #DCFCE7; color: #065F46; }
    .badge-status.badge-warning { background: #FEF3C7; color: #92400E; }
    .badge-status.badge-info { background: #DBEAFE; color: #1E40AF; }
    .badge-status.badge-primary { background: #EDE9FE; color: #5B21B6; }
    .badge-status.badge-danger { background: #FEE2E2; color: #991B1B; }
    
    .payment-timeline {
        position: relative;
        padding-left: 24px;
    }
    
    .payment-timeline::before {
        content: '';
        position: absolute;
        left: 6px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #E5EDE7;
    }
    
    .timeline-item {
        position: relative;
        padding: 8px 0 8px 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -18px;
        top: 14px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #16A34A;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #16A34A;
    }
    
    .timeline-item.completed::before { background: #16A34A; box-shadow: 0 0 0 2px #16A34A; }
    .timeline-item.pending::before { background: #F59E0B; box-shadow: 0 0 0 2px #F59E0B; }
    .timeline-item.cancelled::before { background: #DC2626; box-shadow: 0 0 0 2px #DC2626; }
    
    .timeline-item .timeline-title { font-weight: 600; color: #052E16; }
    .timeline-item .timeline-time { font-size: 12px; color: #6B7A7B; }
    .timeline-item .timeline-desc { font-size: 13px; color: #4A5B5D; }
    
    .btn-action {
        padding: 8px 20px;
        border-radius: 8px;
        border: none;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-action:hover { transform: translateY(-1px); }
    .btn-confirm { background: #DCFCE7; color: #16A34A; }
    .btn-confirm:hover { background: #BBF7D0; }
    .btn-reject { background: #FEE2E2; color: #DC2626; }
    .btn-reject:hover { background: #FECACA; }
    .btn-back { background: #F3F4F6; color: #4A5B5D; }
    .btn-back:hover { background: #E5E7EB; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-receipt" style="color: #16A34A;"></i>
            Payment Details
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $payment['id']; ?>
            </span>
        </h3>
        <a href="payments.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
    </div>
    
    <!-- Payment Summary -->
    <div style="background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%); border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;">
        <div>
            <div style="font-size: 14px; color: #6B7A7B;">Total Amount</div>
            <div style="font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; color: #14532D;">
                ₹ <?php echo number_format($payment['amount'], 2); ?>
            </div>
        </div>
        <div>
            <div style="font-size: 14px; color: #6B7A7B;">Status</div>
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
            <span class="badge-status <?php echo $color; ?>" style="font-size: 16px; padding: 6px 16px;">
                <?php echo ucfirst($payment['status']); ?>
            </span>
        </div>
        <div>
            <div style="font-size: 14px; color: #6B7A7B;">Payment Date</div>
            <div style="font-weight: 600; color: #052E16;"><?php echo formatDate($payment['payment_date']); ?></div>
        </div>
    </div>
    
    <!-- Payment Details -->
    <div class="detail-section">
        <div class="section-title"><i class="fas fa-info-circle" style="color: #16A34A;"></i> Payment Information</div>
        <div class="detail-row"><span class="detail-label">Payment ID</span><span class="detail-value">#<?php echo $payment['id']; ?></span></div>
        <div class="detail-row"><span class="detail-label">Payment Type</span><span class="detail-value"><?php echo str_replace('_', ' ', ucfirst($payment['payment_type'])); ?></span></div>
        <div class="detail-row"><span class="detail-label">Amount</span><span class="detail-value" style="font-weight: 700; color: #14532D;">₹ <?php echo number_format($payment['amount'], 2); ?></span></div>
        <?php if ($payment['order_number']): ?>
        <div class="detail-row">
            <span class="detail-label">Order</span>
            <span class="detail-value">
                <a href="../admin/order-view.php?id=<?php echo $payment['order_id']; ?>" style="color: #16A34A; text-decoration: none;">
                    #<?php echo escapeHtml($payment['order_number']); ?>
                </a>
                <span style="color: #6B7A7B; font-size: 13px;">(Total: ₹ <?php echo number_format($payment['order_total'], 2); ?>)</span>
            </span>
        </div>
        <?php endif; ?>
        <div class="detail-row">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value">
                <?php if ($payment['payment_method']): ?>
                    <?php echo ucfirst($payment['payment_method']); ?>
                    <?php if ($payment['transaction_id']): ?>
                        <span style="color: #6B7A7B; font-size: 13px; margin-left: 8px;">
                            (TXN: <?php echo escapeHtml($payment['transaction_id']); ?>)
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #6B7A7B;">Not specified</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Commission Rate</span>
            <span class="detail-value"><?php echo number_format($payment['commission_rate'] ?? 0, 1); ?>%</span>
        </div>
        <?php if (!empty($payment['notes'])): ?>
        <div class="detail-row">
            <span class="detail-label">Notes</span>
            <span class="detail-value"><?php echo nl2br(escapeHtml($payment['notes'])); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Shop Information -->
    <div class="detail-section">
        <div class="section-title"><i class="fas fa-store" style="color: #16A34A;"></i> Shop Information</div>
        <div class="detail-row"><span class="detail-label">Shop Name</span><span class="detail-value"><?php echo escapeHtml($payment['shop_name']); ?></span></div>
        <div class="detail-row"><span class="detail-label">Shop Code</span><span class="detail-value"><?php echo escapeHtml($payment['shop_code']); ?></span></div>
        <div class="detail-row"><span class="detail-label">Owner</span><span class="detail-value"><?php echo escapeHtml($payment['owner_name']); ?></span></div>
        <div class="detail-row"><span class="detail-label">Contact</span><span class="detail-value"><?php echo escapeHtml($payment['phone']); ?><?php if ($payment['email']): ?> <span style="color: #6B7A7B; font-size: 13px;">(<?php echo escapeHtml($payment['email']); ?>)</span><?php endif; ?></span></div>
        <?php if (!empty($payment['address'])): ?>
        <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value">
                <?php echo escapeHtml($payment['address']); ?>
                <?php if (!empty($payment['city']) || !empty($payment['state'])): ?>
                    <br><?php echo escapeHtml(implode(', ', array_filter([$payment['city'], $payment['state'], $payment['pincode']]))); ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Agent Information -->
    <div class="detail-section">
        <div class="section-title"><i class="fas fa-user-tie" style="color: #7C3AED;"></i> Agent Information</div>
        <div class="detail-row"><span class="detail-label">Agent Name</span><span class="detail-value"><?php echo escapeHtml($payment['agent_name']); ?></span></div>
        <div class="detail-row"><span class="detail-label">Username</span><span class="detail-value"><?php echo escapeHtml($payment['agent_username']); ?></span></div>
    </div>
    
    <!-- Payment Timeline -->
    <div class="detail-section">
        <div class="section-title"><i class="fas fa-clock" style="color: #16A34A;"></i> Payment Timeline</div>
        <div class="payment-timeline">
            <div class="timeline-item completed">
                <div class="timeline-title">Payment Created</div>
                <div class="timeline-time"><?php echo formatDate($payment['created_at']); ?></div>
                <div class="timeline-desc">Payment record created for <?php echo escapeHtml($payment['shop_name']); ?></div>
            </div>
            
            <?php if ($payment['collected_by_agent']): ?>
            <div class="timeline-item completed">
                <div class="timeline-title">Payment Collected</div>
                <div class="timeline-time"><?php echo formatDate($payment['agent_collection_date']); ?></div>
                <div class="timeline-desc">Collected by <?php echo escapeHtml($payment['agent_name']); ?></div>
            </div>
            <?php else: ?>
            <div class="timeline-item pending">
                <div class="timeline-title">Pending Collection</div>
                <div class="timeline-time">Awaiting agent collection</div>
            </div>
            <?php endif; ?>
            
            <?php if ($payment['submitted_to_admin']): ?>
            <div class="timeline-item completed">
                <div class="timeline-title">Submitted to Admin</div>
                <div class="timeline-time"><?php echo formatDate($payment['submitted_to_admin_date']); ?></div>
                <div class="timeline-desc">Payment submitted to admin for confirmation</div>
            </div>
            <?php elseif ($payment['collected_by_agent']): ?>
            <div class="timeline-item pending">
                <div class="timeline-title">Awaiting Submission</div>
                <div class="timeline-time">Ready to submit to admin</div>
            </div>
            <?php endif; ?>
            
            <?php if ($payment['admin_confirmed']): ?>
            <div class="timeline-item completed">
                <div class="timeline-title">Admin Confirmed</div>
                <div class="timeline-time"><?php echo formatDate($payment['admin_confirm_date']); ?></div>
                <div class="timeline-desc">Payment confirmed by admin</div>
            </div>
            <?php elseif ($payment['submitted_to_admin']): ?>
            <div class="timeline-item pending">
                <div class="timeline-title">Pending Admin Confirmation</div>
                <div class="timeline-time">Awaiting admin approval</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Actions -->
    <?php if ($payment['status'] === 'submitted'): ?>
    <div style="display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
        <button class="btn-action btn-confirm" onclick="confirmPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
            <i class="fas fa-check"></i> Confirm Payment
        </button>
        <button class="btn-action btn-reject" onclick="rejectPayment(<?php echo $payment['id']; ?>)">
            <i class="fas fa-times"></i> Reject Payment
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- SweetAlert2 Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            return fetch('../admin/payments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'confirm_payment',
                    'payment_id': paymentId,
                    'notes': notes
                })
            })
            .then(() => ({ success: true }));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ icon: 'success', title: 'Payment Confirmed!', text: 'Payment confirmed successfully.', timer: 2000, showConfirmButton: false })
            .then(() => window.location.reload());
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
            return fetch('../admin/payments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'reject_payment',
                    'payment_id': paymentId,
                    'reject_reason': rejectReason
                })
            })
            .then(() => ({ success: true }));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ icon: 'success', title: 'Payment Rejected!', text: 'Payment rejected successfully.', timer: 2000, showConfirmButton: false })
            .then(() => window.location.reload());
        }
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>