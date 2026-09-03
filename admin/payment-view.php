<?php
/**
 * SAMRIDHI AGRO - Admin Payment View
 * 
 * This page displays detailed information about a specific payment.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 3.1.0
 */

// Set page title
$pageTitle = 'Payment Details';

// Include admin header
require_once '../includes/admin_header.php';

// Require admin login and permission
requirePermissionOrAdmin('payment.view');


// Get database instance
$db = getDB();

// Get payment ID
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paymentId <= 0) {
    setFlashMessage('error', 'Invalid payment ID.');
    redirect('admin/payments.php');
    exit;
}

// Get payment details with remaining balance
$sql = "SELECT p.*, 
        s.shop_name, s.shop_code, s.owner_name, s.phone, s.email,
        s.address, s.city, s.state, s.pincode,
        ua.full_name as agent_name, ua.username as agent_username,
        uc.full_name as confirmed_by_name,
        ag.commission_rate,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE shop_id = p.shop_id AND status != 'cancelled') as total_dues,
        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE shop_id = p.shop_id AND status = 'confirmed') as total_paid
        FROM payments p 
        JOIN shops s ON p.shop_id = s.id 
        LEFT JOIN agents ag ON s.agent_id = ag.id
        LEFT JOIN users ua ON ag.user_id = ua.id
        LEFT JOIN users uc ON p.confirmed_by = uc.id
        WHERE p.id = ?";
$payment = $db->fetchOne($sql, [$paymentId]);

if (!$payment) {
    setFlashMessage('error', 'Payment not found.');
    redirect('admin/payments.php');
    exit;
}

$totalDues = (float)($payment['total_dues'] ?? 0);
$totalPaid = (float)($payment['total_paid'] ?? 0);
$remainingBalance = $totalDues - $totalPaid;

$csrfToken = generateCsrfToken();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    
    .payment-summary {
        background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr;
        gap: 20px;
        align-items: center;
    }
    
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
        padding: 8px 0 16px 20px;
    }
    
    .timeline-item:last-child {
        padding-bottom: 4px;
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
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px #16A34A;
    }
    
    .timeline-item.completed::before {
        background: #16A34A;
        box-shadow: 0 0 0 2px #16A34A;
    }
    
    .timeline-item.pending::before {
        background: #F59E0B;
        box-shadow: 0 0 0 2px #F59E0B;
    }
    
    .timeline-item.failed::before {
        background: #DC2626;
        box-shadow: 0 0 0 2px #DC2626;
    }
    
    .timeline-title {
        font-weight: 600;
        color: #052E16;
        font-size: 14px;
    }
    
    .timeline-time {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }
    
    .timeline-desc {
        font-size: 13px;
        color: #4A5B5D;
        margin-top: 3px;
    }
    
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
    .btn-back { background: #F3F4F6; color: #4A5B5D; }
    .btn-back:hover { background: #E5E7EB; }
    
    .receiver-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 11px;
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
    
    .remaining-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .remaining-badge.zero {
        background: #DCFCE7;
        color: #065F46;
    }
    
    .remaining-badge.positive {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    @media (max-width: 768px) {
        .payment-summary {
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .detail-row {
            flex-direction: column;
            padding: 10px 0;
        }
        .detail-label {
            width: 100%;
        }
        .payment-actions {
            flex-direction: column;
        }
        .payment-actions .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; color: #052E16; margin: 0;">
            <i class="fas fa-receipt" style="color: #16A34A;"></i>
            Payment Details
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $payment['id']; ?>
            </span>
        </h2>
        <a href="payments.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
    </div>
    
    <!-- Payment Summary -->
    <div class="payment-summary">
        <div>
            <div style="font-size: 13px; color: #6B7A7B;">Amount</div>
            <div style="font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; color: #14532D;">
                ₹ <?php echo number_format($payment['amount'], 2); ?>
            </div>
        </div>
        <div>
            <div style="font-size: 13px; color: #6B7A7B;">Status</div>
            <?php 
            $statusColors = [
                'pending' => 'badge-warning',
                'collected' => 'badge-info',
                'submitted' => 'badge-primary',
                'confirmed' => 'badge-success',
                'failed' => 'badge-danger'
            ];
            $color = $statusColors[$payment['status']] ?? 'badge-warning';
            ?>
            <span class="badge-status <?php echo $color; ?>" style="font-size: 16px; padding: 6px 16px;">
                <?php echo ucfirst($payment['status']); ?>
            </span>
        </div>
        <div>
            <div style="font-size: 13px; color: #6B7A7B;">Created On</div>
            <div style="font-weight: 600; color: #052E16;"><?php echo formatDate($payment['created_at']); ?></div>
        </div>
    </div>
    
    <!-- Shop Balance -->
    <div style="background: #F7FCF7; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border: 1px solid #E5EDE7;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
            <div>
                <div style="font-size: 12px; color: #6B7A7B;">Total Dues</div>
                <div style="font-weight: 700; color: #052E16;">₹ <?php echo number_format($totalDues, 2); ?></div>
            </div>
            <div>
                <div style="font-size: 12px; color: #6B7A7B;">Total Paid</div>
                <div style="font-weight: 700; color: #16A34A;">₹ <?php echo number_format($totalPaid, 2); ?></div>
            </div>
            <div>
                <div style="font-size: 12px; color: #6B7A7B;">Remaining Balance</div>
                <div style="font-weight: 700; color: <?php echo $remainingBalance > 0 ? '#DC2626' : '#16A34A'; ?>;">
                    ₹ <?php echo number_format($remainingBalance, 2); ?>
                    <?php if ($remainingBalance <= 0): ?>
                        <span class="remaining-badge zero" style="margin-left: 8px;">✅ Fully Paid</span>
                    <?php else: ?>
                        <span class="remaining-badge positive" style="margin-left: 8px;">⚠️ Pending</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle" style="color: #16A34A;"></i>
            Payment Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment ID</span>
            <span class="detail-value">#<?php echo $payment['id']; ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-value" style="font-weight: 700; color: #14532D;">₹ <?php echo number_format($payment['amount'], 2); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Route</span>
            <span class="detail-value">
                <span class="receiver-badge <?php echo $payment['pay_to']; ?>">
                    <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                    <?php echo $payment['pay_to'] === 'agent' ? 'Agent Collected' : 'Direct'; ?>
                </span>
            </span>
        </div>
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
        <?php if (!empty($payment['notes'])): ?>
        <div class="detail-row">
            <span class="detail-label">Shop Notes</span>
            <span class="detail-value"><?php echo nl2br(escapeHtml($payment['notes'])); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($payment['admin_notes'])): ?>
        <div class="detail-row">
            <span class="detail-label">Admin Notes</span>
            <span class="detail-value" style="color: #7C3AED;"><?php echo nl2br(escapeHtml($payment['admin_notes'])); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Shop Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-store" style="color: #16A34A;"></i>
            Shop Information
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Name</span>
            <span class="detail-value">
                <a href="../admin/shop-view.php?id=<?php echo $payment['shop_id']; ?>" style="color: #16A34A; text-decoration: none;">
                    <?php echo escapeHtml($payment['shop_name']); ?>
                </a>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shop Code</span>
            <span class="detail-value"><?php echo escapeHtml($payment['shop_code']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Owner</span>
            <span class="detail-value"><?php echo escapeHtml($payment['owner_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Contact</span>
            <span class="detail-value">
                <?php echo escapeHtml($payment['phone']); ?>
                <?php if ($payment['email']): ?>
                    <span style="color: #6B7A7B; font-size: 13px; margin-left: 8px;">
                        (<?php echo escapeHtml($payment['email']); ?>)
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <?php if (!empty($payment['address'])): ?>
        <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value">
                <?php echo escapeHtml($payment['address']); ?>
                <?php if (!empty($payment['city']) || !empty($payment['state'])): ?>
                    <br>
                    <?php 
                    $locationParts = [];
                    if (!empty($payment['city'])) $locationParts[] = $payment['city'];
                    if (!empty($payment['state'])) $locationParts[] = $payment['state'];
                    if (!empty($payment['pincode'])) $locationParts[] = $payment['pincode'];
                    echo escapeHtml(implode(', ', $locationParts));
                    ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        <?php if ($payment['agent_name']): ?>
        <div class="detail-row">
            <span class="detail-label">Assigned Agent</span>
            <span class="detail-value">
                <a href="../admin/agent-view.php?id=<?php echo $payment['agent_id']; ?>" style="color: #7C3AED; text-decoration: none;">
                    <?php echo escapeHtml($payment['agent_name']); ?>
                </a>
                <?php if ($payment['commission_rate']): ?>
                    <span style="color: #6B7A7B; font-size: 13px; margin-left: 8px;">
                        (Commission: <?php echo number_format($payment['commission_rate'], 1); ?>%)
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Payment Timeline -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Payment Timeline
        </div>
        <div class="payment-timeline">
            <!-- Created -->
            <div class="timeline-item completed">
                <div class="timeline-title">Payment Created</div>
                <div class="timeline-time"><?php echo formatDate($payment['created_at']); ?></div>
                <div class="timeline-desc">
                    <?php echo escapeHtml($payment['shop_name']); ?> initiated payment of 
                    ₹<?php echo number_format($payment['amount'], 2); ?>
                </div>
            </div>
            
            <!-- Agent Collected -->
            <?php if ($payment['pay_to'] === 'agent'): ?>
                <?php if ($payment['agent_collected_at']): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-title">Collected by Agent</div>
                        <div class="timeline-time"><?php echo formatDate($payment['agent_collected_at']); ?></div>
                        <div class="timeline-desc">
                            <?php echo escapeHtml($payment['agent_name'] ?? 'Agent'); ?> collected the payment
                        </div>
                    </div>
                <?php else: ?>
                    <div class="timeline-item pending">
                        <div class="timeline-title">Pending Collection</div>
                        <div class="timeline-time">Awaiting agent collection</div>
                    </div>
                <?php endif; ?>
                
                <!-- Submitted to Admin -->
                <?php if ($payment['submitted_at']): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-title">Submitted to Admin</div>
                        <div class="timeline-time"><?php echo formatDate($payment['submitted_at']); ?></div>
                        <div class="timeline-desc">
                            Payment submitted to admin for confirmation
                        </div>
                    </div>
                <?php elseif ($payment['agent_collected_at']): ?>
                    <div class="timeline-item pending">
                        <div class="timeline-title">Awaiting Submission</div>
                        <div class="timeline-time">Ready to submit to admin</div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Admin Confirmed -->
            <?php if ($payment['confirmed_at']): ?>
                <div class="timeline-item completed">
                    <div class="timeline-title">Confirmed by Admin</div>
                    <div class="timeline-time"><?php echo formatDate($payment['confirmed_at']); ?></div>
                    <div class="timeline-desc">
                        Payment confirmed by <?php echo escapeHtml($payment['confirmed_by_name'] ?? 'Admin'); ?>
                    </div>
                </div>
            <?php elseif ($payment['status'] === 'failed'): ?>
                <div class="timeline-item failed">
                    <div class="timeline-title">Rejected by Admin</div>
                    <div class="timeline-time"><?php echo formatDate($payment['updated_at']); ?></div>
                    <div class="timeline-desc">
                        Payment was rejected. <?php echo escapeHtml($payment['admin_notes']); ?>
                    </div>
                </div>
            <?php elseif ($payment['pay_to'] === 'admin' || $payment['status'] === 'submitted'): ?>
                <div class="timeline-item pending">
                    <div class="timeline-title">Pending Admin Confirmation</div>
                    <div class="timeline-time">Awaiting admin approval</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Actions -->
    <?php if ($payment['status'] === 'pending' || $payment['status'] === 'submitted'): ?>
    <div style="display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
        <button class="btn-action btn-confirm" onclick="confirmPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
            <i class="fas fa-check"></i> Confirm Payment
        </button>
    </div>
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
            return fetch('../admin/payments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    '<?php echo CSRF_TOKEN_NAME; ?>': csrfToken,
                    'action': 'confirm_payment',
                    'payment_id': paymentId,
                    'admin_notes': adminNotes
                })
            })
            .then(() => ({ success: true }));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Confirmed!',
                text: 'Payment confirmed successfully.',
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