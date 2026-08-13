<?php
/**
 * SAMRIDHI AGRO - Agent Shop Payment View
 * 
 * This page displays detailed information about a specific payment
 * with complete history and receiver details.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// Set page title
$pageTitle = 'Payment Details';

// Include agent header
require_once __DIR__ . '/../includes/agent_header.php';

// Require agent login
requireLogin();
requireRole('agent');

// Get database instance
$db = getDB();

// Get payment ID
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paymentId <= 0) {
    setFlashMessage('error', 'Invalid payment ID.');
    redirect('agent/shop-payments.php');
    exit;
}

// Get payment details with receiver info
$sql = "SELECT sp.*, s.shop_name, s.shop_code, s.owner_name, s.phone, s.email,
        s.address, s.city, s.state, s.pincode,
        o.order_number, o.total_amount as order_total,
        u.full_name as agent_name,
        sp.paid_amount, sp.remaining_amount,
        sp.notes,
        sp.transaction_id,
        sp.payment_method,
        sp.collected_by_agent,
        sp.agent_collection_date,
        sp.submitted_to_admin,
        sp.submitted_to_admin_date,
        sp.admin_confirmed,
        sp.admin_confirm_date
        FROM shop_payments sp 
        JOIN shops s ON sp.shop_id = s.id 
        JOIN agents a ON s.agent_id = a.id
        JOIN users u ON a.user_id = u.id
        LEFT JOIN orders o ON sp.order_id = o.id
        WHERE sp.id = ? AND s.agent_id = ?";
$payment = $db->fetchOne($sql, [$paymentId, $agent['id']]);

if (!$payment) {
    setFlashMessage('error', 'Payment not found or not assigned to you.');
    redirect('agent/shop-payments.php');
    exit;
}

// Extract receiver name from notes
$receiverName = '';
if (!empty($payment['notes'])) {
    preg_match('/Receiver: (.*?)(?:\n|$)/', $payment['notes'], $matches);
    if (!empty($matches[1])) {
        $receiverName = trim($matches[1]);
    }
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
    
    .receiver-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
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
    .btn-collect { background: #16A34A; color: white; }
    .btn-collect:hover { background: #14532D; }
    .btn-submit { background: #7C3AED; color: white; }
    .btn-submit:hover { background: #5B21B6; }
    .btn-back { background: #F3F4F6; color: #4A5B5D; }
    .btn-back:hover { background: #E5E7EB; }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-receipt" style="color: #16A34A;"></i>
            Payment Details
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">#<?php echo $payment['id']; ?></span>
        </h3>
        <a href="shop-payments.php" class="card-action">
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
            <?php if ($payment['paid_amount'] > 0): ?>
            <div style="font-size: 13px; color: #16A34A;">
                <i class="fas fa-check-circle"></i> Paid: ₹ <?php echo number_format($payment['paid_amount'], 2); ?>
            </div>
            <?php endif; ?>
            <?php if ($payment['remaining_amount'] > 0): ?>
            <div style="font-size: 13px; color: #DC2626;">
                <i class="fas fa-clock"></i> Remaining: ₹ <?php echo number_format($payment['remaining_amount'], 2); ?>
            </div>
            <?php endif; ?>
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
            <span class="detail-label">Payment Type</span>
            <span class="detail-value"><?php echo str_replace('_', ' ', ucfirst($payment['payment_type'])); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-value" style="font-weight: 700; color: #14532D;">₹ <?php echo number_format($payment['amount'], 2); ?></span>
        </div>
        <?php if ($payment['paid_amount'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Amount Paid</span>
            <span class="detail-value" style="color: #16A34A; font-weight: 600;">₹ <?php echo number_format($payment['paid_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($payment['remaining_amount'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Remaining</span>
            <span class="detail-value" style="color: #DC2626; font-weight: 600;">₹ <?php echo number_format($payment['remaining_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($payment['order_number']): ?>
        <div class="detail-row">
            <span class="detail-label">Order</span>
            <span class="detail-value">
                <a href="order-view.php?id=<?php echo $payment['order_id']; ?>" style="color: #16A34A; text-decoration: none;">
                    #<?php echo escapeHtml($payment['order_number']); ?>
                </a>
                <span style="color: #6B7A7B; font-size: 13px;">
                    (Total: ₹ <?php echo number_format($payment['order_total'], 2); ?>)
                </span>
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
        <?php if (!empty($receiverName)): ?>
        <div class="detail-row" style="background: #F7FCF7; border-radius: 8px; padding: 8px 12px;">
            <span class="detail-label">Received By</span>
            <span class="detail-value">
                <span class="receiver-badge <?php echo strpos(strtolower($payment['notes']), 'agent') !== false ? 'agent' : 'admin'; ?>">
                    <i class="fas fa-<?php echo strpos(strtolower($payment['notes']), 'agent') !== false ? 'user-tie' : 'user-shield'; ?>"></i>
                    <?php echo escapeHtml($receiverName); ?>
                </span>
            </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($payment['notes'])): ?>
        <div class="detail-row">
            <span class="detail-label">Notes</span>
            <span class="detail-value" style="white-space: pre-wrap;"><?php echo nl2br(escapeHtml($payment['notes'])); ?></span>
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
                <a href="shop-view.php?id=<?php echo $payment['shop_id']; ?>" style="color: #16A34A; text-decoration: none;">
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
    </div>
    
    <!-- Payment Timeline -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-clock" style="color: #16A34A;"></i>
            Payment Timeline
        </div>
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
                <div class="timeline-desc">
                    Collected by <?php echo escapeHtml($payment['agent_name']); ?>
                    <?php if (!empty($receiverName)): ?>
                        <br><span style="font-size: 12px; color: #6B7A7B;">Receiver: <?php echo escapeHtml($receiverName); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="timeline-item pending">
                <div class="timeline-title">Pending Collection</div>
                <div class="timeline-time">Awaiting agent collection</div>
                <div class="timeline-desc">Payment needs to be collected from shop</div>
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
                <div class="timeline-desc">Click "Submit to Admin" to send for confirmation</div>
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
                <div class="timeline-desc">Admin will confirm the payment shortly</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Actions -->
    <?php if ($payment['status'] === 'pending'): ?>
    <div style="display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
        <button class="btn-action btn-collect" onclick="collectPayment(<?php echo $payment['id']; ?>, <?php echo $payment['remaining_amount']; ?>)">
            <i class="fas fa-hand-holding-usd"></i> Collect Payment
        </button>
        <a href="shop-payments.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <?php elseif ($payment['status'] === 'collected'): ?>
    <div style="display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
        <button class="btn-action btn-submit" onclick="submitToAdmin(<?php echo $payment['id']; ?>)">
            <i class="fas fa-arrow-up"></i> Submit to Admin
        </button>
        <a href="shop-payments.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <?php else: ?>
    <div style="display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
        <a href="shop-payments.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <?php endif; ?>
</div>

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
            
            return fetch('../agent/shop-payments.php', {
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
            fetch('../agent/shop-payments.php', {
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